<?php

namespace App\Http\Requests\Horario;

use Illuminate\Foundation\Http\FormRequest;

class StoreHorarioDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'docente_id'  => ['required', 'exists:docente,id'],
            'dia_semana'  => ['required', 'string', 'max:50'],
            'hora_inicio' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'hora_fin'    => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', 'after:hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'docente_id.required'  => 'El docente es obligatorio.',
            'docente_id.exists'    => 'El docente seleccionado no es válido.',
            'dia_semana.required'  => 'El día de la semana es obligatorio.',
            'dia_semana.string'    => 'El día de la semana debe ser una cadena de texto.',
            'dia_semana.max'       => 'El día de la semana no puede exceder 50 caracteres.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio no tiene un formato válido.',
            'hora_fin.required'    => 'La hora de fin es obligatoria.',
            'hora_fin.date_format' => 'La hora de fin no tiene un formato válido.',
            'hora_fin.after'       => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
