<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la connexion d'un utilisateur.
 */
final class LoginRequestDTO
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    public string $password;
}
