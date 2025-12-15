<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Enviar mensaje al chat
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:2000',
                'chat_id' => 'nullable|integer|exists:chats,id'
            ]);

            $user = Auth::user();
            $message = $request->input('message');
            $chatId = $request->input('chat_id');

            // Transacción para asegurar consistencia
            $result = DB::transaction(function () use ($user, $message, $chatId) {
                $startTime = microtime(true);

                // Obtener o crear chat
                if ($chatId) {
                    $chat = Chat::findOrFail($chatId);

                    // Verificar que el usuario puede acceder a este chat
                    if (!$chat->canAccess($user)) {
                        throw new \Exception('No tienes permiso para acceder a este chat.');
                    }
                } else {
                    // Crear nuevo chat
                    $chat = Chat::create([
                        'user_id' => $user->id,
                        'title' => 'Nueva conversación',
                        'first_message' => $message,
                        'is_active' => true,
                        'metadata' => [
                            'created_from' => 'chat_widget',
                            'user_agent' => request()->userAgent(),
                        ]
                    ]);
                }

                // Guardar mensaje del usuario
                $userMessage = ChatMessage::createUserMessage(
                    $chat->id,
                    $message,
                    ['ip_address' => request()->ip()]
                );

                // Obtener respuesta de Gemini
                $geminiResponse = $this->geminiService->sendMessage($message, $user);

                $endTime = microtime(true);
                $responseTimeMs = intval(($endTime - $startTime) * 1000);

                if ($geminiResponse['success']) {
                    // Guardar respuesta del asistente
                    $assistantMessage = ChatMessage::createAssistantMessage(
                        $chat->id,
                        $geminiResponse['message'],
                        ['gemini_model' => 'gemini-2.0-flash'],
                        null, // tokens_used - se puede agregar en el futuro
                        'gemini-2.0-flash',
                        $responseTimeMs
                    );

                    // Actualizar título del chat si es necesario
                    $chat->updateTitle();

                    return [
                        'success' => true,
                        'message' => $geminiResponse['message'],
                        'user_message' => $message,
                        'chat_id' => $chat->id,
                        'chat_title' => $chat->title,
                        'message_id' => $assistantMessage->id,
                        'response_time_ms' => $responseTimeMs
                    ];
                } else {
                    // Guardar mensaje de error
                    ChatMessage::create([
                        'chat_id' => $chat->id,
                        'content' => $geminiResponse['error'] ?? 'Error desconocido',
                        'is_from_user' => false,
                        'message_type' => ChatMessage::TYPE_ERROR,
                        'response_time_ms' => $responseTimeMs
                    ]);

                    return [
                        'success' => false,
                        'error' => $geminiResponse['error'] ?? 'Error al procesar el mensaje',
                        'user_message' => $message,
                        'chat_id' => $chat->id
                    ];
                }
            });

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos: ' . $e->getMessage(),
                'user_message' => $request->input('message', '')
            ], 422);

        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error en ChatController::sendMessage', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'message' => $request->input('message', ''),
                'chat_id' => $request->input('chat_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el mensaje. Inténtalo de nuevo.',
                'user_message' => $request->input('message', '')
            ], 500);
        }
    }

    /**
     * Obtener información de contexto empresarial
     */
    public function getContext()
    {
        $user = Auth::user();
        $empresas = $user->empresasActivas()->with(['clientes' => function($query) {
            $query->select('id', 'nombres', 'apellidos', 'cedula_nit', 'empresa_id')
                  ->orderBy('created_at', 'desc')
                  ->limit(5);
        }])->get();

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email
            ],
            'empresas' => $empresas
        ]);
    }

    /**
     * Verificar configuración de Gemini
     */
    public function testConfig()
    {
        try {
            $apiKey = config('gemini.api_key');
            $hasApiKey = !empty($apiKey);

            // Intentar listar modelos disponibles
            $modelsResult = null;
            if ($hasApiKey) {
                try {
                    $modelsResult = \Gemini\Laravel\Facades\Gemini::models()->list();
                } catch (\Exception $e) {
                    $modelsResult = 'Error: ' . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'has_api_key' => $hasApiKey,
                'api_key_length' => $hasApiKey ? strlen($apiKey) : 0,
                'config_loaded' => config('gemini') !== null,
                'models_test' => $modelsResult,
                'current_model' => $this->geminiService->getCurrentModel()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener lista de chats del usuario
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 15);

            $chatsQuery = Chat::with(['lastMessage', 'empresa:id,razon_social'])
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id);

                    // Si es admin global, puede ver todos los chats
                    if ($user->esAdministradorGlobal()) {
                        $query->orWhere('id', '>', 0);
                    }
                })
                ->orderBy('updated_at', 'desc');

            $chats = $chatsQuery->paginate($perPage);

            return response()->json([
                'success' => true,
                'chats' => $chats->items(),
                'pagination' => [
                    'current_page' => $chats->currentPage(),
                    'last_page' => $chats->lastPage(),
                    'per_page' => $chats->perPage(),
                    'total' => $chats->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en ChatController::index', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener el historial de chats'
            ], 500);
        }
    }

    /**
     * Obtener un chat específico con sus mensajes
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $chat = Chat::with([
                'messages' => function($query) {
                    $query->orderBy('created_at', 'asc');
                },
                'user:id,name,email',
                'empresa:id,razon_social'
            ])->findOrFail($id);

            // Verificar acceso
            if (!$chat->canAccess($user)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para acceder a este chat'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'chat' => $chat
            ]);

        } catch (\Exception $e) {
            Log::error('Error en ChatController::show', [
                'error' => $e->getMessage(),
                'chat_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener el chat'
            ], 500);
        }
    }

    /**
     * Crear un nuevo chat
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'nullable|string|max:255',
                'empresa_id' => 'nullable|integer|exists:empresas,id'
            ]);

            $user = Auth::user();

            $chat = Chat::create([
                'user_id' => $user->id,
                'title' => $request->input('title', 'Nueva conversación'),
                'empresa_id' => $request->input('empresa_id'),
                'is_active' => true,
                'metadata' => [
                    'created_from' => 'chat_history',
                    'user_agent' => $request->userAgent(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'chat' => $chat
            ]);

        } catch (\Exception $e) {
            Log::error('Error en ChatController::store', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al crear el chat'
            ], 500);
        }
    }

    /**
     * Actualizar un chat
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'title' => 'sometimes|string|max:255',
                'is_active' => 'sometimes|boolean'
            ]);

            $user = Auth::user();
            $chat = Chat::findOrFail($id);

            // Verificar acceso
            if (!$chat->canAccess($user)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para editar este chat'
                ], 403);
            }

            $chat->update($request->only(['title', 'is_active']));

            return response()->json([
                'success' => true,
                'chat' => $chat
            ]);

        } catch (\Exception $e) {
            Log::error('Error en ChatController::update', [
                'error' => $e->getMessage(),
                'chat_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar el chat'
            ], 500);
        }
    }

    /**
     * Eliminar un chat
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $chat = Chat::findOrFail($id);

            // Solo el propietario o admin global puede eliminar
            if ($chat->user_id !== $user->id && !$user->esAdministradorGlobal()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para eliminar este chat'
                ], 403);
            }

            $chat->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chat eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en ChatController::destroy', [
                'error' => $e->getMessage(),
                'chat_id' => $id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar el chat'
            ], 500);
        }
    }

    /**
     * Vista principal del historial de chats
     */
    public function history()
    {
        return view('chat.history');
    }
}
