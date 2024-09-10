<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DetteRequest extends FormRequest
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
            'client.id' => ['required', 'integer', 'exists:clients,id'],
            'details_dette' => ['required', 'array', 'min:1'], 
            //'details_dette.*.article_id' => ['required', 'integer', 'exists:articles,id'],
            'details_dette.*.articleId' => ['required', 'integer'],
            'details_dette.*.qteVente' => ['required', 'integer', 'min:1'], 
            'details_dette.*.prixVente' => ['required', 'integer', 'min:1'], 
            'paiement.montant' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array{
        return [
            'client.id.required' => 'L\'id du client est obligatoire',
            'client.id.integer' => 'L\'id du client doit être un nombre entier',
            'client.id.exists' => 'Le client avec cet id n\'existe pas',
            'details_dette.required' => 'Les détails de la dette sont obligatoires',
            'details_dette.array' => 'Les détails de la dette doivent être un tableau',
            'details_dette.min' => 'Il doit y avoir au moins un objet dans les détails de la dette',
            'details_dette.*.articleId.required' => 'L\'id de l\'article est obligatoire pour chaque détail',
            'details_dette.*.articleId.integer' => 'L\'id de l\'article est obligatoire pour chaque détail',
            //'details_dette.*.article_id.exists' => 'L\'article sélectionné n\'existe pas',
            'details_dette.*.qteVente.required' => 'La quantité de vente est obligatoire pour chaque détail',
            'details_dette.*.qteVente.integer' => 'La quantité de vente doit être un nombre entier',
            'details_dette.*.qteVente.min' => 'La quantité de vente doit être au minimum 1',
            'details_dette.*.prixVente.required' => 'Le prix de vente est obligatoire pour chaque détail',
            'details_dette.*.prixVente.integer' => 'Le prix de vente doit être un nombre entier',
            'details_dette.*.prixVente.min' => 'Le prix de vente doit être au minimum 1',
            'paiement.montant.integer' => 'Le montant du paiement doit être un nombre entier',
            'paiement.montant.min' => 'Le montant du paiement ne doit pas être inférieur à 0',
            
        ];
    }

    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }
}
