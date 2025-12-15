<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InventarioExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $movimientos;
    protected $filtros;
    protected $totales;
    protected $movimientosExpandidos;

    public function __construct($movimientos, $filtros = [])
    {
        $this->movimientos = $movimientos;
        $this->filtros = $filtros;
        $this->movimientosExpandidos = $this->expandirMovimientosPorProductos($movimientos);
        $this->totales = $this->calcularTotales($movimientos);
    }

    public function collection()
    {
        return $this->movimientosExpandidos;
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'DOCUMENTO',
            'CLIENTE',
            'CODIGO PRODUCTO',
            'NOMBRE PRODUCTO',
            'CANTIDAD',
            'COSTO',
            'VENTA',
            'MONTO BOLETA',
            'TIPO DE ORO',
            'TIPO DE PRODUCTO',
            'CEDULA/NIT',
            'TIPO DE MOVIMIENTO',
            'TIPO REGISTRO',
            'FECHA VENCIMIENTO',
            'ESTADO',
            'OBSERVACIÓN DEL PRODUCTO',
            'OBSERVACIÓN BOLETA',
            'DESCRIPCIÓN DEL PRODUCTO',
            'UBICACIÓN DEL PRODUCTO',

            // 'Empresa',
            // 'Sede',

        ];
    }

    public function map($item): array
    {
        $movimiento = $item['movimiento'];
        $producto = $item['producto'];

        // Determinar si es boleta de empeño o desempeño
        // Una boleta de empeño tiene tipo_movimiento_id = 1
        $esBoleta = ($movimiento instanceof \App\Models\BolletaEmpeno) ||
                   (isset($movimiento->tipo_movimiento_id) && $movimiento->tipo_movimiento_id == 1);

        $cliente = $esBoleta ? $movimiento->cliente : $movimiento->boletaEmpeno?->cliente;

        // Obtener el monto de la boleta con debug mejorado
        $montoPrestamoBoletaEmpeno = 0;

        if ($esBoleta) {
            // Es una boleta de empeño directa
            // El InventarioController hace alias 'monto_prestamo as monto'
            $montoPrestamoBoletaEmpeno = $movimiento->monto;

            // Si monto es null o 0, intentar obtenerlo de otras formas
            if (empty($montoPrestamoBoletaEmpeno)) {
                // Verificar si el movimiento tiene el campo original
                if (isset($movimiento->monto_prestamo)) {
                    $montoPrestamoBoletaEmpeno = $movimiento->monto_prestamo;
                }
            }
        } else {
            // Es una boleta de desempeño, obtener monto de la boleta de empeño relacionada
            if ($movimiento->boletaEmpeno) {
                $montoPrestamoBoletaEmpeno = $movimiento->boletaEmpeno->monto_prestamo;
            }

            // Si no encontramos monto en boletaEmpeno, verificar si el movimiento mismo tiene monto
            if (empty($montoPrestamoBoletaEmpeno) && isset($movimiento->monto)) {
                $montoPrestamoBoletaEmpeno = $movimiento->monto;
            }
        }

        // Asegurar que el monto no sea null y convertir a float
        $montoPrestamoBoletaEmpeno = (float)($montoPrestamoBoletaEmpeno ?? 0);

        // Debug: Si es 0, podría ser que realmente sea 0 en la base de datos
        // En ese caso, verificar si hay otro campo con el valor

        // Calcular el monto proporcional por producto
        $productos = $esBoleta ? $movimiento->productos : $movimiento->boletaEmpeno?->productos;
        $cantidadProductos = ($productos && $productos->count() > 0) ? $productos->count() : 1;

        // Si no hay producto específico (producto es null), mostrar el monto completo
        if ($producto === null) {
            $montoProporcionado = $montoPrestamoBoletaEmpeno;
        } else {
            $montoProporcionado = $cantidadProductos > 0 ? ($montoPrestamoBoletaEmpeno / $cantidadProductos) : $montoPrestamoBoletaEmpeno;
        }

        $empresa = $esBoleta ? $movimiento->empresa : $movimiento->boletaEmpeno?->empresa;
        $sede = $esBoleta ? $movimiento->sede : $movimiento->boletaEmpeno?->sede;
        $numeroContrato = $esBoleta ? $movimiento->numero_contrato : ($movimiento->boletaEmpeno?->numero_contrato ?? 'N/A');

        // Obtener el nombre del cliente
        $nombreCliente = 'N/A';
        // $tipoDocumento = 'N/A';
        $cedulaNit = 'N/A';

        if ($cliente) {
            $nombreCliente = $cliente->razon_social
                ? $cliente->razon_social
                : trim($cliente->nombres . ' ' . $cliente->apellidos);

            // $tipoDocumento = $cliente->tipoDocumento ? $cliente->tipoDocumento->abreviacion : 'N/A';
            $cedulaNit = $cliente->cedula_nit;
        }

        // Determinar estado
        $estado = 'Activa';
        if ($esBoleta) {
            if ($movimiento->anulada) {
                $estado = 'Anulada';
            } elseif ($movimiento->es_vencida) {
                $estado = 'Vencida';
            }
        } else {
            $estado = ucfirst($movimiento->estado);
        }

        // Datos del producto específico
        $nombreProducto = 'N/A';
        $codigoProducto = 'N/A';
        $descripcionProducto = 'N/A';
        $tipoProducto = 'N/A';
        $tipoOro = 'N/A';
        // $kilates = 'N/A';
        // $peso = 'N/A';
        $cantidad = 'N/A';
        $valorUnitario = 'N/A';
        // $valorTotalProducto = 'N/A';
        $observacionesProducto = 'N/A';

        if ($producto && $producto->producto) {
            $prod = $producto->producto;
            $nombreProducto = $prod->nombre ?? 'Producto eliminado';
            $codigoProducto = $prod->codigo_barras ?? 'N/A';
            $descripcionProducto = $prod->descripcion ?? 'N/A';
            $tipoProducto = $prod->tipoProducto ? $prod->tipoProducto->nombre : 'N/A';
            $tipoOro = $prod->tipoOro ? $prod->tipoOro->nombre : 'N/A';
            $kilates = $prod->kilates ?? 'N/A';
            $peso = $prod->peso ? number_format($prod->peso, 2) : 'N/A';
            $cantidad = number_format($producto->cantidad, 2);
            $valorUnitario = '$' . number_format($prod->precio_compra ?? 0, 2);
            $valorTotalProducto = '$' . number_format(($producto->cantidad * ($prod->precio_compra ?? 0)), 2);
            $precio_venta = '$' . number_format($prod->precio_venta ?? 0, 2);
            $observacionesProducto = $producto->observaciones ?? 'N/A';
        } elseif (!$producto) {
            $nombreProducto = 'Sin productos';
        }

        // Observaciones de la boleta
        $observacionesBoleta = 'N/A';
        if ($esBoleta) {
            $observacionesBoleta = $movimiento->observaciones ?? 'N/A';
        } else {
            // Para boletas de desempeño, obtener observaciones de la boleta o del desempeño
            $observacionesBoleta = $movimiento->observaciones ?? ($movimiento->boletaEmpeno?->observaciones ?? 'N/A');
        }

        // Ubicación del producto (de la boleta de empeño)
        $ubicacionProducto = 'N/A';
        if ($esBoleta) {
            $ubicacionProducto = $movimiento->ubicacion ?? 'N/A';
        } else {
            // Para boletas de desempeño, obtener ubicación de la boleta de empeño relacionada
            $ubicacionProducto = $movimiento->boletaEmpeno?->ubicacion ?? 'N/A';
        }

        return [
            $movimiento->created_at->format('d/m/Y H:i'),
            $numeroContrato,
            $nombreCliente,
            // $tipoDocumento,
            $codigoProducto,
            $nombreProducto,
            $cantidad,
            $valorUnitario,
            $precio_venta,
            '$' . number_format((float)$montoProporcionado, 2),
            $tipoOro,
            $tipoProducto,
            $cedulaNit,
            $movimiento->tipoMovimiento->nombre ?? 'N/A',
            $esBoleta ? 'Boleta Empeño' : 'Boleta Desempeño',
            ($esBoleta && $movimiento->fecha_vencimiento) ? $movimiento->fecha_vencimiento->format('d/m/Y') : 'N/A',
            $estado,
            $observacionesProducto,
            $observacionesBoleta,
            $descripcionProducto,
            $ubicacionProducto

            // $empresa->razon_social ?? 'N/A',
            // $sede->nombre ?? 'N/A',

        ];
    }

    /**
     * Expandir cada movimiento por sus productos para crear una fila por producto
     */
    private function expandirMovimientosPorProductos($movimientos)
    {
        $movimientosExpandidos = collect();

        foreach ($movimientos as $movimiento) {
            // Determinar si es boleta de empeño o desempeño
            $esBoleta = $movimiento->tipo_registro === 'empeno';
            $productos = $esBoleta ? $movimiento->productos : $movimiento->boletaEmpeno?->productos;

            if ($productos && $productos->count() > 0) {
                // Crear una fila por cada producto
                foreach ($productos as $producto) {
                    $movimientosExpandidos->push([
                        'movimiento' => $movimiento,
                        'producto' => $producto
                    ]);
                }
            } else {
                // Si no hay productos, crear una fila sin producto
                $movimientosExpandidos->push([
                    'movimiento' => $movimiento,
                    'producto' => null
                ]);
            }
        }

        return $movimientosExpandidos;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Estilo para encabezados
        $this->applyHeaderStyle($sheet, $lastColumn);

        // Estilo para datos
        if ($lastRow > 1) {
            $this->applyDataStyle($sheet, $lastRow, $lastColumn);
        }

        // Agregar filtros y totales
        $sheet->setAutoFilter('A1:' . $lastColumn . '1');

        // Insertar información de filtros aplicados
        $this->insertFilterInfo($sheet, $lastRow);

        // Agregar totales
        $this->insertTotals($sheet, $lastRow);

        return [];
    }

    private function applyHeaderStyle(Worksheet $sheet, $lastColumn)
    {
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
    }

    private function applyDataStyle(Worksheet $sheet, $lastRow, $lastColumn)
    {
        $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true
            ]
        ]);

        // Alinear números a la derecha (columnas específicas)
        $sheet->getStyle('J2:J' . $lastRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
        $sheet->getStyle('S2:V' . $lastRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
    }

    private function insertFilterInfo(Worksheet $sheet, &$lastRow)
    {
        if (!empty($this->filtros)) {
            $row = $lastRow + 3;
            $sheet->setCellValue('A' . $row, 'FILTROS APLICADOS:');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12]
            ]);

            $row++;
            foreach ($this->filtros as $key => $value) {
                if (!empty($value)) {
                    $sheet->setCellValue('A' . $row, ucfirst(str_replace('_', ' ', $key)) . ': ' . $value);
                    $row++;
                }
            }
            $lastRow = $row;
        }
    }

    private function insertTotals(Worksheet $sheet, &$lastRow)
    {
        $row = $lastRow + 2;

        // Totales generales
        $sheet->setCellValue('A' . $row, 'RESUMEN DE TOTALES (VALORES NETOS):');
        $sheet->getStyle('A' . $row . ':X' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF']]
        ]);
        $sheet->mergeCells('A' . $row . ':X' . $row);

        $row++;
        $sheet->setCellValue('A' . $row, 'Total Movimientos:');
        $sheet->setCellValue('B' . $row, number_format($this->totales['totalMovimientos']));
        $sheet->setCellValue('C' . $row, 'Total Monto Neto:');
        $sheet->setCellValue('D' . $row, '$' . number_format($this->totales['totalMontoNeto'], 2));

        $row++;
        $sheet->setCellValue('A' . $row, 'Total Oro Neto:');
        $sheet->setCellValue('B' . $row, '$' . number_format($this->totales['totalOroNeto'], 2));
        $sheet->setCellValue('C' . $row, 'Total No Oro Neto:');
        $sheet->setCellValue('D' . $row, '$' . number_format($this->totales['totalNoOroNeto'], 2));

        $this->insertTotalsByType($sheet, $row, 'PRODUCTOS DE ORO (NETO):', $this->totales['totalesPorTipoOro'], 'F59E0B');
        $this->insertTotalsByType($sheet, $row, 'PRODUCTOS NO ORO (NETO):', $this->totales['totalesPorTipoNoOro'], '8B5CF6');

        $lastRow = $row;
    }

    private function insertTotalsByType(Worksheet $sheet, &$row, $title, $totals, $color)
    {
        if (!empty($totals)) {
            $row += 2;
            $sheet->setCellValue('A' . $row, $title);
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color]
                ]
            ]);
            $sheet->mergeCells('A' . $row . ':D' . $row);

            foreach ($totals as $tipo => $total) {
                $row++;
                $sheet->setCellValue('A' . $row, $tipo . ':');
                $sheet->setCellValue('B' . $row, '$' . number_format($total, 2));
            }
        }
    }

    /**
     * Calcular totales solo con valores netos (sin restar)
     */
    private function calcularTotales($movimientos)
    {
        $totalMontoNeto = 0;
        $totalMovimientos = $movimientos->count();
        $totalOroNeto = 0;
        $totalNoOroNeto = 0;
        $totalesPorTipoOro = [];
        $totalesPorTipoNoOro = [];

        foreach ($movimientos as $movimiento) {
            $tipoMovimiento = $movimiento->tipoMovimiento;
            $monto = $movimiento->monto ?? 0;

            // Solo contar sumas para el monto neto (no restar)
            if ($tipoMovimiento && $tipoMovimiento->es_suma) {
                $totalMontoNeto += $monto;

                // Calcular totales por tipo de oro y no oro
                $productos = null;
                if ($movimiento->tipo_registro === 'empeno') {
                    $productos = $movimiento->productos;
                } else {
                    $productos = $movimiento->boletaEmpeno?->productos;
                }

                if ($productos && $productos->count() > 0) {
                    $tieneOro = false;
                    $tiposOroEncontrados = [];
                    $tiposNoOroEncontrados = [];

                    foreach ($productos as $producto) {
                        if ($producto->producto) {
                            if ($producto->producto->tipoOro) {
                                // Producto de oro
                                $tieneOro = true;
                                $tipoOro = $producto->producto->tipoOro;
                                $tipoOroNombre = $tipoOro->nombre;

                                if (!in_array($tipoOroNombre, $tiposOroEncontrados)) {
                                    $tiposOroEncontrados[] = $tipoOroNombre;
                                }
                            } else {
                                // Producto no oro
                                $tipoProducto = $producto->producto->tipoProducto;
                                if ($tipoProducto) {
                                    $tipoProductoNombre = $tipoProducto->nombre;

                                    if (!in_array($tipoProductoNombre, $tiposNoOroEncontrados)) {
                                        $tiposNoOroEncontrados[] = $tipoProductoNombre;
                                    }
                                }
                            }
                        }
                    }

                    // Distribuir montos entre tipos encontrados
                    if ($tieneOro && count($tiposOroEncontrados) > 0) {
                        $montoPorTipoOro = $monto / count($tiposOroEncontrados);
                        $totalOroNeto += $monto;

                        foreach ($tiposOroEncontrados as $tipoOroNombre) {
                            if (!isset($totalesPorTipoOro[$tipoOroNombre])) {
                                $totalesPorTipoOro[$tipoOroNombre] = 0;
                            }
                            $totalesPorTipoOro[$tipoOroNombre] += $montoPorTipoOro;
                        }
                    }

                    if (count($tiposNoOroEncontrados) > 0) {
                        $montoPorTipoNoOro = $monto / count($tiposNoOroEncontrados);
                        $totalNoOroNeto += $monto;

                        foreach ($tiposNoOroEncontrados as $tipoProductoNombre) {
                            if (!isset($totalesPorTipoNoOro[$tipoProductoNombre])) {
                                $totalesPorTipoNoOro[$tipoProductoNombre] = 0;
                            }
                            $totalesPorTipoNoOro[$tipoProductoNombre] += $montoPorTipoNoOro;
                        }
                    }

                    // Si no tiene ni oro ni productos identificados, contar como no oro genérico
                    if (!$tieneOro && count($tiposNoOroEncontrados) === 0) {
                        $totalNoOroNeto += $monto;
                        if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                            $totalesPorTipoNoOro['Sin especificar'] = 0;
                        }
                        $totalesPorTipoNoOro['Sin especificar'] += $monto;
                    }
                } else {
                    // Si no hay productos, considerarlo como no oro sin especificar
                    $totalNoOroNeto += $monto;
                    if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                        $totalesPorTipoNoOro['Sin especificar'] = 0;
                    }
                    $totalesPorTipoNoOro['Sin especificar'] += $monto;
                }
            }
        }

        return [
            'totalMontoNeto' => $totalMontoNeto,
            'totalMovimientos' => $totalMovimientos,
            'totalOroNeto' => $totalOroNeto,
            'totalNoOroNeto' => $totalNoOroNeto,
            'totalesPorTipoOro' => $totalesPorTipoOro,
            'totalesPorTipoNoOro' => $totalesPorTipoNoOro
        ];
    }
}
