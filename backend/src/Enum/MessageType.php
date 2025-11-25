<?php

namespace App\Enum;

/**
 * Énumération des types de messages dans une partie.
 */
enum MessageType: string
{
    case CHAT = 'chat';           // Message de discussion standard
    case EMOTE = 'emote';         // Message d'action/émotion
    case WHISPER = 'whisper';     // Message privé/chuchoté
    case SYSTEM = 'system';       // Message système
    case DICE_ROLL = 'dice_roll'; // Résultat de jet de dés

    /**
     * Retourne le label français du type de message.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CHAT => 'Discussion',
            self::EMOTE => 'Action',
            self::WHISPER => 'Chuchotement',
            self::SYSTEM => 'Système',
            self::DICE_ROLL => 'Jet de dés',
        };
    }

    /**
     * Vérifie si le message est un jet de dés.
     */
    public function isDiceRoll(): bool
    {
        return $this === self::DICE_ROLL;
    }

    /**
     * Vérifie si le message est un message système.
     */
    public function isSystemMessage(): bool
    {
        return $this === self::SYSTEM;
    }

    /**
     * Vérifie si le message est un chuchotement.
     */
    public function isWhisper(): bool
    {
        return $this === self::WHISPER;
    }

    /**
     * Vérifie si le message est une action/émotion.
     */
    public function isEmote(): bool
    {
        return $this === self::EMOTE;
    }

    /**
     * Vérifie si le message est un chat standard.
     */
    public function isChat(): bool
    {
        return $this === self::CHAT;
    }

    /**
     * Vérifie si le message peut être vu par tout le monde.
     */
    public function isPublic(): bool
    {
        return match ($this) {
            self::CHAT, self::EMOTE, self::SYSTEM, self::DICE_ROLL => true,
            self::WHISPER => false,
        };
    }

    /**
     * Vérifie si le message nécessite un formatage spécial.
     */
    public function needsSpecialFormatting(): bool
    {
        return match ($this) {
            self::EMOTE, self::SYSTEM, self::DICE_ROLL => true,
            self::CHAT, self::WHISPER => false,
        };
    }
}
