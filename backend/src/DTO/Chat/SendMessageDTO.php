<?php

declare(strict_types=1);

namespace App\DTO\Chat;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour l'envoi d'un message dans le chat.
 */
final class SendMessageDTO
{
    #[Assert\NotBlank(message: 'Le type de message est obligatoire.')]
    #[Assert\Choice(
        choices: ['chat', 'emote', 'whisper', 'system', 'dice_roll'],
        message: 'Le type doit être "chat", "emote", "whisper", "system" ou "dice_roll".',
    )]
    public string $type;

    #[Assert\NotBlank(message: 'Le contenu du message est obligatoire.')]
    #[Assert\Length(
        min: 1,
        max: 5000,
        minMessage: 'Le message doit faire au moins {{ limit }} caractère.',
        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères.',
    )]
    public string $content;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $diceResult = null;

    #[Assert\Type(type: 'bool', message: 'Le champ "isInCharacter" doit être un booléen.')]
    public bool $isInCharacter = false;

    /**
     * ID du destinataire pour les chuchotements (whisper).
     */
    #[Assert\Positive(message: 'L\'ID du destinataire doit être un nombre positif.')]
    public ?int $recipientId = null;
}
