<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\TipoOro;
use App\Models\TipoProducto;
use App\Models\TipoMovimiento;
use App\Models\TipoInteres;
use App\Models\Sede;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with real system data.
     */
    public function index()
    {
        $user = Auth::user();

        // Estadísticas generales del sistema
        $stats = [
            'total_empresas' => Empresa::count(),
            'empresas_activas' => Empresa::where('activa', true)->count(),
            'total_clientes' => Cliente::count(),
            'clientes_este_mes' => Cliente::whereMonth('created_at', Carbon::now()->month)
                                        ->whereYear('created_at', Carbon::now()->year)
                                        ->count(),
            'total_productos' => Producto::count(),
            'total_sedes' => Sede::where('activa', true)->count(),
            'total_usuarios' => User::count(),
            'usuarios_activos' => User::whereHas('roles')->count(),
        ];

        // Estadísticas por tipo de oro
        $tiposOro = TipoOro::with('empresa')
            ->select('tipo_oros.*', DB::raw('COALESCE(empresa_id, 0) as empresa_sort'))
            ->orderBy('empresa_sort')
            ->orderBy('valor_de_mercado', 'desc')
            ->take(10)
            ->get();

        // Estadísticas por tipo de producto
        $tiposProducto = TipoProducto::with('empresa')
            ->select('tipo_productos.*', DB::raw('COALESCE(empresa_id, 0) as empresa_sort'))
            ->withCount('productos')
            ->orderBy('empresa_sort')
            ->orderBy('productos_count', 'desc')
            ->take(10)
            ->get();

        // Actividad reciente del sistema
        $actividadReciente = Activity::with('causer')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'descripcion' => $activity->description,
                    'usuario' => $activity->causer?->name ?? 'Sistema',
                    'fecha' => $activity->created_at->diffForHumans(),
                    'tipo' => $activity->log_name ?? 'general',
                ];
            });

        // Empresas con más clientes
        $empresasTopClientes = Empresa::withCount('clientes')
            ->where('activa', true)
            ->orderBy('clientes_count', 'desc')
            ->take(5)
            ->get();

        // Distribución geográfica de clientes
        $clientesPorDepartamento = Cliente::with('departamento')
            ->select('departamento_id', DB::raw('count(*) as total'))
            ->groupBy('departamento_id')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        // Estadísticas de configuración
        $estadisticasConfig = [
            'tipos_oro_totales' => TipoOro::count(),
            'tipos_oro_globales' => TipoOro::whereNull('empresa_id')->count(),
            'tipos_producto_totales' => TipoProducto::count(),
            'tipos_interes_activos' => TipoInteres::where('activo', true)->count(),
            'tipos_movimiento_activos' => TipoMovimiento::where('activo', true)->count(),
        ];

        return view('dashboard', compact(
            'stats',
            'tiposOro',
            'tiposProducto',
            'actividadReciente',
            'empresasTopClientes',
            'clientesPorDepartamento',
            'estadisticasConfig'
        ));
    }
}
