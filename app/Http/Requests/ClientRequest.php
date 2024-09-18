<?php

namespace App\Http\Requests;

use App\Rules\CustomPasswordRule;
use App\Rules\TelephoneRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClientRequest extends FormRequest
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
    public function rules(): array{
        $rules = [
            'surname' => ['required', 'string', 'max:255', 'unique:clients,surname'],
            'telephone' => ['required', 'unique:clients,telephone', new TelephoneRule()],
            'category_client.id' => ['required_with:id', 'integer', 'exists:category_clients,id'],
        ];

        // Vérifier si l'objet 'user' a au moins une clé non vide
        if ($this->filled('user.nom') || $this->filled('user.prenom') || $this->filled('user.login') || $this->filled('user.photo') || $this->filled('user.password')) {
            $rules['user.nom'] = ['required', 'string', 'min:2', 'max:255'];
            $rules['user.prenom'] = ['required', 'string', 'min:2', 'max:255'];
            $rules['user.login'] = ['required', 'string', 'min:4', 'max:255', 'unique:users,login'];
            $rules['user.photo'] = ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:40'];
            $rules['user.password'] = ['required', 'confirmed', new CustomPasswordRule()];
            $rules['user.password_confirmation'] = ['required', 'string'];
        }

        return $rules;
    }


    public function messages(): array
    {
        return [
            'surname.required' => 'Le surnom est obligatoire.',
            'surname.unique' => 'Ce surnom existe déjà.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.unique' => 'Ce numéro existe déjà.',
            'cateogry_client.id.exists' => "Cette catégorie de client n'existe pas.",
            'cateogry_client.id.required_with' => "La category du client est obligatoire.",

            'user' => ['sometimes', 'array'],
            'user.nom.required_with' => 'Le nom est obligatoire',
            'user.nom.min' => 'Le nom doit avoir au moins 2 caractères.',
            'user.prenom.required_with' => 'Le prénom est obligatoire',
            'user.prenom.min' => 'Le prénom doit avoir au moins 2 caractères.',
            'user.login.required_with' => 'Le login est obligatoire',
            'user.login.unique' => 'Ce login est déjà utilisé.',
            'user.login.min' => 'Le login doit avoir au moins 4 caractères.',
            'user.photo.required_with' => 'La photo est obligatoire',
            'user.password.confirmed' => 'Les mots de passe ne concordent pas.',
            'user.password_confirmation.required_with' => 'La confirmation du mot de passe est obligatoire si un utilisateur est créé.',
            'user.role.id' => ['required_with:id.required_with','integer', 'exists:roles,id'],
        ];
    }

    function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }

}
