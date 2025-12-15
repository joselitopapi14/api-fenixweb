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

class MovimientosInventarioExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $movimientos;
    protected $filtros;
    protected $totales;
    protected $movimientosExpandidos;

    public function __construct($movimientos, $filtros = [])
    {
        $this->movimientos = $movimientos;
        $this->filtros = $filtros;

        // Debug: Log información de los movimientos recibidos
        \Log::info('MovimientosInventarioExport - Datos recibidos', [
            'total_movimientos' => $movimientos->count(),
            'tipos_registro' => $movimientos->groupBy('tipo_registro')->map->count()->toArray(),
            'filtros' => $filtros
        ]);

        // Ejemplo de algunos movimientos para debug
        if ($movimientos->count() > 0) {
            \Log::info('MovimientosInventarioExport - Primeros 3 movimientos', [
                'movimientos_sample' => $movimientos->take(3)->map(function($m) {
                    return [
                        'id' => $m->id ?? 'N/A',
                        'tipo_registro' => $m->tipo_registro ?? 'N/A',
                        'numero_contrato' => $m->numero_contrato ?? 'N/A',
                        'fecha_movimiento' => $m->fecha_movimiento ?? $m->created_at ?? 'N/A'
                    ];
                })->toArray()
            ]);
        }

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
        ];
    }

    public function map($item): array
    {
        $movimiento = $item['movimiento'];
        $producto = $item['producto'];

        // Determinar el tipo de movimiento según tipo_registro
        $tipoRegistro = $movimiento->tipo_registro ?? 'unknown';
        $esEmpeno = $tipoRegistro === 'empeno';
        $esDesempeno = $tipoRegistro === 'desempeno';
        $esMovimientoInventario = $tipoRegistro === 'movimiento_inventario';

        // Obtener cliente según el tipo de registro
        $cliente = null;
        if ($esEmpeno) {
            $cliente = $movimiento->cliente;
        } elseif ($esDesempeno) {
            $cliente = $movimiento->boletaEmpeno?->cliente;
        }
        // MovimientoInventario no tiene cliente directo

        // Obtener el monto según el tipo de registro
        $montoPrestamoBoletaEmpeno = 0;

        if ($esEmpeno) {
            // Es una boleta de empeño
            $montoPrestamoBoletaEmpeno = $movimiento->monto ?? $movimiento->monto_prestamo ?? 0;
        } elseif ($esDesempeno) {
            // Es una boleta de desempeño
            if ($movimiento->boletaEmpeno) {
                $montoPrestamoBoletaEmpeno = $movimiento->boletaEmpeno->monto_prestamo ?? 0;
            }
            // Si no encontramos monto en boletaEmpeno, usar el monto del desempeño
            if (empty($montoPrestamoBoletaEmpeno) && isset($movimiento->monto)) {
                $montoPrestamoBoletaEmpeno = $movimiento->monto;
            }
        } elseif ($esMovimientoInventario) {
            // Para MovimientoInventario, calcular el monto basado en el producto específico
            if ($producto && $producto->producto) {
                $precioUnitario = $producto->producto->precio_compra ?? 0;
                $cantidad = $producto->cantidad ?? 1;
                $montoPrestamoBoletaEmpeno = $precioUnitario * $cantidad;
            } else {
                // Si no hay producto específico, calcular el total de todos los productos
                $montoTotal = 0;
                foreach ($movimiento->productos as $movProducto) {
                    if ($movProducto->producto && $movProducto->producto->precio_compra) {
                        $montoTotal += $movProducto->producto->precio_compra * $movProducto->cantidad;
                    }
                }
                $montoPrestamoBoletaEmpeno = $montoTotal;
            }
        }

        // Asegurar que el monto no sea null y convertir a float
        $montoPrestamoBoletaEmpeno = (float)($montoPrestamoBoletaEmpeno ?? 0);

        // Calcular el monto proporcional por producto (solo para empeños y desempeños)
        if ($esEmpeno || $esDesempeno) {
            $productos = $esEmpeno ? $movimiento->productos : $movimiento->boletaEmpeno?->productos;
            $cantidadProductos = ($productos && $productos->count() > 0) ? $productos->count() : 1;

            // Si no hay producto específico (producto es null), mostrar el monto completo
            if ($producto === null) {
                $montoProporcionado = $montoPrestamoBoletaEmpeno;
            } else {
                $montoProporcionado = $cantidadProductos > 0 ? ($montoPrestamoBoletaEmpeno / $cantidadProductos) : $montoPrestamoBoletaEmpeno;
            }
        } else {
            // Para MovimientoInventario, el monto ya está calculado correctamente arriba
            $montoProporcionado = $montoPrestamoBoletaEmpeno;
        }

        // Obtener empresa, sede y número de contrato según el tipo
        $empresa = null;
        $sede = null;
        $numeroContrato = 'N/A';

        if ($esEmpeno) {
            $empresa = $movimiento->empresa;
            $sede = $movimiento->sede;
            $numeroContrato = $movimiento->numero_contrato;
        } elseif ($esDesempeno) {
            $empresa = $movimiento->boletaEmpeno?->empresa;
            $sede = $movimiento->boletaEmpeno?->sede;
            $numeroContrato = $movimiento->boletaEmpeno?->numero_contrato ?? 'N/A';
        } elseif ($esMovimientoInventario) {
            $empresa = $movimiento->empresa;
            $sede = $movimiento->sede;
            $numeroContrato = $movimiento->numero_contrato;
        }

        // Obtener el nombre del cliente
        $nombreCliente = 'N/A';
        $cedulaNit = 'N/A';

        if ($cliente) {
            $nombreCliente = $cliente->razon_social
                ? $cliente->razon_social
                : ($cliente->nombres . ' ' . $cliente->apellidos);

            $cedulaNit = $cliente->cedula_nit;
        } elseif ($esMovimientoInventario) {
            // MovimientoInventario no tiene cliente, usar datos del usuario/sistema
            $nombreCliente = 'Sistema/Inventario';
            $cedulaNit = 'N/A';
        }

        // Determinar estado
        $estado = 'Activa';
        if ($esEmpeno) {
            if ($movimiento->anulada) {
                $estado = 'Anulada';
            } elseif ($movimiento->es_vencida) {
                $estado = 'Vencida';
            }
        } elseif ($esDesempeno) {
            $estado = ucfirst($movimiento->estado);
        } elseif ($esMovimientoInventario) {
            if ($movimiento->anulado) {
                $estado = 'Anulado';
            } else {
                $estado = 'Activo';
            }
        }

        // Datos del producto específico
        $nombreProducto = 'N/A';
        $codigoProducto = 'N/A';
        $descripcionProducto = 'N/A';
        $tipoProducto = 'N/A';
        $tipoOro = 'N/A';
        $cantidad = 'N/A';
        $valorUnitario = 'N/A';
        $precio_venta = 'N/A';
        $observacionesProducto = 'N/A';

        if ($producto && $producto->producto) {
            $prod = $producto->producto;
            $nombreProducto = $prod->nombre ?? 'Producto eliminado';
            $codigoProducto = $prod->codigo_barras ?? 'N/A';
            $descripcionProducto = $prod->descripcion ?? 'N/A';
            $tipoProducto = $prod->tipoProducto ? $prod->tipoProducto->nombre : 'N/A';
            $tipoOro = $prod->tipoOro ? $prod->tipoOro->nombre : 'N/A';
            $cantidad = number_format($producto->cantidad, 2);
            $valorUnitario = '$' . number_format($prod->precio_compra ?? 0, 2);
            $precio_venta = '$' . number_format($prod->precio_venta ?? 0, 2);

            // Observaciones del producto según el tipo de registro
            if ($esMovimientoInventario) {
                $observacionesProducto = $producto->descripcion_adicional ?? 'N/A';
            } else {
                $observacionesProducto = $producto->observaciones ?? 'N/A';
            }
        } elseif (!$producto) {
            $nombreProducto = 'Sin productos';
        }

        // Observaciones según el tipo de registro
        $observacionesBoleta = 'N/A';
        if ($esEmpeno) {
            $observacionesBoleta = $movimiento->observaciones ?? 'N/A';
        } elseif ($esDesempeno) {
            // Para boletas de desempeño, obtener observaciones del desempeño o de la boleta relacionada
            $observacionesBoleta = $movimiento->observaciones ?? ($movimiento->boletaEmpeno?->observaciones ?? 'N/A');
        } elseif ($esMovimientoInventario) {
            // Para MovimientoInventario, combinar observaciones generales y específicas
            $obsGenerales = $movimiento->observaciones_generales ?? '';
            $obsEspecificas = $movimiento->observaciones ?? '';

            if ($obsGenerales && $obsEspecificas) {
                $observacionesBoleta = $obsGenerales . ' | ' . $obsEspecificas;
            } elseif ($obsGenerales) {
                $observacionesBoleta = $obsGenerales;
            } elseif ($obsEspecificas) {
                $observacionesBoleta = $obsEspecificas;
            } else {
                $observacionesBoleta = 'N/A';
            }
        }

        // Ubicación del producto
        $ubicacionProducto = 'N/A';
        if ($esEmpeno) {
            $ubicacionProducto = $movimiento->ubicacion ?? 'N/A';
        } elseif ($esDesempeno) {
            // Para boletas de desempeño, obtener ubicación de la boleta de empeño relacionada
            $ubicacionProducto = $movimiento->boletaEmpeno?->ubicacion ?? 'N/A';
        } elseif ($esMovimientoInventario) {
            // MovimientoInventario puede tener ubicación en la sede
            $ubicacionProducto = $movimiento->sede?->nombre ?? 'Inventario General';
        }

        // Obtener fecha correcta según el tipo de registro
        $fechaMovimiento = null;
        if ($esEmpeno) {
            $fechaMovimiento = $movimiento->created_at;
        } elseif ($esDesempeno) {
            $fechaMovimiento = $movimiento->created_at;
        } elseif ($esMovimientoInventario) {
            $fechaMovimiento = $movimiento->fecha_movimiento ?
                \Carbon\Carbon::parse($movimiento->fecha_movimiento) :
                $movimiento->created_at;
        }

        $fechaMovimiento = $fechaMovimiento ?? $movimiento->created_at;

        return [
            $fechaMovimiento->format('d/m/Y H:i'),
            $numeroContrato,
            $nombreCliente,
            $codigoProducto,
            $nombreProducto,
            $cantidad,
            $valorUnitario,
            $precio_venta,
            '$' . number_format((float)$montoProporcionado, 2),
            $tipoOro,
            $tipoProducto,
            $cedulaNit,
            $movimiento->tipoMovimiento ? $movimiento->tipoMovimiento->nombre : 'N/A',
            ucfirst($movimiento->tipo_registro),
            $movimiento->fecha_vencimiento ? $movimiento->fecha_vencimiento->format('d/m/Y') : 'N/A',
            $estado,
            $observacionesProducto,
            $observacionesBoleta,
            $descripcionProducto,
            $ubicacionProducto
        ];
    }

    /**
     * Expandir cada movimiento por sus productos para crear una fila por producto
     */
    private function expandirMovimientosPorProductos($movimientos)
    {
        $movimientosExpandidos = collect();

        foreach ($movimientos as $movimiento) {
            $productos = null;

            // Debug: Agregar información de tipo de registro
            \Log::info('Procesando movimiento', [
                'id' => $movimiento->id ?? 'N/A',
                'tipo_registro' => $movimiento->tipo_registro ?? 'N/A',
                'numero_contrato' => $movimiento->numero_contrato ?? 'N/A'
            ]);

            // Obtener productos según el tipo de movimiento
            if ($movimiento->tipo_registro === 'empeno') {
                $productos = $movimiento->productos;
            } elseif ($movimiento->tipo_registro === 'desempeno') {
                $productos = $movimiento->boletaEmpeno?->productos;
            } elseif ($movimiento->tipo_registro === 'movimiento_inventario') {
                $productos = $movimiento->productos;
                \Log::info('Movimiento inventario productos', [
                    'productos_count' => $productos ? $productos->count() : 0
                ]);
            }

            if ($productos && $productos->count() > 0) {
                // Si hay productos, crear una fila por cada producto
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

        \Log::info('Total movimientos expandidos', [
            'original_count' => $movimientos->count(),
            'expandidos_count' => $movimientosExpandidos->count()
        ]);

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
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
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
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Alinear números a la derecha (columnas específicas)
        $sheet->getStyle('J2:J' . $lastRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
    }

    private function insertFilterInfo(Worksheet $sheet, &$lastRow)
    {
        if (!empty($this->filtros)) {
            $row = $lastRow + 2;

            $sheet->setCellValue('A' . $row, 'FILTROS APLICADOS:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            foreach ($this->filtros as $key => $value) {
                if (!empty($value) && $value !== '' && $value !== null) {
                    $row++;
                    $displayKey = ucwords(str_replace('_', ' ', $key));
                    $sheet->setCellValue('A' . $row, $displayKey . ':');
                    $sheet->setCellValue('B' . $row, $value);
                }
            }

            $lastRow = $row;
        }
    }

    private function insertTotals(Worksheet $sheet, &$lastRow)
    {
        $row = $lastRow + 2;

        // Totales generales
        $sheet->setCellValue('A' . $row, 'RESUMEN DE TOTALES:');
        $sheet->getStyle('A' . $row . ':X' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        $sheet->mergeCells('A' . $row . ':X' . $row);

        $row++;
        $sheet->setCellValue('A' . $row, 'Total Movimientos:');
        $sheet->setCellValue('B' . $row, number_format($this->totales['totalMovimientos']));
        $sheet->setCellValue('C' . $row, 'Total Anulados:');
        $sheet->setCellValue('D' . $row, number_format($this->totales['totalAnulados']));

        $row++;
        $sheet->setCellValue('A' . $row, 'Total Entradas:');
        $sheet->setCellValue('B' . $row, number_format($this->totales['totalEntradas']));
        $sheet->setCellValue('C' . $row, 'Total Salidas:');
        $sheet->setCellValue('D' . $row, number_format($this->totales['totalSalidas']));

        $row++;
        $sheet->setCellValue('A' . $row, 'Saldo:');
        $sheet->setCellValue('B' . $row, number_format($this->totales['totalSaldo']));
        $sheet->setCellValue('C' . $row, 'Total Monto Neto:');
        $sheet->setCellValue('D' . $row, '$' . number_format($this->totales['totalMontoNeto'], 2));

        $row++;
        $sheet->setCellValue('A' . $row, 'Total Oro Neto:');
        $sheet->setCellValue('B' . $row, '$' . number_format($this->totales['totalOroNeto'], 2));
        $sheet->setCellValue('C' . $row, 'Total No Oro Neto:');
        $sheet->setCellValue('D' . $row, '$' . number_format($this->totales['totalNoOroNeto'], 2));

        $this->insertTotalsByType($sheet, $row, 'PRODUCTOS DE ORO:', $this->totales['totalesPorTipoOro'], 'F59E0B');
        $this->insertTotalsByType($sheet, $row, 'PRODUCTOS NO ORO:', $this->totales['totalesPorTipoNoOro'], '8B5CF6');

        $lastRow = $row;
    }

    private function insertTotalsByType(Worksheet $sheet, &$row, $title, $totals, $color)
    {
        if (!empty($totals)) {
            $row += 2;
            $sheet->setCellValue('A' . $row, $title);
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            $sheet->mergeCells('A' . $row . ':D' . $row);

            foreach ($totals as $tipo => $monto) {
                $row++;
                $sheet->setCellValue('A' . $row, $tipo . ':');
                $sheet->setCellValue('B' . $row, '$' . number_format($monto, 2));
            }
        }
    }

    /**
     * Calcular totales con la misma lógica que MovimientoInventarioController
     */
    private function calcularTotales($movimientos)
    {
        $totalMovimientos = $movimientos->count();
        $totalAnulados = 0;
        $totalEntradas = 0;
        $totalSalidas = 0;
        $totalMontoNeto = 0;
        $totalOroNeto = 0;
        $totalNoOroNeto = 0;
        $totalesPorTipoOro = [];
        $totalesPorTipoNoOro = [];

        foreach ($movimientos as $movimiento) {
            // Verificar si está anulado
            $esAnulado = $movimiento->anulado || $movimiento->anulada;

            // Contar anulados
            if ($esAnulado) {
                $totalAnulados++;
            }

            // Contar entradas y salidas solo si NO están anulados
            if (!$esAnulado) {
                $tipoMovimiento = $movimiento->tipoMovimiento;
                if ($tipoMovimiento) {
                    if ($tipoMovimiento->es_suma) {
                        $totalEntradas++;
                    } else {
                        $totalSalidas++;
                    }
                }
            }

            // Calcular totales de montos y oro
            $tipoMovimiento = $movimiento->tipoMovimiento;
            $monto = 0;

            // Para movimientos de inventario, calcular el monto basado en productos
            if ($movimiento->tipo_registro === 'movimiento_inventario' && !$esAnulado && $tipoMovimiento) {
                $montoCalculado = 0;
                foreach ($movimiento->productos as $movProducto) {
                    if ($movProducto->producto && $movProducto->producto->precio_compra) {
                        $montoCalculado += $movProducto->producto->precio_compra * $movProducto->cantidad;
                    }
                }

                // Aplicar suma o resta según el tipo de movimiento
                if ($tipoMovimiento->es_suma) {
                    $monto = $montoCalculado;
                    $totalMontoNeto += $monto;
                } else {
                    $monto = $montoCalculado;
                    $totalMontoNeto -= $monto; // Restar para movimientos de resta
                }
            } else {
                // Para empeños y desempeños, usar el monto existente
                $monto = $movimiento->monto ?? 0;

                // Aplicar suma o resta según el tipo de movimiento para empeños y desempeños también
                if (!$esAnulado && $tipoMovimiento) {
                    if ($tipoMovimiento->es_suma) {
                        $totalMontoNeto += $monto; // SUMA (empeños)
                    } else {
                        $totalMontoNeto -= $monto; // RESTA (desempeños)
                    }
                }
            }

            // Calcular totales por tipo de oro y no oro (solo si hay monto y no está anulado)
            if (!$esAnulado && $monto > 0) {
                $productos = null;
                if ($movimiento->tipo_registro === 'empeno') {
                    $productos = $movimiento->productos;
                } elseif ($movimiento->tipo_registro === 'desempeno') {
                    $productos = $movimiento->boletaEmpeno?->productos;
                } elseif ($movimiento->tipo_registro === 'movimiento_inventario') {
                    $productos = $movimiento->productos;
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

                    // Determinar el factor multiplicador según el tipo de movimiento
                    $factor = 1; // Por defecto suma
                    if ($tipoMovimiento && !$tipoMovimiento->es_suma) {
                        $factor = -1; // Restar para todos los movimientos de resta (desempeños e inventario)
                    }

                    // Distribuir montos entre tipos encontrados
                    if ($tieneOro && count($tiposOroEncontrados) > 0) {
                        $montoPorTipoOro = $monto / count($tiposOroEncontrados);
                        $totalOroNeto += $monto * $factor;

                        foreach ($tiposOroEncontrados as $tipoOroNombre) {
                            if (!isset($totalesPorTipoOro[$tipoOroNombre])) {
                                $totalesPorTipoOro[$tipoOroNombre] = 0;
                            }
                            $totalesPorTipoOro[$tipoOroNombre] += $montoPorTipoOro * $factor;
                        }
                    }

                    if (count($tiposNoOroEncontrados) > 0) {
                        $montoPorTipoNoOro = $monto / count($tiposNoOroEncontrados);
                        $totalNoOroNeto += $monto * $factor;

                        foreach ($tiposNoOroEncontrados as $tipoProductoNombre) {
                            if (!isset($totalesPorTipoNoOro[$tipoProductoNombre])) {
                                $totalesPorTipoNoOro[$tipoProductoNombre] = 0;
                            }
                            $totalesPorTipoNoOro[$tipoProductoNombre] += $montoPorTipoNoOro * $factor;
                        }
                    }

                    // Si no tiene ni oro ni productos identificados, contar como no oro genérico
                    if (!$tieneOro && count($tiposNoOroEncontrados) === 0) {
                        $totalNoOroNeto += $monto * $factor;
                        if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                            $totalesPorTipoNoOro['Sin especificar'] = 0;
                        }
                        $totalesPorTipoNoOro['Sin especificar'] += $monto * $factor;
                    }
                } else {
                    // Si no hay productos, considerarlo como no oro sin especificar
                    $factor = 1; // Por defecto suma
                    if ($tipoMovimiento && !$tipoMovimiento->es_suma) {
                        $factor = -1; // Restar para todos los movimientos de resta (desempeños e inventario)
                    }

                    $totalNoOroNeto += $monto * $factor;
                    if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                        $totalesPorTipoNoOro['Sin especificar'] = 0;
                    }
                    $totalesPorTipoNoOro['Sin especificar'] += $monto * $factor;
                }
            }
        }

        return [
            'totalMovimientos' => $totalMovimientos,
            'totalAnulados' => $totalAnulados,
            'totalEntradas' => $totalEntradas,
            'totalSalidas' => $totalSalidas,
            'totalSaldo' => $totalEntradas - $totalSalidas,
            'totalMontoNeto' => $totalMontoNeto,
            'totalOroNeto' => $totalOroNeto,
            'totalNoOroNeto' => $totalNoOroNeto,
            'totalesPorTipoOro' => $totalesPorTipoOro,
            'totalesPorTipoNoOro' => $totalesPorTipoNoOro
        ];
    }
}
