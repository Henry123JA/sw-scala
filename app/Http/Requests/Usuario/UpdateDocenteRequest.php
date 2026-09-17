<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('docente');

        return [
            // Usuario base
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'ci'        => ['required', 'string', 'max:20', Rule::unique('usuario', 'ci')->ignore($id)],
            'email'     => ['required', 'email', 'max:150', Rule::unique('usuario', 'email')->ignore($id)],
            'password'  => ['nullable', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/'],

            // Docente specific
            'codigo'              => ['nullable', 'string', 'max:20', Rule::unique('docente', 'codigo')->ignore($id)],
            'estado'              => ['nullable', 'string', 'in:ACTIVO,INACTIVO'],
            'fecha_nacimiento'    => ['nullable', 'date', 'before_or_equal:today'],
            'fecha_incorporacion' => ['nullable', 'date'],
            'telefono'            => ['nullable', 'string', 'max:20'],
            'observaciones'       => ['nullable', 'string'],
            'especialidades'      => ['nullable', 'array'],
            'especialidades.*'    => ['integer', 'exists:especialidad,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required'         => 'El campo nombre es obligatorio.',
            'nombres.max'              => 'El nombre no puede exceder 100 caracteres.',
            'apellidos.required'       => 'El campo apellidos es obligatorio.',
            'apellidos.max'            => 'Los apellidos no pueden exceder 100 caracteres.',
            'ci.required'              => 'El campo CI es obligatorio.',
            'ci.unique'                => 'El CI ya está registrado en el sistema.',
            'email.required'           => 'El campo correo electrónico es obligatorio.',
            'email.email'              => 'El correo electrónico no tiene un formato válido.',
            'email.unique'             => 'El correo electrónico ya está registrado.',
            'password.min'             => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex'           => 'La contraseña debe tener al menos una mayúscula y un número.',
            'codigo.unique'            => 'El código ya está registrado.',
            'estado.in'                => 'El estado debe ser ACTIVO o INACTIVO.',
            'fecha_nacimiento.date'    => 'La fecha de nacimiento no tiene un formato válido.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser una fecha futura.',
            'especialidades.array'     => 'Las especialidades deben ser una lista.',
            'especialidades.*.exists'  => 'Una o más especialidades no son válidas.',
        ];
    }
}
