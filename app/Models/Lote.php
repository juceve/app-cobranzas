<?php

namespace App\Models;

use App\Support\EmpresaActual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
{
    use SoftDeletes;

    protected $perPage = 20;

    public const ESTADOS_ABIERTOS = ['borrador', 'asignado', 'en_proceso'];

    public function scopeDeEmpresaActual($query)
    {
        $empresaId = EmpresaActual::id();

        return $empresaId === null ? $query : $query->where($this->qualifyColumn('company_id'), $empresaId);
    }

    protected $fillable = [
        'cartera_id', 'company_id', 'user_id', 'created_by', 'codigo',
        'nombre', 'descripcion', 'ciudad', 'estado', 'fecha_asignacion', 'fecha_limite',
        'total_deudas', 'total_deudores', 'total_saldo', 'total_importe',
        'cerrado_en', 'cerrado_por', 'motivo_cierre',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
        'fecha_limite' => 'date',
        'cerrado_en' => 'datetime',
        'total_saldo' => 'decimal:2',
        'total_importe' => 'decimal:2',
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

    public function gestiones()
    {
        return $this->hasMany(Gestion::class);
    }

    public function cobros()
    {
        return $this->hasMany(Cobro::class);
    }

    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function estaAbierto(): bool
    {
        return in_array($this->estado, self::ESTADOS_ABIERTOS, true);
    }

    public function deudas()
    {
        return $this->belongsToMany(Deudacartera::class, 'lote_deuda')
            ->withPivot(['codigo_cliente', 'orden_visita', 'estado_gestion', 'fecha_gestion', 'observacion', 'monto_recuperado', 'gestionado_por'])
            ->withTimestamps();
    }

    public static function generarCodigo(): string
    {
        $anio = now()->year;
        $ultimo = static::whereYear('created_at', $anio)->count() + 1;

        return sprintf('LOTE-%d-%04d', $anio, $ultimo);
    }
}
