<?php

namespace App\Http\Requests\Perfil;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerfilFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Debes seleccionar una imagen para subir.',
            'foto.file'     => 'El archivo de foto no es válido.',
            'foto.image'    => 'El archivo debe ser una imagen.',
            'foto.mimes'    => 'La imagen debe ser JPG o PNG.',
            'foto.max'      => 'La imagen no debe pesar más de 2MB.',
        ];
    }
}
