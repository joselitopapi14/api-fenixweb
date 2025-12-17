<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Empresa::with(['departamento', 'municipio', 'tipoPersona', 'tipoResponsabilidad']);

            // Filtro por usuario (si no es admin global)
            $user = auth()->user();
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas->pluck('id');
                $query->whereIn('id', $empresasIds);
            }

            // Filtro de búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('razon_social', 'ilike', "%{$search}%")
                      ->orWhere('nit', 'like', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%");
                });
            }

            // Filtro por estado
            if ($request->filled('activa')) {
                $query->where('activa', $request->activa === 'true' || $request->activa === '1');
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'razon_social');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $empresas = $query->paginate($perPage);

            return response()->json($empresas);
        } catch (\Exception $e) {
            Log::error('Error al listar empresas', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nit' => 'required|string|max:20|unique:empresas,nit',
                'dv' => 'required|string|max:1',
                'razon_social' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',
                'telefono_fijo' => 'nullable|string|max:20',
                'celular' => 'nullable|string|max:20',
                'email' => 'required|email|max:255',
                'pagina_web' => 'nullable|url|max:255',
                'departamento_id' => 'nullable|exists:departamentos,id',
                'municipio_id' => 'nullable|exists:municipios,id',
                'comuna_id' => 'nullable|exists:comunas,id',
                'barrio_id' => 'nullable|exists:barrios,id',
                'tipo_persona_id' => 'required|exists:tipo_personas,id',
                'tipo_responsabilidad_id' => 'required|exists:tipo_responsabilidades,id',
                'tipo_documento_id' => 'required|exists:tipo_documentos,id',
                'representante_legal' => 'nullable|string|max:255',
                'cedula_representante' => 'nullable|string|max:20',
                'email_representante' => 'nullable|email|max:255',
                'direccion_representante' => 'nullable|string|max:255',
                'software_id' => 'nullable|string|max:255',
                'software_pin' => 'nullable|string|max:255',
                'certificate_password' => 'nullable|string|max:255',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'certificate_path' => 'nullable|file|mimes:p12,pfx|max:5120',
                'activa' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->except(['logo', 'certificate_path']);

            // Manejar subida de logo
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('empresas/logos', 'public');
                $data['logo'] = $logoPath;
            }

            // Manejar subida de certificado
            if ($request->hasFile('certificate_path')) {
                $certPath = $request->file('certificate_path')->store('empresas/certificates', 'private');
                $data['certificate_path'] = $certPath;
            }

            $empresa = Empresa::create($data);

            // Si el usuario actual no es admin global, asociarlo como administrador de la empresa
            $user = auth()->user();
            if ($user && !$user->esAdministradorGlobal()) {
                $empresa->usuarios()->attach($user->id, [
                    'es_administrador' => true,
                    'activo' => true
                ]);
            }

            return response()->json([
                'message' => 'Empresa creada exitosamente',
                'empresa' => $empresa->load(['departamento', 'municipio', 'tipoPersona', 'tipoResponsabilidad'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear empresa', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['certificate_password'])
            ]);
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $empresa = Empresa::with([
                'departamento',
                'municipio',
                'comuna',
                'barrio',
                'tipoPersona',
                'tipoResponsabilidad',
                'tipoDocumento',
                'usuarios',
                'administradores'
            ])->find($id);

            if (!$empresa) {
                return response()->json([
                    'message' => 'Empresa no encontrada'
                ], 404);
            }

            // Verificar permisos
            $user = auth()->user();
            if (!$user->esAdministradorGlobal() && !$user->perteneceAEmpresa($id)) {
                return response()->json([
                    'message' => 'No tiene permisos para ver esta empresa'
                ], 403);
            }

            return response()->json($empresa);
        } catch (\Exception $e) {
            Log::error('Error al mostrar empresa', [
                'message' => $e->getMessage(),
                'empresa_id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $empresa = Empresa::find($id);

            if (!$empresa) {
                return response()->json([
                    'message' => 'Empresa no encontrada'
                ], 404);
            }

            // Verificar permisos
            $user = auth()->user();
            if (!$user->esAdministradorGlobal() && !$empresa->esAdministrador($user)) {
                return response()->json([
                    'message' => 'No tiene permisos para editar esta empresa'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nit' => 'sometimes|required|string|max:20|unique:empresas,nit,' . $id,
                'dv' => 'sometimes|required|string|max:1',
                'razon_social' => 'sometimes|required|string|max:255',
                'direccion' => 'sometimes|required|string|max:255',
                'telefono_fijo' => 'nullable|string|max:20',
                'celular' => 'nullable|string|max:20',
                'email' => 'sometimes|required|email|max:255',
                'pagina_web' => 'nullable|url|max:255',
                'departamento_id' => 'nullable|exists:departamentos,id',
                'municipio_id' => 'nullable|exists:municipios,id',
                'comuna_id' => 'nullable|exists:comunas,id',
                'barrio_id' => 'nullable|exists:barrios,id',
                'tipo_persona_id' => 'sometimes|required|exists:tipo_personas,id',
                'tipo_responsabilidad_id' => 'sometimes|required|exists:tipo_responsabilidades,id',
                'tipo_documento_id' => 'sometimes|required|exists:tipo_documentos,id',
                'representante_legal' => 'nullable|string|max:255',
                'cedula_representante' => 'nullable|string|max:20',
                'email_representante' => 'nullable|email|max:255',
                'direccion_representante' => 'nullable|string|max:255',
                'software_id' => 'nullable|string|max:255',
                'software_pin' => 'nullable|string|max:255',
                'certificate_password' => 'nullable|string|max:255',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'certificate_path' => 'nullable|file|mimes:p12,pfx|max:5120',
                'activa' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->except(['logo', 'certificate_path']);

            // Manejar actualización de logo
            if ($request->hasFile('logo')) {
                // Eliminar logo anterior si existe
                if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                    Storage::disk('public')->delete($empresa->logo);
                }
                $logoPath = $request->file('logo')->store('empresas/logos', 'public');
                $data['logo'] = $logoPath;
            }

            // Manejar actualización de certificado
            if ($request->hasFile('certificate_path')) {
                // Eliminar certificado anterior si existe
                if ($empresa->certificate_path && Storage::disk('private')->exists($empresa->certificate_path)) {
                    Storage::disk('private')->delete($empresa->certificate_path);
                }
                $certPath = $request->file('certificate_path')->store('empresas/certificates', 'private');
                $data['certificate_path'] = $certPath;
            }

            $empresa->update($data);

            return response()->json([
                'message' => 'Empresa actualizada exitosamente',
                'empresa' => $empresa->load(['departamento', 'municipio', 'tipoPersona', 'tipoResponsabilidad'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar empresa', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'empresa_id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        try {
            $empresa = Empresa::find($id);

            if (!$empresa) {
                return response()->json([
                    'message' => 'Empresa no encontrada'
                ], 404);
            }

            // Verificar permisos (solo admin global puede eliminar)
            $user = auth()->user();
            if (!$user->esAdministradorGlobal()) {
                return response()->json([
                    'message' => 'No tiene permisos para eliminar empresas'
                ], 403);
            }

            // Soft delete
            $empresa->delete();

            return response()->json([
                'message' => 'Empresa eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar empresa', [
                'message' => $e->getMessage(),
                'empresa_id' => $id
            ]);
            throw $e;
        }
    }
}
