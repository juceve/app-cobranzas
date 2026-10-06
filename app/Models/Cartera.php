<?php

namespace App\Models;

use App\Support\EmpresaActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cartera
 *
 * @property $id
 * @property $company_id
 * @property $ult_actualizacion
 * @property $created_at
 * @property $updated_at
 * @property Company $company
 * @property Deudacartera[] $deudacarteras
 * @property Historialcartera[] $historialcarteras
 *
 * @mixin Builder
 */
class Cartera extends Model
{
    protected $perPage = 20;

    protected $fillable = ['company_id', 'ult_actualizacion'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function scopeDeEmpresaActual($query)
    {
        $empresaId = EmpresaActual::id();

        return $empresaId === null ? $query : $query->where($this->qualifyColumn('company_id'), $empresaId);
    }

    public function deudacarteras()
    {
        return $this->hasMany(Deudacartera::class, 'cartera_id', 'id');
    }

    public function historialcarteras()
    {
        return $this->hasMany(Historialcartera::class, 'cartera_id', 'id');
    }
}
