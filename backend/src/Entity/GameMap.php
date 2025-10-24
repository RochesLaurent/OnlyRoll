<?php

namespace App\Entity;

use App\Repository\GameMapRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameMapRepository::class)]
#[ORM\Table(name: 'game_map')]
#[ORM\HasLifecycleCallbacks]
class GameMap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'map_id')]
    #[Groups(['map:list', 'map:read', 'game:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(
        name: 'game_id',
        referencedColumnName: 'game_id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    #[Groups(['map:read'])]
    private ?Game $game = null;

    #[ORM\Column(name: 'map_name', type: Types::STRING, length: 250)]
    #[Assert\NotBlank(message: 'Le nom de la carte est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 250,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères',
    )]
    #[Groups(['map:list', 'map:read', 'map:write', 'game:read'])]
    private ?string $name = null;

    #[ORM\Column(name: 'map_description', type: Types::TEXT, nullable: true)]
    #[Groups(['map:read', 'map:write'])]
    private ?string $description = null;

    #[ORM\Column(name: 'map_image_url', type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['map:read', 'map:write'])]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'map_grid_size', type: Types::INTEGER)]
    #[Assert\Range(
        min: 10,
        max: 200,
        notInRangeMessage: 'La taille de la grille doit être entre {{ min }} et {{ max }} pixels',
    )]
    #[Groups(['map:read', 'map:write'])]
    private int $gridSize = 50;

    #[ORM\Column(name: 'map_grid_type', type: Types::STRING, length: 20)]
    #[Assert\Choice(
        choices: ['square', 'hex', 'none'],
        message: 'Le type de grille doit être "square", "hex" ou "none"',
    )]
    #[Groups(['map:read', 'map:write'])]
    private string $gridType = 'square';

    #[ORM\Column(name: 'map_width', type: Types::INTEGER)]
    #[Assert\Range(
        min: 5,
        max: 200,
        notInRangeMessage: 'La largeur doit être entre {{ min }} et {{ max }} cases',
    )]
    #[Groups(['map:read', 'map:write'])]
    private int $width = 20;

    #[ORM\Column(name: 'map_height', type: Types::INTEGER)]
    #[Assert\Range(
        min: 5,
        max: 200,
        notInRangeMessage: 'La hauteur doit être entre {{ min }} et {{ max }} cases',
    )]
    #[Groups(['map:read', 'map:write'])]
    private int $height = 20;

    #[ORM\Column(name: 'map_is_active', type: Types::BOOLEAN)]
    #[Groups(['map:list', 'map:read', 'map:write', 'game:read'])]
    private bool $isActive = false;

    /**
     * @var Collection<int, GameToken>
     */
    #[ORM\OneToMany(
        targetEntity: GameToken::class,
        mappedBy: 'map',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[Groups(['map:read'])]
    private Collection $tokens;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'map_settings', type: Types::JSON, nullable: true)]
    #[Groups(['map:read', 'map:write'])]
    private ?array $settings = null;

    #[ORM\Column(name: 'map_created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['map:read'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'map_updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->tokens = new ArrayCollection();
    }

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

    public function getTotalCells(): int
    {
        return $this->width * $this->height;
    }

    public function activate(): static
    {
        $this->isActive = true;

        return $this;
    }

    public function deactivate(): static
    {
        $this->isActive = false;

        return $this;
    }

    #[Groups(['map:read'])]
    public function getDimensions(): string
    {
        return \sprintf('%dx%d', $this->width, $this->height);
    }

    #[Groups(['map:read'])]
    public function getTokensCount(): int
    {
        return $this->tokens->count();
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getGridSize(): int
    {
        return $this->gridSize;
    }

    public function setGridSize(int $gridSize): static
    {
        $this->gridSize = $gridSize;

        return $this;
    }

    public function getGridType(): string
    {
        return $this->gridType;
    }

    public function setGridType(string $gridType): static
    {
        $this->gridType = $gridType;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, GameToken>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function addToken(GameToken $token): static
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens->add($token);
            $token->setMap($this);
        }

        return $this;
    }

    public function removeToken(GameToken $token): static
    {
        if ($this->tokens->removeElement($token)) {
            if ($token->getMap() === $this) {
                $token->setMap(null);
            }
        }

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
