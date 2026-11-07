<?php

namespace App\Http\Requests\Asistencia;

use App\Models\Asistencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol?->nombre, ['Propietario', 'Secretaria', 'Docente'], true);
    }

    public function rules(): array
    {
        return [
            'asistencias' => ['required', 'array', 'min:1'],
            'asistencias.*' => ['required', 'array:inscripcion_id,estado,observacion'],
            'asistencias.*.inscripcion_id' => ['required', 'integer', 'distinct', 'exists:inscripcion,id'],
            'asistencias.*.estado' => ['required', Rule::in(Asistencia::ESTADOS)],
            'asistencias.*.observacion' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
