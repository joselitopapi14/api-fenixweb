<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\JsonResponse;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;

class ActivityLogController extends Controller
{
    /**
     * Mostrar el historial de actividades
     */
    public function index(Request $request)
    {
        // Si se solicita una actividad específica para el modal
        if ($request->filled('activity_id')) {
            $activity = Activity::with(['subject', 'causer'])
                ->find($request->activity_id);

            if ($activity && $request->expectsJson()) {
                return response()->json([
                    'activity' => $activity,
                    'success' => true
                ]);
            }

            if (!$activity) {
                return response()->json([
                    'error' => 'Actividad no encontrada',
                    'success' => false
                ], 404);
            }
        }

        $query = Activity::with(['subject', 'causer'])
            ->latest();

        // Filtro por búsqueda general
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($subq) use ($search) {
                      $subq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por modelo/tabla
        if ($request->filled('model')) {
            $query->where('subject_type', $request->model);
        }

        // Filtro por tipo de evento
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filtro por usuario
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        // Filtro para actividades del sistema (sin usuario)
        if ($request->filled('no_user')) {
            $query->whereNull('causer_id');
        }

        // Filtros de fecha
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(20)->appends($request->query());

        // Obtener usuarios para el filtro
        $users = \App\Models\User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // Estadísticas para las cards
        $estadisticas = [
            'hoy' => Activity::whereDate('created_at', today())->count(),
            'semana' => Activity::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'usuarios_activos' => Activity::distinct('causer_id')->whereNotNull('causer_id')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total' => Activity::count(),
        ];

        if ($request->expectsJson()) {
            return response()->json(compact('activities', 'estadisticas', 'users'));
        }

        // Para requests AJAX, devolver solo la vista parcial
        if ($request->ajax()) {
            return view('admin.activity-log.partials.activities-list', compact('activities'))->render();
        }

        return view('admin.activity-log.index', compact('activities', 'estadisticas', 'users'));
    }

    /**
     * Obtener actividades de un modelo específico
     */
    public function forModel(Request $request, string $model, int $id): JsonResponse
    {
        $activities = Activity::forSubject($model, $id)
            ->with('causer')
            ->latest()
            ->get();

        return response()->json($activities);
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanup(): JsonResponse
    {
        $deletedCount = Activity::where('created_at', '<', now()->subDays(365))->delete();

        return response()->json([
            'message' => "Se eliminaron {$deletedCount} registros antiguos",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Exportar actividades a Excel
     */
    public function export(Request $request)
    {
        try {
            $fileName = 'registro_actividades_' . now('America/Bogota')->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new ActivityLogExport($request), $fileName);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar la exportación: ' . $e->getMessage());
        }
    }
}
