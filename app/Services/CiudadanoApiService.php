<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CiudadanoApiService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('app.api_url', env('API_URL'));
    }

    /**
     * Consultar ciudadano por cédula en la API externa
     */
    public function consultarCiudadano(string $cedula): ?array
    {
        try {
            $url = rtrim($this->apiUrl, '/') . "/ciudadano/{$cedula}";

            Log::info('Consultando ciudadano en API externa', [
                'url' => $url,
                'cedula' => $cedula
            ]);

            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Respuesta exitosa de API externa', [
                    'cedula' => $cedula,
                    'data' => $data
                ]);

                return $data;
            }

            if ($response->status() === 404) {
                Log::info('Ciudadano no encontrado en API externa', [
                    'cedula' => $cedula,
                    'status' => $response->status()
                ]);
                return null;
            }

            Log::error('Error en respuesta de API externa', [
                'cedula' => $cedula,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (Exception $e) {
            Log::error('Error al consultar API externa', [
                'cedula' => $cedula,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
