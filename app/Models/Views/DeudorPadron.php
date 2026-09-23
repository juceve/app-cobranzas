<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DeudorPadron
 *
 * Vista de solo lectura que consolida los datos personales y de ubicación
 * de los deudores, agrupando por combinación única de datos.
 *
 * @property string      $rut_id
 * @property string      $codigo_cliente
 * @property string      $cliente
 * @property string|null $cli_lugar
 * @property string|null $ciu_nombre
 * @property string|null $direccion
 * @property float|null  $coordenada_x
 * @property float|null  $coordenada_y
 * @property string|null $telefono
 * @property string|null $ent_nombre_jefe_vendedor
 * @property string|null $ent_nombre_supervisor
 * @property string|null $ent_nombre_vendedor
 * @property \Carbon\Carbon $primera_aparicion
 * @property \Carbon\Carbon $ultima_aparicion
 * @property int         $carteras_distintas
 *
 * @package App\Models\Views
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class DeudorPadron extends Model
{
    protected $table = 'v_deudores_padron';

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = ['*'];

    protected $casts = [
        'coordenada_x'       => 'decimal:8',
        'coordenada_y'       => 'decimal:8',
        'primera_aparicion'  => 'date',
        'ultima_aparicion'   => 'date',
        'carteras_distintas' => 'integer',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(fn () => throw new \RuntimeException('DeudorPadron es una vista de solo lectura.'));
        static::updating(fn () => throw new \RuntimeException('DeudorPadron es una vista de solo lectura.'));
        static::deleting(fn () => throw new \RuntimeException('DeudorPadron es una vista de solo lectura.'));
    }

    public function scopeBuscar($query, ?string $termino)
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function ($q) use ($termino) {
            $q->where('cliente', 'ilike', "%{$termino}%")
              ->orWhere('rut_id', 'ilike', "%{$termino}%")
              ->orWhere('codigo_cliente', 'ilike', "%{$termino}%")
              ->orWhere('telefono', 'ilike', "%{$termino}%")
              ->orWhere('direccion', 'ilike', "%{$termino}%");
        });
    }

    public function scopeConCoordenadas($query)
    {
        return $query->whereNotNull('coordenada_x')
                     ->whereNotNull('coordenada_y');
    }

    public function scopeDeCiudad($query, ?string $ciudad)
    {
        return $ciudad ? $query->where('ciu_nombre', $ciudad) : $query;
    }
}