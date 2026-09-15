<?php

namespace App\Http\Requests\Inscripcion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio_clases' => ['nullable', 'date'],
            'observaciones'       => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio_clases.date' => 'La fecha de inicio de clases debe ser una fecha válida.',
        ];
    }
}
