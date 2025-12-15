<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CertificateHelper
{
    /**
     * Check if OpenSSL is available on the system
     *
     * @return bool
     */
    public static function isOpenSSLAvailable()
    {
        $output = shell_exec('openssl version 2>&1');
        return $output !== null && strpos($output, 'OpenSSL') !== false;
    }

    /**
     * Extract certificate expiration information from base64 certificate data
     *
     * @param string $certificateBase64
     * @param string $password
     * @return array|null
     */
    public static function extractCertificateExpirationInfo($certificateBase64, $password)
    {
        try {
            // Check if OpenSSL is available
            if (!self::isOpenSSLAvailable()) {
                Log::error('OpenSSL is not available on this system');
                return null;
            }

            // Decode base64 certificate
            $certificateData = base64_decode($certificateBase64);

            // Create temporary file
            $tempCertPath = tempnam(sys_get_temp_dir(), 'cert_') . '.pfx';
            file_put_contents($tempCertPath, $certificateData);

            Log::info('Created temporary certificate file: ' . $tempCertPath . ' (size: ' . strlen($certificateData) . ' bytes)');

            // Try multiple extraction methods
            $output = self::extractCertificateInfo($tempCertPath, $password);            // Clean up temporary file
            unlink($tempCertPath);

            if (!$output) {
                Log::error('Failed to execute openssl command for certificate parsing');
                return null;
            }

            if (self::isErrorOutput($output)) {
                Log::error('OpenSSL command returned an error: ' . $output);
                return null;
            }

            Log::info('Certificate dump output: ' . substr($output, 0, 1000) . '...'); // Log first 1000 chars to avoid huge logs

            // Parse the output to find expiration date
            $expirationInfo = self::parseOpenSSLOutput($output);

            if (!$expirationInfo) {
                Log::error('No expiration date found in certificate output');
                // Try alternative parsing methods
                $altExpirationInfo = self::parseAlternativeFormat($output);
                if ($altExpirationInfo) {
                    Log::info('Found expiration using alternative parsing');
                    $expirationInfo = $altExpirationInfo;
                } else {
                    return null;
                }
            }

            return [
                'due_date' => $expirationInfo['date'],
                'due_time' => $expirationInfo['time'],
                'certificate_info' => $expirationInfo
            ];

        } catch (\Exception $e) {
            Log::error('Error extracting certificate expiration info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Try multiple methods to extract certificate information
     *
     * @param string $certPath
     * @param string $password
     * @return string|null
     */
    private static function extractCertificateInfo($certPath, $password)
    {
        $commands = [
            // Method 1: Extract end entity certificate only
            "openssl pkcs12 -in " . escapeshellarg($certPath) . " -nokeys -clcerts -passin pass:" . escapeshellarg($password),

            // Method 2: Extract all certificates
            "openssl pkcs12 -in " . escapeshellarg($certPath) . " -nokeys -passin pass:" . escapeshellarg($password),

            // Method 3: Try with legacy provider (for newer OpenSSL versions)
            "openssl pkcs12 -legacy -in " . escapeshellarg($certPath) . " -nokeys -clcerts -passin pass:" . escapeshellarg($password),

            // Method 4: Try with legacy provider for all certs
            "openssl pkcs12 -legacy -in " . escapeshellarg($certPath) . " -nokeys -passin pass:" . escapeshellarg($password),
        ];

        foreach ($commands as $index => $baseCommand) {
            Log::info("Trying extraction method " . ($index + 1) . ": " . str_replace($password, '[PASSWORD]', $baseCommand));

            $output = shell_exec($baseCommand . " 2>&1");

            if ($output && !self::isErrorOutput($output)) {
                Log::info("Method " . ($index + 1) . " successful, output length: " . strlen($output));

                // Parse certificates and try to get expiration info
                $certificates = self::splitCertificates($output);
                if (!empty($certificates)) {
                    // Try each certificate, starting with the last one (end entity)
                    foreach (array_reverse($certificates) as $certIndex => $cert) {
                        $tempCertFile = tempnam(sys_get_temp_dir(), 'cert_' . $certIndex . '_') . '.pem';
                        file_put_contents($tempCertFile, $cert);

                        $textCommand = "openssl x509 -in " . escapeshellarg($tempCertFile) . " -noout -text 2>&1";
                        $textOutput = shell_exec($textCommand);

                        unlink($tempCertFile);

                        if ($textOutput && strpos($textOutput, 'Not After') !== false && !self::isErrorOutput($textOutput)) {
                            Log::info("Successfully extracted certificate info from certificate " . $certIndex);
                            return $textOutput;
                        }
                    }
                }
            } else {
                Log::warning("Method " . ($index + 1) . " failed: " . substr($output ?? 'NULL', 0, 200));
            }
        }

        return null;
    }

    /**
     * Check if OpenSSL output indicates an error
     *
     * @param string $output
     * @return bool
     */
    private static function isErrorOutput($output)
    {
        $errorIndicators = [
            'Could not read certificate',
            'Unable to load certificate',
            'unable to load certificates',
            'No certificate matches',
            'Error',
            'error',
            'MAC verify failure',
            'invalid password',
            'bad decrypt'
        ];

        foreach ($errorIndicators as $indicator) {
            if (strpos($output, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split multiple certificates from openssl output
     *
     * @param string $output
     * @return array
     */
    private static function splitCertificates($output)
    {
        $certificates = [];
        $lines = explode("\n", $output);
        $currentCert = "";
        $inCert = false;

        foreach ($lines as $line) {
            if (strpos($line, '-----BEGIN CERTIFICATE-----') !== false) {
                $inCert = true;
                $currentCert = $line . "\n";
            } elseif (strpos($line, '-----END CERTIFICATE-----') !== false) {
                $currentCert .= $line . "\n";
                $certificates[] = $currentCert;
                $currentCert = "";
                $inCert = false;
            } elseif ($inCert) {
                $currentCert .= $line . "\n";
            }
        }

        return $certificates;
    }

    /**
     * Parse openssl x509 -text output to extract expiration information
     *
     * @param string $output
     * @return array|null
     */
    private static function parseOpenSSLOutput($output)
    {
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Look for "Not After : " line which contains the expiration date
            if (strpos($line, 'Not After') !== false) {
                // Extract the date part after the colon
                $parts = explode(':', $line, 2);
                if (count($parts) > 1) {
                    $dateStr = trim($parts[1]);
                    return self::parseDate($dateStr);
                }
            }
        }

        return null;
    }

    /**
     * Parse alternative OpenSSL output formats
     *
     * @param string $output
     * @return array|null
     */
    private static function parseAlternativeFormat($output)
    {
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Look for different variations of expiration date
            $patterns = [
                '/Not After\s*:\s*(.+)$/i',
                '/notAfter\s*=\s*(.+)$/i',
                '/Validity\s*Not After\s*:\s*(.+)$/i',
                '/Valid\s*to\s*:\s*(.+)$/i'
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $dateStr = trim($matches[1]);
                    $parsedDate = self::parseDate($dateStr);
                    if ($parsedDate) {
                        return $parsedDate;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse certificate output (legacy method, kept for compatibility)
     *
     * @param string $output
     * @return array
     */
    private static function parseCertificateOutput($output)
    {
        $certificates = [];
        $lines = explode("\n", $output);
        $currentCert = null;
        $certIndex = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Start of a new certificate section
            if (strpos($line, 'subject=') === 0) {
                if ($currentCert !== null) {
                    $certificates[] = $currentCert;
                }
                $currentCert = [
                    'index' => $certIndex++,
                    'subject' => substr($line, 8) // Remove 'subject='
                ];
            }

            // Issuer information
            if (strpos($line, 'issuer=') === 0) {
                if ($currentCert !== null) {
                    $currentCert['issuer'] = substr($line, 7); // Remove 'issuer='
                }
            }

            // Not Before date
            if (strpos($line, 'notBefore=') === 0) {
                if ($currentCert !== null) {
                    $dateStr = substr($line, 10); // Remove 'notBefore='
                    $currentCert['notBefore'] = self::parseDate($dateStr);
                }
            }

            // Not After date (expiration)
            if (strpos($line, 'notAfter=') === 0) {
                if ($currentCert !== null) {
                    $dateStr = substr($line, 9); // Remove 'notAfter='
                    $currentCert['notAfter'] = self::parseDate($dateStr);
                }
            }
        }

        // Add the last certificate
        if ($currentCert !== null) {
            $certificates[] = $currentCert;
        }

        return $certificates;
    }

    /**
     * Parse date string from openssl output
     *
     * @param string $dateStr
     * @return array|null
     */
    private static function parseDate($dateStr)
    {
        try {
            // Clean the date string
            $dateStr = trim($dateStr);

            Log::info('Attempting to parse date: ' . $dateStr);

            // OpenSSL typically outputs dates in various formats:
            // - Mar 15 14:30:25 2024 GMT
            // - Jun 26 11:14:00 2026 GMT
            // - 2026-06-26 11:14:00 GMT
            // - Jun 26 2026 11:14:00 GMT
            // - 26-Jun-2026 11:14:00

            // Try to parse with Carbon which handles multiple formats
            $carbon = Carbon::parse($dateStr);

            $result = [
                'date' => $carbon->format('Y-m-d'),
                'time' => $carbon->format('H:i:s'),
                'full' => $carbon->toDateTimeString(),
                'original' => $dateStr
            ];

            Log::info('Successfully parsed date: ' . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::warning('Carbon failed to parse date: ' . $dateStr . ' - ' . $e->getMessage());

            // Try alternative parsing for common OpenSSL formats
            try {
                // Pattern 1: Month DD HH:MM:SS YYYY GMT
                if (preg_match('/(\w{3})\s+(\d{1,2})\s+(\d{2}):(\d{2}):(\d{2})\s+(\d{4})\s*(?:GMT)?/i', $dateStr, $matches)) {
                    return self::buildDateFromMatches($matches, 1);
                }

                // Pattern 2: DD-Month-YYYY HH:MM:SS
                if (preg_match('/(\d{1,2})-(\w{3})-(\d{4})\s+(\d{2}):(\d{2}):(\d{2})/i', $dateStr, $matches)) {
                    return self::buildDateFromMatches($matches, 2);
                }

                // Pattern 3: Month DD YYYY HH:MM:SS GMT
                if (preg_match('/(\w{3})\s+(\d{1,2})\s+(\d{4})\s+(\d{2}):(\d{2}):(\d{2})\s*(?:GMT)?/i', $dateStr, $matches)) {
                    return self::buildDateFromMatches($matches, 3);
                }

                // Pattern 4: YYYY-MM-DD HH:MM:SS
                if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})\s+(\d{2}):(\d{2}):(\d{2})/i', $dateStr, $matches)) {
                    $date = $matches[1] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[3], 2, '0', STR_PAD_LEFT);
                    $time = $matches[4] . ':' . $matches[5] . ':' . $matches[6];

                    $result = [
                        'date' => $date,
                        'time' => $time,
                        'full' => $date . ' ' . $time,
                        'original' => $dateStr
                    ];

                    Log::info('Successfully parsed date with pattern 4: ' . json_encode($result));
                    return $result;
                }

            } catch (\Exception $e2) {
                Log::error('Alternative date parsing also failed: ' . $e2->getMessage());
            }

            return null;
        }
    }

    /**
     * Build date array from regex matches
     *
     * @param array $matches
     * @param int $pattern
     * @return array
     */
    private static function buildDateFromMatches($matches, $pattern)
    {
        $monthMap = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
        ];

        switch ($pattern) {
            case 1: // Month DD HH:MM:SS YYYY GMT
                $month = $monthMap[$matches[1]] ?? '01';
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $hour = $matches[3];
                $minute = $matches[4];
                $second = $matches[5];
                $year = $matches[6];
                break;

            case 2: // DD-Month-YYYY HH:MM:SS
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = $monthMap[$matches[2]] ?? '01';
                $year = $matches[3];
                $hour = $matches[4];
                $minute = $matches[5];
                $second = $matches[6];
                break;

            case 3: // Month DD YYYY HH:MM:SS GMT
                $month = $monthMap[$matches[1]] ?? '01';
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                $hour = $matches[4];
                $minute = $matches[5];
                $second = $matches[6];
                break;

            default:
                throw new \Exception('Unknown pattern');
        }

        $date = $year . '-' . $month . '-' . $day;
        $time = $hour . ':' . $minute . ':' . $second;

        $result = [
            'date' => $date,
            'time' => $time,
            'full' => $date . ' ' . $time,
            'original' => $matches[0]
        ];

        Log::info('Successfully parsed date with pattern ' . $pattern . ': ' . json_encode($result));
        return $result;
    }
}
