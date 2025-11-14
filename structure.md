# Structure du Projet OnlyRoll

Cette structure représente l'arborescence complète du projet.

```
OnlyRoll/
├── README.md
├── structure.md
├── LICENSE
├── .gitignore
├── check-ci.ps1
├── .claude.md
├── .claude/                        # Configuration Claude Code
│
├── docs/                           # Documentation du projet
│
├── .github/
│   └── workflows/
│       └── ci.yml
│
├── backend/
│   ├── bin/
│   ├── config/
│   │   ├── packages/
│   │   │   ├── cache.yaml
│   │   │   ├── doctrine_migrations.yaml
│   │   │   ├── doctrine.yaml
│   │   │   ├── framework.yaml
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   ├── mercure.yaml
│   │   │   ├── nelmio_cors.yaml
│   │   │   ├── property_info.yaml
│   │   │   ├── routing.yaml
│   │   │   ├── serializer.yaml
│   │   │   ├── validator.yaml
│   │   │   └── security.yaml
│   │   ├── jwt/
│   │   ├── routes/
│   │   │   ├── framework.yaml
│   │   │   └── security.yaml
│   │   ├── bundles.php
│   │   ├── preload.php
│   │   ├── services.yaml
│   │   └── routes.yaml
│   │
│   ├── public/
│   │   ├── uploads/
│   │   │   ├── avatars/
│   │   │   ├── maps/
│   │   │   ├── tokens/
│   │   │   └── .gitkeep
│   │   └── index.php
│   │
│   ├── src/
│   │   ├── EventListener/
│   │   │   └── CorsListener.php
│   │   │
│   │   ├── EventSubscriber/
│   │   │   └── AuthenticationSuccessSubscriber.php
│   │   │
│   │   ├── Security/
│   │   │   └── JwtCookieAuthenticator.php
│   │   │
│   │   ├── DataFixtures/
│   │   │   ├── AppFixtures.php
│   │   │   └── GameFixtures.php
│   │   │
│   │   ├── Controller/
│   │   │   ├── AuthController.php
│   │   │   ├── ChatController.php
│   │   │   ├── GameController.php
│   │   │   ├── MapController.php
│   │   │   ├── PresenceController.php
│   │   │   └── TokenController.php
│   │   │
│   │   ├── Enum/
│   │   │   ├── GameStatus.php
│   │   │   ├── PlayerRole.php
│   │   │   └── PlayerStatus.php
│   │   │
│   │   ├── Entity/
│   │   │   ├── User.php
│   │   │   ├── Game.php
│   │   │   ├── GameMap.php
│   │   │   ├── GameMessage.php
│   │   │   ├── GameToken.php
│   │   │   └── GamePlayer.php
│   │   │
│   │   ├── Repository/
│   │   │   ├── GamePlayerRepository.php
│   │   │   ├── GameRepository.php
│   │   │   ├── GameMapRepository.php
│   │   │   ├── GameTokenRepository.php
│   │   │   ├── GameMessageRepository.php
│   │   │   └── UserRepository.php
│   │   │
│   │   ├── Exception/
│   │   │   └── Game/
│   │   │       ├── GameException.php
│   │   │       ├── GameFullException.php
│   │   │       ├── InvalidPasswordException.php
│   │   │       ├── AccessDeniedException.php
│   │   │       └── GameNotFoundException.php
│   │   │
│   │   ├── DTO/
│   │   │   ├── Chat/
│   │   │   │   └── SendMessageDTO.php
│   │   │   ├── Map/
│   │   │   │   ├── CreateMapDTO.php
│   │   │   │   └── UpdateMapDTO.php
│   │   │   ├── Token/
│   │   │   │   ├── CreateTokenDTO.php
│   │   │   │   └── MoveTokenDTO.php
│   │   │   ├── Game/
│   │   │   │   ├── UpdateGameDTO.php
│   │   │   │   ├── JoinGameDTO.php
│   │   │   │   ├── GameFilterDTO.php
│   │   │   │   └── CreateGameDTO.php
│   │   │   └── Auth/
│   │   │       ├── LoginRequestDTO.php
│   │   │       ├── RegisterRequestDTO.php
│   │   │       └── UserResponseDTO.php
│   │   │
│   │   └── Service/
│   │       ├── ChatService.php
│   │       ├── DtoValidatorService.php
│   │       ├── FileUploader.php
│   │       ├── GameService.php
│   │       ├── MapService.php
│   │       ├── MercurePublisher.php
│   │       ├── PresenceService.php
│   │       └── TokenService.php
│   │
│   ├── tests/
│   │   ├── Functional/
│   │   │   └── Controller/
│   │   │       ├── AuthControllerTest.php
│   │   │       ├── ChatControllerTest.php
│   │   │       ├── MapControllerTest.php
│   │   │       ├── TokenControllerTest.php
│   │   │       └── GameControllerTest.php
│   │   │
│   │   ├── Unit/
│   │   │   ├── Entity/
│   │   │   │   ├── GameMapTest.php
│   │   │   │   ├── GameMessageTest.php
│   │   │   │   ├── GamePlayerTest.php
│   │   │   │   ├── GameTest.php
│   │   │   │   ├── GameTokenTest.php
│   │   │   │   └── UserTest.php
│   │   │   │
│   │   │   └── Service/
│   │   │       ├── ChatServiceTest.php
│   │   │       ├── DtoValidatorServiceTest.php
│   │   │       ├── FileUploadServiceTest.php
│   │   │       ├── MapServiceTest.php
│   │   │       ├── MercurePublisherServiceTest.php
│   │   │       ├── TokenServiceTest.php
│   │   │       └── GameServiceTest.php
│   │   │
│   │   └── bootstrap.php
│   │
│   ├── migrations/
│   ├── composer.json
│   ├── phpstan.dist.neon
│   ├── .php-cs-fixer.dist.php
│   ├── phpunit.dist.xml
│   └── .env
│
└── frontend/
    ├── src/
    │   ├── components/
    │   │   ├── auth/
    │   │   │   ├── LoginForm.vue
    │   │   │   └── RegisterForm.vue
    │   │   │
    │   │   ├── common/
    │   │   │   ├── FeatureCard.vue
    │   │   │   ├── KonamiVictory.vue
    │   │   │   └── UserProfileBadge.vue
    │   │   │
    │   │   ├── game/
    │   │   │   ├── ChatPanel.vue
    │   │   │   ├── CreateGameModal.vue
    │   │   │   ├── CreateTokenModal.vue
    │   │   │   ├── DiceRoller.vue
    │   │   │   ├── EmptyMapState.vue
    │   │   │   ├── GameCard.vue
    │   │   │   ├── GameHeader.vue
    │   │   │   ├── GameMap.vue
    │   │   │   ├── JoinGameModal.vue
    │   │   │   ├── MapToolbar.vue
    │   │   │   ├── PlayersList.vue
    │   │   │   └── UploadMapModal.vue
    │   │   │
    │   │   └── dashboard/
    │   │       └── DashboardCard.vue
    │   │
    │   ├── composables/
    │   │   ├── useAuth.ts
    │   │   ├── useFormValidation.ts
    │   │   ├── useKonamiCode.ts
    │   │   ├── useMercure.ts
    │   │   └── usePagination.ts
    │   │
    │   ├── layouts/
    │   │   └── AuthLayout.vue
    │   │
    │   ├── router/
    │   │   └── index.ts
    │   │
    │   ├── services/
    │   │   ├── mercure.ts
    │   │   └── api/
    │   │       ├── apiClient.ts
    │   │       ├── authApi.ts
    │   │       ├── chatApi.ts
    │   │       ├── gameApi.ts
    │   │       ├── index.ts
    │   │       ├── mapApi.ts
    │   │       ├── presenceApi.ts
    │   │       └── tokenApi.ts
    │   │
    │   ├── stores/
    │   │   ├── auth.ts
    │   │   ├── chatStore.ts
    │   │   ├── game.ts
    │   │   ├── mapStore.ts
    │   │   └── presenceStore.ts
    │   │
    │   ├── styles/
    │   │   └── tailwind.css
    │   │
    │   ├── utils/
    │   │   ├── errorHelpers.ts
    │   │   └── logger.ts
    │   │
    │   ├── types/
    │   │   ├── auth.ts
    │   │   ├── websocket.ts
    │   │   └── game.ts
    │   │
    │   ├── views/
    │   │   ├── auth/
    │   │   │   ├── LoginView.vue
    │   │   │   ├── RegisterSuccessView.vue
    │   │   │   └── RegisterView.vue
    │   │   │
    │   │   ├── dashboard/
    │   │   │   └── DashboardView.vue
    │   │   │
    │   │   ├── games/
    │   │   │   ├── GameListView.vue
    │   │   │   └── GamePlayView.vue
    │   │   │
    │   │   ├── HomeView.vue
    │   │   └── NotFoundView.vue
    │   │
    │   ├── App.vue
    │   └── main.ts
    │
    ├── public/
    │   └── sounds/
    │       ├── konami.mp3
    │       └── README.md
    │
    ├── e2e/
    │   ├── tsconfig.json
    │   └── vue.spec.ts
    │
    ├── tests/
    │   └── unit/
    │       ├── components/
    │       │   └── auth/
    │       │       ├── LoginForm.spec.ts
    │       │       └── RegisterForm.spec.ts
    │       │
    │       ├── composables/
    │       │   ├── useAuth.spec.ts
    │       │   ├── useMercure.spec.ts
    │       │   ├── useFormValidation.spec.ts
    │       │   └── usePagination.spec.ts
    │       │
    │       ├── stores/
    │       │   ├── authStore.spec.ts
    │       │   ├── chatStore.spec.ts
    │       │   ├── gameStore.spec.ts
    │       │   └── mapStore.spec.ts
    │       │
    │       └── utils/
    │           ├── errorHelpers.spec.ts
    │           └── logger.spec.ts
    │
    ├── package.json
    ├── .env
    ├── env.d.ts
    ├── tsconfig.json
    ├── tsconfig.app.json
    ├── tsconfig.node.json
    ├── tsconfig.vitest.json
    ├── vitest.config.ts
    ├── playwright.config.ts
    ├── tailwind.config.js
    └── vite.config.ts
```

## Maintenance

**IMPORTANT** : Ce fichier doit être mis à jour à chaque fois qu'un fichier est créé, déplacé ou supprimé dans le projet.
