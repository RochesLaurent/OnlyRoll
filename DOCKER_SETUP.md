# 🐳 Docker Setup - OnlyRoll

Guide complet pour utiliser Docker avec OnlyRoll.

## 📋 Structure des Fichiers

### Fichiers à Commiter (Git)
```
✅ .env.docker.example       → Exemple de configuration (template)
✅ backend/Dockerfile        → Image PHP production/dev
✅ backend/.dockerignore      → Exclusions pour le build backend
✅ backend/docker/            → Configs PHP (php.ini, php-dev.ini, entrypoint.sh)
✅ frontend/Dockerfile       → Image Node/Nginx production/dev
✅ frontend/.dockerignore     → Exclusions pour le build frontend
✅ frontend/docker/           → Configs Nginx
✅ docker/                    → Configs services (MySQL, Redis, Nginx dev)
✅ docker-compose.yml        → Dev (6 services)
✅ docker-compose.prod.yml   → Production
✅ .github/workflows/ci.yml  → CI/CD avec validation Docker
```

### Fichiers à NE PAS Commiter (Gitignored)
```
❌ .env.docker              → Configuration locale (secrets)
❌ .env.docker.local        → Surcharge locale optionnelle
```

---

## 🚀 Démarrage Rapide

### 1️⃣ Première Installation

```bash
# Copier l'exemple en configuration locale
cp .env.docker.example .env.docker

# (Optionnel) Adapter les ports/secrets si nécessaire
nano .env.docker

# Build et démarrage
docker-compose build
docker-compose up -d

# Vérifier les services
docker-compose ps
```

### 2️⃣ Accès aux Services

| Service | URL | Port |
|---------|-----|------|
| **Frontend (Vite)** | http://localhost:5173 | 5173 |
| **Backend API** | http://localhost/api | 80 |
| **Mercure WebSocket** | http://localhost:3000 | 3000 |
| **MySQL** | localhost:3306 | 3306 |
| **Redis** | localhost:6379 | 6379 |

### 3️⃣ Commandes Courantes

```bash
# Logs en temps réel
docker-compose logs -f

# Logs d'un service spécifique
docker-compose logs -f php-fpm

# Arrêter les services
docker-compose down

# Arrêter et supprimer les volumes (données)
docker-compose down -v

# Redémarrer
docker-compose restart

# Rebuild (sans cache)
docker-compose build --no-cache
docker-compose up -d
```

---

## 🛠️ Commandes de Développement

### Backend (PHP)

```bash
# Shell PHP
docker-compose exec php-fpm sh

# Commandes Symfony
docker-compose exec php-fpm php bin/console cache:clear
docker-compose exec php-fpm php bin/console doctrine:migrations:migrate
docker-compose exec php-fpm php bin/console doctrine:fixtures:load

# Tests
docker-compose exec php-fpm vendor/bin/phpunit
```

### Frontend (Vue)

```bash
# Shell Node
docker-compose exec frontend sh

# NPM
docker-compose exec frontend npm install
docker-compose exec frontend npm run build

# Tests
docker-compose exec frontend npm run test:unit
docker-compose exec frontend npm run test:e2e
```

### Database (MySQL)

```bash
# Accès MySQL
docker-compose exec mysql mysql -u onlyroll_db -p onlyroll_db

# Dump de la base
docker-compose exec mysql mysqldump -u onlyroll_db -p onlyroll_db > backup.sql

# Restaurer depuis dump
docker-compose exec -T mysql mysql -u onlyroll_db -p onlyroll_db < backup.sql
```

### Redis

```bash
# CLI Redis
docker-compose exec redis redis-cli

# Vérifier la connexion
docker-compose exec redis redis-cli ping
```

---

## 📝 Variables d'Environnement

### .env.docker vs .env.docker.example

**`.env.docker.example`** (✅ Commité - Public)
- Template avec valeurs par défaut
- Guide pour les nouvelles variables
- Documentation intégrée

**`.env.docker`** (❌ Gitignored - Privé)
- Votre configuration locale réelle
- À créer manuellement : `cp .env.docker.example .env.docker`
- Peut contenir des secrets de développement
- **Ne jamais commiter !**

### Variables Importantes

| Variable | Dev | Notes |
|----------|-----|-------|
| `APP_ENV` | `dev` | Basculer à `prod` pour tests production |
| `DATABASE_URL` | Points vers `mysql` | Service Docker, pas `127.0.0.1` |
| `REDIS_URL` | Points vers `redis` | Service Docker |
| `MERCURE_JWT_SECRET` | Depuis le Caddyfile | Doit correspondre |
| `CORS_ALLOW_ORIGIN` | `http://localhost:5173` | Domaine du frontend |

---

## 🔍 Troubleshooting

### "Port déjà utilisé"
```bash
# Trouver le processus
lsof -i :80
lsof -i :3306

# Ou changer dans .env.docker
NGINX_PORT=8080
DB_PORT=3307
```

### "MySQL connection refused"
```bash
# Vérifier la santé
docker-compose ps

# Attendre que MySQL soit prêt
docker-compose logs mysql

# Reset la base
docker-compose down -v
docker-compose up -d
```

### "Permission denied on entrypoint.sh"
```bash
# Les permissions sont déjà correctes dans le Dockerfile
# Si problème local :
chmod +x backend/docker/entrypoint.sh
```

### "Hot-reload ne fonctionne pas"
```bash
# Vérifier que les volumes sont mappés
docker-compose exec php-fpm mount | grep "/var/www/html"

# Remapper si nécessaire (Windows/macOS)
# Ajouter ':cached' à la fin du volume
volumes:
  - ./backend:/var/www/html:cached
```

### "Mercure ne reçoit pas les événements"
```bash
# Vérifier que le secret correspond au Caddyfile
echo $MERCURE_JWT_SECRET

# Test simple
curl "http://localhost:3000/.well-known/mercure?topic=test"
```

---

## 🐳 Multi-Stage Builds

### Images Produites

**Backend**
- `php_base` → Base avec extensions PHP
- `php_dependencies` → Composer install
- `php_app` → Application complète
- `php_dev` → Avec Xdebug + config dev
- `php_prod` → Optimisée production (OPcache, no-dev)

**Frontend**
- `node_dependencies` → npm ci
- `node_builder` → Build Vite
- `node_dev` → Dev server + hot-reload
- `nginx_prod` → Production (Nginx + dist)

### Sélection du Stage

```yaml
# Development
build:
  context: ./backend
  target: php_dev

# Production
build:
  context: ./backend
  target: php_prod
```

---

## 🔐 Secrets & Configuration

### Development (Sécurité Réduite OK)
```env
JWT_PASSPHRASE="onlyroll123!"
MERCURE_JWT_SECRET="uhMPKzA2Ta034+z/ktuKM0w6LZTrj1R1tgPBJ4Emwtg="
DB_PASSWORD="onlyroll_docker_pass"
```

### Production (À Changer !)
- Générer des secrets forts
- Utiliser des variables d'environnement
- Ne jamais commiter `.env.docker` en prod
- Utiliser Docker secrets ou gestionnaire

---

## 📊 Composition des Services

```
┌─────────────────────────────────────────────┐
│          DOCKER COMPOSE NETWORK              │
│           (onlyroll-network)                │
└─────────────────────────────────────────────┘
         ↓           ↓          ↓         ↓
    ┌─────────┬─────────┬──────────┬─────────┐
    │ MySQL   │ Redis   │ Mercure  │ Nginx   │
    │ (3306)  │ (6379)  │ (3000)   │ (80)    │
    └────┬────┴────┬────┴────┬─────┴────┬────┘
         │         │         │          │
         └─────────┼─────────┼──────────┘
                   │
            ┌──────┴──────┬────────────┐
            │             │            │
        ┌───────┐    ┌──────────┐  ┌────────┐
        │PHP-FPM│    │Frontend  │  │Healthcheck
        │(9000) │    │(5173)    │
        └───────┘    └──────────┘
```

---

## ✅ Checklist de Configuration

- [ ] `.env.docker` créé (`cp .env.docker.example .env.docker`)
- [ ] Variables adaptées si ports différents
- [ ] `docker-compose build` réussit
- [ ] `docker-compose up -d` démarre les services
- [ ] `docker-compose ps` affiche tous les services "healthy"
- [ ] `curl http://localhost/health` fonctionne
- [ ] Frontend accessible sur `http://localhost:5173`
- [ ] Mercure répond sur `http://localhost:3000/.well-known/mercure`

---

## 🔗 Ressources

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Symfony Docker Guide](https://symfony.com/doc/current/setup/docker.html)
- [Mercure Hub Documentation](https://mercure.rocks/)
- [Vite Documentation](https://vitejs.dev/)

---

**Dernière mise à jour**: 2025-11-17
**Version Docker**: Compose 3.9+
