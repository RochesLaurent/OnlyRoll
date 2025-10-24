<?php

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequestDTO
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters')]
    public string $email;

    #[Assert\NotBlank(message: 'Pseudo is required')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Pseudo must be at least {{ limit }} characters',
        maxMessage: 'Pseudo cannot be longer than {{ limit }} characters',
    )]
    public string $pseudo;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain at least one uppercase letter, one lowercase letter, and one number',
    )]
    public string $password;
}
