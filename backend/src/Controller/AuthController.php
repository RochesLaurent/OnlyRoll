<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['pseudo'])) {
            return $this->json(['error' => 'Missing fields'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPseudo($data['pseudo']);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
            ],
        ], 201);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/debug-login', name: 'api_debug_login', methods: ['POST'])]
    public function debugLogin(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email'], $data['password'])) {
                return $this->json(['error' => 'Missing fields'], 400);
            }

            // Étape 1: Chercher l'utilisateur
            $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }

            // Étape 2: Vérifier le mot de passe
            $isValid = $passwordHasher->isPasswordValid($user, $data['password']);
            if (!$isValid) {
                return $this->json(['error' => 'Invalid password'], 401);
            }

            // Étape 3: Vérifier que l'utilisateur est vérifié
            if (!$user->isVerified()) {
                return $this->json(['error' => 'Account not verified'], 401);
            }

            return $this->json([
                'success' => true,
                'message' => 'User validated successfully - JWT should work',
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'user_pseudo' => $user->getPseudo(),
                'user_verified' => $user->isVerified(),
                'user_roles' => $user->getRoles(),
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
