<?php

namespace App\Http\Requests;

use App\Enums\StatusResponseEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStockArticleRequest extends FormRequest
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
            'qteStock' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'qteStock.required' => 'La quantité de stock est obligatoire.',
            'qteStock.numeric' => 'La quantité de stock doit être un nombre.',
            'qteStock.min' => 'La quantité de stock doit être positive.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 400));
    }
}
