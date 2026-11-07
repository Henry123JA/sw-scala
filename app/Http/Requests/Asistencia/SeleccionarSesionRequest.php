<?php

namespace App\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

class SeleccionarSesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol?->nombre, ['Propietario', 'Secretaria', 'Docente'], true);
    }

    public function rules(): array
    {
        return [
            'grupo_id' => ['required', 'integer', 'exists:grupo,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
