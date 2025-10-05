<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Token\CreateTokenDTO;
use App\DTO\Token\MoveTokenDTO;
use App\Entity\GameMap;
use App\Entity\GameToken;
use App\Repository\GameTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameTokenRepository $tokenRepository,
    ) {
    }

    /**
     * Crée un nouveau token sur une carte.
     */
    public function createToken(GameMap $map, CreateTokenDTO $dto): GameToken
    {
        // Validation: vérifier que les coordonnées sont dans les limites de la carte
        $this->validatePosition($map, $dto->x, $dto->y);

        $token = new GameToken();
        $token->setMap($map);
        $token->setName($dto->name);
        $token->setType($dto->type);
        $token->setImageUrl($dto->imageUrl);
        $token->setX($dto->x);
        $token->setY($dto->y);
        $token->setSize($dto->size);
        $token->setRotation($dto->rotation);
        $token->setIsVisible($dto->isVisible);
        $token->setIsLocked($dto->isLocked);
        $token->setLayer($dto->layer);
        $token->setSettings($dto->settings);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * Déplace un token avec validation snap-to-grid.
     */
    public function moveToken(GameToken $token, int $x, int $y): GameToken
    {
        // Vérifier que le token n'est pas verrouillé
        if ($token->isLocked()) {
            throw new BadRequestHttpException('Ce token est verrouillé et ne peut pas être déplacé.');
        }

        $map = $token->getMap();
        
        if (null === $map) {
            throw new BadRequestHttpException('Le token n\'est associé à aucune carte.');
        }

        // Validation snap-to-grid: les coordonnées doivent être des entiers (cases)
        $this->validatePosition($map, $x, $y);

        // Snap to grid: s'assurer que les coordonnées sont bien des entiers
        $snappedX = (int) round($x);
        $snappedY = (int) round($y);

        $token->move($snappedX, $snappedY);

        $this->entityManager->flush();

        return $token;
    }

    /**
     * Déplace un token avec un DTO.
     */
    public function moveTokenWithDTO(GameToken $token, MoveTokenDTO $dto): GameToken
    {
        $this->moveToken($token, $dto->x, $dto->y);

        // Appliquer la rotation si fournie
        if (null !== $dto->rotation) {
            $token->setRotation($dto->rotation);
            $this->entityManager->flush();
        }

        return $token;
    }

    /**
     * Récupère tous les tokens d'une carte.
     *
     * @return GameToken[]
     */
    public function getTokensByMap(GameMap $map, bool $visibleOnly = false): array
    {
        return $this->tokenRepository->findTokensByMap($map, $visibleOnly);
    }

    /**
     * Met à jour un token.
     */
    public function updateToken(GameToken $token, array $data): GameToken
    {
        if (isset($data['name'])) {
            $token->setName($data['name']);
        }

        if (isset($data['imageUrl'])) {
            $token->setImageUrl($data['imageUrl']);
        }

        if (isset($data['size'])) {
            $token->setSize((float) $data['size']);
        }

        if (isset($data['rotation'])) {
            $token->setRotation((int) $data['rotation']);
        }

        if (isset($data['isVisible'])) {
            $token->setIsVisible((bool) $data['isVisible']);
        }

        if (isset($data['isLocked'])) {
            $token->setIsLocked((bool) $data['isLocked']);
        }

        if (isset($data['layer'])) {
            $token->setLayer($data['layer']);
        }

        if (isset($data['settings'])) {
            $token->setSettings($data['settings']);
        }

        $this->entityManager->flush();

        return $token;
    }

    /**
     * Supprime un token.
     */
    public function deleteToken(GameToken $token): void
    {
        $this->entityManager->remove($token);
        $this->entityManager->flush();
    }

    /**
     * Affiche ou masque un token.
     */
    public function toggleVisibility(GameToken $token): GameToken
    {
        if ($token->isVisible()) {
            $token->hide();
        } else {
            $token->show();
        }

        $this->entityManager->flush();

        return $token;
    }

    /**
     * Verrouille ou déverrouille un token.
     */
    public function toggleLock(GameToken $token): GameToken
    {
        if ($token->isLocked()) {
            $token->unlock();
        } else {
            $token->lock();
        }

        $this->entityManager->flush();

        return $token;
    }

    /**
     * Valide qu'une position est dans les limites de la carte.
     */
    private function validatePosition(GameMap $map, int $x, int $y): void
    {
        if ($x < 0 || $y < 0) {
            throw new BadRequestHttpException('Les coordonnées ne peuvent pas être négatives.');
        }

        if ($x >= $map->getWidth()) {
            throw new BadRequestHttpException(
                sprintf('La position X (%d) dépasse la largeur de la carte (%d).', $x, $map->getWidth())
            );
        }

        if ($y >= $map->getHeight()) {
            throw new BadRequestHttpException(
                sprintf('La position Y (%d) dépasse la hauteur de la carte (%d).', $y, $map->getHeight())
            );
        }
    }
}
