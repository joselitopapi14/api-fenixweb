<?php

// Script de prueba para verificar el cálculo de boleta de desempeño
require_once 'vendor/autoload.php';

use App\Models\BoletaDesempeno;
use App\Models\BolletaEmpeno;
use Carbon\Carbon;

// Simulamos un préstamo de $200,000 al 3% mensual
// Fecha préstamo: 25/08/2025
// Fecha desempeño: 25/09/2025 (1 mes después)
// Abonos previos: $20,000

echo "=== TEST DE CÁLCULO BOLETA DESEMPEÑO ===\n";

// Simulamos los datos del préstamo
$montoPrestamo = 200000;
$tasaInteres = 3.0; // 3% mensual
$fechaPrestamo = Carbon::parse('2025-08-25');
$fechaDesempeno = Carbon::parse('2025-09-25');
$abonosPrevios = 20000;

// Cálculo manual esperado:
// 1 mes = 30 días
// Interés = $200,000 * 3% = $6,000
// Monto total = $200,000 + $6,000 - $20,000 = $186,000

$diasTranscurridos = $fechaPrestamo->diffInDays($fechaDesempeno);
$interesCalculado = ($montoPrestamo * $tasaInteres / 100) * ($diasTranscurridos / 30);
$montoEsperado = $montoPrestamo + $interesCalculado - $abonosPrevios;

echo "Datos del préstamo:\n";
echo "- Monto préstamo: $" . number_format($montoPrestamo, 2) . "\n";
echo "- Tasa interés: {$tasaInteres}% mensual\n";
echo "- Fecha préstamo: {$fechaPrestamo->format('d/m/Y')}\n";
echo "- Fecha desempeño: {$fechaDesempeno->format('d/m/Y')}\n";
echo "- Días transcurridos: {$diasTranscurridos}\n";
echo "- Abonos previos: $" . number_format($abonosPrevios, 2) . "\n";
echo "\n";

echo "Cálculo esperado:\n";
echo "- Interés calculado: $" . number_format($interesCalculado, 2) . "\n";
echo "- Monto desempeño: $" . number_format($montoEsperado, 2) . "\n";
echo "\n";

echo "Para verificar en la aplicación:\n";
echo "1. Crear un préstamo de $" . number_format($montoPrestamo, 2) . " el 25/08/2025\n";
echo "2. Crear un abono de $" . number_format($abonosPrevios, 2) . " en septiembre\n";
echo "3. Crear un desempeño con fecha 25/09/2025\n";
echo "4. El monto debería autocompletearse a $" . number_format($montoEsperado, 2) . "\n";
echo "\n";

echo "Desglose esperado en la vista:\n";
echo "- Monto préstamo: $" . number_format($montoPrestamo, 2) . "\n";
echo "- Intereses (hasta fecha de abono): $" . number_format($interesCalculado, 2) . "\n";
echo "- Abonos aplicados (mismo mes): $" . number_format($abonosPrevios, 2) . "\n";
echo "- Monto resultante: $" . number_format($montoEsperado, 2) . "\n";
