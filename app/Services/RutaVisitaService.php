<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RutaVisitaService
{
    public static function distanciaKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $radioTierra = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Elige como semilla el deudor con más vecinos dentro del radio (hasta $n); ante empate, el de mayor saldo vencido.
     *
     * @param  Collection<int, object>  $puntos  objetos con codigo_cliente, lat, lon, saldo_vencido
     */
    public static function mejorSemilla(Collection $puntos, float $radioMaxKm, int $n): ?object
    {
        $conCoordenadas = $puntos->filter(fn ($p) => $p->lat !== null && $p->lon !== null)->values();
        $candidatos = $conCoordenadas->sortByDesc(fn ($p) => (float) $p->saldo_vencido)->take(400);

        $mejor = null;
        $mejorVecinos = -1;

        foreach ($candidatos as $candidato) {
            $vecinos = 0;

            foreach ($conCoordenadas as $otro) {
                if (self::distanciaKm((float) $candidato->lat, (float) $candidato->lon, (float) $otro->lat, (float) $otro->lon) <= $radioMaxKm) {
                    $vecinos++;
                }
            }

            $vecinos = min($vecinos, $n);

            if ($vecinos > $mejorVecinos) {
                $mejor = $candidato;
                $mejorVecinos = $vecinos;
            }
        }

        return $mejor;
    }

    /**
     * Devuelve los $n deudores con coordenadas más cercanos a la semilla (incluida), con su distancia.
     *
     * @param  Collection<int, object>  $puntos  objetos con codigo_cliente, lat, lon
     * @return Collection<int, object>
     */
    public static function masCercanos(Collection $puntos, object $semilla, int $n, ?float $radioMaxKm = null): Collection
    {
        return $puntos
            ->filter(fn ($p) => $p->lat !== null && $p->lon !== null)
            ->map(function ($p) use ($semilla) {
                $p->distancia_km = self::distanciaKm((float) $semilla->lat, (float) $semilla->lon, (float) $p->lat, (float) $p->lon);

                return $p;
            })
            ->when($radioMaxKm !== null, fn ($c) => $c->filter(fn ($p) => $p->distancia_km <= $radioMaxKm))
            ->sortBy('distancia_km')
            ->take($n)
            ->values();
    }

    /**
     * Ordena la ruta de visitas: arranca por el deudor más urgente y avanza al vecino más cercano.
     * Los deudores sin coordenadas van al final por prioridad.
     *
     * @param  Collection<int, object>  $puntos  objetos con codigo_cliente, lat, lon, saldo_vencido
     * @return array<string, int> codigo_cliente => orden (desde 1)
     */
    public static function ordenar(Collection $puntos): array
    {
        $conCoordenadas = $puntos->filter(fn ($p) => $p->lat !== null && $p->lon !== null)->values();
        $sinCoordenadas = $puntos->filter(fn ($p) => $p->lat === null || $p->lon === null)
            ->sortByDesc(fn ($p) => (float) $p->saldo_vencido)
            ->values();

        $orden = [];
        $posicion = 1;

        if ($conCoordenadas->isNotEmpty()) {
            $actual = $conCoordenadas->sortByDesc(fn ($p) => (float) $p->saldo_vencido)->first();
            $pendientes = $conCoordenadas->keyBy('codigo_cliente');

            while ($actual !== null) {
                $orden[$actual->codigo_cliente] = $posicion++;
                $pendientes->forget($actual->codigo_cliente);

                $actual = $pendientes
                    ->sortBy(fn ($p) => self::distanciaKm((float) $actual->lat, (float) $actual->lon, (float) $p->lat, (float) $p->lon))
                    ->first();
            }
        }

        foreach ($sinCoordenadas as $p) {
            $orden[$p->codigo_cliente] = $posicion++;
        }

        return $orden;
    }
}
