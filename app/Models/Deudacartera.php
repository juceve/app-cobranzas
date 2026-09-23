<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deudacartera extends Model
{
    protected $perPage = 20;

    protected $fillable = [
        'cartera_id', 'fecha', 'num_doc', 'importe', 'saldo', 'vence', 'antiguedad',
        'anticuacion', 'rango', 'cliente', 'cli_lugar', 'ent_nombre_jefe_vendedor',
        'ent_nombre_supervisor', 'ent_nombre_vendedor', 'plazo', 'fecha_ultimo_pago',
        'ciu_nombre', 'codigo_cliente', 'limite_credito', 'rut_id', 'coordenada_x',
        'coordenada_y', 'telefono', 'estado', 'direccion', 'fecha_carga', 'ausente',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'vence'             => 'date',
        'fecha_ultimo_pago' => 'date',
        'fecha_carga'       => 'date',
        'importe'           => 'decimal:2',
        'saldo'             => 'decimal:2',
        'limite_credito'    => 'decimal:2',
        'coordenada_x'      => 'decimal:8',
        'coordenada_y'      => 'decimal:8',
        'ausente'           => 'boolean',
    ];

    public function cartera()
    {
        return $this->belongsTo(Cartera::class, 'cartera_id', 'id');
    }

    public function lotes()
    {
        return $this->belongsToMany(Lote::class, 'lote_deuda')
                    ->withPivot(['estado_gestion', 'fecha_gestion', 'observacion', 'monto_recuperado', 'gestionado_por'])
                    ->withTimestamps();
    }

    public function getPorcentajeAttribute(): float
    {
        $importe = (float) $this->importe;
        return $importe > 0 ? round(((float) $this->saldo / $importe) * 100, 2) : 0;
    }
}