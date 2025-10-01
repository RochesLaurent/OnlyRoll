<?php

namespace App\Controller;

use App\Dto\Input\LoginRequestDto;
use App\Dto\Input\RegisterRequestDto;
use App\Dto\Output\UserResponseDto;
use App\Entity\User;
use App\Service\DtoValidatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private DtoValidatorService $dtoValidator,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        // Validation du DTO
        ['dto' => $dto, 'errors' => $errors] = $this->dtoValidator->validateDto(
            $request->getContent(),
            RegisterRequestDto::class
        );

        if ($errors) {
            return $errors;
        }

        // Vérifier si l'email existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $dto->getEmail()]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

        // Vérifier si le pseudo existe déjà
        $existingPseudo = $em->getRepository(User::class)->findOneBy(['pseudo' => $dto->getPseudo()]);
        if ($existingPseudo) {
            return $this->json(['error' => 'Pseudo already exists'], 409);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail($dto->getEmail());
        $user->setPseudo($dto->getPseudo());
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $hashedPassword = $passwordHasher->hashPassword($user, $dto->getPassword());
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        // Réponse avec DTO
        $userResponse = UserResponseDto::fromEntity($user);

        return new JsonResponse([
            'message' => 'User created successfully',
            'user' => json_decode($this->serializer->serialize($userResponse, 'json', ['groups' => 'user:read'])),
        ], 201);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userResponse = UserResponseDto::fromEntity($user);

        return new JsonResponse(
            json_decode($this->serializer->serialize($userResponse, 'json', ['groups' => 'user:read']))
        );
    }

    #[Route('/api/debug-login', name: 'api_debug_login', methods: ['POST'])]
    public function debugLogin(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        // Validation du DTO
        ['dto' => $dto, 'errors' => $errors] = $this->dtoValidator->validateDto(
            $request->getContent(),
            LoginRequestDto::class
        );

        if ($errors) {
            return $errors;
        }

        try {
            // Étape 1: Chercher l'utilisateur
            $user = $em->getRepository(User::class)->findOneBy(['email' => $dto->getEmail()]);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }

            // Étape 2: Vérifier le mot de passe
            $isValid = $passwordHasher->isPasswordValid($user, $dto->getPassword());
            if (!$isValid) {
                return $this->json(['error' => 'Invalid password'], 401);
            }

            // Étape 3: Vérifier que l'utilisateur est vérifié
            if (!$user->isVerified()) {
                return $this->json(['error' => 'Account not verified'], 401);
            }

            $userResponse = UserResponseDto::fromEntity($user);

            return new JsonResponse([
                'success' => true,
                'message' => 'User validated successfully - JWT should work',
                'user' => json_decode($this->serializer->serialize($userResponse, 'json', ['groups' => 'user:read'])),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Exception occurred',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}