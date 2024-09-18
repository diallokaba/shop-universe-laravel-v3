<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ArticleRequest extends FormRequest
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
            'libelle' => 'required|string|unique:articles,libelle|max:255',
            'reference' => 'required|string|unique:articles,reference|max:255',
            'prix' => 'required|integer|min:1',
            'quantite' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.string' => 'Le libellé est une chaine de caractère',
            'libelle.unique' => 'Ce libellé existe déjà.',
            'libelle.max' => 'Le libellé doit contenir maximum 255 caractères.',
            'reference.required' => 'La référence est obligatoire.',
            'reference.string' => 'La référence est une chaîne de caractère',
            'reference.unique' => 'Cette référence existe déjà.',
            'reference.max' => 'La référence doit contenir maximum 255 caractères.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.integer' => 'Le prix doit être un nombre entier.',
            'prix.min' => 'Le prix prix minimum est 1.',
            'quantite.required' => 'La quantité en stock est obligatoire.',
            'quantite.integer' => 'La quantité en stock doit être un nombre entier.',
            'quantite.min' => 'La quantité en stock minimale est 1.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }
}