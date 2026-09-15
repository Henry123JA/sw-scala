<?php

namespace App\Http\Requests\Perfil;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email'     => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuario', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/\d/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required'   => 'El campo nombres es obligatorio.',
            'nombres.max'        => 'Los nombres no pueden exceder 100 caracteres.',
            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'apellidos.max'      => 'Los apellidos no pueden exceder 100 caracteres.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'El formato del correo electrónico es inválido.',
            'email.max'          => 'El correo electrónico no puede exceder 150 caracteres.',
            'email.unique'       => 'El correo electrónico ya está registrado.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex'     => 'La contraseña debe contener al menos una letra mayúscula y un número.',
        ];
    }
}
