<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Comuna;
use App\Models\Barrio;
use App\Models\RedSocial;
use App\Models\User;
use App\Models\TipoPersona;
use App\Models\TipoResponsabilidad;
use App\Models\TipoDocumento;
use App\Helpers\CertificateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

                        // Construir query base
            $query = Empresa::with(['departamento', 'municipio', 'comuna', 'barrio', 'tipoPersona', 'tipoResponsabilidad', 'tipoDocumento', 'sedes']);

            // Si no es admin global, filtrar por empresas del usuario
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $query->whereIn('id', $empresasIds);
            }

            // Aplicar filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nit', 'like', "%{$search}%")
                      ->orWhere('razon_social', 'like', "%{$search}%")
                      ->orWhere('representante_legal', 'like', "%{$search}%");
                });
            }

            // Aplicar filtro de estado
            if ($request->filled('estado')) {
                $query->where('activa', $request->estado === 'activa');
            }

            // Ordenamiento
            $sortField = $request->get('sort', 'razon_social');
            $sortDirection = $request->get('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            $empresas = $query->paginate(10);

            // Calcular estadísticas de sedes
            $totalSedes = 0;
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $totalSedes = \App\Models\Sede::whereIn('empresa_id', $empresasIds)->count();
            } else {
                $totalSedes = \App\Models\Sede::count();
            }

            if ($request->ajax()) {
                return view('empresas.partials.empresas-list', compact('empresas'))->render();
            }

            return view('empresas.index', compact('empresas', 'totalSedes'));
        } catch (Exception $e) {
            Log::error('Error al listar empresas: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar empresas.'], 500);
            }
            return redirect()->back()->with('error', 'Error al cargar empresas.');
        }
    }

    public function create()
    {
        $departamentos = Departamento::orderBy('name')->get();
        $redesSociales = RedSocial::orderBy('nombre')->get();
        $tipoPersonas = TipoPersona::orderBy('name')->get();
        $tipoResponsabilidades = TipoResponsabilidad::orderBy('name')->get();
        $tipoDocumentos = TipoDocumento::activos()->orderBy('name')->get();

        return view('empresas.create', compact('departamentos', 'redesSociales', 'tipoPersonas', 'tipoResponsabilidades', 'tipoDocumentos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nit' => 'required|string|max:20|unique:empresas,nit',
            'dv' => 'required|string|size:1',
            'razon_social' => 'required|string|max:255',
            'direccion' => 'required|string',
            'departamento_id' => 'required|exists:departamentos,id',
            'municipio_id' => 'required|exists:municipios,id',
            'comuna_id' => 'nullable|exists:comunas,id',
            'barrio_id' => 'nullable|exists:barrios,id',
            'tipo_persona_id' => 'nullable|exists:tipo_personas,id',
            'tipo_responsabilidad_id' => 'nullable|exists:tipo_responsabilidades,id',
            'tipo_documento_id' => 'nullable|exists:tipo_documentos,id',
            'telefono_fijo' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'pagina_web' => 'nullable|url|max:255',
            'software_id' => 'nullable|string|max:255',
            'software_pin' => 'nullable|string|max:255',
            // certificate: validate as file here; extension/mime validated manually later
            'certificate' => 'nullable|file|max:5120',
            'certificate_password' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'representante_legal' => 'required|string|max:255',
            'cedula_representante' => 'required|string|max:20',
            'email_representante' => 'nullable|email|max:255',
            'direccion_representante' => 'required|string',
            'redes_sociales' => 'nullable|array',
            'redes_sociales.*.red_social_id' => 'required|exists:redes_sociales,id',
            'redes_sociales.*.usuario' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Subir archivos
            $logoPath = null;

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('empresas/logos', 'public');
            }

            // Subir certificado (si existe) al disco local (no público)
            $certificatePath = null;
            if ($request->hasFile('certificate')) {
                $certificatePath = $request->file('certificate')->store('empresas/certificados', 'local');
            }

            // Crear empresa
            $empresa = Empresa::create([
                'nit' => $request->nit,
                'dv' => $request->dv,
                'razon_social' => $request->razon_social,
                'direccion' => $request->direccion,
                'departamento_id' => $request->departamento_id,
                'municipio_id' => $request->municipio_id,
                'comuna_id' => $request->comuna_id,
                'barrio_id' => $request->barrio_id,
                'tipo_persona_id' => $request->tipo_persona_id,
                'tipo_responsabilidad_id' => $request->tipo_responsabilidad_id,
                'tipo_documento_id' => $request->tipo_documento_id,
                'telefono_fijo' => $request->telefono_fijo,
                'celular' => $request->celular,
                'logo' => $logoPath,
                'software_id' => $request->software_id,
                'software_pin' => $request->software_pin,
                'certificate_path' => $certificatePath,
                'certificate_password' => $request->certificate_password,
                'representante_legal' => $request->representante_legal,
                'cedula_representante' => $request->cedula_representante,
                'direccion_representante' => $request->direccion_representante,
                'activa' => true,
            ]);

            // Agregar redes sociales
            if ($request->filled('redes_sociales')) {
                foreach ($request->redes_sociales as $redSocial) {
                    $empresa->redesSociales()->attach($redSocial['red_social_id'], [
                        'usuario_red_social' => $redSocial['usuario']
                    ]);
                }
            }

            // Si no es admin global, asociar al usuario actual como administrador
            $user = auth()->user();
            if (!$user->esAdministradorGlobal()) {
                $empresa->usuarios()->attach($user->id, [
                    'es_administrador' => true,
                    'activo' => true
                ]);
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Empresa creada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('empresas.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear empresa: ' . $e->getMessage());

            // Limpiar archivos subidos en caso de error
            if ($logoPath) Storage::disk('public')->delete($logoPath);

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear la empresa. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function show(Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->puedeAccederAEmpresa($empresa->id)) {
            abort(403, 'No tienes acceso a esta empresa.');
        }

        $empresa->load([
            'departamento', 'municipio', 'comuna', 'barrio',
            'tipoPersona', 'tipoResponsabilidad',
            'usuarios', 'administradores', 'empleados',
            'redesSociales', 'tipoProductos', 'tipoOros'
        ]);

        return view('empresas.show', compact('empresa'));
    }

    public function edit(Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para editar esta empresa.');
        }

        $departamentos = Departamento::orderBy('name')->get();
        $municipios = Municipio::where('departamento_id', $empresa->departamento_id)->orderBy('name')->get();
        $comunas = Comuna::where('municipio_id', $empresa->municipio_id)->orderBy('nombre')->get();
        $barrios = Barrio::where('comuna_id', $empresa->comuna_id)->orderBy('nombre')->get();
        $redesSociales = RedSocial::orderBy('nombre')->get();
        $tipoPersonas = TipoPersona::orderBy('name')->get();
        $tipoResponsabilidades = TipoResponsabilidad::orderBy('name')->get();
        $tipoDocumentos = TipoDocumento::activos()->orderBy('name')->get();

        $empresa->load('redesSociales');

        return view('empresas.edit', compact('empresa', 'departamentos', 'municipios', 'comunas', 'barrios', 'redesSociales', 'tipoPersonas', 'tipoResponsabilidades', 'tipoDocumentos'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para editar esta empresa.');
        }

        $request->validate([
            'nit' => 'required|string|max:20|unique:empresas,nit,' . $empresa->id,
            'dv' => 'required|string|size:1',
            'razon_social' => 'required|string|max:255',
            'direccion' => 'required|string',
            'departamento_id' => 'required|exists:departamentos,id',
            'municipio_id' => 'required|exists:municipios,id',
            'comuna_id' => 'nullable|exists:comunas,id',
            'barrio_id' => 'nullable|exists:barrios,id',
            'tipo_persona_id' => 'nullable|exists:tipo_personas,id',
            'tipo_responsabilidad_id' => 'nullable|exists:tipo_responsabilidades,id',
            'tipo_documento_id' => 'nullable|exists:tipo_documentos,id',
            'telefono_fijo' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'pagina_web' => 'nullable|url|max:255',
            'software_id' => 'nullable|string|max:255',
            'software_pin' => 'nullable|string|max:255',
            'certificate' => 'nullable|file|max:5120',
            'certificate_password' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'representante_legal' => 'required|string|max:255',
            'cedula_representante' => 'required|string|max:20',
            'email_representante' => 'nullable|email|max:255',
            'direccion_representante' => 'required|string',
            'activa' => 'boolean',
            'redes_sociales' => 'nullable|array',
            'redes_sociales.*.red_social_id' => 'required|exists:redes_sociales,id',
            'redes_sociales.*.usuario' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $updateData = $request->only([
                'nit', 'dv', 'razon_social', 'direccion',
                'departamento_id', 'municipio_id', 'comuna_id', 'barrio_id',
                'tipo_persona_id', 'tipo_responsabilidad_id', 'tipo_documento_id',
                'telefono_fijo', 'celular', 'email', 'pagina_web',
                'representante_legal', 'cedula_representante', 'email_representante', 'direccion_representante'
            ]);

            // Solo admin global puede cambiar el estado
            if ($user->esAdministradorGlobal()) {
                $updateData['activa'] = $request->boolean('activa');
            }

            // Manejar archivos
            if ($request->hasFile('logo')) {
                if ($empresa->logo) {
                    Storage::disk('public')->delete($empresa->logo);
                }
                $updateData['logo'] = $request->file('logo')->store('empresas/logos', 'public');
            }

            // Manejar certificado
            if ($request->hasFile('certificate')) {
                // Eliminar certificado anterior si existe
                if ($empresa->certificate_path) {
                    Storage::disk('local')->delete($empresa->certificate_path);
                }
                $updateData['certificate_path'] = $request->file('certificate')->store('empresas/certificados', 'local');
            }

            if ($request->filled('certificate_password')) {
                $updateData['certificate_password'] = $request->certificate_password;
            }

            // Software fields
            $updateData['software_id'] = $request->software_id;
            $updateData['software_pin'] = $request->software_pin;

            $empresa->update($updateData);

            // Actualizar redes sociales
            $empresa->redesSociales()->detach();
            if ($request->filled('redes_sociales')) {
                foreach ($request->redes_sociales as $redSocial) {
                    $empresa->redesSociales()->attach($redSocial['red_social_id'], [
                        'usuario_red_social' => $redSocial['usuario']
                    ]);
                }
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Empresa actualizada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('empresas.show', $empresa);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar empresa: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar la empresa. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function destroy(Empresa $empresa)
    {
        // Solo admin global puede eliminar empresas
        if (!auth()->user()->esAdministradorGlobal()) {
            abort(403, 'No tienes permisos para eliminar empresas.');
        }

        DB::beginTransaction();
        try {
            // Eliminar archivos
            if ($empresa->logo) {
                Storage::disk('public')->delete($empresa->logo);
            }

            $empresa->delete();
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Empresa eliminada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('empresas.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar empresa: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar la empresa. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    // Métodos para gestionar usuarios de empresa
    public function usuarios(Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para gestionar usuarios de esta empresa.');
        }

        $empresa->load(['usuarios' => function ($query) {
            $query->withPivot('es_administrador', 'activo');
        }]);

        $usuariosDisponibles = User::whereDoesntHave('empresas', function ($query) use ($empresa) {
            $query->where('empresa_id', $empresa->id);
        })->get();

        return view('empresas.usuarios', compact('empresa', 'usuariosDisponibles'));
    }

    public function agregarUsuario(Request $request, Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para gestionar usuarios de esta empresa.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'es_administrador' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            $empresa->usuarios()->attach($request->user_id, [
                'es_administrador' => $request->boolean('es_administrador'),
                'activo' => true
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Usuario agregado exitosamente a la empresa.',
                'status' => 'success'
            ]);

            return back();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al agregar usuario a empresa: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al agregar el usuario. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    public function actualizarUsuario(Request $request, Empresa $empresa, User $usuario)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para gestionar usuarios de esta empresa.');
        }

        $request->validate([
            'es_administrador' => 'boolean',
            'activo' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            $empresa->usuarios()->updateExistingPivot($usuario->id, [
                'es_administrador' => $request->boolean('es_administrador'),
                'activo' => $request->boolean('activo')
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Usuario actualizado exitosamente.',
                'status' => 'success'
            ]);

            return back();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar usuario de empresa: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el usuario. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    public function removerUsuario(Empresa $empresa, User $usuario)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para gestionar usuarios de esta empresa.');
        }

        DB::beginTransaction();
        try {
            $empresa->usuarios()->detach($usuario->id);
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Usuario removido exitosamente de la empresa.',
                'status' => 'success'
            ]);

            return back();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al remover usuario de empresa: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al remover el usuario. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    /**
     * Get certificate expiration information
     */
    public function getCertificateInfo(Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->puedeAccederAEmpresa($empresa->id)) {
            return response()->json(['error' => 'No tienes acceso a esta empresa.'], 403);
        }

        if (!$empresa->certificate_path || !$empresa->certificate_password) {
            return response()->json(['error' => 'No hay certificado o contraseña configurada.'], 404);
        }

        try {
            // Leer el archivo del certificado desde storage
            if (!Storage::disk('local')->exists($empresa->certificate_path)) {
                return response()->json(['error' => 'Archivo de certificado no encontrado.'], 404);
            }

            $certificateData = Storage::disk('local')->get($empresa->certificate_path);
            $certificateBase64 = base64_encode($certificateData);

            // Extraer información del certificado
            $certInfo = CertificateHelper::extractCertificateExpirationInfo(
                $certificateBase64,
                $empresa->certificate_password
            );

            if (!$certInfo) {
                return response()->json(['error' => 'No se pudo extraer información del certificado.'], 400);
            }

            // Calcular días restantes
            $expiryDate = \Carbon\Carbon::parse($certInfo['due_date'] . ' ' . $certInfo['due_time']);
            $now = \Carbon\Carbon::now();
            $daysRemaining = $now->diffInDays($expiryDate, false);
            $isExpired = $expiryDate->isPast();

            // Formatear tiempo restante
            $expiresIn = '';
            if ($isExpired) {
                $expiresIn = 'Vencido';
            } else {
                if ($daysRemaining >= 365) {
                    $years = floor($daysRemaining / 365);
                    $remainingDays = $daysRemaining % 365;
                    $expiresIn = $years . ' año' . ($years > 1 ? 's' : '');
                    if ($remainingDays > 0) {
                        $expiresIn .= ' y ' . $remainingDays . ' día' . ($remainingDays > 1 ? 's' : '');
                    }
                } elseif ($daysRemaining >= 30) {
                    $months = floor($daysRemaining / 30);
                    $remainingDays = $daysRemaining % 30;
                    $expiresIn = $months . ' mes' . ($months > 1 ? 'es' : '');
                    if ($remainingDays > 0) {
                        $expiresIn .= ' y ' . $remainingDays . ' día' . ($remainingDays > 1 ? 's' : '');
                    }
                } else {
                    $expiresIn = $daysRemaining . ' día' . ($daysRemaining > 1 ? 's' : '');
                }
            }

            // Determinar color según el estado
            $color = 'green'; // Por defecto verde
            if ($isExpired) {
                $color = 'red';
            } elseif ($daysRemaining <= 30) {
                $color = 'red';
            } elseif ($daysRemaining <= 90) {
                $color = 'yellow';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'fileName' => basename($empresa->certificate_path),
                    'expiresIn' => $expiresIn,
                    'isExpired' => $isExpired,
                    'color' => $color,
                    'daysRemaining' => $daysRemaining,
                    'expiryDate' => $certInfo['due_date'],
                    'expiryTime' => $certInfo['due_time']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting certificate info: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener información del certificado.'], 500);
        }
    }
}
