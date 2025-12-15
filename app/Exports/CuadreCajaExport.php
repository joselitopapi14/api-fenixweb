<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CuadreCajaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    protected $movimientos;
    protected $filtros;

    public function __construct($movimientos, $filtros = [])
    {
        $this->movimientos = $movimientos;
        $this->filtros = $filtros;
    }

    public function collection()
    {
        return $this->movimientos;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Hora',
            'Tipo de Movimiento',
            'Número de Contrato/Documento',
            'Cliente',
            'Documento Cliente',
            'Empresa',
            'Tipo de Producto/Concepto',
            'Descripción Productos/Concepto',
            'Monto',
            'Débito',
            'Crédito',
            'Impacto en Caja',
            'Observaciones'
        ];
    }

    public function map($movimiento): array
    {
        // Obtener información del cliente
        $cliente = $movimiento->cliente;
        $nombreCliente = $cliente ?
            ($cliente->razon_social ?: trim($cliente->nombres . ' ' . $cliente->apellidos)) :
            'Sin cliente';
        $documentoCliente = $cliente ? $cliente->cedula_nit : '';

        // Obtener información de la empresa
        $empresa = $movimiento->empresa;
        $nombreEmpresa = $empresa ? $empresa->razon_social : 'Sin empresa';

        // Obtener información de productos/concepto
        $tiposProducto = [];
        $descripcionProductos = [];

        if ($movimiento->tipo_registro === 'documento_equivalente') {
            // Para documentos equivalentes, mostrar el concepto
            $tiposProducto[] = $movimiento->concepto_nombre ?? 'Sin concepto';
            $descripcionProductos[] = 'Documento Equivalente - ' . ($movimiento->concepto_nombre ?? 'Sin concepto');
        } elseif ($movimiento->productos && $movimiento->productos->count() > 0) {
            // Para empeños, desempeños y cuotas, mostrar productos
            foreach ($movimiento->productos as $producto) {
                if ($producto->producto) {
                    if ($producto->producto->tipoOro) {
                        $tiposProducto[] = $producto->producto->tipoOro->nombre . ' (Oro)';
                    } elseif ($producto->producto->tipoProducto) {
                        $tiposProducto[] = $producto->producto->tipoProducto->nombre;
                    }
                    $descripcionProductos[] = $producto->producto->descripcion;
                }
            }
        }

        $tiposProductoStr = count($tiposProducto) > 0 ? implode(', ', array_unique($tiposProducto)) : 'Sin productos';
        $descripcionProductosStr = count($descripcionProductos) > 0 ? implode(', ', $descripcionProductos) : 'Sin descripción';

        // Determinar el tipo de movimiento
        $tipoMovimiento = '';
        switch ($movimiento->tipo_registro) {
            case 'empeno':
                $tipoMovimiento = 'Empeño';
                break;
            case 'cuota':
                $tipoMovimiento = 'Cuota';
                break;
            case 'desempeno':
                $tipoMovimiento = 'Desempeño';
                break;
            case 'documento_equivalente':
                $tipoMovimiento = 'Documento Equivalente';
                break;
            default:
                $tipoMovimiento = 'Desconocido';
        }

        // Determinar impacto en caja
        $impactoCaja = $movimiento->signo_movimiento === 'suma' ? 'SUMA' : 'RESTA';

        // Calcular Débito y Crédito
        $monto = $movimiento->monto ?? 0;
        $debito = $movimiento->signo_movimiento === 'suma' ? $monto : 0;
        $credito = $movimiento->signo_movimiento === 'resta' ? $monto : 0;

        // Número de contrato/documento
        $numeroContrato = $movimiento->numero_contrato ?? 'N/A';
        if ($movimiento->tipo_registro === 'cuota') {
            $numeroContrato .= ' (Cuota ID: ' . $movimiento->id . ')';
        }

        return [
            $movimiento->created_at->format('d/m/Y'),
            $movimiento->created_at->format('H:i:s'),
            $tipoMovimiento,
            $numeroContrato,
            $nombreCliente,
            $documentoCliente,
            $nombreEmpresa,
            $tiposProductoStr,
            $descripcionProductosStr,
            $monto,
            $debito,
            $credito,
            $impactoCaja,
            $movimiento->observaciones ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        return [
            // Header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            // All data
            "A1:N{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
            ],

            // Date columns
            "A2:B{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],

            // Amount column
            "J2:J{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'numberFormat' => [
                    'formatCode' => '#,##0'
                ],
            ],

            // Débito column
            "K2:K{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'numberFormat' => [
                    'formatCode' => '#,##0'
                ],
            ],

            // Crédito column
            "L2:L{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'numberFormat' => [
                    'formatCode' => '#,##0'
                ],
            ],

            // Impact column
            "M2:M{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Cuadre de Caja';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Fecha
            'B' => 10,  // Hora
            'C' => 18,  // Tipo de Movimiento
            'D' => 20,  // Número de Contrato
            'E' => 25,  // Cliente
            'F' => 15,  // Documento Cliente
            'G' => 20,  // Empresa
            'H' => 20,  // Tipo de Producto/Concepto
            'I' => 30,  // Descripción Productos/Concepto
            'J' => 12,  // Monto
            'K' => 12,  // Débito
            'L' => 12,  // Crédito
            'M' => 12,  // Impacto en Caja
            'N' => 25,  // Observaciones
        ];
    }
}
