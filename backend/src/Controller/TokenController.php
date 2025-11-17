<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Token\CreateTokenDTO;
use App\DTO\Token\MoveTokenDTO;
use App\Repository\GameMapRepository;
use App\Repository\GameRepository;
use App\Repository\GameTokenRepository;
use App\Service\FileUploader;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur de gestion des tokens sur les cartes.
 */
#[Route('/api/games/{gameId}/maps/{mapId}/tokens', name: 'api_token_')]
#[IsGranted('ROLE_USER')]
final class TokenController extends AbstractController
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly GameRepository $gameRepository,
        private readonly GameMapRepository $mapRepository,
        private readonly GameTokenRepository $tokenRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileUploader $fileUploader,
    ) {
    }

    /**
     * Parse multipart/form-data for PUT/PATCH requests.
     * PHP only automatically parses multipart data for POST requests.
     */
    private function parseMultipartFormData(Request $request): ?JsonResponse
    {
        $contentType = $request->headers->get('Content-Type') ?? '';

        // Extraire la boundary du Content-Type
        if (!preg_match('/boundary=(.+)$/i', $contentType, $matches)) {
            return null;
        }

        $boundary = trim($matches[1]);
        // IMPORTANT: Ne pas consommer le stream - le rendre réutilisable
        $rawData = $request->getContent(false);

        // Découper le contenu en parties
        $parts = preg_split('/--' . preg_quote($boundary, '/') . '/', $rawData);

        if (false === $parts) {
            return $this->json(['error' => 'Erreur lors du parsing du contenu multipart'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--') {
                continue;
            }

            // Séparer les headers du body avec \r\n\r\n ou \n\n
            $divider = str_contains($part, "\r\n\r\n") ? "\r\n\r\n" : "\n\n";
            $sections = explode($divider, $part, 2);

            if (\count($sections) < 2) {
                continue;
            }

            [$headers, $body] = $sections;

            // Parser le nom du champ depuis Content-Disposition
            if (preg_match('/name="([^"]+)"/', $headers, $nameMatch)) {
                $name = $nameMatch[1];

                // Vérifier si c'est un fichier
                if (preg_match('/filename="([^"]*)"/', $headers, $filenameMatch)) {
                    // C'est un fichier
                    $filename = $filenameMatch[1];

                    if (!empty($filename)) {
                        // Extraire le type MIME
                        $mimeType = 'application/octet-stream';
                        if (preg_match('/Content-Type:\s*(.+)/i', $headers, $mimeMatch)) {
                            $mimeType = trim($mimeMatch[1]);
                        }

                        // Créer un fichier temporaire
                        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_');
                        $bodyContent = rtrim($body, "\r\n");
                        file_put_contents($tmpPath, $bodyContent);

                        // Créer un UploadedFile
                        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                            $tmpPath,
                            $filename,
                            $mimeType,
                            null,
                            true, // test mode = true pour éviter les vérifications de sécurité
                        );

                        // Ajouter au files bag
                        $request->files->set($name, $uploadedFile);
                    }
                }
                else {
                    // C'est un champ texte normal
                    $value = rtrim($body, "\r\n");
                    $request->request->set($name, $value);
                }
            }
        }

        return null;
    }

    /**
     * Liste tous les tokens d'une carte.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $gameId, int $mapId, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($mapId);

        // Vérification null-safety pour PHPStan
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        // Les joueurs normaux ne voient que les tokens visibles
        $tokens = $game->isGameMaster($user)
            ? $this->tokenService->getTokensByMap($map)
            : $this->tokenService->getTokensByMap($map, $user);

        return $this->json(
            $tokens,
            Response::HTTP_OK,
            [],
            ['groups' => 'token:list'],
        );
    }

    /**
     * Détails d'un token.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $token = $this->tokenRepository->find($id);

        // Vérification null-safety pour PHPStan
        $tokenMap = $token?->getMap();
        if (!$token || !$tokenMap || $tokenMap->getId() !== $mapId) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        // Vérifier si le token est visible pour les joueurs normaux
        if (!$game->isGameMaster($user) && !$token->isVisible()) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->json(
            $token,
            Response::HTTP_OK,
            [],
            ['groups' => 'token:read'],
        );
    }

    /**
     * Créer un nouveau token.
     * Supporte à la fois JSON et multipart/form-data pour l'upload d'image.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $gameId, int $mapId, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($mapId);

        // Vérification null-safety pour PHPStan
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut créer des tokens'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $imageUrl = null;
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                $imageUrl = $this->fileUploader->uploadTokenImage($imageFile);
            }

            $contentType = $request->headers->get('Content-Type') ?? '';

            if (str_contains($contentType, 'multipart/form-data')) {
                // Gestion du FormData
                $dto = new CreateTokenDTO();

                $name = $request->request->get('name');
                if (!\is_string($name)) {
                    return $this->json(
                        ['error' => 'Le nom est requis et doit être une chaîne de caractères'],
                        Response::HTTP_BAD_REQUEST,
                    );
                }
                $dto->name = $name;

                $type = $request->request->get('type');
                $dto->type = \is_string($type) ? $type : 'character';

                $dto->x = (int) $request->request->get('x', 0);
                $dto->y = (int) $request->request->get('y', 0);
                $dto->size = (float) $request->request->get('size', 1.0);
                $dto->rotation = (int) $request->request->get('rotation', 0);

                $isVisible = $request->request->get('isVisible');
                $dto->isVisible = $isVisible === 'true' || $isVisible === true;

                $isLocked = $request->request->get('isLocked');
                $dto->isLocked = $isLocked === 'true' || $isLocked === true;

                $layer = $request->request->get('layer');
                $dto->layer = \is_string($layer) ? $layer : 'tokens';

                $dto->imageUrl = $imageUrl;
            }
            else {
                // Gestion du JSON
                $dto = $this->serializer->deserialize(
                    $request->getContent(),
                    CreateTokenDTO::class,
                    'json',
                );

                if ($imageUrl) {
                    $dto->imageUrl = $imageUrl;
                }
            }

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                return $this->json(
                    ['errors' => (string) $errors],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $token = $this->tokenService->createToken($map, $dto);

            return $this->json(
                $token,
                Response::HTTP_CREATED,
                [],
                ['groups' => 'token:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }
    }

    /**
     * Déplacer un token.
     */
    #[Route('/{id}/move', name: 'move', methods: ['POST'])]
    public function move(int $gameId, int $mapId, int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $token = $this->tokenRepository->find($id);

        // Vérification null-safety pour PHPStan
        $tokenMap = $token?->getMap();
        if (!$token || !$tokenMap || $tokenMap->getId() !== $mapId) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        // Vérifier les permissions de contrôle du token
        if (!$this->tokenService->canControlToken($token, $user, $game)) {
            return $this->json(
                ['error' => 'Vous n\'avez pas la permission de déplacer ce token'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            MoveTokenDTO::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(
                ['errors' => (string) $errors],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $token = $this->tokenService->moveToken($token, $dto);

            return $this->json(
                $token,
                Response::HTTP_OK,
                [],
                ['groups' => 'token:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }
    }

    /**
     * Basculer la visibilité d'un token.
     */
    #[Route('/{id}/toggle-visibility', name: 'toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $token = $this->tokenRepository->find($id);

        // Vérification null-safety pour PHPStan
        $tokenMap = $token?->getMap();
        if (!$token || !$tokenMap || $tokenMap->getId() !== $mapId) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut modifier la visibilité'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $token = $this->tokenService->toggleVisibility($token);

            return $this->json(
                $token,
                Response::HTTP_OK,
                [],
                ['groups' => 'token:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Verrouiller/déverrouiller un token.
     */
    #[Route('/{id}/toggle-lock', name: 'toggle_lock', methods: ['POST'])]
    public function toggleLock(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $token = $this->tokenRepository->find($id);

        // Vérification null-safety pour PHPStan
        $tokenMap = $token?->getMap();
        if (!$token || !$tokenMap || $tokenMap->getId() !== $mapId) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut verrouiller des tokens'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $token = $this->tokenService->toggleLock($token);

            return $this->json(
                $token,
                Response::HTTP_OK,
                [],
                ['groups' => 'token:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Gérer les permissions de contrôle d'un token.
     */
    #[Route('/{id}/permissions', name: 'permissions', methods: ['POST'])]
    public function managePermissions(int $gameId, int $mapId, int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $token = $this->tokenRepository->find($id);

        // Vérification null-safety pour PHPStan
        $tokenMap = $token?->getMap();
        if (!$token || !$tokenMap || $tokenMap->getId() !== $mapId) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut modifier les permissions'],
                Response::HTTP_FORBIDDEN,
            );
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? null; // 'add' ou 'remove'
        $userId = $data['userId'] ?? null;

        if (!\in_array($action, ['add', 'remove'], true) || !$userId) {
            return $this->json(
                ['error' => 'Action ou userId invalide'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            if ($action === 'add') {
                $token = $this->tokenService->addControlPermission($token, $userId);
            }
            else {
                $token = $this->tokenService->removeControlPermission($token, $userId);
            }

            return $this->json(
                $token,
                Response::HTTP_OK,
                [],
                ['groups' => 'token:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Mettre à jour un token.
     * Supporte à la fois JSON et multipart/form-data pour l'upload d'image.
     */
    #[Route('/{id}', name: 'update', methods: ['PATCH', 'PUT'])]
    public function update(int $gameId, int $mapId, int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($mapId);
        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $token = $this->tokenRepository->find($id);
        $tokenMap = $token?->getMap();
        if (!$token || !$tokenMap || $tokenMap->getId() !== $mapId) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut modifier des tokens'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $contentType = $request->headers->get('Content-Type') ?? '';

            // Parser le multipart/form-data pour PATCH/PUT AVANT de récupérer les fichiers
            if (str_contains($contentType, 'multipart/form-data') && \in_array($request->getMethod(), ['PUT', 'PATCH'], true)) {
                try {
                    $this->parseMultipartFormData($request);
                }
                catch (Exception $parseException) {
                    error_log('Erreur parsing multipart: ' . $parseException->getMessage());

                    throw $parseException;
                }
            }

            $imageUrl = null;
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($token->getImageUrl()) {
                    $this->fileUploader->deleteFile($token->getImageUrl());
                }
                $imageUrl = $this->fileUploader->uploadTokenImage($imageFile);
            }

            // Mettre à jour uniquement les champs fournis
            if (str_contains($contentType, 'multipart/form-data')) {

                $name = $request->request->get('name');
                if (\is_string($name) && !empty($name)) {
                    $token->setName($name);
                }

                $type = $request->request->get('type');
                if (\is_string($type)) {
                    $token->setType($type);
                }

                $size = $request->request->get('size');
                if (null !== $size) {
                    $token->setSize((float) $size);
                }

                $rotation = $request->request->get('rotation');
                if (null !== $rotation) {
                    $token->setRotation((int) $rotation);
                }

                $x = $request->request->get('x');
                if (null !== $x) {
                    $token->setX((int) $x);
                }

                $y = $request->request->get('y');
                if (null !== $y) {
                    $token->setY((int) $y);
                }

                if ($imageUrl) {
                    $token->setImageUrl($imageUrl);
                }
            }
            else {
                // Gestion JSON
                $data = json_decode($request->getContent(), true);

                if (isset($data['name']) && \is_string($data['name'])) {
                    $token->setName($data['name']);
                }

                if (isset($data['type']) && \is_string($data['type'])) {
                    $token->setType($data['type']);
                }

                if (isset($data['size'])) {
                    $token->setSize((float) $data['size']);
                }

                if (isset($data['rotation'])) {
                    $token->setRotation((int) $data['rotation']);
                }

                if (isset($data['x'])) {
                    $token->setX((int) $data['x']);
                }

                if (isset($data['y'])) {
                    $token->setY((int) $data['y']);
                }

                if ($imageUrl) {
                    $token->setImageUrl($imageUrl);
                }
            }

            $this->entityManager->flush();

            // Recharger le token depuis la BDD pour éviter les problèmes de proxies
            $this->entityManager->refresh($token);

            // Publier l'événement Mercure pour notifier les autres joueurs
            try {
                $this->tokenService->publishTokenUpdate($token);
            }
            catch (Exception $mercureException) {
                // Ignorer silencieusement les erreurs Mercure
            }

            // Retourner une réponse JSON simple
            return new JsonResponse([
                'id' => $token->getId(),
                'name' => $token->getName(),
                'type' => $token->getType(),
                'size' => $token->getSize(),
                'x' => $token->getX(),
                'y' => $token->getY(),
                'rotation' => $token->getRotation(),
                'isVisible' => $token->isVisible(),
                'isLocked' => $token->isLocked(),
                'layer' => $token->getLayer(),
                'imageUrl' => $token->getImageUrl(),
            ], Response::HTTP_OK);
        }
        catch (Exception $e) {
            error_log('ERROR in token update: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Supprimer un token.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $gameId, int $mapId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $token = $this->tokenRepository->find($id);

        // Vérification null-safety pour PHPStan
        $tokenMap = $token?->getMap();
        if (!$token || !$tokenMap || $tokenMap->getId() !== $mapId) {
            return $this->json(
                ['error' => 'Token introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut supprimer des tokens'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $this->tokenService->deleteToken($token);

            return $this->json(
                ['message' => 'Token supprimé avec succès'],
                Response::HTTP_OK,
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
