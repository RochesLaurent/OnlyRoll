<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequestDTO
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    public string $password;
}
