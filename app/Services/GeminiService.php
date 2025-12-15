<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\BolletaEmpeno;
use App\Models\BoletaDesempeno;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $model;

    public function __construct()
    {
        $this->model = 'gemini-2.0-flash'; // Usar modelo disponible según documentación

        // Verificar que la API key esté configurada
        if (empty(config('gemini.api_key'))) {
            Log::warning('GEMINI_API_KEY no está configurada en el archivo .env');
        }
    }    /**
     * Enviar mensaje al chat con contexto empresarial
     */
    public function sendMessage(string $message, User $user = null): array
    {
        try {
            // Verificar configuración de API
            if (empty(config('gemini.api_key'))) {
                throw new \Exception('API Key de Gemini no configurada. Por favor, configura GEMINI_API_KEY en tu archivo .env');
            }

            $user = $user ?? Auth::user();

            if (!$user) {
                throw new \Exception('Usuario no autenticado');
            }

            // Validar longitud del mensaje
            if (strlen($message) > 2000) {
                throw new \Exception('El mensaje es demasiado largo. Máximo 2000 caracteres.');
            }

            if (strlen(trim($message)) === 0) {
                throw new \Exception('El mensaje no puede estar vacío.');
            }

            // Obtener contexto empresarial
            $context = $this->getEmpresarialContext($user);

            // Construir el prompt con contexto
            $fullPrompt = $this->buildContextualPrompt($message, $context, $user);

            // Enviar a Gemini usando la nueva sintaxis
            try {
                $response = Gemini::generativeModel(model: $this->model)->generateContent($fullPrompt);
            } catch (\Exception $modelError) {
                // Si el modelo no funciona, intentar con un modelo alternativo
                Log::warning("Error con modelo {$this->model}: " . $modelError->getMessage());

                // Intentar con modelo alternativo
                $alternativeModel = 'gemini-1.5-flash';
                Log::info("Intentando con modelo alternativo: {$alternativeModel}");

                try {
                    $response = Gemini::generativeModel(model: $alternativeModel)->generateContent($fullPrompt);
                } catch (\Exception $altError) {
                    Log::error("Error también con modelo alternativo {$alternativeModel}: " . $altError->getMessage());
                    throw new \Exception('No se pudo conectar con el servicio de IA. Modelos no disponibles.');
                }
            }

            if (!$response || !$response->text()) {
                throw new \Exception('No se recibió una respuesta válida de Gemini');
            }            return [
                'success' => true,
                'message' => $response->text(),
                'user_message' => $message
            ];

        } catch (\Exception $e) {
            Log::error('Error en GeminiService::sendMessage: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'message_length' => strlen($message ?? ''),
                'trace' => $e->getTraceAsString()
            ]);

            // Mensaje de error más amigable para el usuario
            $userError = $this->getUserFriendlyError($e->getMessage());

            return [
                'success' => false,
                'error' => $userError,
                'user_message' => $message
            ];
        }
    }

    /**
     * Convertir errores técnicos en mensajes amigables
     */
    private function getUserFriendlyError(string $error): string
    {
        if (strpos($error, 'API Key') !== false) {
            return 'El servicio de chat no está disponible temporalmente. Por favor, contacta al administrador.';
        }

        if (strpos($error, 'demasiado largo') !== false) {
            return 'Tu mensaje es demasiado largo. Por favor, intenta con un mensaje más corto.';
        }

        if (strpos($error, 'vacío') !== false) {
            return 'Por favor, escribe un mensaje antes de enviarlo.';
        }

        if (strpos($error, 'conexión') !== false || strpos($error, 'timeout') !== false) {
            return 'Problema de conexión. Por favor, inténtalo de nuevo.';
        }

        return 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo.';
    }    /**
     * Obtener contexto empresarial del usuario
     */
    private function getEmpresarialContext(User $user): array
    {
        $context = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
                'es_admin_global' => $user->esAdministradorGlobal()
            ],
            'empresas' => [],
            'empresas_totales' => 0,
            'clientes_count' => 0,
            'productos_count' => 0,
            'bolletas_empeno_count' => 0,
            'boletas_desempeno_count' => 0
        ];

        // Obtener todas las empresas a las que el usuario tiene acceso
        $empresas = $user->empresasActivas()->with([
            'clientes' => function($query) {
                $query->select('id', 'empresa_id', 'nombres', 'apellidos', 'razon_social', 'cedula_nit', 'email', 'telefono_fijo', 'celular', 'created_at');
            },
            'productos' => function($query) {
                $query->select('id', 'empresa_id', 'nombre', 'created_at');
            },
            'departamento',
            'municipio'
        ])->get();

        // Contador total de empresas en el sistema (si es admin global)
        if ($user->esAdministradorGlobal()) {
            $context['empresas_totales'] = Empresa::count();
            $context['empresas_activas_sistema'] = Empresa::where('activa', true)->count();
            $context['empresas_inactivas_sistema'] = Empresa::where('activa', false)->count();

            // Si es admin global, obtener información de todas las empresas del sistema
            $context['empresas_resumen_global'] = Empresa::select('id', 'razon_social', 'nit', 'dv', 'activa', 'created_at')
                ->withCount('clientes')
                ->orderBy('razon_social')
                ->get()
                ->map(function($empresa) {
                    return [
                        'id' => $empresa->id,
                        'razon_social' => $empresa->razon_social,
                        'nit_completo' => $empresa->nit . '-' . $empresa->dv,
                        'activa' => $empresa->activa,
                        'clientes_count' => $empresa->clientes_count,
                        'created_at' => $empresa->created_at?->format('d/m/Y'),
                    ];
                })->toArray();
        } else {
            $context['empresas_totales'] = $empresas->count();
            $context['empresas_activas_sistema'] = $empresas->where('activa', true)->count();
            $context['empresas_inactivas_sistema'] = $empresas->where('activa', false)->count();
        }

        foreach ($empresas as $empresa) {
            $empresaData = [
                'id' => $empresa->id,
                'nit' => $empresa->nit,
                'dv' => $empresa->dv,
                'nit_completo' => $empresa->nit_completo,
                'razon_social' => $empresa->razon_social,
                'direccion' => $empresa->direccion,
                'direccion_completa' => $empresa->direccion_completa,
                'telefono_fijo' => $empresa->telefono_fijo,
                'celular' => $empresa->celular,
                'email' => $empresa->email,
                'activa' => $empresa->activa,
                'is_admin' => $empresa->pivot->es_administrador ?? false,
                'departamento' => $empresa->departamento?->name,
                'municipio' => $empresa->municipio?->name
            ];

            // Estadísticas detalladas de clientes (todos son considerados activos ya que usan SoftDeletes)
            $clientesTotales = $empresa->clientes->count();
            $clientesActivos = $clientesTotales; // Todos los clientes no eliminados son activos
            $clientesInactivos = 0; // Los eliminados se manejan con SoftDeletes

            // Estadísticas de productos (todos son considerados activos ya que no hay campo estado)
            $productosTotales = $empresa->productos->count();
            $productosActivos = $productosTotales;

            // Conteos adicionales (boletas)
            $bolletasEmpenoCount = BolletaEmpeno::where('empresa_id', $empresa->id)->count() ?? 0;
            $boletasDesempenoCount = BoletaDesempeno::where('empresa_id', $empresa->id)->count() ?? 0;

            $empresaData['estadisticas'] = [
                'clientes_totales' => $clientesTotales,
                'clientes_activos' => $clientesActivos,
                'clientes_inactivos' => $clientesInactivos,
                'productos_totales' => $productosTotales,
                'productos_activos' => $productosActivos,
                'bolletas_empeno' => $bolletasEmpenoCount,
                'boletas_desempeno' => $boletasDesempenoCount
            ];

            // Primeros 10 clientes con información completa
            $clientesCompletos = $empresa->clientes
                ->sortBy('created_at')
                ->take(10)
                ->values()
                ->map(function($cliente) {
                    $nombreCompleto = $cliente->esPersonaJuridica() ?
                        $cliente->razon_social :
                        trim(($cliente->nombres ?? '') . ' ' . ($cliente->apellidos ?? ''));

                    return [
                        'id' => $cliente->id,
                        'nombre_completo' => $nombreCompleto,
                        'nombres' => $cliente->nombres,
                        'apellidos' => $cliente->apellidos,
                        'razon_social' => $cliente->razon_social,
                        'cedula_nit' => $cliente->cedula_nit,
                        'dv' => $cliente->dv,
                        'documento_completo' => $cliente->dv ? $cliente->cedula_nit . '-' . $cliente->dv : $cliente->cedula_nit,
                        'email' => $cliente->email,
                        'telefono_fijo' => $cliente->telefono_fijo,
                        'celular' => $cliente->celular,
                        'direccion' => $cliente->direccion,
                        'es_persona_juridica' => $cliente->esPersonaJuridica(),
                        'tipo_persona' => $cliente->esPersonaJuridica() ? 'Jurídica' : 'Natural',
                        'fecha_registro' => $cliente->created_at?->format('d/m/Y'),
                        'antiguedad_dias' => $cliente->created_at?->diffInDays(now()),
                        'representante_legal' => $cliente->representante_legal,
                        'cedula_representante' => $cliente->cedula_representante
                    ];
                });

            $empresaData['clientes_completos'] = $clientesCompletos->toArray();

            // También mantener los clientes recientes para referencia
            $clientesRecientes = $empresa->clientes
                ->sortByDesc('created_at')
                ->take(5)
                ->values()
                ->map(function($cliente) {
                    $nombreCompleto = $cliente->esPersonaJuridica() ?
                        $cliente->razon_social :
                        trim(($cliente->nombres ?? '') . ' ' . ($cliente->apellidos ?? ''));

                    return [
                        'id' => $cliente->id,
                        'nombre_completo' => $nombreCompleto,
                        'documento_completo' => $cliente->dv ? $cliente->cedula_nit . '-' . $cliente->dv : $cliente->cedula_nit,
                        'telefono' => $cliente->telefono_fijo ?: $cliente->celular,
                        'email' => $cliente->email,
                        'es_persona_juridica' => $cliente->esPersonaJuridica(),
                        'created_at' => $cliente->created_at?->format('d/m/Y H:i'),
                        'dias_desde_registro' => $cliente->created_at?->diffInDays(now())
                    ];
                });

            $empresaData['clientes_recientes'] = $clientesRecientes->toArray();

            // Análisis de crecimiento de clientes (últimos 30 días)
            $clientesUltimos30Dias = $empresa->clientes
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $empresaData['crecimiento'] = [
                'clientes_ultimos_30_dias' => $clientesUltimos30Dias,
                'promedio_clientes_por_dia' => round($clientesUltimos30Dias / 30, 2)
            ];

            $context['empresas'][] = $empresaData;

            // Sumar totales globales
            $context['clientes_count'] += $clientesTotales;
            $context['productos_count'] += $productosTotales;
            $context['bolletas_empeno_count'] += $bolletasEmpenoCount;
            $context['boletas_desempeno_count'] += $boletasDesempenoCount;
        }

        // Estadísticas generales del usuario
        $context['resumen_acceso'] = [
            'total_empresas_acceso' => $empresas->count(),
            'empresas_como_admin' => $empresas->where('pivot.es_administrador', true)->count(),
            'empresas_como_empleado' => $empresas->where('pivot.es_administrador', false)->count(),
            'puede_ver_todo_sistema' => $user->esAdministradorGlobal()
        ];

        return $context;
    }

    /**
     * Construir prompt con contexto empresarial
     */
    private function buildContextualPrompt(string $userMessage, array $context, User $user): string
    {
        $systemPrompt = "Eres un asistente virtual inteligente para el sistema de gestión empresarial Fenix Gold. ";
        $systemPrompt .= "Tu función es ayudar a los usuarios con información sobre empresas, clientes, productos, empeños y desempeños. ";
        $systemPrompt .= "Siempre mantén un tono profesional y amigable, utiliza formato Markdown para resaltar información importante. ";
        $systemPrompt .= "Si no tienes información específica sobre algo, indícalo claramente. ";
        $systemPrompt .= "Responde en español y de manera concisa pero informativa.\n\n";

        $systemPrompt .= "**CONTEXTO ACTUAL DEL USUARIO:**\n";
        $systemPrompt .= "- Usuario: **{$context['user']['name']}** ({$context['user']['email']})\n";
        $systemPrompt .= "- Roles: " . implode(', ', $context['user']['roles']) . "\n";
        $systemPrompt .= "- Es administrador global: " . ($context['user']['es_admin_global'] ? '**Sí**' : 'No') . "\n\n";

        $systemPrompt .= "**RESUMEN ESTADÍSTICO GLOBAL:**\n";
        $systemPrompt .= "- Total de empresas en el sistema: **{$context['empresas_totales']}**\n";
        if (isset($context['empresas_activas_sistema'])) {
            $systemPrompt .= "- Empresas activas: **{$context['empresas_activas_sistema']}** | Inactivas: **{$context['empresas_inactivas_sistema']}**\n";
        }
        $systemPrompt .= "- Empresas a las que tienes acceso: **{$context['resumen_acceso']['total_empresas_acceso']}**\n";
        $systemPrompt .= "- Como administrador: **{$context['resumen_acceso']['empresas_como_admin']}**\n";
        $systemPrompt .= "- Como empleado: **{$context['resumen_acceso']['empresas_como_empleado']}**\n";
        $systemPrompt .= "- Total de clientes (todas tus empresas): **{$context['clientes_count']}**\n";
        $systemPrompt .= "- Total de productos (todas tus empresas): **{$context['productos_count']}**\n";
        $systemPrompt .= "- Total de boletas de empeño: **{$context['bolletas_empeno_count']}**\n";
        $systemPrompt .= "- Total de boletas de desempeño: **{$context['boletas_desempeno_count']}**\n\n";

        // Agregar resumen global de todas las empresas si es admin global
        if (isset($context['empresas_resumen_global']) && !empty($context['empresas_resumen_global'])) {
            $systemPrompt .= "**LISTADO COMPLETO DE EMPRESAS EN EL SISTEMA:**\n";
            foreach ($context['empresas_resumen_global'] as $empresa) {
                $estado = $empresa['activa'] ? '✅ Activa' : '❌ Inactiva';
                $systemPrompt .= "- **{$empresa['razon_social']}** (NIT: {$empresa['nit_completo']}) - {$estado}\n";
                $systemPrompt .= "  📊 {$empresa['clientes_count']} clientes | Creada: {$empresa['created_at']}\n";
            }
            $systemPrompt .= "\n";
        }

        if (!empty($context['empresas'])) {
            $systemPrompt .= "**EMPRESAS DETALLADAS:**\n";
            foreach ($context['empresas'] as $empresa) {
                $adminStatus = $empresa['is_admin'] ? '(Administrador)' : '(Empleado)';
                $estadoEmpresa = $empresa['activa'] ? 'Activa' : 'Inactiva';

                $systemPrompt .= "\n📋 **{$empresa['razon_social']}** {$adminStatus}\n";
                $systemPrompt .= "   - NIT: {$empresa['nit_completo']}\n";
                $systemPrompt .= "   - Estado: {$estadoEmpresa}\n";
                $systemPrompt .= "   - Ubicación: {$empresa['direccion_completa']}\n";
                $systemPrompt .= "   - Teléfono: {$empresa['telefono_fijo']} | Celular: {$empresa['celular']}\n";
                $systemPrompt .= "   - Email: {$empresa['email']}\n";

                $stats = $empresa['estadisticas'];
                $systemPrompt .= "   - **Estadísticas:**\n";
                $systemPrompt .= "     * Clientes totales: **{$stats['clientes_totales']}** (Activos: **{$stats['clientes_activos']}**, Inactivos: **{$stats['clientes_inactivos']}**)\n";
                $systemPrompt .= "     * Productos: **{$stats['productos_totales']}** (Activos: **{$stats['productos_activos']}**)\n";
                $systemPrompt .= "     * Boletas empeño: **{$stats['bolletas_empeno']}**\n";
                $systemPrompt .= "     * Boletas desempeño: **{$stats['boletas_desempeno']}**\n";

                // Información de crecimiento
                if (isset($empresa['crecimiento'])) {
                    $systemPrompt .= "   - **Crecimiento últimos 30 días:**\n";
                    $systemPrompt .= "     * Nuevos clientes: **{$empresa['crecimiento']['clientes_ultimos_30_dias']}**\n";
                    $systemPrompt .= "     * Promedio diario: **{$empresa['crecimiento']['promedio_clientes_por_dia']}** clientes/día\n";
                }

                // Lista completa de clientes (primeros 10)
                if (!empty($empresa['clientes_completos'])) {
                    $systemPrompt .= "   - **📋 LISTA COMPLETA DE CLIENTES (primeros 10):**\n";
                    foreach ($empresa['clientes_completos'] as $index => $cliente) {
                        $numero = $index + 1;
                        $tipoPersona = $cliente['tipo_persona'];
                        $contacto = [];

                        if ($cliente['telefono_fijo']) $contacto[] = "Tel: {$cliente['telefono_fijo']}";
                        if ($cliente['celular']) $contacto[] = "Cel: {$cliente['celular']}";
                        if ($cliente['email']) $contacto[] = "Email: {$cliente['email']}";

                        $contactoStr = !empty($contacto) ? " | " . implode(" | ", $contacto) : "";

                        $systemPrompt .= "     **{$numero}.** **{$cliente['nombre_completo']}** ({$tipoPersona})\n";
                        $systemPrompt .= "         - Documento: {$cliente['documento_completo']}{$contactoStr}\n";

                        if ($cliente['direccion']) {
                            $systemPrompt .= "         - Dirección: {$cliente['direccion']}\n";
                        }

                        if ($cliente['representante_legal']) {
                            $systemPrompt .= "         - Representante Legal: {$cliente['representante_legal']} (CC: {$cliente['cedula_representante']})\n";
                        }

                        $systemPrompt .= "         - Registrado: {$cliente['fecha_registro']} (hace {$cliente['antiguedad_dias']} días)\n";
                    }

                    if (count($empresa['clientes_completos']) >= 10) {
                        $systemPrompt .= "     *(Mostrando los primeros 10 clientes de {$stats['clientes_totales']} total)*\n";
                    }
                }

                // Clientes recientes (resumen)
                if (!empty($empresa['clientes_recientes'])) {
                    $systemPrompt .= "   - **📅 Últimos clientes registrados (resumen):**\n";
                    foreach ($empresa['clientes_recientes'] as $cliente) {
                        $tipoPersona = $cliente['es_persona_juridica'] ? 'PJ' : 'PN';
                        $documento = $cliente['documento_completo'];
                        $telefono = $cliente['telefono'] ? " | {$cliente['telefono']}" : "";

                        $systemPrompt .= "     * **{$cliente['nombre_completo']}** ({$tipoPersona}) - {$documento}{$telefono} - {$cliente['created_at']}\n";
                    }
                }
            }
        }

        $systemPrompt .= "\n**INSTRUCCIONES ESPECÍFICAS:**\n";
        $systemPrompt .= "- Cuando te pregunten sobre clientes, empresas o estadísticas, usa SIEMPRE la información del contexto proporcionado\n";
        $systemPrompt .= "- **PARA CONSULTAS DE CLIENTES**: Si te piden listar clientes de una empresa específica, usa la información de la 'LISTA COMPLETA DE CLIENTES' del contexto\n";
        $systemPrompt .= "- **FORMATO DE LISTADO**: Cuando listes clientes, incluye: nombre completo, tipo de persona (Natural/Jurídica), documento, contacto y fecha de registro\n";
        $systemPrompt .= "- **EMPRESAS ESPECÍFICAS**: Si mencionan una empresa por nombre (como 'Fenix BG SAS'), busca esa empresa en el contexto y proporciona sus clientes específicos\n";
        $systemPrompt .= "- Usa formato **negrita** para resaltar números y datos importantes\n";
        $systemPrompt .= "- Si te preguntan sobre cantidad de clientes o empresas, proporciona datos específicos y exactos del contexto\n";
        $systemPrompt .= "- Si te preguntan el nombre de empresas, lista todas las empresas con sus datos completos\n";
        $systemPrompt .= "- Para estadísticas, siempre proporciona desglose por empresa cuando esté disponible\n";
        $systemPrompt .= "- Para consultas sobre empeños y desempeños, explica los procesos básicos si es necesario\n";
        $systemPrompt .= "- Si te preguntan sobre funcionalidades del sistema, guía al usuario hacia las secciones relevantes\n";
        $systemPrompt .= "- Si necesitas información más específica que no está en el contexto, sugiere consultar la sección correspondiente del sistema\n";
        $systemPrompt .= "- Siempre saluda al usuario por su nombre cuando sea apropiado\n";
        $systemPrompt .= "- Responde con información completa y detallada basada en el contexto proporcionado\n";
        $systemPrompt .= "- **IMPORTANTE**: Si hay clientes en el contexto, SIEMPRE proporciónelos cuando se soliciten, no digas que no tienes acceso a esa información\n\n";

        $systemPrompt .= "**PREGUNTA DEL USUARIO:** {$userMessage}\n\n";
        $systemPrompt .= "Responde de manera útil y específica basándote en el contexto proporcionado. Usa los datos exactos del contexto:";

        return $systemPrompt;
    }

    /**
     * Obtener información específica de una empresa
     */
    public function getEmpresaInfo(int $empresaId, User $user = null): array
    {
        try {
            $user = $user ?? Auth::user();

            if (!$user->puedeAccederAEmpresa($empresaId)) {
                throw new \Exception('No tienes acceso a esta empresa');
            }

            $empresa = Empresa::findOrFail($empresaId);

            return [
                'success' => true,
                'empresa' => $empresa,
                'clientes_count' => Cliente::where('empresa_id', $empresaId)->count(),
                'productos_count' => Producto::where('empresa_id', $empresaId)->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
