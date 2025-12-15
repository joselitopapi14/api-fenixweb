<?php

namespace App\Services;

use App\Models\Ciudadano;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Comuna;
use App\Models\Barrio;
use App\Models\CiudadanoVotacion;
use App\Models\Lider;
use App\Models\TipoVotacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CiudadanoService
{
    protected $apiService;

    public function __construct(CiudadanoApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Procesar la consulta ciudadana completa
     */
    public function procesarConsultaCiudadana(array $datos, int $liderId): array
    {
        try {
            DB::beginTransaction();

            // 1. Primero buscar en la base de datos local
            $ciudadano = Ciudadano::where('cedula', $datos['cedula'])->first();

            if ($ciudadano) {
                // El ciudadano ya existe, actualizar teléfono
                $ciudadano->update(['telefono' => $datos['telefono']]);
                $ciudadano = $ciudadano->load('departamento');
            } else {
                // 2. Si no existe, consultar la API externa
                $datosApi = $this->apiService->consultarCiudadano($datos['cedula']);

                if (!$datosApi) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Ciudadano no encontrado en el sistema.',
                        'type' => 'warning'
                    ];
                }

                // 3. Crear el ciudadano con los datos de la API y teléfono del request
                $ciudadano = $this->crearCiudadanoDesdeApi($datosApi, $datos['telefono']);
            }

            // 2.1. Validar elegibilidad por departamento para tipos de votación específicos
            $validacionDepartamento = $this->validarElegibilidadPorDepartamento($ciudadano, $datos['tipos_votaciones'], $liderId);

            if (!$validacionDepartamento['success']) {
                if ($validacionDepartamento['type'] === 'geographic_restriction') {
                    DB::commit();
                }
                return $validacionDepartamento;
            }

            // 3. Validar votación ciudadana
            $resultadoValidacion = $this->validarVotacionCiudadana($ciudadano->id, $liderId, $datos['tipos_votaciones']);

            if (!$resultadoValidacion['success']) {
                if ($resultadoValidacion['type'] === 'conflict' || $resultadoValidacion['type'] === 'double_entry') {
                    DB::commit();
                }
                return $resultadoValidacion;
            }

            // 4. Registrar las votaciones
            $this->registrarVotaciones($ciudadano->id, $liderId, $datos['tipos_votaciones']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Ciudadano registrado exitosamente.',
                'type' => 'success',
                'ciudadano' => $ciudadano->load(['departamento', 'municipio', 'comuna', 'barrio'])
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar consulta ciudadana', [
                'error' => $e->getMessage(),
                'cedula' => $datos['cedula']
            ]);

            return [
                'success' => false,
                'message' => 'Error interno del servidor. Inténtelo de nuevo.',
                'type' => 'error'
            ];
        }
    }

    /**
     * Crear ciudadano desde datos de la API
     */
    protected function crearCiudadanoDesdeApi(array $datosApi, string $telefonoRequest): Ciudadano
    {
        // Buscar ubicaciones geográficas
        $departamentoId = $this->buscarDepartamento($datosApi['departamento_nombre']);
        $municipioId = $this->buscarMunicipio($datosApi['municipio_nombre'], $departamentoId);
        $comunaId = $this->buscarComuna($datosApi['comuna'] ?? null, $municipioId);
        $barrioId = $this->buscarBarrio($datosApi['barrio'] ?? null, $comunaId);

        // Crear nuevo ciudadano usando el teléfono del request
        $nuevoCiudadano = Ciudadano::create([
            'nombre_completo' => $datosApi['nombre_completo'],
            'cedula' => $datosApi['cedula'],
            'direccion' => $datosApi['direccion'] ?? null,
            'fecha_nacimiento' => $datosApi['fecha_nacimiento'] ?? null,
            'lugar_votacion' => $datosApi['lugar_votacion'] ?? null,
            'mesa' => $datosApi['mesa'] ?? null,
            'telefono' => $telefonoRequest, // Usar teléfono del request en lugar de la API
            'departamento_id' => $departamentoId,
            'municipio_id' => $municipioId,
            'comuna_id' => $comunaId,
            'barrio_id' => $barrioId,
        ]);

        // Cargar la relación con departamento
        return $nuevoCiudadano->load('departamento');
    }

    /**
     * Validar elegibilidad por departamento para tipos de votación específicos
     */
    protected function validarElegibilidadPorDepartamento(Ciudadano $ciudadano, array $tiposVotaciones, int $liderId): array
    {
        // Solo validar si el departamento no es Córdoba (ID 14)
        if ($ciudadano->departamento_id == 14) {
            return ['success' => true];
        }

        // Obtener el nombre del departamento del ciudadano
        $nombreDepartamento = $ciudadano->departamento->name ?? 'Departamento no especificado';

        // Tipos de votación que requieren ser de Córdoba
        $tiposRestringidos = [
            3 => 'Cámara de Representantes de Córdoba',
            6 => 'Asamblea de Córdoba',
            4 => 'Gobernación de Córdoba'
        ];

        // Recopilar todos los tipos de votación restringidos seleccionados
        $tiposNoElegibles = [];
        foreach ($tiposVotaciones as $tipoVotacionId) {
            if (array_key_exists($tipoVotacionId, $tiposRestringidos)) {
                $tiposNoElegibles[] = $tiposRestringidos[$tipoVotacionId];
            }
        }

        // Si hay tipos no elegibles, registrar solo los tipos elegibles
        if (!empty($tiposNoElegibles)) {
            // Identificar tipos de votación elegibles (que no están restringidos)
            $tiposElegibles = array_filter($tiposVotaciones, function($tipoVotacionId) use ($tiposRestringidos) {
                return !array_key_exists($tipoVotacionId, $tiposRestringidos);
            });

            // Registrar al ciudadano para los tipos de votación que SÍ puede aplicar
            if (!empty($tiposElegibles)) {
                $this->registrarVotaciones($ciudadano->id, $liderId, $tiposElegibles);
                Log::info('Ciudadano registrado para tipos de votación sin restricción geográfica', [
                    'ciudadano_id' => $ciudadano->id,
                    'lider_id' => $liderId,
                    'tipos_elegibles' => $tiposElegibles,
                    'departamento' => $nombreDepartamento
                ]);
            }

            $listaTipos = count($tiposNoElegibles) === 1
                ? $tiposNoElegibles[0]
                : implode(', ', array_slice($tiposNoElegibles, 0, -1)) . ' y ' . end($tiposNoElegibles);

            // Construir mensaje informativo
            $mensaje = count($tiposNoElegibles) === 1
                ? "Este ciudadano no aplica para {$listaTipos}."
                : "Este ciudadano no aplica para los siguientes tipos de votación: {$listaTipos}.";

            $mensaje .= " El ciudadano pertenece al departamento de {$nombreDepartamento} y solo los ciudadanos de Córdoba pueden votar en estas categorías.";

            // Agregar información sobre tipos registrados exitosamente
            if (!empty($tiposElegibles)) {
                $tiposRegistrados = $this->obtenerNombresTiposVotacion($tiposElegibles);

                if (!empty($tiposRegistrados)) {
                    $listaRegistrados = count($tiposRegistrados) === 1
                        ? $tiposRegistrados[0]
                        : implode(', ', array_slice($tiposRegistrados, 0, -1)) . ' y ' . end($tiposRegistrados);

                    $mensaje .= " Sin embargo, el ciudadano ha sido registrado exitosamente para: {$listaRegistrados}.";
                }
            }

            return [
                'success' => false,
                'message' => $mensaje,
                'type' => 'geographic_restriction'
            ];
        }

        return ['success' => true];
    }

    /**
     * Validar votación ciudadana
     */
    protected function validarVotacionCiudadana(int $ciudadanoId, int $liderId, array $tiposVotaciones): array
    {
        // Verificar si ya tiene votaciones registradas para los tipos seleccionados
        $votacionesExistentes = CiudadanoVotacion::where('ciudadano_id', $ciudadanoId)
            ->whereIn('tipo_votacion_id', $tiposVotaciones)
            ->get();

        if ($votacionesExistentes->isEmpty()) {
            return ['success' => true];
        }

        // Verificar si fue registrado por el mismo líder
        $votacionPorMismoLider = $votacionesExistentes->where('lider_id', $liderId)->first();

        if ($votacionPorMismoLider) {
            return [
                'success' => false,
                'message' => 'Este ciudadano ya ha sido registrado por usted anteriormente.',
                'type' => 'double_entry'
            ];
        }

        // Verificar si fue registrado por otro líder
        $votacionPorOtroLider = $votacionesExistentes->where('lider_id', '!=', $liderId)->first();

        if ($votacionPorOtroLider) {
            return [
                'success' => false,
                'message' => 'CONFLICTO DE LIDERAZGOS: Este ciudadano ya fue registrado por otro líder.',
                'type' => 'conflict'
            ];
        }

        return ['success' => true];
    }

    /**
     * Registrar votaciones del ciudadano
     */
    protected function registrarVotaciones(int $ciudadanoId, int $liderId, array $tiposVotaciones): void
    {
        foreach ($tiposVotaciones as $tipoVotacionId) {
            CiudadanoVotacion::create([
                'ciudadano_id' => $ciudadanoId,
                'tipo_votacion_id' => $tipoVotacionId,
                'lider_id' => $liderId
            ]);
        }
    }

        /**
     * Buscar departamento eliminando tildes y comparando case-insensitive
     */
    protected function buscarDepartamento(string $nombreDepartamento): ?int
    {
        // Primero, buscar coincidencia exacta ignorando mayúsculas
        $departamento = Departamento::whereRaw("LOWER(name) = LOWER(?)", [$nombreDepartamento])->first();

        if ($departamento) {
            return $departamento->id;
        }

        // Si no se encuentra, buscar con nombres normalizados (sin acentos)
        $nombreNormalizado = $this->removeAccents(strtolower($nombreDepartamento));

        $departamentos = Departamento::all();
        foreach ($departamentos as $dep) {
            $depNormalizado = $this->removeAccents(strtolower($dep->name));
            if ($depNormalizado === $nombreNormalizado) {
                return $dep->id;
            }
        }

        Log::warning('Departamento no encontrado', ['nombre' => $nombreDepartamento]);
        return null;
    }

    /**
     * Buscar municipio por nombre y departamento
     */
    protected function buscarMunicipio(string $nombreMunicipio, ?int $departamentoId): ?int
    {
        if (!$departamentoId) {
            return null;
        }

        // Primero, buscar coincidencia exacta ignorando mayúsculas
        $municipio = Municipio::where('departamento_id', $departamentoId)
            ->whereRaw("LOWER(name) = LOWER(?)", [$nombreMunicipio])
            ->first();

        if ($municipio) {
            return $municipio->id;
        }

        // Si no se encuentra, buscar con nombres normalizados (sin acentos)
        $nombreNormalizado = $this->removeAccents(strtolower($nombreMunicipio));

        $municipios = Municipio::where('departamento_id', $departamentoId)->get();
        foreach ($municipios as $mun) {
            $munNormalizado = $this->removeAccents(strtolower($mun->name));
            if ($munNormalizado === $nombreNormalizado) {
                return $mun->id;
            }
        }

        Log::warning('Municipio no encontrado', [
            'nombre' => $nombreMunicipio,
            'departamento_id' => $departamentoId
        ]);
        return null;
    }

    /**
     * Buscar comuna por nombre y municipio, crear si no existe
     */
    protected function buscarComuna(?string $nombreComuna, ?int $municipioId): ?int
    {
        if (!$nombreComuna || !$municipioId) {
            return null;
        }

        // Primero, buscar coincidencia exacta ignorando mayúsculas
        $comuna = Comuna::where('municipio_id', $municipioId)
            ->whereRaw("LOWER(nombre) = LOWER(?)", [$nombreComuna])
            ->first();

        if ($comuna) {
            return $comuna->id;
        }

        // Si no se encuentra, buscar con nombres normalizados (sin acentos)
        $nombreNormalizado = $this->removeAccents(strtolower($nombreComuna));

        $comunas = Comuna::where('municipio_id', $municipioId)->get();
        foreach ($comunas as $com) {
            $comNormalizado = $this->removeAccents(strtolower($com->nombre));
            if ($comNormalizado === $nombreNormalizado) {
                return $com->id;
            }
        }

        // Si no se encuentra, crear nueva comuna
        try {
            $nuevaComuna = Comuna::create([
                'nombre' => $nombreComuna,
                'municipio_id' => $municipioId
            ]);

            Log::info('Nueva comuna creada', [
                'nombre' => $nombreComuna,
                'municipio_id' => $municipioId,
                'comuna_id' => $nuevaComuna->id
            ]);

            return $nuevaComuna->id;
        } catch (Exception $e) {
            Log::error('Error al crear nueva comuna', [
                'nombre' => $nombreComuna,
                'municipio_id' => $municipioId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Buscar barrio por nombre y comuna, crear si no existe
     */
    protected function buscarBarrio(?string $nombreBarrio, ?int $comunaId): ?int
    {
        if (!$nombreBarrio || !$comunaId) {
            return null;
        }

        // Primero, buscar coincidencia exacta ignorando mayúsculas
        $barrio = Barrio::where('comuna_id', $comunaId)
            ->whereRaw("LOWER(nombre) = LOWER(?)", [$nombreBarrio])
            ->first();

        if ($barrio) {
            return $barrio->id;
        }

        // Si no se encuentra, buscar con nombres normalizados (sin acentos)
        $nombreNormalizado = $this->removeAccents(strtolower($nombreBarrio));

        $barrios = Barrio::where('comuna_id', $comunaId)->get();
        foreach ($barrios as $bar) {
            $barNormalizado = $this->removeAccents(strtolower($bar->nombre));
            if ($barNormalizado === $nombreNormalizado) {
                return $bar->id;
            }
        }

        // Si no se encuentra, crear nuevo barrio
        try {
            $nuevoBarrio = Barrio::create([
                'nombre' => $nombreBarrio,
                'comuna_id' => $comunaId
            ]);

            Log::info('Nuevo barrio creado', [
                'nombre' => $nombreBarrio,
                'comuna_id' => $comunaId,
                'barrio_id' => $nuevoBarrio->id
            ]);

            return $nuevoBarrio->id;
        } catch (Exception $e) {
            Log::error('Error al crear nuevo barrio', [
                'nombre' => $nombreBarrio,
                'comuna_id' => $comunaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Eliminar acentos de una cadena (mismo método que en CiudadanosImport)
     */
    protected function removeAccents(string $string): string
    {
        // Reemplazo directo de caracteres con acentos
        $string = str_replace(['Á','À','Ä','Â','Ã','Å'], 'A', $string);
        $string = str_replace(['É','È','Ë','Ê'], 'E', $string);
        $string = str_replace(['Í','Ì','Ï','Î'], 'I', $string);
        $string = str_replace(['Ó','Ò','Ö','Ô','Õ'], 'O', $string);
        $string = str_replace(['Ú','Ù','Ü','Û'], 'U', $string);
        $string = str_replace('Ñ', 'N', $string);

        $string = str_replace(['á','à','ä','â','ã','å'], 'a', $string);
        $string = str_replace(['é','è','ë','ê'], 'e', $string);
        $string = str_replace(['í','ì','ï','î'], 'i', $string);
        $string = str_replace(['ó','ò','ö','ô','õ'], 'o', $string);
        $string = str_replace(['ú','ù','ü','û'], 'u', $string);
        $string = str_replace('ñ', 'n', $string);

        return $string;
    }

    /**
     * Obtener nombres de tipos de votación por sus IDs
     */
    protected function obtenerNombresTiposVotacion(array $tiposVotacionIds): array
    {
        return TipoVotacion::whereIn('id', $tiposVotacionIds)
                          ->pluck('nombre')
                          ->toArray();
    }

    /**
     * Limpiar texto removiendo tildes y convirtiendo a minúsculas (método legacy)
     * @deprecated Usar removeAccents() en su lugar
     */
    protected function limpiarTexto(string $texto): string
    {
        return $this->removeAccents(strtolower(trim($texto)));
    }

}
