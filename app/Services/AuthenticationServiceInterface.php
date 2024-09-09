<?php

namespace App\Services;

interface AuthenticationServiceInterface
{
    public function authenticate(array $credentials);

    public function logout();
}