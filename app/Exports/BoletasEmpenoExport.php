<?php

namespace App\Exports;

use App\Models\BolletaEmpeno;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Http\Request;

class BoletasEmpenoExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $request;
    protected $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = auth()->user();
    }

    public function query()
    {
        $query = BolletaEmpeno::with(['cliente', 'empresa', 'sede', 'usuario', 'productos.producto.tipoProducto', 'productos.producto.tipoOro', 'productos.tipoInteres'])
            ->orderBy('created_at', 'desc');

        // Filtrar por empresa según permisos del usuario
        if (!$this->user->esAdministradorGlobal()) {
            $empresasUsuario = $this->user->empresasActivas->pluck('id');
            $query->whereIn('empresa_id', $empresasUsuario);
        }

        // Aplicar filtros de la request
        if ($this->request->filled('empresa_id')) {
            $query->where('empresa_id', $this->request->empresa_id);
        }

        if ($this->request->filled('estado')) {
            $query->where('estado', $this->request->estado);
        }

        if ($this->request->filled('cliente_id')) {
            $query->where('cliente_id', $this->request->cliente_id);
        }

        if ($this->request->filled('numero_contrato')) {
            $query->where('numero_contrato', 'like', '%' . $this->request->numero_contrato . '%');
        }

        if ($this->request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $this->request->fecha_desde);
        }

        if ($this->request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $this->request->fecha_hasta);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Número de Contrato',
            'Empresa',
            'Sede',
            'Usuario',
            'Cliente',
            'Documento Cliente',
            'Estado',
            'Fecha Creación',
            'Fecha Vencimiento',
            'Valor Total Compra',
            'Valor Total Venta',
            'Ubicación',
            'Productos',
            'Productos Detalle',
            'Observaciones'
        ];
    }

    public function map($boleta): array
    {
        // Crear cadena de productos
        $productosTexto = '';
        $productosDetalle = '';

        foreach ($boleta->productos as $index => $producto) {
            if ($index > 0) {
                $productosTexto .= '; ';
                $productosDetalle .= '; ';
            }

            $productosTexto .= $producto->producto->nombre . ' (Cant: ' . $producto->cantidad . ')';

            $detalle = $producto->producto->nombre .
                      ' - Tipo: ' . ($producto->producto->tipoProducto->nombre ?? 'N/A') .
                      ($producto->producto->tipoOro ? ' - Oro: ' . $producto->producto->tipoOro->nombre : '') .
                      ' - Interés: ' . ($producto->tipoInteres->nombre ?? 'N/A') .
                      ' - Cant: ' . $producto->cantidad .
                      ' - V.Compra: $' . number_format($producto->valor_compra, 2) .
                      ' - V.Venta: $' . number_format($producto->valor_venta, 2);

            if ($producto->descripcion_adicional) {
                $detalle .= ' - Desc: ' . $producto->descripcion_adicional;
            }

            $productosDetalle .= $detalle;
        }

        return [
            $boleta->numero_contrato,
            $boleta->empresa->razon_social ?? 'N/A',
            $boleta->sede ? $boleta->sede->nombre : 'Sede Principal',
            $boleta->usuario ? $boleta->usuario->name : 'N/A',
            $boleta->cliente->nombre_completo ?? 'N/A',
            $boleta->cliente->documento_completo ?? 'N/A',
            ucfirst($boleta->estado),
            $boleta->created_at->format('Y-m-d H:i:s'),
            $boleta->fecha_vencimiento ? $boleta->fecha_vencimiento->format('Y-m-d') : 'N/A',
            '$' . number_format($boleta->valor_total_compra, 2),
            '$' . number_format($boleta->valor_total_venta, 2),
            $boleta->ubicacion ?? 'No especificada',
            $productosTexto,
            $productosDetalle,
            $boleta->observaciones ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF3B82F6', // Color azul
                    ],
                ],
                'font' => [
                    'bold' => true,
                    'color' => [
                        'argb' => 'FFFFFFFF', // Texto blanco
                    ],
                ],
            ],
            // Estilo para todas las celdas
            'A:O' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}
