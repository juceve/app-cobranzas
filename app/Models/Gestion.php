<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    protected $table = 'gestiones';

    public const TIPOS = [
        'llamada' => 'Llamada',
        'visita' => 'Visita',
        'whatsapp' => 'WhatsApp',
        'otro' => 'Otro',
    ];

    /** resultado => [etiqueta, estado_gestion que se refleja en lote_deuda] */
    public const RESULTADOS = [
        'contactado' => ['Contactado', 'visitado'],
        'promesa_pago' => ['Promesa de pago', 'promesa_pago'],
        'no_contesta' => ['No contesta / no está', 'no_contactado'],
        'dato_incorrecto' => ['Dirección o teléfono incorrecto', 'no_contactado'],
        'se_nego' => ['Se negó a pagar', 'rechazado'],
        'pago' => ['Pago registrado', 'visitado'],
    ];

    protected $fillable = [
        'lote_id', 'codigo_cliente', 'user_id', 'tipo', 'resultado', 'observacion',
        'fecha_promesa', 'monto_prometido', 'gestionado_en',
    ];

    protected $casts = [
        'fecha_promesa' => 'date',
        'monto_prometido' => 'decimal:2',
        'gestionado_en' => 'datetime',
    ];

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cobros()
    {
        return $this->hasMany(Cobro::class);
    }

    public function etiquetaResultado(): string
    {
        return self::RESULTADOS[$this->resultado][0] ?? $this->resultado;
    }
}
