# Architecture Backend OnlyRoll - Documentation Technique

## Vue d'Ensemble

OnlyRoll est une Virtual Tabletop (VTT) spécialisée pour Dungeons & Dragons 5e, construite avec une architecture backend moderne et scalable utilisant PHP 8.1+ et Symfony 7.1+.

### Stack Technique Principal

- **Framework** : Symfony 7.1+
- **Langage** : PHP 8.1+
- **Base de données** : MySQL 9.0+
- **API** : API Platform 3.2+
- **Authentification** : Lexik JWT Authentication Bundle
- **Cache** : Redis 7.2+
- **WebSocket** : Socket.io avec ReactPHP/Ratchet
- **ORM** : Doctrine ORM

### Principes Architecturaux

L'architecture suit les principes de **Clean Architecture** avec une séparation claire des responsabilités :

```
┌─────────────────────────────────────────────────┐
│                 API Layer                       │
│         (Controllers + Serializers)             │
├─────────────────────────────────────────────────┤
│              Application Layer                  │
│         (Services + Use Cases)                  │
├─────────────────────────────────────────────────┤
│               Domain Layer                      │
│         (Entities + Business Logic)             │
├─────────────────────────────────────────────────┤
│            Infrastructure Layer                 │
│    (Repositories + External Services)           │
└─────────────────────────────────────────────────┘
```

## Structure des Dossiers

```
src/
├── Controller/              # Contrôleurs API REST
│   ├── Auth/                # Authentification
│   ├── Game/                # Gestion des parties
│   ├── User/                # Gestion utilisateurs
│   ├── Wiki/                # Consultation SRD
│   └── WebSocket/           # Endpoints WebSocket
├── Entity/                  # Entités Doctrine
│   ├── User.php             # Utilisateur
│   ├── Game/                # Entités liées aux parties
│   ├── SRD/                 # Entités du SRD D&D
│   └── Combat/              # Système de combat
├── Repository/              # Repositories Doctrine
├── Service/                 # Services métier
│   ├── AuthService.php      # Logique d'authentification
│   ├── GameService.php      # Logique des parties
│   ├── DiceService.php      # Système de dés
│   ├── ChatService.php      # Système de chat
│   └── WebSocketService.php # WebSocket handlers
├── EventListener/           # Event listeners
├── Security/                # Configuration sécurité
├── Serializer/              # Serializers API Platform
├── Validator/               # Validateurs personnalisés
├── Command/                 # Commandes Symfony
├── DataFixtures/            # Fixtures pour les tests
├── Migration/               # Migrations de base de données
└── WebSocket/               # Serveur WebSocket
    ├── Handler/             # Handlers d'événements
    ├── Room/                # Gestion des rooms
    └── Authentication/      # Auth WebSocket
```

## Architecture de Base de Données

### Modèle Relationnel Optimisé

La base de données suit un modèle relationnel pur avec un minimum de JSON pour optimiser les performances et maintenir l'intégrité des données.

#### Tables Principales

##### **Utilisateurs et Authentification**
```sql
-- Table utilisateur avec gestion des rôles Symfony
CREATE TABLE user (
    user_id INT(11) NOT NULL AUTO_INCREMENT,
    user_pseudo VARCHAR(50) NOT NULL,
    user_email VARCHAR(255) NOT NULL UNIQUE,
    user_password VARCHAR(255) NOT NULL,
    user_roles JSON NOT NULL,
    user_is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    user_avatar VARCHAR(255) NULL,
    user_timezone VARCHAR(50) DEFAULT "UTC",
    user_language VARCHAR(5) DEFAULT "en",
    user_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user_last_login DATETIME NULL,
    PRIMARY KEY (user_id)
);
```

##### **Parties de Jeu**
```sql
-- Gestion des parties avec statuts et permissions
CREATE TABLE game (
    game_id INT(11) NOT NULL AUTO_INCREMENT,
    game_name VARCHAR(250) NOT NULL,
    game_description TEXT NULL,
    game_master_id INT(11) NOT NULL,
    game_status ENUM("preparation", "active", "paused", "archived") NOT NULL DEFAULT "preparation",
    game_max_players INT(2) NOT NULL DEFAULT 6,
    game_is_public BOOLEAN NOT NULL DEFAULT FALSE,
    game_password VARCHAR(255) NULL,
    game_settings JSON NULL,
    game_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    game_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (game_id)
);
```

##### **Système Multiclasse Avancé**
```sql
-- Support du multiclassage D&D 5e
CREATE TABLE character_class_level (
    class_level_id INT(11) NOT NULL AUTO_INCREMENT,
    character_id INT(11) NOT NULL,
    class_id INT(11) NOT NULL,
    subclass_id INT(11) NULL,
    class_level INT(2) NOT NULL,
    is_primary_class BOOLEAN NOT NULL DEFAULT FALSE,
    level_order INT(2) NOT NULL, -- Ordre chronologique d'acquisition
    hit_points_gained INT(2) NOT NULL,
    level_acquired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (class_level_id)
);
```

### Tables de Référence SRD

#### **Données D&D Normalisées**
```sql
-- Sorts avec relations propres
CREATE TABLE srd_spell (
    spell_id INT(11) NOT NULL AUTO_INCREMENT,
    spell_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    spell_level INT(1) NOT NULL,
    school_id INT(11) NOT NULL,
    spell_casting_time VARCHAR(100) NOT NULL,
    spell_range VARCHAR(100) NOT NULL,
    spell_components VARCHAR(50) NOT NULL,
    spell_material_components TEXT NULL,
    spell_duration VARCHAR(100) NOT NULL,
    spell_concentration BOOLEAN NOT NULL DEFAULT FALSE,
    spell_ritual BOOLEAN NOT NULL DEFAULT FALSE,
    spell_description TEXT NOT NULL,
    spell_higher_levels TEXT NULL,
    PRIMARY KEY (spell_id)
);

-- Monstres avec statistiques complètes
CREATE TABLE srd_monster (
    monster_id INT(11) NOT NULL AUTO_INCREMENT,
    monster_name VARCHAR(250) NOT NULL,
    source_id INT(11) NOT NULL,
    size_id INT(11) NOT NULL,
    type_id INT(11) NOT NULL,
    alignment_id INT(11) NULL,
    monster_ac INT(2) NOT NULL,
    monster_hp_average INT(4) NOT NULL,
    monster_hp_dice VARCHAR(50) NOT NULL,
    monster_cr VARCHAR(10) NOT NULL,
    monster_cr_xp INT(6) NOT NULL,
    monster_proficiency_bonus INT(1) NOT NULL,
    monster_passive_perception INT(2) NOT NULL,
    PRIMARY KEY (monster_id)
);
```

### Optimisations de Performance

#### **Index Stratégiques**
```sql
-- Index composés pour requêtes fréquentes
CREATE INDEX idx_character_game_level ON character(game_id, character_level);
CREATE INDEX idx_spell_level_school ON srd_spell(spell_level, school_id);
CREATE INDEX idx_monster_cr_type ON srd_monster(monster_cr, type_id);

-- Index full-text pour recherche
CREATE FULLTEXT INDEX ft_spell_search ON srd_spell(spell_name, spell_description);
CREATE FULLTEXT INDEX ft_monster_search ON srd_monster(monster_name, monster_description);
```

#### **Vues Optimisées**
```sql
-- Vue pour statistiques complètes des personnages
CREATE VIEW character_stats AS
SELECT 
    c.character_id,
    c.character_name,
    c.character_level,
    -- Calculs automatiques des modificateurs avec bonus raciaux
    c.character_str + COALESCE(ram_str.modifier_value, 0) as str_total,
    c.character_dex + COALESCE(ram_dex.modifier_value, 0) as dex_total,
    -- ... autres statistiques calculées
FROM character c
JOIN srd_race r ON c.race_id = r.race_id
LEFT JOIN race_ability_modifier ram_str ON c.race_id = ram_str.race_id AND ram_str.ability_name = "STR";
```

## Services et Logique Métier

### Service d'Authentification

```php
// src/Service/AuthService.php
class AuthService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private CacheInterface $cache
    ) {}

    /**
     * Inscription avec vérification email
     */
    public function register(RegisterDTO $registerData): UserRegistrationResult
    {
        // Validation unicité pseudo/email
        $this->validateUniqueCredentials($registerData->pseudo, $registerData->email);
        
        // Création utilisateur avec mot de passe hashé
        $user = new User();
        $user->setPseudo($registerData->pseudo)
             ->setEmail($registerData->email)
             ->setPassword($this->passwordHasher->hashPassword($user, $registerData->password))
             ->setRoles(['ROLE_USER'])
             ->setIsVerified(false);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Génération et envoi du token de vérification
        $verificationToken = $this->generateVerificationToken();
        $this->cache->set("verify_email_{$verificationToken}", $user->getId(), 86400); // 24h
        $this->sendVerificationEmail($user, $verificationToken);
        
        return new UserRegistrationResult($user, $verificationToken);
    }

    /**
     * Connexion avec JWT
     */
    public function authenticate(LoginDTO $loginData): AuthenticationResult
    {
        $user = $this->userRepository->findByEmailOrPseudo($loginData->identifier);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $loginData->password)) {
            throw new AuthenticationException('Invalid credentials');
        }
        
        if (!$user->isVerified()) {
            throw new AccountNotVerifiedException('Account not verified');
        }
        
        // Génération JWT avec payload personnalisé
        $token = $this->jwtManager->create($user);
        
        // Mise à jour dernière connexion
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();
        
        return new AuthenticationResult($user, $token);
    }
}
```

### Service de Gestion des Parties

```php
// src/Service/GameService.php
class GameService
{
    public function __construct(
        private GameRepository $gameRepository,
        private GamePlayerRepository $gamePlayerRepository,
        private WebSocketService $webSocketService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Création de partie avec validation des règles métier
     */
    public function createGame(CreateGameDTO $gameData, User $gamemaster): Game
    {
        $game = new Game();
        $game->setName($gameData->name)
             ->setDescription($gameData->description)
             ->setGameMaster($gamemaster)
             ->setMaxPlayers($gameData->maxPlayers ?? 6)
             ->setIsPublic($gameData->isPublic ?? false)
             ->setStatus(GameStatus::PREPARATION);

        if (!$gameData->isPublic && $gameData->password) {
            $game->setPassword(password_hash($gameData->password, PASSWORD_ARGON2ID));
        }

        $this->entityManager->persist($game);
        
        // Ajout automatique du MJ comme joueur
        $gameMasterPlayer = new GamePlayer();
        $gameMasterPlayer->setGame($game)
                        ->setUser($gamemaster)
                        ->setPlayerRole(PlayerRole::GAME_MASTER)
                        ->setPlayerStatus(PlayerStatus::ACTIVE);
        
        $this->entityManager->persist($gameMasterPlayer);
        $this->entityManager->flush();
        
        // Notification WebSocket
        $this->webSocketService->notifyGameCreated($game);
        
        return $game;
    }

    /**
     * Rejoindre une partie avec vérifications
     */
    public function joinGame(int $gameId, User $user, ?string $password = null): GamePlayer
    {
        $game = $this->gameRepository->findGameForJoining($gameId);
        
        if (!$game) {
            throw new GameNotFoundException();
        }
        
        // Vérifications de validation métier
        $this->validateGameJoinability($game, $user, $password);
        
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game)
                  ->setUser($user)
                  ->setPlayerRole(PlayerRole::PLAYER)
                  ->setPlayerStatus(PlayerStatus::ACTIVE)
                  ->setJoinedAt(new \DateTime());
        
        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();
        
        // Notification temps réel
        $this->webSocketService->broadcastPlayerJoined($game, $gamePlayer);
        
        return $gamePlayer;
    }
}
```

### Service de Système de Dés

```php
// src/Service/DiceService.php
class DiceService
{
    /**
     * Parseur et calculateur de dés D&D
     */
    public function rollDice(string $expression, User $user, ?Game $game = null): DiceResult
    {
        // Parsing de l'expression (2d6+3, 1d20, 2d20kh1)
        $parsedExpression = $this->parseExpression($expression);
        
        // Validation sécurisée
        $this->validateExpression($parsedExpression);
        
        $results = [];
        $total = 0;
        
        foreach ($parsedExpression->diceGroups as $diceGroup) {
            $groupResults = $this->rollDiceGroup($diceGroup);
            $results[] = $groupResults;
            $total += $groupResults->getTotal();
        }
        
        // Application des modificateurs
        $total += $parsedExpression->modifier;
        
        // Sauvegarde en base
        $diceRoll = new DiceRoll();
        $diceRoll->setUser($user)
                 ->setGame($game)
                 ->setExpression($expression)
                 ->setResult($results)
                 ->setTotal($total)
                 ->setCreatedAt(new \DateTime());
        
        $this->entityManager->persist($diceRoll);
        $this->entityManager->flush();
        
        return new DiceResult($expression, $results, $total, $diceRoll->getId());
    }

    /**
     * Gestion avantage/désavantage D&D 5e
     */
    public function rollWithAdvantage(string $expression, AdvantageType $type, User $user, ?Game $game = null): DiceResult
    {
        if (!$this->isD20Expression($expression)) {
            throw new InvalidDiceExpressionException('Advantage/Disadvantage only works with d20 rolls');
        }
        
        // Lancer deux d20
        $roll1 = random_int(1, 20);
        $roll2 = random_int(1, 20);
        
        // Sélection selon avantage/désavantage
        $keptRoll = match($type) {
            AdvantageType::ADVANTAGE => max($roll1, $roll2),
            AdvantageType::DISADVANTAGE => min($roll1, $roll2)
        };
        
        // Application du modificateur
        $modifier = $this->extractModifier($expression);
        $total = $keptRoll + $modifier;
        
        $result = new AdvantageRollResult($roll1, $roll2, $keptRoll, $modifier, $total, $type);
        
        // Sauvegarde avec métadonnées spéciales
        $this->saveDiceRoll($expression, $result, $user, $game, 'advantage_roll');
        
        return new DiceResult($expression, [$result], $total);
    }
}
```

## API REST avec API Platform

### Configuration des Entités

```php
// src/Entity/Game.php
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/games',
            normalizationContext: ['groups' => ['game:list']],
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            uriTemplate: '/games/{id}',
            normalizationContext: ['groups' => ['game:read']],
            security: "is_granted('ROLE_USER') and object.canBeViewedBy(user)"
        ),
        new Post(
            uriTemplate: '/games',
            denormalizationContext: ['groups' => ['game:write']],
            security: "is_granted('ROLE_USER')",
            processor: CreateGameProcessor::class
        ),
        new Put(
            uriTemplate: '/games/{id}',
            denormalizationContext: ['groups' => ['game:update']],
            security: "is_granted('ROLE_USER') and object.isGameMaster(user)"
        ),
        new Delete(
            uriTemplate: '/games/{id}',
            security: "is_granted('ROLE_USER') and object.isGameMaster(user)"
        )
    ]
)]
#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['game:list', 'game:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 250)]
    #[Groups(['game:list', 'game:read', 'game:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 250)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['game:list', 'game:read'])]
    private ?User $gameMaster = null;

    #[ORM\Column(enumType: GameStatus::class)]
    #[Groups(['game:list', 'game:read'])]
    private GameStatus $status = GameStatus::PREPARATION;

    /**
     * Vérification des permissions d'accès
     */
    public function canBeViewedBy(User $user): bool
    {
        if ($this->isPublic) {
            return true;
        }
        
        return $this->getGamePlayers()->exists(
            fn($key, GamePlayer $player) => $player->getUser() === $user
        );
    }

    /**
     * Vérification du statut de MJ
     */
    public function isGameMaster(User $user): bool
    {
        return $this->gameMaster === $user;
    }
}
```

### Processeurs Personnalisés

```php
// src/Processor/CreateGameProcessor.php
class CreateGameProcessor implements ProcessorInterface
{
    public function __construct(
        private GameService $gameService,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Game
    {
        /** @var CreateGameDTO $data */
        $currentUser = $this->security->getUser();
        
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException('Authentication required');
        }
        
        return $this->gameService->createGame($data, $currentUser);
    }
}
```

## WebSocket et Temps Réel

### Architecture WebSocket

```php
// src/WebSocket/WebSocketServer.php
class WebSocketServer
{
    private array $rooms = [];
    private array $userSessions = [];

    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $userRepository,
        private GameService $gameService
    ) {}

    /**
     * Gestion de la connexion avec authentification JWT
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        $token = $this->extractTokenFromHandshake($conn);
        
        try {
            $payload = $this->jwtManager->decode($token);
            $user = $this->userRepository->find($payload['userId']);
            
            if (!$user) {
                $conn->close();
                return;
            }
            
            $session = new WebSocketSession($conn, $user);
            $this->userSessions[$conn->resourceId] = $session;
            
            $conn->send(json_encode([
                'type' => 'auth_success',
                'user' => [
                    'id' => $user->getId(),
                    'pseudo' => $user->getPseudo()
                ]
            ]));
            
        } catch (\Exception $e) {
            $conn->close();
        }
    }

    /**
     * Gestionnaire de messages entrants
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);
        
        if (!$this->isValidMessage($data)) {
            return;
        }

        $session = $this->userSessions[$from->resourceId] ?? null;
        if (!$session) {
            return;
        }

        match($data['type']) {
            'game:join' => $this->handleGameJoin($session, $data['payload']),
            'chat:message' => $this->handleChatMessage($session, $data['payload']),
            'dice:roll' => $this->handleDiceRoll($session, $data['payload']),
            'map:token:move' => $this->handleTokenMove($session, $data['payload']),
            default => null
        };
    }

    /**
     * Gestion des rooms de partie
     */
    private function handleGameJoin(WebSocketSession $session, array $payload): void
    {
        $gameId = $payload['gameId'];
        $game = $this->gameService->getGameForUser($gameId, $session->getUser());
        
        if (!$game) {
            $session->sendError('Game not found or access denied');
            return;
        }
        
        // Ajout à la room de partie
        $gameRoom = "game:{$gameId}";
        $this->addToRoom($session, $gameRoom);
        
        // Si MJ, ajout à la room privée
        if ($game->isGameMaster($session->getUser())) {
            $this->addToRoom($session, "{$gameRoom}:gm");
        }
        
        // Notification aux autres joueurs
        $this->broadcastToRoom($gameRoom, [
            'type' => 'player_joined',
            'player' => $this->serializeUser($session->getUser())
        ], $session);
        
        // Envoi de l'état actuel au nouveau joueur
        $session->send([
            'type' => 'game_state',
            'game' => $this->serializeGame($game)
        ]);
    }
}
```

### Gestion des Événements en Temps Réel

```php
// src/WebSocket/Handler/ChatHandler.php
class ChatHandler
{
    public function __construct(
        private ChatService $chatService,
        private WebSocketRoomManager $roomManager
    ) {}

    public function handleChatMessage(WebSocketSession $session, array $payload): void
    {
        $gameId = $payload['gameId'];
        $content = $payload['content'];
        $type = $payload['type'] ?? 'chat';
        $isIC = $payload['isIC'] ?? false;
        
        // Sauvegarde du message en base
        $message = $this->chatService->createMessage(
            $session->getUser(),
            $gameId,
            $content,
            $type,
            $isIC
        );
        
        // Broadcast à tous les joueurs de la partie
        $this->roomManager->broadcastToRoom("game:{$gameId}", [
            'type' => 'chat:message',
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'user' => $message->getUser()->getPseudo(),
                'type' => $message->getType(),
                'isIC' => $message->getIsIc(),
                'timestamp' => $message->getCreatedAt()->format('c')
            ]
        ]);
    }

    public function handleWhisper(WebSocketSession $session, array $payload): void
    {
        $gameId = $payload['gameId'];
        $targetUserId = $payload['targetUserId'];
        $content = $payload['content'];
        
        $message = $this->chatService->createWhisper(
            $session->getUser(),
            $targetUserId,
            $gameId,
            $content
        );
        
        // Envoi seulement à l'expéditeur et au destinataire
        $whisperData = [
            'type' => 'chat:whisper',
            'message' => $this->serializeMessage($message)
        ];
        
        $this->roomManager->sendToUser($session->getUser()->getId(), $whisperData);
        $this->roomManager->sendToUser($targetUserId, $whisperData);
        
        // Le MJ voit tous les whispers (optionnel)
        $this->roomManager->broadcastToRoom("game:{$gameId}:gm", $whisperData);
    }
}
```

## Sécurité et Validation

### Configuration Sécurité Symfony

```php
// config/packages/security.yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: argon2i
            
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
                
    firewalls:
        api_auth:
            pattern: ^/api/auth
            stateless: true
            json_login:
                check_path: /api/auth/login
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
                
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
            
    access_control:
        - { path: ^/api/auth, roles: PUBLIC_ACCESS }
        - { path: ^/api/users/register, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: ROLE_USER }

    role_hierarchy:
        ROLE_ADMIN: [ROLE_USER, ROLE_GM]
        ROLE_GM: [ROLE_USER]
```

### Validateurs Métier Personnalisés

```php
// src/Validator/GameCapacity.php
#[Attribute]
class GameCapacity extends Constraint
{
    public string $message = 'The game has reached its maximum capacity of {{ limit }} players.';
}

class GameCapacityValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof Game) {
            return;
        }
        
        $currentPlayerCount = $value->getActivePlayers()->count();
        
        if ($currentPlayerCount >= $value->getMaxPlayers()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ limit }}', $value->getMaxPlayers())
                ->addViolation();
        }
    }
}
```

### Rate Limiting

```php
// src/EventListener/RateLimitListener.php
class RateLimitListener
{
    public function __construct(
        private CacheInterface $cache,
        private array $rateLimits = [
            'chat:message' => ['limit' => 30, 'window' => 60],
            'dice:roll' => ['limit' => 60, 'window' => 60],
            'auth:login' => ['limit' => 5, 'window' => 300]
        ]
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        if (!isset($this->rateLimits[$route])) {
            return;
        }
        
        $identifier = $this->getClientIdentifier($request);
        $key = "rate_limit:{$route}:{$identifier}";
        
        $current = $this->cache->get($key, 0);
        $limit = $this->rateLimits[$route]['limit'];
        $window = $this->rateLimits[$route]['window'];
        
        if ($current >= $limit) {
            throw new TooManyRequestsHttpException($window, 'Rate limit exceeded');
        }
        
        $this->cache->set($key, $current + 1, $window);
    }
}
```

## Performance et Optimisation

### Stratégies de Cache

```php
// src/Service/CacheService.php
class CacheService
{
    public function __construct(
        private CacheInterface $cache,
        private CacheInterface $redisCache
    ) {}

    /**
     * Cache des données SRD avec TTL long
     */
    public function getCachedSpells(array $filters = []): array
    {
        $cacheKey = 'srd:spells:' . md5(serialize($filters));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($filters) {
            $item->expiresAfter(3600); // 1 heure
            
            return $this->spellRepository->findByFilters($filters);
        });
    }

    /**
     * Cache des sessions de jeu actives
     */
    public function cacheGameState(int $gameId, array $state): void
    {
        $this->redisCache->set(
            "game_state:{$gameId}", 
            serialize($state), 
            300 // 5 minutes
        );
    }

    /**
     * Invalidation ciblée du cache
     */
    public function invalidateGameCache(int $gameId): void
    {
        $this->cache->delete("game_state:{$gameId}");
        $this->cache->delete("game_players:{$gameId}");
        
        // Invalidation pattern-based avec Redis
        $pattern = "game:{$gameId}:*";
        $this->redisCache->clear($pattern);
    }
}
```

### Optimisation des Requêtes

```php
// src/Repository/GameRepository.php
class GameRepository extends ServiceEntityRepository
{
    /**
     * Requête optimisée pour la liste des parties avec jointures
     */
    public function findPublicGamesWithDetails(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g', 'gm', 'players', 'u')
            ->leftJoin('g.gameMaster', 'gm')
            ->leftJoin('g.gamePlayers', 'players')
            ->leftJoin('players.user', 'u')
            ->where('g.isPublic = :isPublic')
            ->andWhere('g.status IN (:activeStatuses)')
            ->setParameter('isPublic', true)
            ->setParameter('activeStatuses', [GameStatus::PREPARATION, GameStatus::ACTIVE])
            ->orderBy('g.createdAt', 'DESC');

        if (isset($filters['search'])) {
            $qb->andWhere('g.name LIKE :search OR g.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['status'])) {
            $qb->andWhere('g.status = :status')
               ->setParameter('status', $filters['status']);
        }

        return $qb;
    }

    /**
     * Requête optimisée pour vérifier l'accès à une partie
     */
    public function findGameForUser(int $gameId, User $user): ?Game
    {
        return $this->createQueryBuilder('g')
            ->select('g', 'gm', 'players')
            ->leftJoin('g.gameMaster', 'gm')
            ->leftJoin('g.gamePlayers', 'players')
            ->where('g.id = :gameId')
            ->andWhere('g.isPublic = true OR g.gameMaster = :user OR players.user = :user')
            ->setParameter('gameId', $gameId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

// src/Repository/SpellRepository.php  
class SpellRepository extends ServiceEntityRepository
{
    /**
     * Recherche full-text optimisée avec filtres
     */
    public function searchSpells(SpellSearchDTO $search): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s', 'school', 'source')
            ->leftJoin('s.school', 'school')
            ->leftJoin('s.source', 'source');

        // Recherche full-text si terme de recherche
        if ($search->query) {
            $qb->andWhere('MATCH(s.name, s.description) AGAINST(:query IN BOOLEAN MODE) > 0')
               ->setParameter('query', $search->query);
        }

        // Filtres spécifiques
        if ($search->level !== null) {
            $qb->andWhere('s.level = :level')
               ->setParameter('level', $search->level);
        }

        if ($search->school) {
            $qb->andWhere('school.name = :school')
               ->setParameter('school', $search->school);
        }

        if ($search->concentration !== null) {
            $qb->andWhere('s.concentration = :concentration')
               ->setParameter('concentration', $search->concentration);
        }

        if ($search->ritual !== null) {
            $qb->andWhere('s.ritual = :ritual')
               ->setParameter('ritual', $search->ritual);
        }

        // Tri par pertinence puis par nom
        if ($search->query) {
            $qb->orderBy('MATCH(s.name, s.description) AGAINST(:query IN BOOLEAN MODE)', 'DESC')
               ->addOrderBy('s.name', 'ASC');
        } else {
            $qb->orderBy('s.level', 'ASC')
               ->addOrderBy('s.name', 'ASC');
        }

        return $qb->getQuery()
                 ->setMaxResults($search->limit ?? 50)
                 ->setFirstResult($search->offset ?? 0)
                 ->getResult();
    }
}
```

## Commandes Console

### Import des Données SRD

```php
// src/Command/ImportSrdCommand.php
#[AsCommand(
    name: 'onlyroll:import-srd',
    description: 'Import D&D 5e SRD data into database'
)]
class ImportSrdCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SrdImportService $importService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'Type of data to import (spells, monsters, items, classes, races)')
             ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'Source file path')
             ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for import', 100)
             ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force reimport (delete existing data)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
        $sourcePath = $input->getOption('source');
        $batchSize = (int) $input->getOption('batch-size');
        $force = $input->getOption('force');

        $io->title("Importing SRD {$type}");

        try {
            $result = match($type) {
                'spells' => $this->importService->importSpells($sourcePath, $batchSize, $force),
                'monsters' => $this->importService->importMonsters($sourcePath, $batchSize, $force),
                'items' => $this->importService->importItems($sourcePath, $batchSize, $force),
                'classes' => $this->importService->importClasses($sourcePath, $batchSize, $force),
                'races' => $this->importService->importRaces($sourcePath, $batchSize, $force),
                default => throw new \InvalidArgumentException("Unknown import type: {$type}")
            };

            $io->success([
                "Import completed successfully!",
                "Imported: {$result->imported} items",
                "Skipped: {$result->skipped} items",
                "Errors: {$result->errors} items"
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

### Commande de Nettoyage

```php
// src/Command/CleanupCommand.php
#[AsCommand(
    name: 'onlyroll:cleanup',
    description: 'Cleanup old data and optimize database'
)]
class CleanupCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('OnlyRoll Cleanup');

        // Nettoyage des sessions expirées
        $expiredSessions = $this->entityManager->createQuery(
            'DELETE FROM App\Entity\UserSession s WHERE s.sessionTime < :expiredTime'
        )->setParameter('expiredTime', time() - 86400)
         ->execute();

        $io->text("Cleaned {$expiredSessions} expired sessions");

        // Nettoyage des lancers de dés anciens (> 30 jours)
        $oldDiceRolls = $this->entityManager->createQuery(
            'DELETE FROM App\Entity\DiceRoll d WHERE d.createdAt < :cutoffDate'
        )->setParameter('cutoffDate', new \DateTime('-30 days'))
         ->execute();

        $io->text("Cleaned {$oldDiceRolls} old dice rolls");

        // Nettoyage des messages de chat anciens (> 30 jours) pour parties archivées
        $oldMessages = $this->entityManager->createQuery(
            'DELETE FROM App\Entity\GameMessage m 
             WHERE m.createdAt < :cutoffDate 
             AND m.game IN (SELECT g FROM App\Entity\Game g WHERE g.status = :archivedStatus)'
        )->setParameter('cutoffDate', new \DateTime('-30 days'))
         ->setParameter('archivedStatus', GameStatus::ARCHIVED)
         ->execute();

        $io->text("Cleaned {$oldMessages} old messages");

        // Nettoyage du cache
        $this->cache->clear();
        $io->text("Cache cleared");

        // Optimisation des tables MySQL
        $connection = $this->entityManager->getConnection();
        $tables = ['user', 'game', 'game_message', 'dice_roll', 'character'];
        
        foreach ($tables as $table) {
            $connection->executeStatement("OPTIMIZE TABLE {$table}");
        }
        
        $io->text("Database tables optimized");

        $io->success('Cleanup completed successfully!');

        return Command::SUCCESS;
    }
}
```

## Monitoring et Métriques

### Service de Métriques

```php
// src/Service/MetricsService.php
class MetricsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    /**
     * Collecte des métriques système
     */
    public function collectSystemMetrics(): array
    {
        return [
            'timestamp' => time(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'active_connections' => $this->getActiveConnectionsCount(),
            'database_queries' => $this->getDatabaseMetrics(),
            'cache_stats' => $this->getCacheStats(),
            'websocket_stats' => $this->getWebSocketStats()
        ];
    }

    /**
     * Métriques spécifiques au jeu
     */
    public function collectGameMetrics(): array
    {
        $conn = $this->entityManager->getConnection();

        // Statistiques des parties
        $gameStats = $conn->fetchAssociative('
            SELECT 
                COUNT(*) as total_games,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_games,
                SUM(CASE WHEN status = "preparation" THEN 1 ELSE 0 END) as preparation_games,
                AVG(max_players) as avg_max_players
            FROM game
        ');

        // Utilisateurs actifs
        $userStats = $conn->fetchAssociative('
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as daily_active,
                COUNT(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as weekly_active,
                COUNT(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_active
            FROM user 
            WHERE is_verified = 1
        ');

        // Activité chat et dés
        $activityStats = $conn->fetchAssociative('
            SELECT 
                (SELECT COUNT(*) FROM game_message WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as messages_24h,
                (SELECT COUNT(*) FROM dice_roll WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as dice_rolls_24h
        ');

        return array_merge($gameStats, $userStats, $activityStats);
    }

    /**
     * Alertes de performance
     */
    public function checkPerformanceAlerts(): array
    {
        $alerts = [];
        $metrics = $this->collectSystemMetrics();

        // Alerte mémoire
        if ($metrics['memory_usage'] > 512 * 1024 * 1024) { // 512MB
            $alerts[] = [
                'type' => 'memory_high',
                'level' => 'warning',
                'message' => 'High memory usage detected',
                'value' => $metrics['memory_usage']
            ];
        }

        // Alerte connexions actives
        if ($metrics['active_connections'] > 1000) {
            $alerts[] = [
                'type' => 'connections_high',
                'level' => 'critical',
                'message' => 'High number of active connections',
                'value' => $metrics['active_connections']
            ];
        }

        return $alerts;
    }
}
```

### Logging Structuré

```php
// src/EventListener/ApiLoggerListener.php
class ApiLoggerListener
{
    public function __construct(
        private LoggerInterface $apiLogger,
        private Security $security
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $user = $this->security->getUser();
        
        $this->apiLogger->info('API Request', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'user_id' => $user?->getId(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'timestamp' => new \DateTime()
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $this->apiLogger->info('API Response', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'status_code' => $response->getStatusCode(),
            'response_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'timestamp' => new \DateTime()
        ]);
    }
}
```

## Configuration et Déploiement

### Configuration Environnement

```yaml
# config/packages/prod/doctrine.yaml
doctrine:
    dbal:
        # Configuration optimisée pour production
        options:
            1002: "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'"
        connections:
            default:
                driver: 'pdo_mysql'
                server_version: '9.0'
                charset: utf8mb4
                default_table_options:
                    charset: utf8mb4
                    collate: utf8mb4_unicode_ci
                # Pool de connexions
                pooled: true
                pool_size: 20
                
            # Connexion en lecture seule pour les requêtes de consultation
            readonly:
                driver: 'pdo_mysql'
                server_version: '9.0'
                host: '%env(DATABASE_READONLY_HOST)%'
                port: '%env(DATABASE_READONLY_PORT)%'
                dbname: '%env(DATABASE_NAME)%'
                user: '%env(DATABASE_READONLY_USER)%'
                password: '%env(DATABASE_READONLY_PASSWORD)%'
```

```yaml
# config/packages/framework.yaml
framework:
    cache:
        app: cache.adapter.redis
        system: cache.adapter.redis
        pools:
            # Cache spécifique pour les données SRD (TTL long)
            srd.cache:
                adapter: cache.adapter.redis
                default_lifetime: 3600
                
            # Cache pour les sessions de jeu (TTL court)
            game.cache:
                adapter: cache.adapter.redis
                default_lifetime: 300

    rate_limiter:
        # Rate limiting pour l'authentification
        auth_limiter:
            policy: 'token_bucket'
            limit: 5
            interval: '5 minutes'
            
        # Rate limiting pour les actions de jeu
        game_action_limiter:
            policy: 'sliding_window'
            limit: 60
            interval: '1 minute'
```

### Profils de Performance

```php
// src/EventListener/PerformanceListener.php
class PerformanceListener
{
    public function __construct(
        private LoggerInterface $performanceLogger
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Démarrage du profiling
        $event->getRequest()->attributes->set('_start_time', microtime(true));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        
        $startTime = $request->attributes->get('_start_time');
        if (!$startTime) {
            return;
        }

        $executionTime = microtime(true) - $startTime;
        
        // Log des requêtes lentes (> 1 seconde)
        if ($executionTime > 1.0) {
            $this->performanceLogger->warning('Slow request detected', [
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_peak_usage(true),
                'status_code' => $response->getStatusCode()
            ]);
        }

        // Ajout des headers de performance
        $response->headers->set('X-Execution-Time', number_format($executionTime * 1000, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB');
    }
}
```

## Tests et Qualité

### Configuration PHPUnit

```php
// tests/Service/GameServiceTest.php
class GameServiceTest extends KernelTestCase
{
    private GameService $gameService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->gameService = $kernel->getContainer()->get(GameService::class);
    }

    public function testCreateGameWithValidData(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $gameData = new CreateGameDTO(
            'Test Game',
            'A test game description',
            6,
            true,
            null
        );

        // Act
        $game = $this->gameService->createGame($gameData, $user);

        // Assert
        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals('Test Game', $game->getName());
        $this->assertEquals($user, $game->getGameMaster());
        $this->assertEquals(GameStatus::PREPARATION, $game->getStatus());
        
        // Vérification en base
        $this->entityManager->flush();
        $savedGame = $this->entityManager->getRepository(Game::class)->find($game->getId());
        $this->assertNotNull($savedGame);
    }

    public function testJoinGameWithFullCapacity(): void
    {
        // Arrange
        $game = $this->createTestGameWithMaxPlayers();
        $newUser = $this->createTestUser('newplayer@example.com');

        // Act & Assert
        $this->expectException(GameCapacityExceededException::class);
        $this->gameService->joinGame($game->getId(), $newUser);
    }

    private function createTestUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setPseudo('testuser')
             ->setEmail($email)
             ->setPassword('hashedpassword')
             ->setRoles(['ROLE_USER'])
             ->setIsVerified(true);
             
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }
}
```

### Tests d'Intégration API

```php
// tests/Controller/GameControllerTest.php
class GameControllerTest extends ApiTestCase
{
    public function testCreateGameAsAuthenticatedUser(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        $client->request('POST', '/api/games', 
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json: [
                'name' => 'Integration Test Game',
                'description' => 'A game created during integration testing',
                'maxPlayers' => 4,
                'isPublic' => true
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'name' => 'Integration Test Game',
            'status' => 'preparation'
        ]);
    }

    public function testGetGameListWithFilters(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        $client->request('GET', '/api/games?status=active', 
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertIsArray($data['hydra:member']);
    }
}
```

## Points d'Extension et Évolution

### Architecture Modulaire

```php
// src/Module/ModuleInterface.php
interface ModuleInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function isEnabled(): bool;
    public function getRoutes(): array;
    public function getServices(): array;
}

// Exemple : Module d'intégration Discord
// src/Module/Discord/DiscordModule.php
class DiscordModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'discord_integration';
    }

    public function getServices(): array
    {
        return [
            'discord.webhook.service' => DiscordWebhookService::class,
            'discord.bot.service' => DiscordBotService::class
        ];
    }

    public function getRoutes(): array
    {
        return [
            'discord_webhook' => [
                'path' => '/api/discord/webhook',
                'controller' => DiscordWebhookController::class
            ]
        ];
    }
}
```

### Extensibilité des Règles Métier

```php
// src/Rule/RuleEngineInterface.php
interface RuleEngineInterface
{
    public function addRule(string $context, RuleInterface $rule): void;
    public function executeRules(string $context, array $data): RuleResult;
}

// Exemple : Règles de validation de personnage
// src/Rule/Character/CharacterCreationRule.php
class CharacterCreationRule implements RuleInterface
{
    public function applies(string $context): bool
    {
        return $context === 'character.creation';
    }

    public function execute(array $data): RuleResult
    {
        // Validation des règles D&D 5e pour création de personnage
        $errors = [];
        
        if ($data['race_id'] && $data['class_id']) {
            // Vérifier la compatibilité race/classe
            if (!$this->isRaceClassCompatible($data['race_id'], $data['class_id'])) {
                $errors[] = 'Race and class combination not allowed';
            }
        }
        
        return new RuleResult(empty($errors), $errors);
    }
}
```