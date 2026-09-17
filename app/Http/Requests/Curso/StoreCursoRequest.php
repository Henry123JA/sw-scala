<?php

namespace App\Http\Requests\Curso;

use Illuminate\Foundation\Http\FormRequest;

class StoreCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'            => ['required', 'string', 'max:150'],
            'descripcion'       => ['nullable', 'string'],
            'tipo_ensenanza'    => ['nullable', 'string', 'max:50'],
            'duracion_estandar' => ['nullable', 'string', 'max:50'],
            'estado'            => ['nullable', 'string', 'in:ACTIVO,INACTIVO'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'       => 'El nombre del curso es obligatorio.',
            'nombre.max'            => 'El nombre no puede exceder los 150 caracteres.',
            'tipo_ensenanza.max'    => 'El tipo de enseñanza no puede exceder los 50 caracteres.',
            'duracion_estandar.max' => 'La duración estándar no puede exceder los 50 caracteres.',
            'estado.in'             => 'El estado debe ser ACTIVO o INACTIVO.',
        ];
    }
}
