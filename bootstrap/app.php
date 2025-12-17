<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'empresa.access' => \App\Http\Middleware\EmpresaAccessMiddleware::class,

        ]);

        // Excluir webhooks de Twilio y API de la protección CSRF
        $middleware->validateCsrfTokens(except: [
            '*', // Disable CSRF globally for this stateless API
        ]);

        // Trust all proxies (Required for Traefik/Load Balancers to handle HTTPS correctly)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApiRequest = function (\Illuminate\Http\Request $request): bool {
            return $request->expectsJson() || $request->is('api/*');
        };

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'Forbidden.'
            ], 403);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'Error de validación',
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Recurso no encontrado'
            ], 404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage() ?: 'Ruta no encontrada'
            ], 404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Método no permitido'
            ], 405);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            $status = $e->getStatusCode();
            $message = $e->getMessage();

            // Evitar mensajes vacíos (ej: abort(403) sin mensaje)
            if ($message === '') {
                $message = match ($status) {
                    400 => 'Bad request',
                    401 => 'Unauthenticated.',
                    403 => 'Forbidden.',
                    404 => 'Not found',
                    405 => 'Method not allowed',
                    409 => 'Conflict',
                    422 => 'Unprocessable entity',
                    default => 'Error',
                };
            }

            return response()->json([
                'message' => $message,
            ], $status);
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            // SQLSTATE 23000 = Integrity constraint violation (unique/fk, etc.)
            $sqlState = $e->errorInfo[0] ?? null;
            $status = $sqlState === '23000' ? 409 : 400;

            return response()->json([
                'message' => $status === 409
                    ? 'Conflicto: el recurso ya existe o tiene dependencias'
                    : 'Error en la consulta',
            ], $status);
        });

        // Fallback: nunca devolver HTML para API y nunca “convertir” todo en 500 sin contexto.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) use ($isApiRequest) {
            if (!$isApiRequest($request)) {
                return null;
            }

            $errorId = (string) \Illuminate\Support\Str::uuid();
            logger()->error('Unhandled exception', [
                'error_id' => $errorId,
                'exception' => $e,
                'path' => $request->path(),
                'method' => $request->method(),
                'user_id' => optional($request->user())->id,
            ]);

            $payload = [
                'message' => 'Error interno del servidor',
                'error_id' => $errorId,
            ];

            if (config('app.debug')) {
                $payload['exception'] = get_class($e);
                $payload['detail'] = $e->getMessage();
            }

            return response()->json($payload, 500);
        });
    })->create();
