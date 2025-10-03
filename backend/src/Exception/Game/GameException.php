<?php

namespace App\Exception\Game;

/**
 * Classe de base pour toutes les exceptions liées aux parties.
 * Permet de catcher facilement toutes les exceptions Game d'un coup.
 */
class GameException extends \RuntimeException
{
    // Pas besoin d'override le constructeur ici
    // Les enfants gèrent leurs propres messages/codes
}