<?php

namespace App\DTO\Game;

use Symfony\Component\Validator\Constraints as Assert;

class GameFilterDTO
{
    /**
     * Recherche globale dans le nom et la description.
     */
    #[Assert\Length(max: 100)]
    public ?string $search = null;

    /**
     * Filtre sur le titre de la partie.
     */
    #[Assert\Length(max: 100)]
    public ?string $title = null;

    /**
     * Filtre sur le pseudo du Game Master.
     */
    #[Assert\Length(max: 50)]
    public ?string $gameMaster = null;

    /**
     * Filtre sur le statut de la partie.
     */
    #[Assert\Choice(
        choices: ['preparation', 'in_progress', 'paused', 'completed', 'archived'],
        message: 'Le statut doit être: preparation, in_progress, paused, completed ou archived'
    )]
    public ?string $status = null;

    /**
     * Numéro de page (commence à 1).
     */
    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    #[Assert\Range(
        min: 1,
        max: 10000,
        notInRangeMessage: 'La page doit être entre {{ min }} et {{ max }}'
    )]
    public int $page = 1;

    /**
     * Nombre d'éléments par page.
     */
    #[Assert\Positive(message: 'La limite doit être un nombre positif')]
    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: 'La limite doit être entre {{ min }} et {{ max }}'
    )]
    public int $limit = 12;
}
