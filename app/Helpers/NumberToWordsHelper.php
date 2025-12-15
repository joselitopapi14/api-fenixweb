<?php

namespace App\Helpers;

class NumberToWordsHelper
{
    private static $unidades = [
        '', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete',
        'dieciocho', 'diecinueve'
    ];

    private static $decenas = [
        '', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta',
        'ochenta', 'noventa'
    ];

    private static $centenas = [
        '', 'cien', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
        'seiscientos', 'setecientos', 'ochocientos', 'novecientos'
    ];

    public static function convertir($numero)
    {
        if ($numero == 0) {
            return 'cero';
        }

        $numero = intval($numero);

        if ($numero < 0) {
            return 'menos ' . self::convertir(abs($numero));
        }

        $resultado = '';

        // Millones
        if ($numero >= 1000000) {
            $millones = intval($numero / 1000000);
            if ($millones == 1) {
                $resultado .= 'un millón ';
            } else {
                $resultado .= self::convertirGrupo($millones) . ' millones ';
            }
            $numero %= 1000000;
        }

        // Miles
        if ($numero >= 1000) {
            $miles = intval($numero / 1000);
            if ($miles == 1) {
                $resultado .= 'mil ';
            } else {
                $resultado .= self::convertirGrupo($miles) . ' mil ';
            }
            $numero %= 1000;
        }

        // Centenas, decenas y unidades
        if ($numero > 0) {
            $resultado .= self::convertirGrupo($numero);
        }

        return trim($resultado);
    }

    private static function convertirGrupo($numero)
    {
        $resultado = '';

        // Centenas
        if ($numero >= 100) {
            $centena = intval($numero / 100);
            if ($numero == 100) {
                $resultado .= 'cien';
            } else {
                $resultado .= self::$centenas[$centena] . ' ';
            }
            $numero %= 100;
        }

        // Decenas y unidades
        if ($numero >= 20) {
            $decena = intval($numero / 10);
            $unidad = $numero % 10;

            if ($unidad == 0) {
                $resultado .= self::$decenas[$decena];
            } else {
                $resultado .= self::$decenas[$decena] . ' y ' . self::$unidades[$unidad];
            }
        } elseif ($numero > 0) {
            if ($numero == 1 && strpos($resultado, 'mil') !== false) {
                // No agregar "uno" antes de "mil"
            } else {
                $resultado .= self::$unidades[$numero];
            }
        }

        return trim($resultado);
    }
}
