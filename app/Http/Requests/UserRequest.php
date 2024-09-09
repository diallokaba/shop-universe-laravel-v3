<?php

namespace App\Http\Requests;

use App\Rules\CustomPasswordRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|min:2|max:255',
            'prenom' => 'required|string|min:2|max:255',
            'login' => 'required|string|min:4|max:255|unique:users,login',
            'photo' => 'required|string|max:255',
            'password' =>['confirmed', new CustomPasswordRule()],
            'password_confirmation' => 'required',
            'role.id' => ['required_with:id','integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.min' => 'Le nom doit avoir au moins 2 caractères.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'prenom.min' => 'Le prenom doit avoir au moins 2 caractères.',
            'login.required' => 'Le login est obligatoire.',
            'login.unique' => "Cet login est déjà utilisé.",
            'login.min' => 'Le login doit avoir au moins 4 caractères.',
            'photo.required' => 'La photo est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne concordent pas',
            'password_confirmation.required' => 'La confirmation du mot de passe est obligatoire.',
            'role.id.exists' => "Ce role n'existe pas.",
            'role.id.required_with' => "Le role est obligatoire.",
        ];
    }

    function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }
}
