<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DemandeRequest extends FormRequest
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
            'details_article_demande' => ['required', 'array', 'min:1'], 
            'details_article_demande.*.articleId' => ['required', 'integer'],
            'details_article_demande.*.qteDemande' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'details_article_demande.required' => 'Les détails des articles sont obligatoires.',
            'details_article_demande.array' => 'Les détails des articles doivent être un tableau.',
            'details_article_demande.min' => 'Il doit y avoir au moins un article dans la demande.',
            'details_article_demande.*.articleId.required' => 'L\'ID de l\'article est obligatoire.',
            'details_article_demande.*.articleId.integer' => 'L\'ID de l\'article doit être un nombre entier.',
            'details_article_demande.*.qteDemande.required' => 'La quantité demandée est obligatoire pour chaque article.',
            'details_article_demande.*.qteDemande.integer' => 'La quantité demandée doit être un nombre entier.',
            'details_article_demande.*.qteDemande.min' => 'La quantité demandée doit être au moins de 1.',
        ];
    }

    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }
}
