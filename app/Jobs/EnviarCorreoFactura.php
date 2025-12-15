<?php

namespace App\Jobs;

use App\Models\Factura;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Mailjet\Resources;
use Mailjet\Client;
use Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
use ZipArchive;

class EnviarCorreoFactura implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $facturaId;
    protected $asuntoPersonalizado;

    /**
     * Create a new job instance.
     */
    public function __construct($facturaId, $asuntoPersonalizado = null)
    {
        $this->facturaId = $facturaId;
        $this->asuntoPersonalizado = $asuntoPersonalizado;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Iniciando job de envío de correo para factura", [
                'factura_id' => $this->facturaId,
                'intento' => $this->attempts()
            ]);

            // Cargar factura con relaciones
            $factura = Factura::with([
                'cliente.tipoDocumento',
                'cliente.municipio',
                'cliente.departamento',
                'empresa.tipoDocumento',
                'empresa.tipoResponsabilidad',
                'empresa.municipio',
                'empresa.departamento',
                'user',
                'tipoFactura',
                'tipoMovimiento',
                'medioPago',
                'tipoPago',
                'facturaHasProducts.producto.impuestos.impuesto',
                'facturaHasProducts.producto.unidadMedida',
                'facturaHasRetenciones.tipoRetencion',
                'vendedor'
            ])->find($this->facturaId);

            if (!$factura) {
                Log::warning("Factura no encontrada para envío de correo", [
                    'factura_id' => $this->facturaId
                ]);
                return;
            }

            // Validar que la empresa tenga envíos automáticos habilitados (o permitir envío manual)
            // Nota: El campo envios_automaticos no existe en el modelo Empresa,
            // así que permitimos el envío si la empresa existe
            if (!$factura->empresa) {
                Log::info("Envío de correo cancelado: empresa no encontrada", [
                    'factura_id' => $this->facturaId,
                    'empresa_id' => $factura->empresa_id
                ]);
                return;
            }

            // Validar que el cliente tenga email
            if (!$factura->cliente || !$factura->cliente->email) {
                Log::info("Envío de correo cancelado: cliente sin email", [
                    'factura_id' => $this->facturaId,
                    'cliente_id' => $factura->cliente_id
                ]);
                return;
            }

            // Validar que la factura tenga XML (enviado a DIAN)
            if (!$factura->xml_url || !$factura->cufe) {
                Log::info("Envío de correo cancelado: factura sin XML/CUFE", [
                    'factura_id' => $this->facturaId,
                    'xml_url' => $factura->xml_url,
                    'cufe' => $factura->cufe
                ]);
                return;
            }

            $emailCliente = $factura->cliente->email;

            Log::info("Generando archivos para envío de correo", [
                'factura_id' => $this->facturaId,
                'email' => $emailCliente
            ]);

            // Generar archivos temporales
            $zipPath = $this->generarArchivosFactura($factura);

            if (!$zipPath || !file_exists($zipPath)) {
                throw new Exception("No se pudo generar el archivo ZIP para la factura");
            }

            // Enviar correo con Mailjet SDK
            $asunto = $this->asuntoPersonalizado ?: "Factura Electrónica No. {$factura->numero_factura}";

            $this->enviarConMailjet($factura, $zipPath, $emailCliente, $asunto);

            Log::info("Correo enviado exitosamente", [
                'factura_id' => $this->facturaId,
                'numero_factura' => $factura->numero_factura,
                'email' => $emailCliente,
                'asunto' => $asunto
            ]);

        } catch (Exception $e) {
            Log::error("Error en envío de correo para factura", [
                'factura_id' => $this->facturaId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'intento' => $this->attempts()
            ]);
            throw $e;
        } finally {
            // Limpiar archivo temporal si existe
            if (isset($zipPath) && file_exists($zipPath)) {
                unlink($zipPath);
                Log::info("Archivo temporal eliminado", ['zip_path' => $zipPath]);
            }
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Falló definitivamente el envío de correo para factura", [
            'factura_id' => $this->facturaId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Generar archivos PDF y XML en ZIP
     */
    private function generarArchivosFactura(Factura $factura): string
    {
        // Generar PDF de la factura
        $pdfContent = $this->generarPdfFactura($factura);

        // Descargar XML de la URL
        $xmlContent = $this->descargarXmlFactura($factura);

        // Crear ZIP temporal
        $tmpDir = sys_get_temp_dir();
        $zipPath = tempnam($tmpDir, 'factura_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("No se pudo crear el archivo ZIP");
        }

        // Agregar PDF al ZIP
        $pdfFilename = "factura_{$factura->numero_factura}.pdf";
        $zip->addFromString($pdfFilename, $pdfContent);

        // Agregar XML al ZIP
        $xmlFilename = "factura_{$factura->numero_factura}.xml";
        $zip->addFromString($xmlFilename, $xmlContent);

        // Agregar attached document al ZIP
        if ($factura->xml_url) {
            $attachedDocContent = $this->generarAttachedDocument($factura, $xmlContent);
            $attachedFilename = "attached_document_{$factura->numero_factura}.xml";
            $zip->addFromString($attachedFilename, $attachedDocContent);
        }

        $zip->close();

        Log::info("Archivo ZIP generado", [
            'factura_id' => $factura->id,
            'zip_path' => $zipPath,
            'zip_size' => filesize($zipPath)
        ]);

        return $zipPath;
    }

    /**
     * Generar PDF de la factura
     */
    private function generarPdfFactura(Factura $factura): string
    {
        // Generar QR Code
        $qrCodeImage = $factura->qr_code_image;

        // Configurar DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        // Generar HTML del PDF
        $html = view('facturas.pdf.factura', compact('factura', 'qrCodeImage'))->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Descargar XML desde la URL
     */
    private function descargarXmlFactura(Factura $factura): string
    {
        $response = Http::timeout(30)->get($factura->xml_url);

        if (!$response->successful()) {
            throw new Exception("No se pudo descargar el XML desde: {$factura->xml_url}");
        }

        return $response->body();
    }

    /**
     * Generar attached document XML
     */
    private function generarAttachedDocument(Factura $factura, string $xmlContent): string
    {
        // Obtener la respuesta de la DIAN (si existe)
        $respuestaDian = $factura->respuesta_dian ?? '';

        // Datos para la vista del attached document
        $data = [
            'factura' => $factura,
            'empresa' => $factura->empresa,
            'cliente' => $factura->cliente,
            'documentXMl' => $xmlContent, // Pasar el XML directamente, no en base64
            'respuestaDian' => $respuestaDian
        ];

        return view('attached_documents.invoice', $data)->render();
    }

    /**
     * Enviar correo usando Mailjet SDK
     */
    private function enviarConMailjet(Factura $factura, string $zipPath, string $emailCliente, string $asunto): void
    {
        // Obtener credenciales de Mailjet desde el .env
        $apiKey = config('services.mailjet.key');
        $apiSecret = config('services.mailjet.secret');
        $emailFrom = config('services.mailjet.from_email');

        if (!$apiKey || !$apiSecret || !$emailFrom) {
            throw new Exception("Configuración de Mailjet incompleta. Verifica MAILJET_API_KEY, MAILJET_SECRET_KEY y MAILJET_EMAIL en el archivo .env");
        }

        // Inicializar cliente de Mailjet
        $mj = new Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

        // Leer el archivo ZIP y codificarlo en base64
        $zipContent = file_get_contents($zipPath);
        $zipBase64 = base64_encode($zipContent);
        $zipFilename = "factura_{$factura->numero_factura}.zip";

        // Generar HTML del correo
        $htmlContent = view('emails.factura-enviada', [
            'factura' => $factura,
            'empresa' => $factura->empresa,
            'cliente' => $factura->cliente,
        ])->render();

        // Preparar el cuerpo del mensaje
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $emailFrom,
                        'Name' => $factura->empresa->razon_social ?? 'Facturación Electrónica'
                    ],
                    'To' => [
                        [
                            'Email' => $emailCliente,
                            'Name' => $factura->cliente->nombre_completo ?? $emailCliente
                        ]
                    ],
                    'Subject' => $asunto,
                    'TextPart' => "Factura electrónica No. {$factura->numero_factura}. Por favor, revisa el archivo adjunto.",
                    'HTMLPart' => $htmlContent,
                    'Attachments' => [
                        [
                            'ContentType' => 'application/zip',
                            'Filename' => $zipFilename,
                            'Base64Content' => $zipBase64
                        ]
                    ]
                ]
            ]
        ];

        // Enviar el correo
        $response = $mj->post(Resources::$Email, ['body' => $body]);

        // Verificar la respuesta
        if (!$response->success()) {
            $errorMessage = 'Error al enviar correo con Mailjet';

            if (isset($response->getData()['Messages'][0]['Errors'])) {
                $errors = $response->getData()['Messages'][0]['Errors'];
                $errorMessage .= ': ' . json_encode($errors);
            }

            throw new Exception($errorMessage);
        }

        Log::info("Correo enviado exitosamente con Mailjet", [
            'factura_id' => $factura->id,
            'email' => $emailCliente,
            'message_id' => $response->getData()['Messages'][0]['To'][0]['MessageID'] ?? null
        ]);
    }

    /**
     * Verificar si hay jobs pendientes para una factura
     */
    public static function hasPendingJobs($facturaId): bool
    {
        return DB::table('jobs')
            ->where('payload', 'like', '%"facturaId":' . $facturaId . '%')
            ->exists();
    }

    /**
     * Obtener estadísticas de jobs de email
     */
    public static function getEmailJobStats(): array
    {
        $pendientes = DB::table('jobs')
            ->where('payload', 'like', '%EnviarCorreoFactura%')
            ->count();

        $fallidosTotal = DB::table('failed_jobs')
            ->where('payload', 'like', '%EnviarCorreoFactura%')
            ->count();

        $fallidosHoy = DB::table('failed_jobs')
            ->where('payload', 'like', '%EnviarCorreoFactura%')
            ->whereDate('failed_at', today())
            ->count();

        return [
            'pendientes' => $pendientes,
            'fallidos_total' => $fallidosTotal,
            'fallidos_hoy' => $fallidosHoy,
        ];
    }
}
