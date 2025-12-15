<?php

namespace App\Imports;

use App\Models\Cliente;
use App\Models\TipoDocumento;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Comuna;
use App\Models\Barrio;
use App\Models\Empresa;
use App\Models\RedSocial;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;
use Carbon\Carbon;

class ClientesImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    WithChunkReading,
    SkipsOnError,
    SkipsOnFailure
{
    protected $empresaId;
    protected $modoImportacion;
    protected $errores = [];
    protected $procesados = 0;
    protected $creados = 0;
    protected $actualizados = 0;
    protected $omitidos = 0;
    protected $duplicados = []; // Array para almacenar cédulas/NITs duplicados

    public function __construct($empresaId = null, $modoImportacion = 'crear')
    {
        $this->empresaId = $empresaId;
        $this->modoImportacion = $modoImportacion;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            $this->procesados++;

            // Limpiar y validar datos - manteniendo compatibilidad con ambos formatos
            // Convertir todos los valores a string para evitar problemas de tipo
            $tipoDocumento = trim((string)($row['tipo_de_documento'] ?? ''));
            $cedulaNit = trim((string)($row['cedula_nit'] ?? ''));
            $dv = trim((string)($row['dv'] ?? ''));
            $nombres = trim((string)($row['nombres'] ?? ''));
            $apellidos = trim((string)($row['apellidos'] ?? ''));
            $razonSocial = trim((string)($row['razon_social'] ?? ''));
            $email = trim((string)($row['email'] ?? ''));
            $fechaNacimiento = trim((string)($row['fecha_de_nacimiento'] ?? ''));
            $representanteLegal = trim((string)($row['representante_legal'] ?? ''));
            $cedulaRepresentante = trim((string)($row['cedula_representante'] ?? ''));
            $emailRepresentante = trim((string)($row['email_representante'] ?? ''));
            $direccionRepresentante = trim((string)($row['direccion_representante'] ?? ''));
            $direccion = trim((string)($row['direccion'] ?? ''));
            $departamento = trim((string)($row['departamento'] ?? ''));
            $municipio = trim((string)($row['municipio'] ?? ''));
            $comuna = trim((string)($row['comuna'] ?? ''));
            $barrio = trim((string)($row['barrio'] ?? ''));
            $telefonoFijo = trim((string)($row['telefono_fijo'] ?? ''));
            $celular = trim((string)($row['celular'] ?? ''));
            $redesSociales = trim((string)($row['redes_sociales'] ?? ''));
            $empresaRazonSocial = trim((string)($row['empresa'] ?? ''));

            // Validaciones básicas
            if (empty($cedulaNit)) {
                $this->errores[] = "Fila {$this->procesados}: La cédula/NIT es obligatoria";
                $this->omitidos++;
                return null;
            }

            // // Validar campos de ubicación obligatorios
            // if (empty($comuna)) {
            //     $this->errores[] = "Fila {$this->procesados}: La comuna es obligatoria";
            //     $this->omitidos++;
            //     return null;
            // }

            // if (empty($barrio)) {
            //     $this->errores[] = "Fila {$this->procesados}: El barrio es obligatorio";
            //     $this->omitidos++;
            //     return null;
            // }

            // if (empty($celular)) {
            //     $this->errores[] = "Fila {$this->procesados}: El celular es obligatorio";
            //     $this->omitidos++;
            //     return null;
            // }

            // Buscar o usar tipo de documento por defecto
            $tipoDocumentoId = 1; // Por defecto: Cédula de Ciudadanía
            if (!empty($tipoDocumento)) {
                $tipoDocumentoObj = TipoDocumento::where('name', 'LIKE', "%{$tipoDocumento}%")->first();
                if ($tipoDocumentoObj) {
                    $tipoDocumentoId = $tipoDocumentoObj->id;
                } else {
                    $this->errores[] = "Fila {$this->procesados}: No se encontró el tipo de documento '{$tipoDocumento}'. Usando tipo por defecto.";
                }
            }

            // Validar campos según tipo de documento
            $esPersonaJuridica = $tipoDocumentoId == 6;

            // if ($esPersonaJuridica) {
            //     if (empty($razonSocial)) {
            //         $this->errores[] = "Fila {$this->procesados}: La razón social es obligatoria para personas jurídicas";
            //         $this->omitidos++;
            //         return null;
            //     }
            //     if (empty($representanteLegal)) {
            //         $this->errores[] = "Fila {$this->procesados}: El representante legal es obligatorio para personas jurídicas";
            //         $this->omitidos++;
            //         return null;
            //     }
            //     if (empty($cedulaRepresentante)) {
            //         $this->errores[] = "Fila {$this->procesados}: La cédula del representante es obligatoria para personas jurídicas";
            //         $this->omitidos++;
            //         return null;
            //     }
            //     if (empty($direccionRepresentante)) {
            //         $this->errores[] = "Fila {$this->procesados}: La dirección del representante es obligatoria para personas jurídicas";
            //         $this->omitidos++;
            //         return null;
            //     }
            // } else {
            //     if (empty($nombres)) {
            //         $this->errores[] = "Fila {$this->procesados}: Los nombres son obligatorios para personas naturales";
            //         $this->omitidos++;
            //         return null;
            //     }
            //     if (empty($apellidos)) {
            //         $this->errores[] = "Fila {$this->procesados}: Los apellidos son obligatorios para personas naturales";
            //         $this->omitidos++;
            //         return null;
            //     }
            // }

            // Buscar ubicación geográfica (ahora opcional)
            $ubicaciones = $this->buscarUbicacion($departamento, $municipio, $comuna, $barrio);

            // Determinar empresa_id
            $empresaId = $this->empresaId;
            if (!empty($empresaRazonSocial)) {
                $empresa = Empresa::where('razon_social', $empresaRazonSocial)->first();
                if ($empresa) {
                    $empresaId = $empresa->id;
                } else {
                    $this->errores[] = "Fila {$this->procesados}: No se encontró la empresa '{$empresaRazonSocial}'";
                    $this->omitidos++;
                    return null;
                }
            }

            if (!$empresaId) {
                $this->errores[] = "Fila {$this->procesados}: No se especificó una empresa válida";
                $this->omitidos++;
                return null;
            }

            // Procesar fecha de nacimiento
            $fechaNacimientoProcesada = null;
            if (!empty($fechaNacimiento) && !$esPersonaJuridica) {
                try {
                    $fechaNacimientoProcesada = Carbon::createFromFormat('d/m/Y', $fechaNacimiento);
                } catch (\Exception $e) {
                    try {
                        $fechaNacimientoProcesada = Carbon::parse($fechaNacimiento);
                    } catch (\Exception $e2) {
                        $this->errores[] = "Fila {$this->procesados}: Formato de fecha de nacimiento inválido";
                    }
                }
            }

            // Verificar si el cliente ya existe
            $clienteExistente = Cliente::where('cedula_nit', $cedulaNit)
                ->where('empresa_id', $empresaId)
                ->first();

            $datosCliente = [
                'tipo_documento_id' => $tipoDocumentoId,
                'cedula_nit' => $cedulaNit,
                'dv' => $dv !== '' ? (string)$dv : null,
                'nombres' => $esPersonaJuridica ? null : $nombres,
                'apellidos' => $esPersonaJuridica ? null : $apellidos,
                'razon_social' => $esPersonaJuridica ? $razonSocial : null,
                'email' => $email ?: null,
                'fecha_nacimiento' => $fechaNacimientoProcesada,
                'representante_legal' => $esPersonaJuridica ? $representanteLegal : null,
                'cedula_representante' => $esPersonaJuridica ? $cedulaRepresentante : null,
                'email_representante' => $esPersonaJuridica ? ($emailRepresentante ?: null) : null,
                'direccion_representante' => $esPersonaJuridica ? $direccionRepresentante : null,
                'direccion' => $direccion,
                'departamento_id' => $ubicaciones ? $ubicaciones['departamento_id'] : null,
                'municipio_id' => $ubicaciones ? $ubicaciones['municipio_id'] : null,
                'comuna_id' => $ubicaciones ? $ubicaciones['comuna_id'] : null,
                'barrio_id' => $ubicaciones ? $ubicaciones['barrio_id'] : null,
                'telefono_fijo' => $telefonoFijo ?: null,
                'celular' => $celular,
                'empresa_id' => $empresaId
            ];

            if ($clienteExistente) {
                if ($this->modoImportacion === 'crear') {
                    $this->duplicados[] = $cedulaNit; // Agregar cédula a duplicados
                    $this->errores[] = "Fila {$this->procesados}: El cliente con cédula/NIT '{$cedulaNit}' ya existe y el modo es solo crear";
                    $this->omitidos++;
                    return null;
                }

                if ($this->modoImportacion === 'actualizar' || $this->modoImportacion === 'crear_actualizar') {
                    $clienteExistente->update($datosCliente);

                    // Procesar redes sociales si existen
                    if (!empty($redesSociales)) {
                        $this->procesarRedesSociales($clienteExistente, $redesSociales);
                    }

                    $this->actualizados++;
                    return $clienteExistente;
                }
            } else {
                if ($this->modoImportacion === 'actualizar') {
                    $this->errores[] = "Fila {$this->procesados}: El cliente con cédula/NIT '{$cedulaNit}' no existe y el modo es solo actualizar";
                    $this->omitidos++;
                    return null;
                }

                $cliente = new Cliente($datosCliente);
                $this->creados++;

                // Las redes sociales se procesan después de guardar el cliente
                // por lo que las manejamos en un post-procesamiento

                return $cliente;
            }

        } catch (Throwable $e) {
            $this->errores[] = "Fila {$this->procesados}: Error inesperado - " . $e->getMessage();
            $this->omitidos++;
            Log::error('Error en importación de clientes', [
                'fila' => $this->procesados,
                'datos' => $row,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Buscar código considerando posibles ceros a la izquierda
     * Excel puede eliminar ceros iniciales, pero la DB los conserva.
     */
    private function findByCodeWithLeadingZeros($model, $code, $whereClause = [])
    {
        $code = (string) $code;

        // Buscar código exacto primero
        $query = $model::where('code', $code);
        foreach ($whereClause as $field => $value) {
            $query->where($field, $value);
        }
        $result = $query->first();

        if ($result) {
            return $result;
        }

        // Si no se encuentra y el código es numérico, probar con ceros a la izquierda
        if (is_numeric($code)) {
            // Probar con diferentes cantidades de ceros a la izquierda
            // Para departamentos: 2 dígitos (05, 25, etc.)
            // Para municipios: 5 dígitos (05001, 25001, etc.)
            $variations = [
                str_pad($code, 2, '0', STR_PAD_LEFT), // 5 -> 05 (departamentos)
                str_pad($code, 3, '0', STR_PAD_LEFT), // 5 -> 005
                str_pad($code, 4, '0', STR_PAD_LEFT), // 5 -> 0005
                str_pad($code, 5, '0', STR_PAD_LEFT), // 5001 -> 05001 (municipios)
            ];

            foreach ($variations as $variation) {
                if ($variation !== $code) { // Evitar buscar el mismo código otra vez
                    $query = $model::where('code', $variation);
                    foreach ($whereClause as $field => $value) {
                        $query->where($field, $value);
                    }
                    $result = $query->first();

                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Buscar departamento por nombre o código.
     */
    private function findDepartamento($nombre)
    {
        // Convertir a string para asegurar consistencia en las búsquedas
        $nombre = (string) $nombre;

        // Buscar coincidencia exacta por nombre ignorando mayúsculas
        $departamento = Departamento::whereRaw('LOWER(name) = LOWER(?)', [$nombre])->first();

        if ($departamento) {
            return $departamento;
        }

        // Si no se encuentra, buscar con nombres normalizados (sin acentos)
        $nombreNormalizado = strtolower($this->removeAccents($nombre));

        $departamentos = Departamento::all();
        foreach ($departamentos as $dep) {
            $depNormalizado = strtolower($this->removeAccents($dep->name));
            if ($depNormalizado === $nombreNormalizado) {
                return $dep;
            }
        }

        // Si no se encuentra por nombre, buscar por código con manejo de ceros a la izquierda
        $departamento = $this->findByCodeWithLeadingZeros(Departamento::class, $nombre);
        if ($departamento) {
            return $departamento;
        }

        return null;
    }

    /**
     * Buscar municipio por nombre o código dentro de un departamento específico.
     */
    private function findMunicipioEnDepartamento($nombre, $departamentoId)
    {
        // Convertir a string para asegurar consistencia en las búsquedas
        $nombre = (string) $nombre;

        // Buscar coincidencia exacta por nombre ignorando mayúsculas
        $municipio = Municipio::where('departamento_id', $departamentoId)
            ->whereRaw('LOWER(name) = LOWER(?)', [$nombre])
            ->first();

        if ($municipio) {
            return $municipio;
        }

        // Si no se encuentra, buscar con nombres normalizados (sin acentos)
        $nombreNormalizado = strtolower($this->removeAccents($nombre));

        $municipios = Municipio::where('departamento_id', $departamentoId)->get();
        foreach ($municipios as $mun) {
            $munNormalizado = strtolower($this->removeAccents($mun->name));
            if ($munNormalizado === $nombreNormalizado) {
                return $mun;
            }
        }

        // Si no se encuentra por nombre, buscar por código con manejo de ceros a la izquierda
        $municipio = $this->findByCodeWithLeadingZeros(Municipio::class, $nombre, ['departamento_id' => $departamentoId]);
        if ($municipio) {
            return $municipio;
        }

        return null;
    }

    /**
     * Eliminar acentos de una cadena.
     */
    private function removeAccents($string)
    {
        // Reemplazo directo de caracteres con acentos
        $string = str_replace(['Á', 'À', 'Ä', 'Â', 'Ã', 'Å'], 'A', $string);
        $string = str_replace(['É', 'È', 'Ë', 'Ê'], 'E', $string);
        $string = str_replace(['Í', 'Ì', 'Ï', 'Î'], 'I', $string);
        $string = str_replace(['Ó', 'Ò', 'Ö', 'Ô', 'Õ'], 'O', $string);
        $string = str_replace(['Ú', 'Ù', 'Ü', 'Û'], 'U', $string);
        $string = str_replace('Ñ', 'N', $string);

        $string = str_replace(['á', 'à', 'ä', 'â', 'ã', 'å'], 'a', $string);
        $string = str_replace(['é', 'è', 'ë', 'ê'], 'e', $string);
        $string = str_replace(['í', 'ì', 'ï', 'î'], 'i', $string);
        $string = str_replace(['ó', 'ò', 'ö', 'ô', 'õ'], 'o', $string);
        $string = str_replace(['ú', 'ù', 'ü', 'û'], 'u', $string);
        $string = str_replace('ñ', 'n', $string);

        return $string;
    }

    /**
     * Buscar ubicación geográfica (ahora opcional - puede retornar null)
     */
    private function buscarUbicacion($departamentoNombre, $municipioNombre, $comunaNombre, $barrioNombre)
    {
        // Si no hay información de ubicación, retornar null (permitir ubicaciones vacías)
        if (empty($departamentoNombre) && empty($municipioNombre) && empty($comunaNombre) && empty($barrioNombre)) {
            return null;
        }

        // Si hay información parcial, intentar buscar lo que se pueda
        $resultado = [
            'departamento_id' => null,
            'municipio_id' => null,
            'comuna_id' => null,
            'barrio_id' => null
        ];

        // Buscar departamento si se proporciona
        if (!empty($departamentoNombre)) {
            $departamento = $this->findDepartamento($departamentoNombre);
            if ($departamento) {
                $resultado['departamento_id'] = $departamento->id;

                // Buscar municipio si se proporciona y encontramos el departamento
                if (!empty($municipioNombre)) {
                    $municipio = $this->findMunicipioEnDepartamento($municipioNombre, $departamento->id);
                    if ($municipio) {
                        $resultado['municipio_id'] = $municipio->id;

                        // Buscar comuna si se proporciona y encontramos el municipio
                        if (!empty($comunaNombre)) {
                            $comuna = Comuna::where('nombre', 'LIKE', "%{$comunaNombre}%")
                                ->where('municipio_id', $municipio->id)
                                ->first();
                            if ($comuna) {
                                $resultado['comuna_id'] = $comuna->id;

                                // Buscar barrio si se proporciona y encontramos la comuna
                                if (!empty($barrioNombre)) {
                                    $barrio = Barrio::where('nombre', 'LIKE', "%{$barrioNombre}%")
                                        ->where('comuna_id', $comuna->id)
                                        ->first();
                                    if ($barrio) {
                                        $resultado['barrio_id'] = $barrio->id;
                                    } else {
                                        $this->errores[] = "Fila {$this->procesados}: Advertencia - No se encontró el barrio: {$barrioNombre} en la comuna {$comunaNombre}";
                                    }
                                }
                            } else {
                                $this->errores[] = "Fila {$this->procesados}: Advertencia - No se encontró la comuna: {$comunaNombre} en el municipio {$municipioNombre}";
                            }
                        }
                    } else {
                        $this->errores[] = "Fila {$this->procesados}: Advertencia - No se encontró el municipio: {$municipioNombre} en el departamento {$departamentoNombre}";
                    }
                }
            } else {
                $this->errores[] = "Fila {$this->procesados}: Advertencia - No se encontró el departamento: {$departamentoNombre}";
            }
        }

        // Retornar null si no se encontró al menos el departamento cuando se especificó información de ubicación
        if (!empty($departamentoNombre) && !$resultado['departamento_id']) {
            return null;
        }

        return $resultado;
    }

    /**
     * Procesar redes sociales
     */
    private function procesarRedesSociales($cliente, $redesSocialesStr)
    {
        // Formato esperado: "Facebook: usuario1 | Instagram: usuario2"
        $redesArray = explode(' | ', $redesSocialesStr);

        // Limpiar redes sociales existentes
        $cliente->redesSociales()->detach();

        foreach ($redesArray as $redStr) {
            $redPartes = explode(': ', $redStr, 2);
            if (count($redPartes) === 2) {
                $redNombre = trim($redPartes[0]);
                $redUsuario = trim($redPartes[1]);

                $redSocial = RedSocial::where('nombre', 'LIKE', "%{$redNombre}%")->first();
                if ($redSocial && !empty($redUsuario)) {
                    $cliente->redesSociales()->attach($redSocial->id, [
                        'valor' => $redUsuario
                    ]);
                }
            }
        }
    }

    /**
     * Validación de las filas
     */
    public function rules(): array
    {
        return [
            'cedula_nit' => ['nullable', 'max:20'],
            'tipo_de_documento' => ['nullable', 'string', 'max:255'],
            'dv' => ['nullable', 'max:1'],
            'nombres' => ['nullable', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'fecha_de_nacimiento' => ['nullable', 'string', 'max:255'],
            'representante_legal' => ['nullable', 'string', 'max:255'],
            'cedula_representante' => ['nullable', 'max:20'],
            'email_representante' => ['nullable', 'string', 'max:255'],
            'direccion_representante' => ['nullable', 'string', 'max:500'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:255'],
            'barrio' => ['nullable', 'string', 'max:255'],
            'telefono_fijo' => ['nullable', 'max:20'],
            'celular' => ['nullable', 'max:20'],
            'redes_sociales' => ['nullable', 'string'],
            'empresa' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Mensajes personalizados de validación
     */
    public function customValidationMessages()
    {
        return [
            'cedula_nit.max' => 'La cédula/NIT no puede exceder 20 caracteres',
            'dv.max' => 'El DV no puede exceder 1 caracter',
            'direccion.max' => 'La dirección no puede exceder 500 caracteres',
            'departamento.max' => 'El departamento no puede exceder 255 caracteres',
            'municipio.max' => 'El municipio no puede exceder 255 caracteres',
            'comuna.max' => 'La comuna no puede exceder 255 caracteres',
            'barrio.max' => 'El barrio no puede exceder 255 caracteres',
            'celular.max' => 'El celular no puede exceder 20 caracteres',
            'telefono_fijo.max' => 'El teléfono fijo no puede exceder 20 caracteres',
            'cedula_representante.max' => 'La cédula del representante no puede exceder 20 caracteres',

        ];
    }

    /**
     * Tamaño del chunk para lectura
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Manejar errores
     */
    public function onError(Throwable $e)
    {
        $this->errores[] = "Error general: " . $e->getMessage();
        Log::error('Error en importación de clientes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Manejar fallas de validación
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errores[] = "Fila {$failure->row()}: " . implode(', ', $failure->errors());
            $this->omitidos++;
        }
    }

    /**
     * Obtener resumen de la importación
     */
    public function getResumen(): array
    {
        return [
            'procesados' => $this->procesados,
            'creados' => $this->creados,
            'actualizados' => $this->actualizados,
            'omitidos' => $this->omitidos,
            'errores' => $this->errores,
            'duplicados' => $this->duplicados
        ];
    }

    /**
     * Obtener estadísticas completas para el historial
     */
    public function getImportStats(): array
    {
        $totalExitosos = $this->creados + $this->actualizados;
        $totalFallidos = $this->omitidos;
        $totalDuplicados = count($this->duplicados);

        // Determinar estado de la importación
        $status = 'completed';
        if ($totalFallidos > 0 || !empty($this->errores)) {
            $status = $totalExitosos > 0 ? 'completed_with_errors' : 'failed';
        }

        return [
            'total_rows' => $this->procesados,
            'successful_imports' => $totalExitosos,
            'skipped_duplicates' => $totalDuplicados,
            'failed_imports' => $totalFallidos,
            'created_clients' => $this->creados,
            'updated_clients' => $this->actualizados,
            'duplicate_clients' => $this->duplicados,
            'errors' => $this->errores,
            'status' => $status
        ];
    }
}
