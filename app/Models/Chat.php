<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Chat extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'title',
        'first_message',
        'empresa_id', // Opcional: para chat específico de una empresa
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con el usuario propietario del chat
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la empresa (opcional)
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relación con los mensajes del chat
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    /**
     * Relación con los mensajes del chat ordenados por fecha
     */
    public function messagesOrdered(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    /**
     * Obtener el último mensaje del chat
     */
    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    /**
     * Scope para chats activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para chats del usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para chats por empresa
     */
    public function scopeForEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Generar título automático basado en el primer mensaje
     */
    public function generateTitle(): string
    {
        if ($this->first_message) {
            // Tomar las primeras 50 caracteres y agregar puntos suspensivos si es necesario
            $title = substr($this->first_message, 0, 50);
            if (strlen($this->first_message) > 50) {
                $title .= '...';
            }
            return $title;
        }

        return 'Nueva conversación';
    }

    /**
     * Actualizar el título basado en el contenido
     */
    public function updateTitle(): void
    {
        if (empty($this->title) || $this->title === 'Nueva conversación') {
            $this->title = $this->generateTitle();
            $this->save();
        }
    }

    /**
     * Verificar si el usuario puede acceder a este chat
     */
    public function canAccess(User $user): bool
    {
        // El propietario siempre puede acceder
        if ($this->user_id === $user->id) {
            return true;
        }

        // Si es admin global, puede ver todos los chats
        if ($user->esAdministradorGlobal()) {
            return true;
        }

        // Si el chat está asociado a una empresa, verificar acceso a la empresa
        if ($this->empresa_id && $user->puedeAccederAEmpresa($this->empresa_id)) {
            return true;
        }

        return false;
    }

    /**
     * Obtener información resumida del chat
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'messages_count' => $this->messages()->count(),
            'last_activity' => $this->updated_at,
            'empresa' => $this->empresa?->razon_social,
            'user' => $this->user?->name,
        ];
    }

    /**
     * Identificador para logs
     */
    protected function getActivityIdentifier(): string
    {
        return "Chat: {$this->title} (ID: {$this->id})";
    }
}
