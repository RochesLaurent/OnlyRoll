# OnlyRoll - Virtual Tabletop Platform

![OnlyRoll Logo](./docs/assets/onlyroll-logo.png)

**OnlyRoll** est une plateforme de table virtuelle moderne dédiée à Dungeons & Dragons 5e, offrant une expérience de jeu fluide et immersive pour les joueurs francophones et internationaux.

*"One site to Roll them all"* - Jouer à distance avec vos amis ! Interface simple, données SRD intégrées, aucune installation requise.

## Fonctionnalités Principales

### **Système de Dés Avancé**
- Lancers de dés avec expressions complexes (2d6+3, 4d8-1)
- Historique complet des lancers
- Lancers privés pour les MJ
- Animations visuelles immersives

### **Gestion de Parties**
- Création et gestion simplifiée des parties
- Support jusqu'à 8 joueurs par partie
- Système de rôles (MJ, Co-MJ, Joueurs, Spectateurs)
- Invitation par lien ou code

### **Wiki D&D Intégré**
- Base de données SRD 5e complète
- Recherche instantanée (sorts, objets, créatures)
- Fiches de référence interactives
- Données officielles toujours à portée de main

### **Chat Temps Réel**
- Messagerie instantanée par WebSocket
- Messages In Character (IC) et Out of Character (OOC)
- Commandes slash intégrées
- Historique persistant

### **Feuilles de Personnage**
- Création guidée de personnages D&D 5e
- Calculs automatiques des statistiques
- Gestion des sorts et capacités
- Export/Import de personnages

### **Cartes & Tokens** *(Roadmap)*
- Maps interactives avec grille
- Tokens personnalisables
- Système de vision et éclairage
- Outils de mesure

## Installation & Démarrage Rapide

### Prérequis
- [Docker](https://docker.com) et [Docker Compose](https://docs.docker.com/compose/)
- [Git](https://git-scm.com)

### Installation Locale

```bash
# Cloner le repository
git clone https://github.com/username/onlyroll.git
cd onlyroll

# Copier les variables d'environnement
cp .env.example .env.local

# Démarrer l'environnement de développement
make install
make start

# Ou avec docker-compose directement
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

### Accès aux Services

| Service | URL | Description |
|---------|-----|-------------|
| **Frontend** | http://localhost:5173 | Application Vue.js |
| **API Backend** | http://localhost:8000 | API REST Symfony |
| **WebSocket** | ws://localhost:3000 | Serveur temps réel |
| **Base de données** | localhost:3306 | MySQL 9.0 |
| **Adminer** | http://localhost:8080 | Interface DB |
| **MailHog** | http://localhost:8025 | Mail catcher |

### Premiers Pas

1. **Créer un compte** sur http://localhost:5173
2. **Créer votre première partie** depuis le dashboard
3. **Inviter vos amis** avec le code de partie
4. **Créer vos personnages** avec le générateur intégré
5. **Commencer à jouer !**

## Architecture Technique

### Stack Technologique

**Backend**
- **PHP 8.1+** avec **Symfony 7.1+**
- **API Platform 3.2+** pour l'API REST
- **MySQL 9.0** avec optimisations pour le gaming
- **Redis 7.2+** pour cache et sessions
- **JWT Authentication** sécurisé

**Frontend**
- **Vue.js 3.4+** avec Composition API
- **TypeScript 5.3+** pour la robustesse
- **Pinia 2.1+** pour la gestion d'état
- **Tailwind CSS 3.x** avec design system custom
- **Vite 5.0+** pour un build ultra-rapide

**Temps Réel**
- **Socket.io** pour WebSocket bidirectionnel
- **Redis Pub/Sub** pour la scalabilité
- Architecture orientée événements

**Infrastructure**
- **Docker & Docker Compose** pour la conteneurisation
- **Nginx** comme reverse proxy et load balancer
- **GitHub Actions** pour CI/CD
- Multi-stage builds optimisés pour production

### Architecture Projet

```
onlyroll/
├── backend/                 # API Symfony
│   ├── src/
│   │   ├── Controller/      # Controllers API REST
│   │   ├── Entity/          # Entités Doctrine
│   │   ├── Service/         # Services métier
│   │   ├── Repository/      # Repositories personnalisés
│   │   └── WebSocket/       # Serveur WebSocket
│   ├── config/              # Configuration Symfony
│   ├── migrations/          # Migrations base de données
│   └── tests/               # Tests PHPUnit
│
├── frontend/                # Application Vue.js
│   ├── src/
│   │   ├── components/      # Composants réutilisables
│   │   ├── views/           # Pages/Routes
│   │   ├── stores/          # Stores Pinia
│   │   ├── composables/     # Logique réutilisable
│   │   └── services/        # Services API
│   ├── public/              # Assets statiques
│   └── tests/               # Tests Vitest
│
├── docker/                  # Configuration Docker
│   ├── containers/          # Dockerfiles par service
│   └── compose/             # Configurations Compose
│
├── docs/                    # Documentation
└── scripts/                 # Scripts d'automatisation
```

## Développement

### Standards de Code

**PHP (PSR-12 + Symfony)**
```php
// Exemples de conventions
class GameController extends AbstractController
{
    public function createGame(CreateGameRequest $request): JsonResponse
    {
        // camelCase pour méthodes et variables
        $gameData = $request->getValidatedData();
        
        // Services injectés via constructeur
        $game = $this->gameService->createGame($gameData);
        
        return $this->json($game, 201);
    }
}
```

**TypeScript/Vue (Vue Style Guide)**
```typescript
// Composants en PascalCase
// GameTable.vue
<script setup lang="ts">
interface Props {
  gameId: number
  playerRole: 'gm' | 'player'
}

// Composables avec use prefix
const { game, isLoading } = useGame(props.gameId)
const { rollDice } = useDiceRoller()
</script>
```

### Git Workflow

```bash
# Branches
main           # Production stable
develop        # Intégration continue  
feature/*      # Nouvelles fonctionnalités
fix/*          # Corrections de bugs
hotfix/*       # Corrections urgentes
release/*      # Préparation releases

# Commits conventionnels
feat: ajout système de cartes interactives
fix: correction calcul modificateur de dés
docs: mise à jour installation Docker
style: correction indentation composants Vue
refactor: optimisation requêtes base de données
test: ajout tests unitaires DiceService
chore: mise à jour dépendances npm
```

## Base de Données

### Modèle Principal

```sql
-- Tables principales
user              # Utilisateurs
game              # Parties de jeu
character         # Personnages
dice_roll         # Historique des lancers
game_message      # Messages de chat
game_participant  # Participants aux parties

-- Tables de référence SRD
srd_spell         # Sorts D&D 5e
srd_item          # Objets et équipements
srd_monster       # Créatures et monstres
srd_class         # Classes de personnage
srd_race          # Races de personnage
```

### Optimisations Performances

- **Index composés** sur colonnes fréquemment requêtées
- **Partitioning** des logs et messages par date
- **Cache Redis** pour données SRD et sessions
- **Connection pooling** optimisé
- **Requêtes préparées** exclusivement

## Sécurité

### Authentification & Autorisation

- **JWT Tokens** avec refresh automatique
- **Rate limiting** sur toutes les routes
- **CORS** configuré strictement
- **Input validation** avec Symfony Validator
- **SQL Injection** protection via Doctrine ORM
- **XSS** protection avec échappement automatique

### Données Personnelles

- **RGPD Compliant** avec opt-in explicite
- **Chiffrement** des données sensibles
- **Politique de rétention** des données configurable
- **Export/suppression** de compte utilisateur
- **Logs d'audit** pour traçabilité

## Déploiement Production

### Environnements

```bash
# Staging
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d

# Production
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Monitoring & Observabilité

- **Health checks** automatiques sur tous les services
- **Métriques** Prometheus + Grafana
- **Logs centralisés** avec rotation automatique
- **Alerting** Slack/Discord sur incidents
- **Backups automatisés** MySQL vers S3

### Performance Production

- **CDN** pour assets statiques
- **HTTP/2** et compression gzip/brotli
- **OPcache** PHP avec preloading
- **Redis clustering** pour haute disponibilité
- **Load balancing** Nginx avec sticky sessions

## Support & Communauté

### Liens Utiles

- **Documentation complète** : [docs.onlyroll.com](https://docs.onlyroll.com)
- **Issues GitHub** : [GitHub Issues](https://github.com/username/onlyroll/issues)
- **Discord Communauté** : [OnlyRoll Discord](https://discord.gg/onlyroll)
- **Support** : support@onlyroll.com
- **Site officiel** : [onlyroll.com](https://onlyroll.com)

### Ressources D&D

- **SRD 5e Officiel** : [D&D 5e SRD](https://dnd.wizards.com/resources/systems-reference-document)
- **Outils communautaires** : [5etools](https://5e.tools)
- **Références rapides** : [Donjon](https://donjon.bin.sh)

## Licence

Ce projet est sous licence **MIT** - voir le fichier [LICENSE](LICENSE) pour plus de détails.
