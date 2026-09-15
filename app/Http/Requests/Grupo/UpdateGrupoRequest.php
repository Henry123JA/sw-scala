<?php

namespace App\Http\Requests\Grupo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $grupoId = $this->route('grupo');

        return [
            'codigo_grupo'     => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('grupo')->ignore($grupoId)->where(fn ($q) => $q->where('eliminado', false))
            ],
            'curso_id'         => ['sometimes', 'required', 'integer'],
            'docente_id'       => ['sometimes', 'required', 'integer'],
            'capacidad_maxima' => ['sometimes', 'required', 'integer', 'min:1'],
            'nivel'            => ['nullable', 'string', 'max:50'],
            'estado'           => ['nullable', 'string', 'in:ACTIVO,INACTIVO,CERRADO,CANCELADO'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_grupo.required'     => 'El código de grupo es obligatorio.',
            'codigo_grupo.max'          => 'El código de grupo no puede exceder los 20 caracteres.',
            'codigo_grupo.unique'       => 'El código de grupo ya está en uso.',
            'curso_id.required'         => 'El curso es obligatorio.',
            'docente_id.required'       => 'El docente es obligatorio.',
            'capacidad_maxima.required' => 'La capacidad máxima es obligatoria.',
            'capacidad_maxima.integer'  => 'La capacidad máxima debe ser un número entero.',
            'capacidad_maxima.min'      => 'La capacidad máxima debe ser mayor a 0.',
            'nivel.max'                 => 'El nivel no puede exceder los 50 caracteres.',
            'estado.in'                 => 'El estado debe ser ACTIVO, INACTIVO, CERRADO o CANCELADO.',
        ];
    }
}
