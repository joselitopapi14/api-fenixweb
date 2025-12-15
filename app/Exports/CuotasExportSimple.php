<?php

namespace App\Exports;

use App\Models\Cuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CuotasExportSimple implements FromCollection, WithHeadings
{
    use Exportable;

    protected $request;
    protected $user;

    public function __construct(Request $request = null)
    {
        $this->request = $request;
        $this->user = Auth::user();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Cuota::with([
            'boletaEmpeno.cliente',
            'boletaEmpeno.empresa',
            'usuario'
        ]);

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$this->user->esAdministradorGlobal()) {
            $query->deEmpresa($this->user->empresa_id);
        }

        // Aplicar filtros opcionales pasados en el request (paralelo a CuotaController@index)
        if ($this->request) {
            if ($this->request->filled('numero_contrato')) {
                $numero = $this->request->input('numero_contrato');
                $query->whereHas('boletaEmpeno', function($q) use ($numero) {
                    $q->where('numero_contrato', 'like', '%' . $numero . '%');
                });
            }

            if ($this->request->filled('cedula_cliente')) {
                $cedula = $this->request->input('cedula_cliente');
                $query->whereHas('boletaEmpeno.cliente', function($q) use ($cedula) {
                    $q->where('cedula_nit', 'like', '%' . $cedula . '%');
                });
            }

            if ($this->request->filled('estado')) {
                $query->where('estado', $this->request->input('estado'));
            }

            if ($this->request->filled('fecha_desde')) {
                $query->where('fecha_abono', '>=', $this->request->input('fecha_desde'));
            }

            if ($this->request->filled('fecha_hasta')) {
                $query->where('fecha_abono', '<=', $this->request->input('fecha_hasta'));
            }
        }

        return $query->orderBy('fecha_abono', 'desc')
                    ->get()
                    ->map(function($cuota) {
                        return [
                            'id' => $cuota->id,
                            'numero_contrato' => $cuota->boletaEmpeno->numero_contrato ?? 'N/A',
                            'cliente' => ($cuota->boletaEmpeno->cliente->nombres ?? '') . ' ' . ($cuota->boletaEmpeno->cliente->apellidos ?? ''),
                            'cedula' => $cuota->boletaEmpeno->cliente->cedula_nit ?? 'Sin cédula',
                            'fecha_abono' => $cuota->fecha_abono ? $cuota->fecha_abono->format('d/m/Y') : 'N/A',
                            'monto_pagado' => $cuota->monto_pagado ?? 0,
                            'estado' => ucfirst($cuota->estado ?? 'N/A'),
                            'usuario' => $cuota->usuario->name ?? 'No registrado',
                            'empresa' => $this->user->esAdministradorGlobal() ? ($cuota->boletaEmpeno->empresa->nombre ?? 'N/A') : ''
                        ];
                    });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = [
            'ID',
            'Número Contrato',
            'Cliente',
            'Cédula/NIT',
            'Fecha Abono',
            'Monto Pagado',
            'Estado',
            'Atendido Por',
        ];

        if ($this->user->esAdministradorGlobal()) {
            $headings[] = 'Empresa';
        }

        return $headings;
    }
}
