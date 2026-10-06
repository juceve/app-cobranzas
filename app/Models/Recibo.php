<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    public const MONEDA = 'Bs';

    protected $fillable = [
        'numero', 'token', 'company_id', 'lote_id', 'gestion_id', 'user_id', 'codigo_cliente',
        'cliente', 'empresa', 'cobrador', 'forma_pago', 'referencia', 'monto_total',
        'fecha_cobro', 'emitido_en',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha_cobro' => 'date',
        'emitido_en' => 'datetime',
    ];

    public function cobros()
    {
        return $this->hasMany(Cobro::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function urlPublica(): string
    {
        return route('recibos.publico', $this->token);
    }

    public function urlTicket(bool $imprimir = false): string
    {
        return route('recibos.ticket', ['token' => $this->token] + ($imprimir ? ['imprimir' => 1] : []));
    }

    public function etiquetaFormaPago(): string
    {
        return Cobro::FORMAS_PAGO[$this->forma_pago] ?? $this->forma_pago;
    }

    public function estaValidado(): bool
    {
        return $this->cobros->isNotEmpty() && $this->cobros->every(fn (Cobro $c) => $c->estado === 'validado');
    }
}
