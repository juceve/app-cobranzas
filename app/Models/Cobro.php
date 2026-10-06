<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cobro extends Model
{
    public const FORMAS_PAGO = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'deposito' => 'Depósito',
        'qr' => 'QR',
        'cheque' => 'Cheque',
    ];

    protected $fillable = [
        'lote_id', 'deudacartera_id', 'gestion_id', 'recibo_id', 'user_id', 'monto', 'fecha_cobro',
        'forma_pago', 'referencia', 'estado', 'validado_por', 'validado_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_cobro' => 'date',
        'validado_en' => 'datetime',
    ];

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function deuda()
    {
        return $this->belongsTo(Deudacartera::class, 'deudacartera_id');
    }

    public function gestion()
    {
        return $this->belongsTo(Gestion::class);
    }

    public function recibo()
    {
        return $this->belongsTo(Recibo::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
