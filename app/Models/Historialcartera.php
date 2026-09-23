<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Historialcartera
 *
 * @property $id
 * @property $cartera_id
 * @property $fecha
 * @property $cambios
 * @property $nuevos
 * @property $salidas
 * @property $doc_original
 * @property $hash
 * @property $user_id
 * @property $estado
 * @property $created_at
 * @property $updated_at
 *
 * @property Cartera $cartera
 * @property User $user
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Historialcartera extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['cartera_id', 'fecha', 'cambios', 'nuevos', 'salidas', 'doc_original', 'hash', 'user_id', 'estado'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cartera()
    {
        return $this->belongsTo(\App\Models\Cartera::class, 'cartera_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
    
}
