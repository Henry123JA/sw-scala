<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecretariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'ci'        => ['required', 'string', 'max:20', 'unique:usuario,ci'],
            'email'     => ['required', 'email', 'max:150', 'unique:usuario,email'],
            'password'  => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required'   => 'El campo nombre es obligatorio.',
            'nombres.max'        => 'El nombre no puede exceder 100 caracteres.',
            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'apellidos.max'      => 'Los apellidos no pueden exceder 100 caracteres.',
            'ci.required'        => 'El campo CI es obligatorio.',
            'ci.unique'          => 'El CI ya está registrado en el sistema.',
            'email.required'     => 'El campo correo electrónico es obligatorio.',
            'email.email'        => 'El correo electrónico no tiene un formato válido.',
            'email.unique'       => 'El correo electrónico ya está registrado.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex'     => 'La contraseña debe tener al menos una mayúscula y un número.',
        ];
    }
}
