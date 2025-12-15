<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class ChatMessage extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'chat_id',
        'content',
        'is_from_user',
        'message_type',
        'metadata',
        'tokens_used',
        'model_used',
        'response_time_ms'
    ];

    protected $casts = [
        'is_from_user' => 'boolean',
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'response_time_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Tipos de mensaje
    const TYPE_TEXT = 'text';
    const TYPE_SYSTEM = 'system';
    const TYPE_ERROR = 'error';
    const TYPE_INFO = 'info';

    /**
     * Relación con el chat
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Scope para mensajes del usuario
     */
    public function scopeFromUser($query)
    {
        return $query->where('is_from_user', true);
    }

    /**
     * Scope para mensajes del asistente
     */
    public function scopeFromAssistant($query)
    {
        return $query->where('is_from_user', false);
    }

    /**
     * Scope por tipo de mensaje
     */
    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Accessor para obtener el tipo de remitente
     */
    public function getSenderTypeAttribute(): string
    {
        return $this->is_from_user ? 'user' : 'assistant';
    }

    /**
     * Accessor para obtener el contenido formateado
     */
    public function getFormattedContentAttribute(): string
    {
        // Aplicar formato Markdown si es necesario
        return $this->content;
    }

    /**
     * Verificar si es un mensaje del sistema
     */
    public function isSystemMessage(): bool
    {
        return $this->message_type === self::TYPE_SYSTEM;
    }

    /**
     * Verificar si es un mensaje de error
     */
    public function isErrorMessage(): bool
    {
        return $this->message_type === self::TYPE_ERROR;
    }

    /**
     * Crear un mensaje del usuario
     */
    public static function createUserMessage(int $chatId, string $content, array $metadata = []): self
    {
        return self::create([
            'chat_id' => $chatId,
            'content' => $content,
            'is_from_user' => true,
            'message_type' => self::TYPE_TEXT,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Crear un mensaje del asistente
     */
    public static function createAssistantMessage(
        int $chatId,
        string $content,
        array $metadata = [],
        ?int $tokensUsed = null,
        ?string $modelUsed = null,
        ?int $responseTimeMs = null
    ): self {
        return self::create([
            'chat_id' => $chatId,
            'content' => $content,
            'is_from_user' => false,
            'message_type' => self::TYPE_TEXT,
            'metadata' => $metadata,
            'tokens_used' => $tokensUsed,
            'model_used' => $modelUsed,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Crear un mensaje del sistema
     */
    public static function createSystemMessage(int $chatId, string $content, array $metadata = []): self
    {
        return self::create([
            'chat_id' => $chatId,
            'content' => $content,
            'is_from_user' => false,
            'message_type' => self::TYPE_SYSTEM,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Identificador para logs
     */
    protected function getActivityIdentifier(): string
    {
        $sender = $this->is_from_user ? 'Usuario' : 'Asistente';
        return "Mensaje de {$sender} en Chat ID: {$this->chat_id}";
    }

    /**
     * Obtener información resumida del mensaje
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'content_preview' => substr($this->content, 0, 100) . (strlen($this->content) > 100 ? '...' : ''),
            'sender' => $this->sender_type,
            'type' => $this->message_type,
            'created_at' => $this->created_at,
            'tokens_used' => $this->tokens_used,
        ];
    }
}
