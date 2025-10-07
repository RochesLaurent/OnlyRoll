<?php

namespace App\Entity;

use App\Enum\GameStatus;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'game')]
#[ORM\HasLifecycleCallbacks]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'game_id')]
    #[Groups(['game:list', 'game:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'game_name', type: Types::STRING, length: 250)]
    #[Assert\NotBlank(message: 'Le nom de la partie est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 250,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['game:list', 'game:read', 'game:write'])]
    private ?string $name = null;

    #[ORM\Column(name: 'game_description', type: Types::TEXT, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'game_master_id',
        referencedColumnName: 'user_id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    #[Groups(['game:list', 'game:read'])]
    private ?User $gameMaster = null;

    #[ORM\Column(name: 'game_status', enumType: GameStatus::class)]
    #[Groups(['game:list', 'game:read'])]
    private GameStatus $status = GameStatus::PREPARATION;

    #[ORM\Column(name: 'game_max_players', type: Types::INTEGER)]
    #[Assert\Range(min: 1, max: 20)]
    #[Groups(['game:read', 'game:write'])]
    private int $maxPlayers = 6;

    #[ORM\Column(name: 'game_is_public', type: Types::BOOLEAN)]
    #[Groups(['game:list', 'game:read', 'game:write'])]
    private bool $isPublic = false;

    #[ORM\Column(name: 'game_password', type: Types::STRING, length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(name: 'game_invite_code', type: Types::STRING, length: 10, unique: true)]
    #[Groups(['game:list', 'game:read'])]
    private ?string $inviteCode = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'game_settings', type: Types::JSON, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?array $settings = null;

    /**
     * @var Collection<int, GamePlayer>
     */
    #[ORM\OneToMany(
        targetEntity: GamePlayer::class,
        mappedBy: 'game',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups(['game:read'])]
    private Collection $gamePlayers;

    #[ORM\Column(name: 'game_created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['game:list', 'game:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'game_updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'game_started_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['game:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(name: 'game_completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['game:read'])]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->gamePlayers = new ArrayCollection();
        $this->inviteCode = $this->generateInviteCode();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateInviteCode(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 8; ++$i) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }

    // Méthodes métier

    public function canBeViewedBy(User $user): bool
    {
        if ($this->isPublic) {
            return true;
        }

        $userId = $user->getId();
        if (null === $userId) {
            return false;
        }

        return $this->gamePlayers->exists(
            fn ($key, GamePlayer $player) => $player->getUser()?->getId() === $userId
        );
    }

    public function isGameMaster(User $user): bool
    {
        $gameMasterId = $this->gameMaster?->getId();
        $userId = $user->getId();

        if (null === $gameMasterId || null === $userId) {
            return false;
        }

        return $gameMasterId === $userId;
    }

    /**
     * Récupère le GamePlayer d'un utilisateur dans cette partie.
     */
    public function getPlayerByUser(User $user): ?GamePlayer
    {
        $userId = $user->getId();

        if (null === $userId) {
            return null;
        }

        foreach ($this->gamePlayers as $gamePlayer) {
            if ($gamePlayer->getUser()?->getId() === $userId) {
                return $gamePlayer;
            }
        }

        return null;
    }

    /**
     * Vérifie si un utilisateur est dans cette partie.
     */
    public function hasPlayer(User $user): bool
    {
        return null !== $this->getPlayerByUser($user);
    }

    public function getActivePlayersCount(): int
    {
        return $this->gamePlayers->filter(
            fn (GamePlayer $player) => $player->getStatus()->isParticipating()
        )->count();
    }

    public function isFull(): bool
    {
        return $this->getActivePlayersCount() >= $this->maxPlayers;
    }

    #[Groups(['game:read'])]
    public function getCurrentPlayersCount(): int
    {
        return $this->getActivePlayersCount();
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
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

    public function getGameMaster(): ?User
    {
        return $this->gameMaster;
    }

    public function setGameMaster(?User $gameMaster): static
    {
        $this->gameMaster = $gameMaster;

        return $this;
    }

    public function getStatus(): GameStatus
    {
        return $this->status;
    }

    public function setStatus(GameStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
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

    /**
     * @return Collection<int, GamePlayer>
     */
    public function getGamePlayers(): Collection
    {
        return $this->gamePlayers;
    }

    public function addGamePlayer(GamePlayer $gamePlayer): static
    {
        if (!$this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers->add($gamePlayer);
            $gamePlayer->setGame($this);
        }

        return $this;
    }

    public function removeGamePlayer(GamePlayer $gamePlayer): static
    {
        if ($this->gamePlayers->removeElement($gamePlayer)) {
            if ($gamePlayer->getGame() === $this) {
                $gamePlayer->setGame(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
