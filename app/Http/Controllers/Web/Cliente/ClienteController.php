<?php

namespace App\Http\Controllers\Web\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Departamento;
use App\Models\RedSocial;
use App\Models\TipoDocumento;
use App\Models\TipoPersona;
use App\Models\TipoResponsabilidad;
use App\Exports\ClientesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ClienteController extends Controller
{
    public function index(Request $request, Empresa $empresa)
    {
        $query = Cliente::byEmpresa($empresa->id)
            ->with(['departamento', 'municipio', 'comuna', 'barrio', 'redesSociales', 'tipoDocumento', 'tipoPersona', 'tipoResponsabilidad']);

        // Búsqueda
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtros por ubicación
        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->departamento_id);
        }
        if ($request->filled('municipio_id')) {
            $query->where('municipio_id', $request->municipio_id);
        }
        if ($request->filled('comuna_id')) {
            $query->where('comuna_id', $request->comuna_id);
        }
        if ($request->filled('barrio_id')) {
            $query->where('barrio_id', $request->barrio_id);
        }

        $clientes = $query->orderBy('nombres')->paginate(12);

        // Si es una solicitud AJAX, devolver solo la vista parcial
        if ($request->ajax()) {
            return view('clientes.partials.clientes-list-with-pagination', compact('clientes', 'empresa'))->render();
        }

        // Datos para filtros
        $departamentos = Departamento::orderBy('name')->get();

        // Estadísticas
        $totalClientes = Cliente::byEmpresa($empresa->id)->count();
        $clientesEsteAno = Cliente::byEmpresa($empresa->id)
            ->whereYear('created_at', now()->year)
            ->count();
        $clientesEsteMes = Cliente::byEmpresa($empresa->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return view('clientes.index', compact(
            'clientes',
            'empresa',
            'departamentos',
            'totalClientes',
            'clientesEsteAno',
            'clientesEsteMes'
        ));
    }

    public function create(Empresa $empresa)
    {
        $departamentos = Departamento::orderBy('name')->get();
        $redesSociales = RedSocial::orderBy('nombre')->get();
        $tiposDocumento = TipoDocumento::activos()->orderBy('name')->get();
        $tipoPersonas = TipoPersona::orderBy('name')->get();
        $tipoResponsabilidades = TipoResponsabilidad::orderBy('name')->get();

        return view('clientes.create', compact('empresa', 'departamentos', 'redesSociales', 'tiposDocumento', 'tipoPersonas', 'tipoResponsabilidades'));
    }

    public function store(Request $request, Empresa $empresa)
    {
        // Validación base
        $rules = [
            'tipo_documento_id' => 'required|exists:tipo_documentos,id',
            'cedula_nit' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes')->where(function ($query) use ($empresa) {
                    return $query->where('empresa_id', $empresa->id);
                })
            ],
            'dv' => 'nullable|string|max:1',
            'direccion' => 'nullable|string|max:500',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'comuna_id' => 'nullable|exists:comunas,id',
            'barrio_id' => 'nullable|exists:barrios,id',
            'tipo_persona_id' => 'nullable|exists:tipo_personas,id',
            'tipo_responsabilidad_id' => 'nullable|exists:tipo_responsabilidades,id',
            'telefono_fijo' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'redes_sociales' => 'nullable|array',
            'redes_sociales.*.red_social_id' => 'required|exists:redes_sociales,id',
            'redes_sociales.*.usuario' => 'required|string|max:255'
        ];

        // Validación condicional según el tipo de documento
        if ($request->tipo_documento_id == 6) {
            // Persona Jurídica (NIT)
            $rules = array_merge($rules, [
                'razon_social' => 'required|string|max:255',
                'representante_legal' => 'required|string|max:255',
                'cedula_representante' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'email_representante' => 'nullable|email|max:255',
                'direccion_representante' => 'required|string|max:500',
            ]);
        } else {
            // Persona Natural
            $rules = array_merge($rules, [
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'fecha_nacimiento' => 'nullable|date|before:today',
            ]);
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $data = $request->only([
                'tipo_documento_id', 'cedula_nit', 'dv', 'direccion',
                'departamento_id', 'municipio_id', 'comuna_id', 'barrio_id',
                'tipo_persona_id', 'tipo_responsabilidad_id',
                'telefono_fijo', 'celular'
            ]);

            // Agregar campos específicos según el tipo de documento
            if ($request->tipo_documento_id == 6) {
                // Persona Jurídica
                $data = array_merge($data, $request->only([
                    'razon_social', 'representante_legal', 'cedula_representante',
                    'email_representante', 'direccion_representante', 'email'
                ]));
            } else {
                // Persona Natural
                $data = array_merge($data, $request->only([
                    'nombres', 'apellidos', 'email', 'fecha_nacimiento'
                ]));
            }

            $data['empresa_id'] = $empresa->id;

            // Manejar la foto
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $nombreArchivo = 'clientes/' . time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                $data['foto'] = $foto->storeAs('public/' . $nombreArchivo);
                $data['foto'] = $nombreArchivo; // Guardar solo la ruta relativa
            }

            $cliente = Cliente::create($data);

            // Manejar redes sociales
            if ($request->filled('redes_sociales')) {
                foreach ($request->redes_sociales as $redSocial) {
                    $cliente->redesSociales()->attach($redSocial['red_social_id'], [
                        'valor' => $redSocial['usuario']
                    ]);
                }
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Cliente registrado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()
                ->route('clientes.show', ['empresa' => $empresa, 'cliente' => $cliente]);

        } catch (\Exception $e) {
            DB::rollback();

            // Eliminar foto si se subió
            if (isset($data['foto']) && Storage::disk('public')->exists($data['foto'])) {
                Storage::disk('public')->delete($data['foto']);
            }

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al registrar el cliente: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function show(Empresa $empresa, Cliente $cliente)
    {
        // Verificar que el cliente pertenece a la empresa
        if ($cliente->empresa_id !== $empresa->id) {
            abort(404);
        }

        $cliente->load([
            'departamento',
            'municipio',
            'comuna',
            'barrio',
            'tipoPersona',
            'tipoResponsabilidad',
            'redesSociales'
        ]);

        return view('clientes.show', compact('empresa', 'cliente'));
    }

    public function edit(Empresa $empresa, Cliente $cliente)
    {
        // Verificar que el cliente pertenece a la empresa
        if ($cliente->empresa_id !== $empresa->id) {
            abort(404);
        }

        $departamentos = Departamento::orderBy('name')->get();
        $redesSociales = RedSocial::orderBy('nombre')->get();
        $tiposDocumento = TipoDocumento::activos()->orderBy('name')->get();
        $tipoPersonas = TipoPersona::orderBy('name')->get();
        $tipoResponsabilidades = TipoResponsabilidad::orderBy('name')->get();

        // Obtener redes sociales actuales del cliente
        $redesSocialesCliente = $cliente->redesSociales->pluck('pivot.valor', 'id')->toArray();

        return view('clientes.edit', compact(
            'empresa',
            'cliente',
            'departamentos',
            'redesSociales',
            'redesSocialesCliente',
            'tiposDocumento',
            'tipoPersonas',
            'tipoResponsabilidades'
        ));
    }

    public function update(Request $request, Empresa $empresa, Cliente $cliente)
    {
        // dd($request->all());
        // Verificar que el cliente pertenece a la empresa
        if ($cliente->empresa_id !== $empresa->id) {
            abort(404);
        }

        // Validación base
        $rules = [
            'tipo_documento_id' => 'required|exists:tipo_documentos,id',
            'cedula_nit' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes')->where(function ($query) use ($empresa) {
                    return $query->where('empresa_id', $empresa->id);
                })->ignore($cliente->id)
            ],
            'dv' => 'nullable|string|max:1',
            'direccion' => 'nullable|string|max:500',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'comuna_id' => 'nullable|exists:comunas,id',
            'barrio_id' => 'nullable|exists:barrios,id',
            'tipo_persona_id' => 'nullable|exists:tipo_personas,id',
            'tipo_responsabilidad_id' => 'nullable|exists:tipo_responsabilidades,id',
            'telefono_fijo' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'redes_sociales' => 'nullable|array',
            'redes_sociales.*.red_social_id' => 'required|exists:redes_sociales,id',
            'redes_sociales.*.usuario' => 'required|string|max:255'
        ];

        // Validación condicional según el tipo de documento
        if ($request->tipo_documento_id == 6) {
            // Persona Jurídica (NIT)
            $rules = array_merge($rules, [
                'razon_social' => 'required|string|max:255',
                'representante_legal' => 'required|string|max:255',
                'cedula_representante' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'email_representante' => 'nullable|email|max:255',
                'direccion_representante' => 'required|string|max:500',
            ]);
        } else {
            // Persona Natural
            $rules = array_merge($rules, [
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'fecha_nacimiento' => 'nullable|date|before:today',
            ]);
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $data = $request->only([
                'tipo_documento_id', 'cedula_nit', 'dv', 'direccion',
                'departamento_id', 'municipio_id', 'comuna_id', 'barrio_id',
                'tipo_persona_id', 'tipo_responsabilidad_id',
                'telefono_fijo', 'celular'
            ]);

            // Agregar campos específicos según el tipo de documento
            if ($request->tipo_documento_id == 6) {
                // Persona Jurídica
                $data = array_merge($data, $request->only([
                    'razon_social', 'representante_legal', 'cedula_representante',
                    'email_representante', 'direccion_representante', 'email'
                ]));

                // Limpiar campos de persona natural
                $data['nombres'] = null;
                $data['apellidos'] = null;
                $data['fecha_nacimiento'] = null;
            } else {
                // Persona Natural
                $data = array_merge($data, $request->only([
                    'nombres', 'apellidos', 'email', 'fecha_nacimiento'
                ]));

                // Limpiar campos de persona jurídica
                $data['razon_social'] = null;
                $data['representante_legal'] = null;
                $data['cedula_representante'] = null;
                $data['email_representante'] = null;
                $data['direccion_representante'] = null;
            }

            // Manejar la foto
            if ($request->hasFile('foto')) {
                // Eliminar foto anterior si existe
                if ($cliente->foto && Storage::disk('public')->exists($cliente->foto)) {
                    Storage::disk('public')->delete($cliente->foto);
                }

                $foto = $request->file('foto');
                $nombreArchivo = 'clientes/' . time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                $data['foto'] = $foto->storeAs('public/' . $nombreArchivo);
                $data['foto'] = $nombreArchivo; // Guardar solo la ruta relativa
            }

            $cliente->update($data);

            // Actualizar redes sociales
            $cliente->redesSociales()->detach(); // Eliminar todas las relaciones actuales

            if ($request->filled('redes_sociales')) {
                foreach ($request->redes_sociales as $redSocial) {
                    $cliente->redesSociales()->attach($redSocial['red_social_id'], [
                        'valor' => $redSocial['usuario']
                    ]);
                }
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Cliente actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()
                ->route('clientes.show', ['empresa' => $empresa, 'cliente' => $cliente]);

        } catch (\Exception $e) {
            DB::rollback();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al actualizar el cliente: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function destroy(Empresa $empresa, Cliente $cliente)
    {
        // Verificar que el cliente pertenece a la empresa
        if ($cliente->empresa_id !== $empresa->id) {
            abort(404);
        }

        try {
            // Eliminar foto si existe
            if ($cliente->foto && Storage::disk('public')->exists($cliente->foto)) {
                Storage::disk('public')->delete($cliente->foto);
            }

            // Eliminar relaciones con redes sociales
            $cliente->redesSociales()->detach();

            // Soft delete del cliente
            $cliente->delete();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Cliente eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('clientes.index', $empresa);

        } catch (\Exception $e) {
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al eliminar el cliente: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return back();
        }
    }

    /**
     * Exportar clientes a Excel
     */
    public function export(Request $request, Empresa $empresa)
    {
        try {
            $fileName = 'clientes_' . $empresa->razon_social . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new ClientesExport($request, $empresa->id), $fileName);

        } catch (\Exception $e) {
            Log::error('Error exportando clientes', [
                'error' => $e->getMessage(),
                'empresa_id' => $empresa->id
            ]);

            return back()->with('error', 'Error al generar la exportación: ' . $e->getMessage());
        }
    }
}
