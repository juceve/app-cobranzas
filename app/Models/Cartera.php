<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Cartera
 *
 * @property $id
 * @property $company_id
 * @property $ult_actualizacion
 * @property $created_at
 * @property $updated_at
 *
 * @property Company $company
 * @property Deudacartera[] $deudacarteras
 * @property Historialcartera[] $historialcarteras
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Cartera extends Model
{
    protected $perPage = 20;

    protected $fillable = ['company_id', 'ult_actualizacion'];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id', 'id');
    }

    public function deudacarteras()
    {
        return $this->hasMany(\App\Models\Deudacartera::class, 'cartera_id', 'id');
    }

    public function historialcarteras()
    {
        return $this->hasMany(\App\Models\Historialcartera::class, 'cartera_id', 'id');
    }
}