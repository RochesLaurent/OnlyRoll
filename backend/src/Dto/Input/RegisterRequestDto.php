<?php

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequestDto
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters')]
    private string $email;

    #[Assert\NotBlank(message: 'Pseudo is required')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Pseudo must be at least {{ limit }} characters',
        maxMessage: 'Pseudo cannot be longer than {{ limit }} characters'
    )]
    private string $pseudo;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain at least one uppercase letter, one lowercase letter, and one number'
    )]
    private string $password;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
}
