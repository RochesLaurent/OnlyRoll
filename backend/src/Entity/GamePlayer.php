<?php

namespace App\Entity;

use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use App\Repository\GamePlayerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GamePlayerRepository::class)]
#[ORM\Table(name: 'game_player')]
#[ORM\UniqueConstraint(name: 'unique_game_user', columns: ['game_id', 'user_id'])]
#[ORM\HasLifecycleCallbacks]
class GamePlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'game_player_id')]
    #[Groups(['game:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(
        name: 'game_id',
        referencedColumnName: 'game_id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'user_id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['game:read'])]
    private ?User $user = null;

    #[ORM\Column(name: 'game_player_role', enumType: PlayerRole::class)]
    #[Groups(['game:read'])]
    private PlayerRole $role = PlayerRole::PLAYER;

    #[ORM\Column(name: 'game_player_status', enumType: PlayerStatus::class)]
    #[Groups(['game:read'])]
    private PlayerStatus $status = PlayerStatus::PENDING;

    #[ORM\Column(name: 'game_player_joined_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['game:read'])]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(name: 'game_player_left_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $leftAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->joinedAt === null) {
            $this->joinedAt = new \DateTimeImmutable();
        }
    }

    // Méthodes métier
    
    public function canEdit(): bool
    {
        return $this->role->canEdit();
    }

    public function isParticipating(): bool
    {
        return $this->status->isParticipating();
    }

    public function canReactivate(): bool
    {
        return $this->status->canReactivate();
    }

    public function leave(): void
    {
        $this->status = PlayerStatus::LEFT;
        $this->leftAt = new \DateTimeImmutable();
    }

    public function kick(): void
    {
        $this->status = PlayerStatus::KICKED;
        $this->leftAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->status = PlayerStatus::ACTIVE;
        $this->leftAt = null;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRole(): PlayerRole
    {
        return $this->role;
    }

    public function setRole(PlayerRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getStatus(): PlayerStatus
    {
        return $this->status;
    }

    public function setStatus(PlayerStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLeftAt(): ?\DateTimeImmutable
    {
        return $this->leftAt;
    }

    public function setLeftAt(?\DateTimeImmutable $leftAt): static
    {
        $this->leftAt = $leftAt;
        return $this;
    }
}