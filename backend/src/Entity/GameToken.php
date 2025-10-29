<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameTokenRepository::class)]
#[ORM\Table(name: 'game_token')]
#[ORM\HasLifecycleCallbacks]
class GameToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'token_id')]
    #[Groups(['token:list', 'token:read', 'map:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameMap::class)]
    #[ORM\JoinColumn(
        name: 'map_id',
        referencedColumnName: 'map_id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[Groups(['token:read'])]
    private ?GameMap $map = null;

    #[ORM\Column(name: 'token_name', type: Types::STRING, length: 250)]
    #[Assert\NotBlank(message: 'Le nom du token est obligatoire')]
    #[Assert\Length(
        min: 1,
        max: 250,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractère',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères',
    )]
    #[Groups(['token:list', 'token:read', 'token:write', 'map:read'])]
    private ?string $name = null;

    #[ORM\Column(name: 'token_type', type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['character', 'monster', 'npc', 'object'],
        message: 'Le type doit être "character", "monster", "npc" ou "object"',
    )]
    #[Groups(['token:list', 'token:read', 'token:write', 'map:read'])]
    private ?string $type = null;

    #[ORM\Column(name: 'token_image_url', type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Url(message: 'L\'URL de l\'image n\'est pas valide')]
    #[Groups(['token:read', 'token:write'])]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'token_x', type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'La position X doit être positive ou zéro')]
    #[Groups(['token:list', 'token:read', 'token:write', 'map:read'])]
    private int $x = 0;

    #[ORM\Column(name: 'token_y', type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'La position Y doit être positive ou zéro')]
    #[Groups(['token:list', 'token:read', 'token:write', 'map:read'])]
    private int $y = 0;

    #[ORM\Column(name: 'token_size', type: Types::DECIMAL, precision: 3, scale: 1)]
    #[Assert\Range(
        min: 0.1,
        max: 10.0,
        notInRangeMessage: 'La taille doit être entre {{ min }} et {{ max }}',
    )]
    #[Groups(['token:read', 'token:write', 'map:read'])]
    private float $size = 1.0;

    #[ORM\Column(name: 'token_rotation', type: Types::INTEGER)]
    #[Assert\Range(
        min: 0,
        max: 359,
        notInRangeMessage: 'La rotation doit être entre {{ min }}° et {{ max }}°',
    )]
    #[Groups(['token:read', 'token:write'])]
    private int $rotation = 0;

    #[ORM\Column(name: 'token_is_visible', type: Types::BOOLEAN)]
    #[Groups(['token:list', 'token:read', 'token:write', 'map:read'])]
    private bool $isVisible = true;

    #[ORM\Column(name: 'token_is_locked', type: Types::BOOLEAN)]
    #[Groups(['token:read', 'token:write'])]
    private bool $isLocked = false;

    #[ORM\Column(name: 'token_layer', type: Types::STRING, length: 20)]
    #[Assert\Choice(
        choices: ['background', 'objects', 'tokens', 'effects'],
        message: 'Le calque doit être "background", "objects", "tokens" ou "effects"',
    )]
    #[Groups(['token:read', 'token:write'])]
    private string $layer = 'tokens';

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'token_settings', type: Types::JSON, nullable: true)]
    #[Groups(['token:read', 'token:write'])]
    private ?array $settings = null;

    #[ORM\Column(name: 'token_created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['token:read'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'token_updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // Méthodes métier

    public function move(int $x, int $y): static
    {
        if (!$this->isLocked) {
            $this->x = $x;
            $this->y = $y;
        }

        return $this;
    }

    public function rotate(int $degrees): static
    {
        if (!$this->isLocked) {
            $this->rotation = ($this->rotation + $degrees) % 360;
            if ($this->rotation < 0) {
                $this->rotation += 360;
            }
        }

        return $this;
    }

    public function show(): static
    {
        $this->isVisible = true;

        return $this;
    }

    public function hide(): static
    {
        $this->isVisible = false;

        return $this;
    }

    public function lock(): static
    {
        $this->isLocked = true;

        return $this;
    }

    public function unlock(): static
    {
        $this->isLocked = false;

        return $this;
    }

    public function isAt(int $x, int $y): bool
    {
        return $this->x === $x && $this->y === $y;
    }

    /**
     * @return array{x: int, y: int}
     */
    #[Groups(['token:read'])]
    public function getPosition(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }

    /**
     * @return array{x: float, y: float}
     */
    #[Groups(['token:read'])]
    public function getCenterPosition(): array
    {
        return [
            'x' => $this->x + ($this->size / 2),
            'y' => $this->y + ($this->size / 2),
        ];
    }

    public function occupiesCell(int $cellX, int $cellY): bool
    {
        $sizeInCells = (int) ceil($this->size);

        return $cellX >= $this->x
            && $cellX < $this->x + $sizeInCells
            && $cellY >= $this->y
            && $cellY < $this->y + $sizeInCells;
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMap(): ?GameMap
    {
        return $this->map;
    }

    public function setMap(?GameMap $map): static
    {
        $this->map = $map;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function setX(int $x): static
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function setY(int $y): static
    {
        $this->y = $y;

        return $this;
    }

    public function getSize(): float
    {
        return $this->size;
    }

    public function setSize(float $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getRotation(): int
    {
        return $this->rotation;
    }

    public function setRotation(int $rotation): static
    {
        $this->rotation = $rotation;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): static
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    public function getLayer(): string
    {
        return $this->layer;
    }

    public function setLayer(string $layer): static
    {
        $this->layer = $layer;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    public function setSettings(?array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
