<?php

namespace App\Http\Requests\Inscripcion;

use Illuminate\Foundation\Http\FormRequest;

class StoreInscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alumno_id'           => ['required', 'integer', 'exists:alumno,id'],
            'grupo_id'            => ['required', 'integer', 'exists:grupo,id'],
            'fecha'               => ['nullable', 'date'],
            'fecha_inicio_clases' => ['required', 'date'],
            'observaciones'       => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'alumno_id.required'           => 'El alumno es obligatorio.',
            'alumno_id.exists'             => 'El alumno seleccionado no existe.',
            'grupo_id.required'            => 'El grupo es obligatorio.',
            'grupo_id.exists'              => 'El grupo seleccionado no existe.',
            'fecha.date'                   => 'La fecha de inscripción debe ser una fecha válida.',
            'fecha_inicio_clases.required' => 'La fecha de inicio de clases es obligatoria.',
            'fecha_inicio_clases.date'     => 'La fecha de inicio de clases debe ser una fecha válida.',
        ];
    }
}
