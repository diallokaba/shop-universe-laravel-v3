<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

class CustomPasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Utiliser la classe Password directement pour vérifier les règles
        $passwordRules = Password::min(8)->letters()->mixedCase()->numbers()->symbols();

        if (!preg_match('/[A-Z]/', $value) || // Vérifie la présence d'une lettre majuscule
            !preg_match('/[a-z]/', $value) || // Vérifie la présence d'une lettre minuscule
            !preg_match('/[0-9]/', $value) || // Vérifie la présence d'un chiffre
            !preg_match('/[\W_]/', $value) || // Vérifie la présence d'un symbole
            strlen($value) < 8                // Vérifie la longueur minimale
        ) {
            $fail('Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule, un chiffre, et un caractère spécial.');
        }
    }
}
