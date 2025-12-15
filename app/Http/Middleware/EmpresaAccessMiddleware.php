<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Empresa;

class EmpresaAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Si el usuario es administrador global, puede acceder a todo
        if ($user->esAdministradorGlobal()) {
            return $next($request);
        }

        // Verificar si hay un parámetro de empresa en la ruta
        $empresaId = $request->route('empresa')
                  ?? $request->route('empresaId')
                  ?? $request->input('empresa_id');

        // Si hay una empresa específica, verificar acceso
        if ($empresaId) {
            if ($empresaId instanceof Empresa) {
                $empresaId = $empresaId->id;
            }

            if (!$user->puedeAccederAEmpresa($empresaId)) {
                abort(403, 'No tienes acceso a esta empresa.');
            }

            return $next($request);
        }

        // Si no hay empresa específica, verificar que el usuario tenga al menos una empresa asociada
        if (!$user->empresasActivas()->exists()) {
            abort(403, 'No tienes acceso a ninguna empresa. Contacta al administrador.');
        }

        return $next($request);
    }
}
