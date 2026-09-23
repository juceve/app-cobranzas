<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
{
    use SoftDeletes;

    protected $perPage = 20;

    protected $fillable = [
        'cartera_id', 'company_id', 'user_id', 'created_by', 'codigo',
        'nombre', 'descripcion', 'estado', 'fecha_asignacion', 'fecha_limite',
        'total_deudas', 'total_saldo', 'total_importe',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_limite'     => 'date',
        'total_saldo'      => 'decimal:2',
        'total_importe'    => 'decimal:2',
    ];

    public function cartera()
    {
        return $this->belongsTo(Cartera::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function cobrador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deudas()
    {
        return $this->belongsToMany(Deudacartera::class, 'lote_deuda')
                    ->withPivot(['estado_gestion', 'fecha_gestion', 'observacion', 'monto_recuperado', 'gestionado_por'])
                    ->withTimestamps();
    }

    public static function generarCodigo(): string
    {
        $anio = now()->year;
        $ultimo = static::whereYear('created_at', $anio)->count() + 1;
        return sprintf('LOTE-%d-%04d', $anio, $ultimo);
    }
}