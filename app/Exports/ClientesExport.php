<?php

namespace App\Exports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class ClientesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $request;
    protected $user;
    protected $empresaId;

    public function __construct(Request $request, $empresaId = null)
    {
        $this->request = $request;
        $this->user = auth()->user();
        $this->empresaId = $empresaId;
    }

    public function query()
    {
        $query = Cliente::with([
            'tipoDocumento',
            'departamento',
            'municipio',
            'comuna',
            'barrio',
            'empresa',
            'redesSociales'
        ]);

        // Filtrar por empresa específica si se proporciona
        if ($this->empresaId) {
            $query->where('empresa_id', $this->empresaId);
        } else {
            // Filtrar por empresas del usuario si no es admin global
            if (!$this->user->esAdministradorGlobal()) {
                $empresasIds = $this->user->empresasActivas()->pluck('empresas.id');
                $query->whereIn('empresa_id', $empresasIds);
            }
        }

        // Aplicar filtros basados en la request
        if ($this->request->filled('search')) {
            $query->search($this->request->search);
        }

        // Filtros por ubicación
        if ($this->request->filled('departamento_id')) {
            $query->where('departamento_id', $this->request->departamento_id);
        }
        if ($this->request->filled('municipio_id')) {
            $query->where('municipio_id', $this->request->municipio_id);
        }
        if ($this->request->filled('comuna_id')) {
            $query->where('comuna_id', $this->request->comuna_id);
        }
        if ($this->request->filled('barrio_id')) {
            $query->where('barrio_id', $this->request->barrio_id);
        }

        // Filtro por tipo de documento
        if ($this->request->filled('tipo_documento_id')) {
            $query->where('tipo_documento_id', $this->request->tipo_documento_id);
        }

        // Filtro por tipo de cliente (persona natural/jurídica)
        if ($this->request->filled('tipo_cliente')) {
            if ($this->request->tipo_cliente === 'natural') {
                $query->where('tipo_documento_id', '!=', 6);
            } elseif ($this->request->tipo_cliente === 'juridica') {
                $query->where('tipo_documento_id', 6);
            }
        }

        // Filtro por fechas de creación
        if ($this->request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $this->request->fecha_desde);
        }

        if ($this->request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $this->request->fecha_hasta);
        }

        // Ordenamiento
        $sortBy = $this->request->get('sort', 'nombre_asc');
        switch ($sortBy) {
            case 'nombre_desc':
                $query->orderByRaw("COALESCE(razon_social, CONCAT(nombres, ' ', apellidos)) DESC");
                break;
            case 'cedula_asc':
                $query->orderBy('cedula_nit', 'asc');
                break;
            case 'cedula_desc':
                $query->orderBy('cedula_nit', 'desc');
                break;
            case 'created_at_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderByRaw("COALESCE(razon_social, CONCAT(nombres, ' ', apellidos)) ASC");
                break;
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tipo de Documento',
            'Cédula/NIT',
            'DV',
            'Nombres',
            'Apellidos',
            'Razón Social',
            'Email',
            'Fecha de Nacimiento',
            'Representante Legal',
            'Cédula Representante',
            'Email Representante',
            'Dirección Representante',
            'Dirección',
            'Departamento',
            'Municipio',
            'Comuna',
            'Barrio',
            'Teléfono Fijo',
            'Celular',
            'Redes Sociales',
            'Empresa',
            'Fecha de Creación',
            'Última Actualización'
        ];
    }

    public function map($cliente): array
    {
        // Procesar redes sociales
        $redesSociales = [];
        foreach ($cliente->redesSociales as $redSocial) {
            $redesSociales[] = $redSocial->nombre . ': ' . $redSocial->pivot->valor;
        }
        $redesSocialesStr = implode(' | ', $redesSociales);

        return [
            $cliente->id,
            $cliente->tipoDocumento ? $cliente->tipoDocumento->name : '',
            $cliente->cedula_nit ?? '',
            $cliente->dv ?? '',
            $cliente->nombres ?? '',
            $cliente->apellidos ?? '',
            $cliente->razon_social ?? '',
            $cliente->email ?? '',
            $cliente->fecha_nacimiento ? $cliente->fecha_nacimiento->format('d/m/Y') : '',
            $cliente->representante_legal ?? '',
            $cliente->cedula_representante ?? '',
            $cliente->email_representante ?? '',
            $cliente->direccion_representante ?? '',
            $cliente->direccion ?? '',
            $cliente->departamento ? $cliente->departamento->name : '',
            $cliente->municipio ? $cliente->municipio->name : '',
            $cliente->comuna ? $cliente->comuna->nombre : '',
            $cliente->barrio ? $cliente->barrio->nombre : '',
            $cliente->telefono_fijo ?? '',
            $cliente->celular ?? '',
            $redesSocialesStr,
            $cliente->empresa ? $cliente->empresa->razon_social : '',
            $cliente->created_at->setTimezone('America/Bogota')->format('d/m/Y H:i:s'),
            $cliente->updated_at->setTimezone('America/Bogota')->format('d/m/Y H:i:s')
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
            'A:X' => [
                'alignment' => [
                    'vertical' => 'top',
                    'wrapText' => true,
                ],
            ],
            // ID column
            'A:A' => [
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],
            // Cedula/NIT column
            'C:C' => [
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],
            // DV column
            'D:D' => [
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],
            // Phone columns
            'S:T' => [
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],
        ];
    }
}
