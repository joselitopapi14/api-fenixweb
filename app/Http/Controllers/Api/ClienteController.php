<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Cliente::with([
                'tipoDocumento',
                'tipoPersona',
                'tipoResponsabilidad',
                'departamento',
                'municipio',
                'comuna',
                'barrio',
                'empresa'
            ]);

            // Filtro por empresa (obligatorio para usuarios no admin)
            $user = auth()->user();
            if ($request->filled('empresa_id')) {
                $empresaId = $request->empresa_id;
                
                // Verificar permisos
                if (!$user->esAdministradorGlobal() && !$user->perteneceAEmpresa($empresaId)) {
                    return response()->json([
                        'message' => 'No tiene permisos para ver clientes de esta empresa'
                    ], 403);
                }
                
                $query->where('empresa_id', $empresaId);
            } else {
                // Si no especifica empresa, filtrar por empresas del usuario
                if (!$user->esAdministradorGlobal()) {
                    $empresasIds = $user->empresasActivas->pluck('id');
                    $query->whereIn('empresa_id', $empresasIds);
                }
            }

            // Filtro de búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombres', 'ilike', "%{$search}%")
                      ->orWhere('apellidos', 'ilike', "%{$search}%")
                      ->orWhere('razon_social', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%")
                      ->orWhereRaw('CAST(cedula_nit AS TEXT) ilike ?', ["%{$search}%"])
                      ->orWhere('celular', 'like', "%{$search}%");
                });
            }

            // Filtro por tipo de persona
            if ($request->filled('tipo_persona_id')) {
                $query->where('tipo_persona_id', $request->tipo_persona_id);
            }

            // Filtro por tipo de documento
            if ($request->filled('tipo_documento_id')) {
                $query->where('tipo_documento_id', $request->tipo_documento_id);
            }

            // Filtro por ubicación
            if ($request->filled('departamento_id')) {
                $query->where('departamento_id', $request->departamento_id);
            }
            if ($request->filled('municipio_id')) {
                $query->where('municipio_id', $request->municipio_id);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $clientes = $query->paginate($perPage);

            return response()->json($clientes);
        } catch (\Exception $e) {
            Log::error('Error al listar clientes', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al listar clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'empresa_id' => 'required|exists:empresas,id',
                'tipo_documento_id' => 'required|exists:tipo_documentos,id',
                'tipo_persona_id' => 'required|exists:tipo_personas,id',
                'tipo_responsabilidad_id' => 'required|exists:tipo_responsabilidades,id',
                'cedula_nit' => 'required|string|max:20',
                'dv' => 'nullable|string|max:1',
                'nombres' => 'required_if:tipo_persona_id,1|nullable|string|max:255',
                'apellidos' => 'required_if:tipo_persona_id,1|nullable|string|max:255',
                'razon_social' => 'required_if:tipo_persona_id,2|nullable|string|max:255',
                'email' => 'required|email|max:255',
                'celular' => 'required|string|max:20',
                'telefono_fijo' => 'nullable|string|max:20',
                'direccion' => 'required|string|max:255',
                'departamento_id' => 'nullable|exists:departamentos,id',
                'municipio_id' => 'nullable|exists:municipios,id',
                'comuna_id' => 'nullable|exists:comunas,id',
                'barrio_id' => 'nullable|exists:barrios,id',
                'fecha_nacimiento' => 'nullable|date',
                'representante_legal' => 'nullable|string|max:255',
                'cedula_representante' => 'nullable|string|max:20',
                'email_representante' => 'nullable|email|max:255',
                'direccion_representante' => 'nullable|string|max:255',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar permisos sobre la empresa
            $user = auth()->user();
            if (!$user->esAdministradorGlobal() && !$user->perteneceAEmpresa($request->empresa_id)) {
                return response()->json([
                    'message' => 'No tiene permisos para crear clientes en esta empresa'
                ], 403);
            }

            // Verificar duplicado de documento en la misma empresa
            $existeCliente = Cliente::where('empresa_id', $request->empresa_id)
                ->where('cedula_nit', $request->cedula_nit)
                ->exists();

            if ($existeCliente) {
                return response()->json([
                    'message' => 'Ya existe un cliente con este documento en la empresa'
                ], 422);
            }

            $data = $request->except(['foto']);

            // Manejar subida de foto
            if ($request->hasFile('foto')) {
                $fotoPath = $request->file('foto')->store('clientes/fotos', 'public');
                $data['foto'] = $fotoPath;
            }

            $cliente = Cliente::create($data);

            return response()->json([
                'message' => 'Cliente creado exitosamente',
                'cliente' => $cliente->load([
                    'tipoDocumento',
                    'tipoPersona',
                    'tipoResponsabilidad',
                    'departamento',
                    'municipio'
                ])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear cliente', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Error al crear el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $cliente = Cliente::with([
                'tipoDocumento',
                'tipoPersona',
                'tipoResponsabilidad',
                'departamento',
                'municipio',
                'comuna',
                'barrio',
                'empresa',
                'redesSociales'
            ])->find($id);

            if (!$cliente) {
                return response()->json([
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar permisos
            $user = auth()->user();
            if (!$user->esAdministradorGlobal() && !$user->perteneceAEmpresa($cliente->empresa_id)) {
                return response()->json([
                    'message' => 'No tiene permisos para ver este cliente'
                ], 403);
            }

            return response()->json($cliente);
        } catch (\Exception $e) {
            Log::error('Error al mostrar cliente', [
                'message' => $e->getMessage(),
                'cliente_id' => $id
            ]);
            return response()->json([
                'message' => 'Error al obtener el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar permisos
            $user = auth()->user();
            if (!$user->esAdministradorGlobal() && !$user->perteneceAEmpresa($cliente->empresa_id)) {
                return response()->json([
                    'message' => 'No tiene permisos para editar este cliente'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'tipo_documento_id' => 'sometimes|required|exists:tipo_documentos,id',
                'tipo_persona_id' => 'sometimes|required|exists:tipo_personas,id',
                'tipo_responsabilidad_id' => 'sometimes|required|exists:tipo_responsabilidades,id',
                'cedula_nit' => 'sometimes|required|string|max:20',
                'dv' => 'nullable|string|max:1',
                'nombres' => 'nullable|string|max:255',
                'apellidos' => 'nullable|string|max:255',
                'razon_social' => 'nullable|string|max:255',
                'email' => 'sometimes|required|email|max:255',
                'celular' => 'sometimes|required|string|max:20',
                'telefono_fijo' => 'nullable|string|max:20',
                'direccion' => 'sometimes|required|string|max:255',
                'departamento_id' => 'nullable|exists:departamentos,id',
                'municipio_id' => 'nullable|exists:municipios,id',
                'comuna_id' => 'nullable|exists:comunas,id',
                'barrio_id' => 'nullable|exists:barrios,id',
                'fecha_nacimiento' => 'nullable|date',
                'representante_legal' => 'nullable|string|max:255',
                'cedula_representante' => 'nullable|string|max:20',
                'email_representante' => 'nullable|email|max:255',
                'direccion_representante' => 'nullable|string|max:255',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar duplicado de documento si se está cambiando
            if ($request->filled('cedula_nit') && $request->cedula_nit !== $cliente->cedula_nit) {
                $existeCliente = Cliente::where('empresa_id', $cliente->empresa_id)
                    ->where('cedula_nit', $request->cedula_nit)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($existeCliente) {
                    return response()->json([
                        'message' => 'Ya existe un cliente con este documento en la empresa'
                    ], 422);
                }
            }

            $data = $request->except(['foto', 'empresa_id']); // No permitir cambiar empresa

            // Manejar actualización de foto
            if ($request->hasFile('foto')) {
                // Eliminar foto anterior si existe
                if ($cliente->foto && Storage::disk('public')->exists($cliente->foto)) {
                    Storage::disk('public')->delete($cliente->foto);
                }
                $fotoPath = $request->file('foto')->store('clientes/fotos', 'public');
                $data['foto'] = $fotoPath;
            }

            $cliente->update($data);

            return response()->json([
                'message' => 'Cliente actualizado exitosamente',
                'cliente' => $cliente->load([
                    'tipoDocumento',
                    'tipoPersona',
                    'tipoResponsabilidad',
                    'departamento',
                    'municipio'
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cliente_id' => $id
            ]);
            return response()->json([
                'message' => 'Error al actualizar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        try {
            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar permisos
            $user = auth()->user();
            if (!$user->esAdministradorGlobal() && !$user->perteneceAEmpresa($cliente->empresa_id)) {
                return response()->json([
                    'message' => 'No tiene permisos para eliminar este cliente'
                ], 403);
            }

            // Soft delete
            $cliente->delete();

            return response()->json([
                'message' => 'Cliente eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar cliente', [
                'message' => $e->getMessage(),
                'cliente_id' => $id
            ]);
            return response()->json([
                'message' => 'Error al eliminar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
