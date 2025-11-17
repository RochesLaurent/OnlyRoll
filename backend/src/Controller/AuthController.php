<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Auth\RegisterRequestDTO;
use App\DTO\Auth\UserResponseDTO;
use App\Entity\User;
use App\Service\DtoValidatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Contrôleur d'authentification.
 */
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly DtoValidatorService $dtoValidator,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Enregistre un nouvel utilisateur.
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        // Validation du DTO
        ['dto' => $dto, 'errors' => $errors] = $this->dtoValidator->validateDto(
            $request->getContent(),
            RegisterRequestDTO::class,
        );

        if ($errors) {
            return $errors;
        }

        \assert($dto instanceof RegisterRequestDTO, 'DTO should not be null after validation');

        // Vérifier si l'email existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

        // Vérifier si le pseudo existe déjà
        $existingPseudo = $em->getRepository(User::class)->findOneBy(['pseudo' => $dto->pseudo]);
        if ($existingPseudo) {
            return $this->json(['error' => 'Pseudo already exists'], 409);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail($dto->email);
        $user->setPseudo($dto->pseudo);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true); // Passeras à false lorsque l'emailing sera en place

        $hashedPassword = $passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        // Réponse avec DTO
        $userResponse = UserResponseDTO::fromEntity($user);

        return new JsonResponse([
            'message' => 'User created successfully',
            'user' => json_decode($this->serializer->serialize($userResponse, 'json', ['groups' => 'user:read'])),
        ], 201);
    }

    /**
     * Récupère les informations de l'utilisateur connecté.
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Retour direct sans double sérialisation
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'timezone' => $user->getTimezone(),
            'language' => $user->getLanguage(),
            'avatar' => $user->getAvatar(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()->format('c'),
        ]);
    }

    /**
     * Déconnecte l'utilisateur en supprimant le cookie JWT.
     */
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Créer la réponse
        $response = new JsonResponse(['message' => 'Déconnexion réussie']);

        // On crée un cookie expiré pour le supprimer côté client
        $isProduction = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';

        $response->headers->clearCookie(
            'jwt_token',
            '/',
            null,
            $isProduction,
            true,
            Cookie::SAMESITE_STRICT,
        );

        return $response;
    }
}
