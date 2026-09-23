<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeudacarteraRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'num_doc' => 'string',
			'importe' => 'required',
			'saldo' => 'required',
			'anticuacion' => 'string',
			'rango' => 'string',
			'cliente' => 'string',
			'cli_lugar' => 'string',
			'ent_nombre_jefe_vendedor' => 'string',
			'ent_nombre_supervisor' => 'string',
			'ent_nombre_vendedor' => 'string',
			'ciu_nombre' => 'string',
			'codigo_cliente' => 'string',
			'limite_credito' => 'required',
			'rut_id' => 'string',
			'telefono' => 'string',
			'estado' => 'string',
			'direccion' => 'string',
			'ausente' => 'required|boolean',
        ];
    }
}
