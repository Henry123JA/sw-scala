<?php

namespace App\Http\Requests\Horario;

use Illuminate\Foundation\Http\FormRequest;

class StoreHorarioGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grupo_id'    => ['required', 'exists:grupo,id'],
            'sala_id'     => ['required', 'exists:sala,id'],
            'dia_semana'  => ['required', 'string', 'max:50'],
            'hora_inicio' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'hora_fin'    => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', 'after:hora_inicio'],
            'tipo_sesion' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'grupo_id.required'    => 'El grupo es obligatorio.',
            'grupo_id.exists'      => 'El grupo seleccionado no es válido.',
            'sala_id.required'     => 'La sala es obligatoria.',
            'sala_id.exists'       => 'La sala seleccionada no es válida.',
            'dia_semana.required'  => 'El día de la semana es obligatorio.',
            'dia_semana.string'    => 'El día de la semana debe ser una cadena de texto.',
            'dia_semana.max'       => 'El día de la semana no puede exceder 50 caracteres.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio no tiene un formato válido.',
            'hora_fin.required'    => 'La hora de fin es obligatoria.',
            'hora_fin.date_format' => 'La hora de fin no tiene un formato válido.',
            'hora_fin.after'       => 'La hora de fin debe ser posterior a la hora de inicio.',
            'tipo_sesion.string'   => 'El tipo de sesión debe ser una cadena de texto.',
            'tipo_sesion.max'      => 'El tipo de sesión no puede exceder 50 caracteres.',
        ];
    }
}
