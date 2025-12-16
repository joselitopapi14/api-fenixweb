<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class ProductosExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $request;
    protected $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = auth()->user();
    }

    public function query()
    {
        $query = Producto::with(['tipoProducto', 'tipoOro', 'empresa', 'tipoMedida', 'impuestos']);

        // Aplicar filtros basados en la request (Misma lógica que ProductoController)
        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('descripcion', 'ilike', "%{$search}%")
                  ->orWhere('codigo_barras', 'ilike', "%{$search}%");
            });
        }

        if ($this->request->filled('empresa_id')) {
            $query->where('empresa_id', $this->request->empresa_id);
        }

        // Filtro por tipo de producto
        if ($this->request->filled('tipo_producto_id')) {
            $query->where('tipo_producto_id', $this->request->tipo_producto_id);
        }

        // Filtro por tipo de oro
        if ($this->request->filled('tipo_oro_id')) {
            $query->where('tipo_oro_id', $this->request->tipo_oro_id);
        }

        // Filtro por código de barras
        if ($this->request->filled('codigo_barras')) {
            $query->where('codigo_barras', $this->request->codigo_barras);
        }

        // Filtros de precio de venta
        if ($this->request->filled('precio_venta_min')) {
            $query->where('precio_venta', '>=', $this->request->precio_venta_min);
        }
        if ($this->request->filled('precio_venta_max')) {
            $query->where('precio_venta', '<=', $this->request->precio_venta_max);
        }

        // Filtros de precio de compra
        if ($this->request->filled('precio_compra_min')) {
            $query->where('precio_compra', '>=', $this->request->precio_compra_min);
        }
        if ($this->request->filled('precio_compra_max')) {
            $query->where('precio_compra', '<=', $this->request->precio_compra_max);
        }

        // Filtro por fechas de creación
        if ($this->request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $this->request->fecha_desde);
        }
        if ($this->request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $this->request->fecha_hasta);
        }

        // Ordenamiento
        $sort = $this->request->get('sort', 'nombre_asc');
        switch ($sort) {
            case 'nombre_desc': $query->orderBy('nombre', 'desc'); break;
            case 'precio_asc': $query->orderBy('precio_venta', 'asc'); break;
            case 'precio_desc': $query->orderBy('precio_venta', 'desc'); break;
            case 'newest': $query->orderBy('created_at', 'desc'); break;
            case 'oldest': $query->orderBy('created_at', 'asc'); break;
            default: $query->orderBy('nombre', 'asc'); break;
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Descripción',
            'Código de Barras',
            'Tipo de Producto',
            'Tipo de Oro',
            'Tipo de Medida',
            'Peso',
            'Precio de Venta',
            'Precio de Compra',
            'Impuestos',
            'Empresa',
            'Fecha de Creación',
            'Última Actualización'
        ];
    }

    public function map($producto): array
    {
        return [
            $producto->id,
            $producto->nombre,
            $producto->descripcion ?? '',
            $producto->codigo_barras ?? '',
            $producto->tipoProducto ? $producto->tipoProducto->nombre : '',
            $producto->tipoOro ? $producto->tipoOro->nombre : '',
            $producto->tipoMedida ? $producto->tipoMedida->nombre . ' (' . $producto->tipoMedida->abreviatura . ')' : '',
            $producto->peso ? $producto->peso : '',
            $producto->precio_venta ? '$' . number_format($producto->precio_venta, 2, ',', '.') : '',
            $producto->precio_compra ? '$' . number_format($producto->precio_compra, 2, ',', '.') : '',
            $producto->impuestos->count() > 0 ? $producto->impuestos->pluck('name')->implode(', ') : '',
            $producto->empresa ? $producto->empresa->razon_social : 'Global',
            $producto->created_at->setTimezone('America/Bogota')->format('d/m/Y H:i:s'),
            $producto->updated_at->setTimezone('America/Bogota')->format('d/m/Y H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '059669'], // Green-600
                ],
            ],
            // All cells
            'A:M' => [
                'alignment' => [
                    'vertical' => 'top',
                    'wrapText' => true,
                ],
            ],
            // Price columns formatting
            'H:I' => [
                'alignment' => [
                    'horizontal' => 'right',
                ],
            ],
        ];
    }
}
