<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity as SpatieLogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivity
{
    use SpatieLogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getLoggedAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => $this->getActivityDescription($eventName));
    }

    /**
     * Obtiene los atributos que se deben registrar
     * Cada modelo puede sobrescribir este método
     */
    protected function getLoggedAttributes(): array
    {
        // Por defecto, registra todos los fillable excepto timestamps
        return array_diff($this->getFillable(), ['created_at', 'updated_at']);
    }

    /**
     * Genera descripción personalizada para cada evento
     */
    protected function getActivityDescription(string $eventName): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getActivityIdentifier();

        return match($eventName) {
            'created' => "Se creó {$modelName}: {$identifier}",
            'updated' => "Se actualizó {$modelName}: {$identifier}",
            'deleted' => "Se eliminó {$modelName}: {$identifier}",
            default => "Acción '{$eventName}' en {$modelName}: {$identifier}"
        };
    }

    /**
     * Obtiene el identificador del modelo para el log
     * Cada modelo puede sobrescribir este método
     */
    protected function getActivityIdentifier(): string
    {
        // Intenta usar nombre, titulo, descripcion, o ID como fallback
        return $this->name ??
               $this->titulo ??
               $this->descripcion ??
               "ID: {$this->id}";
    }

    /**
     * Registra un evento personalizado
     */
    public function logCustomActivity(string $event, string $description = null, array $properties = []): void
    {
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->event($event)
            ->withProperties($properties)
            ->log($description ?? $this->getActivityDescription($event));
    }
}
