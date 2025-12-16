<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'codigo_barras' => $this->codigo_barras,
            'precio_venta' => $this->precio_venta ? (float)$this->precio_venta : null,
            'precio_compra' => $this->precio_compra ? (float)$this->precio_compra : null,
            'peso' => $this->peso ? (float)$this->peso : null,
            'imagen' => $this->imagen,
            'imagen_url' => $this->imagen_url,
            'tipo_producto_id' => $this->tipo_producto_id,
            'tipo_oro_id' => $this->tipo_oro_id,
            'empresa_id' => $this->empresa_id,
            'tipo_medida_id' => $this->tipo_medida_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'tipo_producto' => $this->whenLoaded('tipoProducto', function () {
                return [
                    'id' => $this->tipoProducto->id,
                    'nombre' => $this->tipoProducto->nombre,
                    'descripcion' => $this->tipoProducto->descripcion,
                ];
            }),
            'tipo_oro' => $this->whenLoaded('tipoOro', function () {
                return $this->tipoOro ? [
                    'id' => $this->tipoOro->id,
                    'nombre' => $this->tipoOro->nombre,
                    'pureza' => (float)$this->tipoOro->pureza, // Asumiendo que tiene este campo
                ] : null;
            }),
            'empresa' => $this->whenLoaded('empresa', function () {
                return $this->empresa ? [
                    'id' => $this->empresa->id,
                    'razon_social' => $this->empresa->razon_social,
                    'nit' => $this->empresa->nit,
                    'nombre' => $this->empresa->nombre, // Asumiendo alias o nombre corto
                ] : null;
            }),
            'tipo_medida' => $this->whenLoaded('tipoMedida', function () {
                return $this->tipoMedida ? [
                    'id' => $this->tipoMedida->id,
                    'nombre' => $this->tipoMedida->nombre,
                    'abreviatura' => $this->tipoMedida->abreviatura,
                    'activo' => (bool)$this->tipoMedida->activo,
                ] : null;
            }),
            'impuestos' => $this->whenLoaded('impuestos', function () {
                return $this->impuestos->map(function ($impuesto) {
                    // Determinar porcentaje: Probar pivot, luego primera entrada en tabla porcentajes si existe
                    $percentage = null;
                    if ($impuesto->pivot && isset($impuesto->pivot->porcentaje)) {
                        $percentage = $impuesto->pivot->porcentaje;
                    } elseif ($impuesto->impuestoPorcentajes->isNotEmpty()) {
                        $percentage = $impuesto->impuestoPorcentajes->first()->percentage;
                    }

                    return [
                        'id' => $impuesto->id,
                        'name' => $impuesto->name,
                        'code' => $impuesto->code,
                        'percentage' => $percentage ? (float)$percentage : null,
                    ];
                });
            }),
        ];
    }
}
