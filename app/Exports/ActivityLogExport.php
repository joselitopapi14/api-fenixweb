<?php

namespace App\Exports;

use Spatie\Activitylog\Models\Activity;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class ActivityLogExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Activity::with(['subject', 'causer'])
            ->latest();

        // Aplicar filtros basados en la request
        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($subq) use ($search) {
                      $subq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($this->request->filled('model')) {
            $query->where('subject_type', $this->request->model);
        }

        if ($this->request->filled('event')) {
            $query->where('event', $this->request->event);
        }

        if ($this->request->filled('user_id')) {
            $query->where('causer_id', $this->request->user_id);
        }

        if ($this->request->filled('no_user')) {
            $query->whereNull('causer_id');
        }

        if ($this->request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $this->request->date_from);
        }

        if ($this->request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $this->request->date_to);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tabla/Modelo',
            'Evento',
            'Descripción',
            'Usuario',
            'Email Usuario',
            'ID Registro Afectado',
            'Fecha y Hora',
            'Propiedades Anteriores',
            'Propiedades Nuevas'
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->id,
            class_basename($activity->subject_type),
            ucfirst($activity->event),
            $activity->description,
            $activity->causer ? $activity->causer->name : 'Sistema',
            $activity->causer ? $activity->causer->email : '',
            $activity->subject_id,
            $activity->created_at->setTimezone('America/Bogota')->format('d/m/Y H:i:s'),
            $this->formatProperties($activity->properties['old'] ?? []),
            $this->formatProperties($activity->properties['attributes'] ?? $activity->properties ?? [])
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '059669'], // Green-600
                ],
            ],
            // All cells
            'A:J' => [
                'alignment' => [
                    'vertical' => 'top',
                    'wrapText' => true,
                ],
            ],
        ];
    }

    private function formatProperties($properties): string
    {
        if (empty($properties)) {
            return '';
        }

        if (is_array($properties) || is_object($properties)) {
            $formatted = [];
            foreach ((array)$properties as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $formatted[] = "{$key}: {$value}";
            }
            return implode("\n", $formatted);
        }

        return (string)$properties;
    }
}
