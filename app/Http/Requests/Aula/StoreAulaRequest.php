<?php

namespace App\Http\Requests\Aula;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sala')->where(fn ($q) => $q->where('eliminado', false))
            ],
            'tipo' => ['nullable', 'string', 'max:50'],
            'capacidad' => ['required', 'integer', 'min:1'],
            'ubicacion_piso' => ['nullable', 'string', 'max:50'],
            'equipamiento' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'in:ACTIVO,INACTIVO'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'nombre.unique' => 'El nombre de la sala ya está en uso.',
            'capacidad.required' => 'La capacidad es obligatoria.',
            'capacidad.integer' => 'La capacidad debe ser un número entero.',
            'capacidad.min' => 'La capacidad debe ser mayor a 0.',
            'tipo.max' => 'El tipo no puede exceder 50 caracteres.',
            'ubicacion_piso.max' => 'La ubicación del piso no puede exceder 50 caracteres.',
            'equipamiento.max' => 'El equipamiento no puede exceder 255 caracteres.',
            'estado.in' => 'El estado debe ser ACTIVO o INACTIVO.',
        ];
    }
}
