<?php

namespace App\DTO\Auth;

use App\Entity\User;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\Groups;

class UserResponseDTO
{
    #[Groups(['user:read'])]
    private int $id;

    #[Groups(['user:read'])]
    private string $email;

    #[Groups(['user:read'])]
    private string $pseudo;

    /**
     * @var array<string>
     */
    #[Groups(['user:read'])]
    private array $roles;

    #[Groups(['user:read'])]
    private ?string $avatar;

    #[Groups(['user:read'])]
    private string $timezone;

    #[Groups(['user:read'])]
    private string $language;

    #[Groups(['user:read'])]
    private bool $isVerified;

    #[Groups(['user:read'])]
    private DateTimeImmutable $createdAt;

    #[Groups(['user:read'])]
    private ?DateTimeImmutable $lastLogin;

    public static function fromEntity(User $user): self
    {
        $dto = new self();

        // Gestion du cas où l'ID serait null (ne devrait pas arriver en production)
        $userId = $user->getId();
        if (null === $userId) {
            throw new RuntimeException('User ID cannot be null');
        }

        $dto->id = $userId;
        $dto->email = $user->getEmail();
        $dto->pseudo = $user->getPseudo();
        $dto->roles = $user->getRoles();
        $dto->avatar = $user->getAvatar();
        $dto->timezone = $user->getTimezone();
        $dto->language = $user->getLanguage();
        $dto->isVerified = $user->isVerified();
        $dto->createdAt = $user->getCreatedAt();
        $dto->lastLogin = $user->getLastLogin();

        return $dto;
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLogin(): ?DateTimeImmutable
    {
        return $this->lastLogin;
    }
}
