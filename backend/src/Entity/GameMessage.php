<?php

namespace App\Entity;

use App\Repository\GameMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameMessageRepository::class)]
#[ORM\Table(name: 'game_message')]
#[ORM\HasLifecycleCallbacks]
class GameMessage
{
    public const TYPE_CHAT = 'chat';
    public const TYPE_EMOTE = 'emote';
    public const TYPE_WHISPER = 'whisper';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_DICE_ROLL = 'dice_roll';

    public const TYPES = [
        self::TYPE_CHAT,
        self::TYPE_EMOTE,
        self::TYPE_WHISPER,
        self::TYPE_SYSTEM,
        self::TYPE_DICE_ROLL,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'message_id')]
    #[Groups(['message:list', 'message:read', 'game:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(
        name: 'game_id',
        referencedColumnName: 'game_id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['message:read'])]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'user_id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    #[Groups(['message:list', 'message:read', 'game:read'])]
    private ?User $user = null;

    #[ORM\Column(name: 'message_type', type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: self::TYPES,
        message: 'Le type doit être "chat", "emote", "whisper", "system" ou "dice_roll"'
    )]
    #[Groups(['message:list', 'message:read', 'message:write', 'game:read'])]
    private ?string $type = null;

    #[ORM\Column(name: 'message_content', type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu du message est obligatoire')]
    #[Assert\Length(
        min: 1,
        max: 5000,
        minMessage: 'Le message doit faire au moins {{ limit }} caractère',
        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['message:list', 'message:read', 'message:write', 'game:read'])]
    private ?string $content = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'message_dice_result', type: Types::JSON, nullable: true)]
    #[Groups(['message:list', 'message:read', 'message:write'])]
    private ?array $diceResult = null;

    #[ORM\Column(name: 'message_is_ic', type: Types::BOOLEAN)]
    #[Groups(['message:list', 'message:read', 'message:write'])]
    private bool $isInCharacter = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'message_recipient_id',
        referencedColumnName: 'user_id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    #[Groups(['message:read'])]
    private ?User $recipient = null;

    #[ORM\Column(name: 'message_created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['message:list', 'message:read', 'game:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Méthodes métier

    public function isDiceRoll(): bool
    {
        return self::TYPE_DICE_ROLL === $this->type;
    }

    public function isSystemMessage(): bool
    {
        return self::TYPE_SYSTEM === $this->type;
    }

    public function isWhisper(): bool
    {
        return self::TYPE_WHISPER === $this->type;
    }

    public function isEmote(): bool
    {
        return self::TYPE_EMOTE === $this->type;
    }

    public function isChat(): bool
    {
        return self::TYPE_CHAT === $this->type;
    }

    public function canBeSeenBy(User $user): bool
    {
        // Les messages système sont visibles par tous
        if ($this->isSystemMessage()) {
            return true;
        }

        // Les chuchotements ne sont visibles que par l'expéditeur et le destinataire
        if ($this->isWhisper()) {
            $userId = $user->getId();
            $senderId = $this->user?->getId();
            $recipientId = $this->recipient?->getId();

            if (null === $userId || null === $senderId) {
                return false;
            }

            return $userId === $senderId || $userId === $recipientId;
        }

        // Les autres messages sont visibles par tous les joueurs de la partie
        return true;
    }

    #[Groups(['message:read'])]
    public function getDiceTotal(): ?int
    {
        if (!$this->isDiceRoll() || null === $this->diceResult) {
            return null;
        }

        return $this->diceResult['total'] ?? null;
    }

    #[Groups(['message:read'])]
    public function getFormattedContent(): ?string
    {
        if (null === $this->content) {
            return null;
        }

        return match ($this->type) {
            self::TYPE_EMOTE => sprintf('*%s*', $this->content),
            self::TYPE_WHISPER => sprintf('[Chuchotement] %s', $this->content),
            self::TYPE_SYSTEM => sprintf('[Système] %s', $this->content),
            default => $this->content,
        };
    }

    /**
     * @param array<string, mixed> $diceConfig
     * @param array<int>           $results
     */
    public function setDiceRoll(array $diceConfig, array $results, int $total): static
    {
        $this->type = self::TYPE_DICE_ROLL;
        $this->diceResult = [
            'config' => $diceConfig,
            'results' => $results,
            'total' => $total,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ];

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDiceResult(): ?array
    {
        return $this->diceResult;
    }

    /**
     * @param array<string, mixed>|null $diceResult
     */
    public function setDiceResult(?array $diceResult): static
    {
        $this->diceResult = $diceResult;

        return $this;
    }

    public function isInCharacter(): bool
    {
        return $this->isInCharacter;
    }

    public function setIsInCharacter(bool $isInCharacter): static
    {
        $this->isInCharacter = $isInCharacter;

        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
