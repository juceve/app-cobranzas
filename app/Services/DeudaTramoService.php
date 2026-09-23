<?php

namespace App\Services;

class DeudaTramoService
{
    public static function tramo(int $dias): string
    {
        return match (true) {
            $dias >= 251 => 'mas_251',
            $dias >= 211 => '211_250',
            $dias >= 181 => '181_210',
            $dias >= 151 => '151_180',
            $dias >= 121 => '121_150',
            $dias >= 91  => '91_120',
            $dias >= 61  => '61_90',
            $dias >= 31  => '31_60',
            $dias >= 1   => '01_30',
            default      => 'vigente',
        };
    }

    public static function etiqueta(string $tramo): string
    {
        return match ($tramo) {
            'mas_251' => 'Más de 251',
            '211_250' => '211 - 250',
            '181_210' => '181 - 210',
            '151_180' => '151 - 180',
            '121_150' => '121 - 150',
            '91_120'  => '91 - 120',
            '61_90'   => '61 - 90',
            '31_60'   => '31 - 60',
            '01_30'   => '01 - 30',
            default   => 'Vigente',
        };
    }

    public static function color(string $tramo): string
    {
        return match ($tramo) {
            'mas_251', '211_250', '181_210' => 'danger',
            '151_180', '121_150'            => 'warning',
            '91_120', '61_90'               => 'info',
            '31_60', '01_30'                => 'primary',
            default                         => 'success',
        };
    }
}