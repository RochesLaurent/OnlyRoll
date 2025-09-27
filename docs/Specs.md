# OnlyRoll - Cahier des Charges Technique Complet

## Vue d'Ensemble du Projet

### **Identité du Projet**
- **Nom officiel** : OnlyRoll
- **Type** : Virtual Tabletop (VTT) - Plateforme de table virtuelle de jeu de rôle
- **Spécialisation** : Dungeons & Dragons 5e Edition (SRD)
- **Public cible** : Joueurs et Maîtres de Jeu francophones et internationaux
- **Modèle économique** : Freemium (MVP gratuit, fonctionnalités premium en phases ultérieures)
- **Positionnement** : "L'accessible et puissant" - Entre Roll20 (trop basique) et Foundry (trop complexe)

### **Stack Technique Exacte**
- **Backend** : 
  - PHP 8.1+ / Symfony 7.1+
  - MySQL 9.0+
  - API Platform 3.2+
  - Lexik JWT Authentication Bundle
- **Frontend** : 
  - Vue.js 3.4+ avec Composition API
  - TypeScript 5.3+
  - Pinia 2.1+ (state management)
  - Tailwind CSS 3.x
  - Vite 5.0+
- **WebSocket** : 
  - Socket.io pour le temps réel
  - Redis 7.2+ pour pub/sub
- **Infrastructure** : 
  - Docker avec docker-compose
  - nginx comme reverse proxy
  - GitHub Actions CI/CD (ou GitLab CI)
  - Redis pour cache et sessions

### **Design System - Dark Fantasy Professional**

#### **Palette de Couleurs Officielles**
```css
/* Primary Colors - Violet mystique */
--primary-900: #1a0b2e;  /* Fond principal */
--primary-800: #2d1b44;
--primary-700: #402a5b;
--primary-500: #6366f1;  /* Accent principal */
--primary-400: #818cf8;
--primary-300: #a5b4fc;

/* Secondary Colors - Gris ardoise */
--secondary-900: #0f172a;
--secondary-800: #1e293b;  /* Fond cartes */
--secondary-700: #334155;  /* Bordures */
--secondary-600: #475569;
--secondary-400: #94a3b8;  /* Texte muet */
--secondary-300: #cbd5e1;  /* Texte secondaire */
--secondary-200: #e2e8f0;
--secondary-100: #f1f5f9;
--secondary-50: #f8fafc;   /* Texte principal */

/* Accent Colors D&D */
--accent-amber: #f59e0b;    /* Or/Trésor */
--accent-emerald: #10b981;  /* Succès/Nature */
--accent-rose: #f43f5e;     /* Dégâts/Danger */
--accent-cyan: #06b6d4;     /* Magie/Eau */
--accent-purple: #8b5cf6;   /* Arcane */

/* System Colors */
--success: #22c55e;
--warning: #eab308;
--error: #ef4444;
--info: #3b82f6;
```

#### **Typographie**
```css
--font-display: 'Figtree', -apple-system, sans-serif;  /* Titres */
--font-body: 'Figtree', -apple-system, sans-serif;     /* Corps */
--font-mono: 'JetBrains Mono', 'Fira Code', monospace; /* Code/Stats */
```

---

## Roadmap Détaillée avec Estimations

### **Phase MVP (77-95h) - v1.0.0**
**Objectif** : Table virtuelle minimale fonctionnelle

#### **1.1 Authentification (8-12h)**
- Entity User Symfony avec validation
- JWT avec refresh tokens
- Endpoints : `/api/auth/register`, `/api/auth/login`, `/api/auth/refresh`, `/api/auth/logout`
- Récupération mot de passe : `/api/auth/forgot-password`, `/api/auth/reset-password`
- Vérification email : `/api/auth/verify-email/{token}`

#### **1.2 Gestion Compte/Profil (6-8h)**
- Liste profils : `/api/users` (GET)
- Profil public : `/api/users/{id}` (GET)
- Profil perso : `/api/users/me` (GET)
- CRUD profil : `/api/users/me` (PATCH/PUT/DELETE)
- Upload avatar : `/api/users/me/avatar` (POST)

#### **1.3 Création de Partie (6-8h)**
- Entity Game avec code unique
- CRUD parties : `/api/games` (GET/POST)
- Liste de mes parties : `/api/games/my` (GET)
- CRUD partie : `/api/games/{id}` (GET/PUT/PATCH/DELETE)
- Inviter dans une partie : `/api/games/{id}/invite` (POST)
- Rejoindre partie : `/api/games/{id}/join` (POST)

#### **1.4 Rôles MJ/PJ (2-4h)**
- Système RBAC Symfony
- Rôles : ROLE_USER, ROLE_GM, ROLE_ADMIN
- Permissions par partie
- Changement rôle : `/api/games/{id}/players/{playerId}/role` (PUT)

#### **1.5 Tchat WebSocket (12h)**
- Socket.io avec rooms par partie
- Messages : `/api/games/{gameId}/messages` (GET/POST)
- Suppression : `/api/games/{gamesId}/messages/{messageId}` (DELETE)
- Types : message, dice, system, whisper
- Historique persistant MySQL

#### **1.6 Système de Dés (3-4h)**
- Parser expressions (2d6+3, 1d20+5, 2d20kh1)
- Endpoint : `/api/games/{gamesId}/dice/roll` (POST)
- Historique : `/api/games/{gameId}/dice/history` (GET)
- Résultats publics/privés

#### **1.7 Tests & Documentation (15-20h)**
- Tests unitaires PHPUnit
- Tests frontend Vitest
- Tests E2E Cypress
- Documentation OpenAPI

**Total MVP : 77-95 heures**

---

### **Phase 2 - Intégration SRD D&D 5e (40-58h) - v1.5.0**

#### **2.1 Import Données SRD (12-16h)**
- Schéma DB optimisé (tables séparées, pas de JSON)
- Import ~360 sorts : `/api/srd/spells`
- Import ~500 items : `/api/srd/items` 
- Import ~300 monstres : `/api/srd/monsters`
- Import règles : `/api/srd/rules/{category}`
- Endpoints recherche : `/api/srd/*/search`

#### **2.2 Interface Consultation (18-32h)**
- Composants Vue.js pour affichage
- Filtres multicritères
- Recherche avancée avec ElasticSearch (optionnel)
- Favoris utilisateur : `/api/srd/favorites` (GET/POST/DELETE)
- Import dans parties

#### **2.3 API Publique (10h)**
- Documentation OpenAPI complète
- Rate limiting par endpoint
- Authentification API keys
- Webhooks pour événements

**Total Phase 2 : 40-58 heures**

---

### **Phase 3 - Personnages & Combat (60-80h) - v2.0.0**

#### **3.1 Fiches de Personnage (30-40h)**

**Endpoints Personnages :**
- CRUD : `/api/characters` (GET/POST), `/api/characters/{id}` (GET/PUT/DELETE)
- Level up : `/api/characters/{id}/level-up` (POST)
- Repos : `/api/characters/{id}/rest/{type}` (POST)
- HP : `/api/characters/{id}/hp` (PUT)
- Inventaire : `/api/characters/{id}/inventory` (GET/PUT)
- Sorts : `/api/characters/{id}/spells` (GET), `/api/characters/{id}/spells/cast` (POST)
- Export PDF : `/api/characters/{id}/export` (GET)

**Fonctionnalités :**
- Races et classes SRD
- Calculs automatiques (stats, modificateurs, proficiency)
- Gestion inventaire avec poids
- Slots de sorts et ressources
- Progression niveau 1-20

#### **3.2 Système de Combat (30-40h)**

**Endpoints Combat :**
- Démarrer : `/api/games/{gameId}/combat/start` (POST)
- État actuel : `/api/games/{gameId}/combat/current` (GET)
- Action : `/api/games/{gameId}/combat/action` (POST)
- Tour suivant : `/api/games/{gameId}/combat/next-turn` (PUT)
- Terminer : `/api/games/{gameId}/combat/end` (POST)
- Conditions : `/api/games/{gameId}/combat/conditions` (POST)

**Fonctionnalités :**
- Initiative tracker automatique
- Gestion HP/AC
- Conditions et états
- Death saves
- Concentration des sorts

**Total Phase 3 : 60-80 heures**

---

### **Phase 4 - Immersion & Premium (100-150h) - v3.0.0**

#### **4.1 Cartes Interactives (40-60h)**

**Endpoints Cartes :**
- CRUD : `/api/games/{gameId}/maps` (GET/POST)
- Détails : `/api/games/{gameId}/maps/{mapId}` (GET/PUT/DELETE)
- Jetons : `/api/maps/{id}/tokens` (POST), `/api/maps/{id}/tokens/{tokenId}` (PUT/DELETE)
- Éclairage : `/api/maps/{id}/lighting` (PUT)
- Fog of war : `/api/maps/{id}/fog` (PUT)

**Fonctionnalités :**
- Grille hexagonale/carrée
- Layers (fond, objets, tokens)
- Vision et éclairage dynamique
- Mesure de distance
- Drawing tools

#### **4.2 Multimédia (30-40h)**

**Endpoints Médias :**
- Upload : `/api/media/upload` (POST)
- Liste : `/api/games/{gameId}/media` (GET)
- Suppression : `/api/media/{id}` (DELETE)
- Ambiance : `/api/games/{gameId}/ambiance` (POST)

**Fonctionnalités :**
- Bibliothèque sons/musiques
- Playlists par scène
- WebRTC audio/vidéo
- Partage d'écran

#### **4.3 Features Premium (30-50h)**

**OnlyRoll+ (9.99€/mois) :**
- Parties illimitées
- 10GB stockage
- Assets premium
- Cartes avec éclairage
- Support prioritaire

**OnlyRoll Pro (24.99€/mois) :**
- Tout OnlyRoll+
- API access
- Custom content
- Analytics dashboard
- White label

**Total Phase 4 : 100-150 heures**

---

## Architecture & Design Patterns

### **Backend Architecture - Clean Architecture**

```
Controller Layer (API endpoints)
    ↓ [DTO]
Application Layer (Use Cases/Services)
    ↓ [Domain Events]
Domain Layer (Entities/Business Logic)
    ↓ [Repository Interface]
Infrastructure Layer (Doctrine/MySQL/Redis)
```

#### **Design Patterns Backend**
- **Repository Pattern** : Abstraction de la persistance
- **Service Layer** : GameService, DiceService, CombatService
- **DTO Pattern** : Request/Response objects
- **Event Dispatcher** : Symfony Events
- **Factory Pattern** : CharacterFactory, GameFactory
- **Strategy Pattern** : DiceRoller strategies
- **Command Pattern** : Actions utilisateur

### **Frontend Architecture**

```
Views/Pages (Routes Vue Router)
    ↓
Smart Components (Logic + State)
    ↓
Dumb Components (Presentation only)
    ↓
Base Components (Design System)
```

#### **Patterns Frontend**
- **Composables** : useAuth, useWebSocket, useDice, useGame
- **Store Pattern** : Pinia stores (auth, game, character, chat)
- **Service Layer** : API services with Axios
- **Factory Pattern** : Component factories
- **Observer Pattern** : WebSocket events

---

## Routes API Complètes

### **Routes Utilitaires**
```http
GET  /api/health          # Health check public
GET  /api/ping            # Simple ping
GET  /api/stats           # Statistiques globales
GET  /api/version         # Version et features
```

### **WebSocket Events**
```javascript
// Client → Server
'game:join'          { gameId }
'game:leave'         { gameId }
'chat:message'       { gameId, content, channel }
'dice:roll'          { gameId, expression }
'character:update'   { characterId, changes }
'map:token:move'     { mapId, tokenId, position }
'combat:action'      { combatId, action }

// Server → Clients
'player:joined'      { player }
'player:left'        { playerId }
'chat:message'       { message }
'dice:result'        { roll }
'character:updated'  { character }
'map:updated'        { changes }
'combat:turn'        { activePlayer }
'game:notification'  { type, message }
```

---

## Security & Performance

### **Security Requirements**
- JWT avec rotation automatique (15min token, 7j refresh)
- 2FA optionnel (TOTP)
- Rate limiting par endpoint (voir tableau dans API doc)
- Session timeout configurable
- RBAC avec permissions granulaires
- Chiffrement AES-256 données sensibles
- HTTPS/WSS obligatoire production
- CORS strict configuration
- CSP headers
- Protection XSS/CSRF native Symfony

### **Performance Targets**
- **API Response** : < 200ms (P95)
- **Database queries** : < 50ms (P95)
- **WebSocket latency** : < 100ms
- **First Contentful Paint** : < 1.5s
- **Time to Interactive** : < 3s
- **Lighthouse Score** : > 90
- **Bundle size** : < 500KB gzipped
- **Concurrent users** : 1000+
- **Uptime** : 99.9%

### **Scalability Plan**
- Horizontal scaling (Kubernetes ready)
- Database read replicas
- Redis clustering
- CDN (Cloudflare)
- Message queue (RabbitMQ)
- ElasticSearch pour recherche

---

## Testing Strategy

### **Coverage Requirements**
- Unit tests : > 80%
- Integration tests : > 60%
- E2E critical paths : 100%
- Performance tests : 1000 users load

### **Test Stack**
- **Backend** : PHPUnit 10+, Behat
- **Frontend** : Vitest, Testing Library
- **E2E** : Cypress
- **Performance** : K6, Artillery
- **Security** : OWASP ZAP

---

## Database Schema

### **Tables Principales**

```sql
-- Utilisateurs
CREATE TABLE user (
    user_id INT(11) NOT NULL AUTO_INCREMENT,
    user_pseudo VARCHAR(50) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    user_roles JSON NOT NULL COMMENT "Rôles Symfony [ROLE_USER, ROLE_ADMIN]",
    user_is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    user_avatar VARCHAR(255) NULL,
    user_timezone VARCHAR(50) DEFAULT "UTC",
    user_language VARCHAR(5) DEFAULT "en",
    user_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user_last_login DATETIME NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY uk_user_pseudo (user_pseudo),
    UNIQUE KEY uk_user_email (user_email),
    KEY idx_user_verified (user_is_verified),
    KEY idx_user_created (user_created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parties
CREATE TABLE game (
    game_id INT(11) NOT NULL AUTO_INCREMENT,
    game_name VARCHAR(250) NOT NULL,
    game_description TEXT NULL,
    game_master_id INT(11) NOT NULL COMMENT "Maître de jeu",
    game_status ENUM("preparation", "active", "paused", "archived") NOT NULL DEFAULT "preparation",
    game_max_players INT(2) NOT NULL DEFAULT 6,
    game_is_public BOOLEAN NOT NULL DEFAULT FALSE,
    game_password VARCHAR(255) NULL,
    game_settings JSON NULL COMMENT "Règles maison, options de partie",
    game_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    game_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (game_id),
    KEY idx_game_master (game_master_id),
    KEY idx_game_status (game_status),
    KEY idx_game_public (game_is_public),
    KEY idx_game_created (game_created_at),
    CONSTRAINT fk_game_master FOREIGN KEY (game_master_id) REFERENCES user(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages de chat
CREATE TABLE game_message (
    message_id INT(11) NOT NULL AUTO_INCREMENT,
    game_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    character_id INT(11) NULL COMMENT "Personnage qui parle (si applicable)",
    message_type ENUM("chat", "emote", "whisper", "system", "dice_roll") NOT NULL DEFAULT "chat",
    message_content TEXT NOT NULL,
    message_target_user_id INT(11) NULL COMMENT "Pour les whispers",
    message_dice_result JSON NULL COMMENT "Structure simple des résultats de dés",
    message_is_ic BOOLEAN NOT NULL DEFAULT FALSE COMMENT "In Character",
    message_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_game_id (game_id),
    KEY idx_user_id (user_id),
    KEY idx_character_id (character_id),
    KEY idx_message_type (message_type),
    KEY idx_target_user (message_target_user_id),
    KEY idx_message_created (message_created_at),
    CONSTRAINT fk_game_message_game FOREIGN KEY (game_id) REFERENCES game(game_id) ON DELETE CASCADE,
    CONSTRAINT fk_game_message_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_game_message_character FOREIGN KEY (character_id) REFERENCES character(character_id) ON DELETE SET NULL,
    CONSTRAINT fk_game_message_target FOREIGN KEY (message_target_user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Internationalisation & Localisation

### **Langues Prévues**
- **Phase 1** : Français (principal), Anglais

### **Implementation**
- Vue i18n pour le frontend
- Symfony Translation pour l'API
- Formats date/nombre selon locale
- Support RTL préparé
- SEO multilingue

---

## Business Model & KPIs

### **Tiers Freemium**

#### **Free Tier**
- 3 parties actives max
- 5 joueurs max par partie
- 100MB stockage
- Accès SRD complet
- Chat & dés illimités

#### **OnlyRoll+ (9.99€/mois)**
- Parties illimitées
- 8 joueurs max
- 10GB stockage
- Assets premium
- Cartes avec éclairage
- Support prioritaire

#### **OnlyRoll Pro (24.99€/mois)**
- Tout OnlyRoll+
- API access complet
- Custom content/homebrew
- Analytics dashboard
- White label option
- Support dédié

### **KPIs Cibles**
- **MAU** : 10K en 6 mois, 50K en 12 mois
- **DAU/MAU** : > 25%
- **Session duration** : > 90 minutes
- **Retention D30** : > 40%
- **Conversion free→paid** : 5%
- **Churn mensuel** : < 5%
- **MRR** : 10K€ en 12 mois
- **LTV/CAC** : > 3

---

## Risk Management

### **Risques Techniques**
- **Scalabilité WebSocket** → Redis pub/sub + horizontal scaling
- **Performance cartes** → Canvas optimisé, WebGL, workers
- **Mobile responsive** → PWA, touch events
- **Compatibilité navigateurs** → Tests cross-browser

### **Risques Business**
- **Concurrence** → Différenciation UX et prix
- **Licensing D&D** → Respect strict OGL 1.0a
- **Monétisation** → Tests A/B, feedback users
- **Rétention** → Gamification, communauté

### **Mitigation**
1. MVP lean pour validation
2. Feedback loops courts (2 semaines)
3. Architecture modulaire
4. Documentation exhaustive
5. Tests automatisés
6. Monitoring temps réel

---

## Conventions & Standards

### **Naming Conventions**

#### **Backend (PHP/Symfony)**
- Classes : PascalCase (`GameController`)
- Methods : camelCase (`createGame()`)
- Variables : camelCase (`$userId`)
- Constants : UPPER_SNAKE (`MAX_PLAYERS`)
- Tables DB : snake_case (`user_game`)
- Colonnes DB : table_field (`user_email`)

#### **Frontend (Vue/TypeScript)**
- Components : PascalCase (`GameTable.vue`)
- Composables : camelCase avec `use` (`useWebSocket`)
- Props/Events : kebab-case dans template
- Types/Interfaces : PascalCase avec `I` ou `Type`
- Stores : camelCase avec `Store` suffix

### **Git Workflow**
```
main        → Production
develop     → Intégration
feature/*   → Nouvelles fonctionnalités
fix/*       → Corrections bugs
hotfix/*    → Fixes urgents production
release/*   → Préparation versions
```

### **Commit Convention**
```
feat: nouvelle fonctionnalité
fix: correction de bug
docs: documentation
style: formatage
refactor: refactoring
test: ajout tests
chore: maintenance
```

---

## Ressources & Références

### **Documentation Technique**
- [Symfony 7 Docs](https://symfony.com/doc/current/)
- [Vue.js 3 Guide](https://vuejs.org/guide/)
- [D&D 5e SRD](https://dnd.wizards.com/resources/systems-reference-document || https://github.com/5etools-mirror-3/5etools-src/)
- [API Platform](https://api-platform.com/)
- [Socket.io Docs](https://socket.io/docs/v4/)

### **Outils Projet**
- **Repository** : GitHub privé
- **Project Management** : Trello
- **Design** : Figma
- **Documentation** : Google Docs
- **Communication** : Discord
- **Monitoring** : Sentry, Prometheus, Grafana

### **Assets & Ressources**
- Icons : Lucide React, Hero Icons
- Images D&D : OpenGameArt / Pinterest, licence compatible
- Fonts : Google Fonts (Figtree, JetBrains Mono)
- Sons : Freesound.org avec attribution

---

*Document Version : 1.0.0*
*Date : Septembre 2025*
*Statut : Mise à jour continue*
*Auteur : ROCHES Laurent*