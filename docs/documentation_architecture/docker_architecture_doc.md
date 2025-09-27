# Modules
# loadmodule /usr/lib/redis/modules/redisearch.so
# loadmodule /usr/lib/redis/modules/redisjson.so
```

## Variables d'Environnement

### .env.example
```env
# Application
APP_ENV=dev
APP_SECRET=your-app-secret-key
APP_DEBUG=true
APP_DOMAIN=localhost

# Database
DB_HOST=mysql
DB_PORT=3306
DB_NAME=onlyroll
DB_USER=onlyroll_user
DB_PASSWORD=secure_password
DB_ROOT_PASSWORD=secure_root_password

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_URL=redis://redis:6379

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-jwt-passphrase
JWT_TTL=3600

# Mailer
MAILER_DSN=smtp://mailhog:1025

# CORS
CORS_ALLOW_ORIGIN=http://localhost:5173

# WebSocket
WS_PORT=3000
VITE_WS_URL=ws://localhost:3000

# Frontend
VITE_API_URL=http://localhost/api
NODE_ENV=development

# Docker
COMPOSE_PROJECT_NAME=onlyroll
DOCKER_REGISTRY=registry.gitlab.com/onlyroll

# Monitoring
GRAFANA_ADMIN_PASSWORD=admin
PROMETHEUS_RETENTION=15d

# Backups
BACKUP_RETENTION_DAYS=7
S3_BUCKET=onlyroll-backups
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-west-1

# Notifications
SLACK_WEBHOOK_URL=
DISCORD_WEBHOOK_URL=
```

## Monitoring et Observabilité

### Stack de Monitoring
```yaml
# docker-compose.monitoring.yml

version: '3.9'

services:
  # ===========================================
  # PROMETHEUS - Métriques
  # ===========================================
  prometheus:
    image: prom/prometheus:v2.45.0
    container_name: onlyroll-prometheus
    volumes:
      - ./docker/monitoring/prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
      - ./docker/monitoring/prometheus/alerts.yml:/etc/prometheus/alerts.yml:ro
      - prometheus-data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--storage.tsdb.retention.time=${PROMETHEUS_RETENTION:-15d}'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'
      - '--web.enable-lifecycle'
    ports:
      - "9090:9090"
    networks:
      - onlyroll-network
    restart: unless-stopped

  # ===========================================
  # GRAFANA - Dashboards
  # ===========================================
  grafana:
    image: grafana/grafana:10.0.0
    container_name: onlyroll-grafana
    volumes:
      - grafana-data:/var/lib/grafana
      - ./docker/monitoring/grafana/provisioning:/etc/grafana/provisioning:ro
      - ./docker/monitoring/grafana/dashboards:/var/lib/grafana/dashboards:ro
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=${GRAFANA_ADMIN_PASSWORD:-admin}
      - GF_INSTALL_PLUGINS=redis-datasource,mysql-datasource
      - GF_SERVER_ROOT_URL=https://${APP_DOMAIN}/grafana
      - GF_SERVER_SERVE_FROM_SUB_PATH=true
    ports:
      - "3001:3000"
    networks:
      - onlyroll-network
    restart: unless-stopped

  # ===========================================
  # LOKI - Logs
  # ===========================================
  loki:
    image: grafana/loki:2.9.0
    container_name: onlyroll-loki
    volumes:
      - ./docker/monitoring/loki/loki-config.yml:/etc/loki/local-config.yaml:ro
      - loki-data:/loki
    command: -config.file=/etc/loki/local-config.yaml
    ports:
      - "3100:3100"
    networks:
      - onlyroll-network
    restart: unless-stopped

  # ===========================================
  # PROMTAIL - Log Collector
  # ===========================================
  promtail:
    image: grafana/promtail:2.9.0
    container_name: onlyroll-promtail
    volumes:
      - ./docker/monitoring/promtail/promtail-config.yml:/etc/promtail/config.yml:ro
      - /var/log:/var/log:ro
      - /var/lib/docker/containers:/var/lib/docker/containers:ro
    command: -config.file=/etc/promtail/config.yml
    networks:
      - onlyroll-network
    restart: unless-stopped

  # ===========================================
  # NODE EXPORTER - Host Metrics
  # ===========================================
  node-exporter:
    image: prom/node-exporter:v1.6.0
    container_name: onlyroll-node-exporter
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--path.rootfs=/rootfs'
      - '--collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)($|/)'
    ports:
      - "9100:9100"
    networks:
      - onlyroll-network
    restart: unless-stopped

  # ===========================================
  # CADVISOR - Container Metrics
  # ===========================================
  cadvisor:
    image: gcr.io/cadvisor/cadvisor:v0.47.0
    container_name: onlyroll-cadvisor
    volumes:
      - /:/rootfs:ro
      - /var/run:/var/run:ro
      - /sys:/sys:ro
      - /var/lib/docker/:/var/lib/docker:ro
      - /dev/disk/:/dev/disk:ro
    privileged: true
    devices:
      - /dev/kmsg
    ports:
      - "8080:8080"
    networks:
      - onlyroll-network
    restart: unless-stopped

volumes:
  prometheus-data:
    driver: local
  grafana-data:
    driver: local
  loki-data:
    driver: local
```

### Configuration Prometheus
```yaml
# docker/monitoring/prometheus/prometheus.yml

global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    environment: '${APP_ENV}'
    project: 'onlyroll'

alerting:
  alertmanagers:
    - static_configs:
        - targets: []

rule_files:
  - "alerts.yml"

scrape_configs:
  # PHP-FPM Metrics
  - job_name: 'php-fpm'
    static_configs:
      - targets: ['php-fpm:9253']
    metrics_path: /metrics

  # Node Exporter
  - job_name: 'node'
    static_configs:
      - targets: ['node-exporter:9100']

  # cAdvisor
  - job_name: 'cadvisor'
    static_configs:
      - targets: ['cadvisor:8080']

  # MySQL Exporter
  - job_name: 'mysql'
    static_configs:
      - targets: ['mysql-exporter:9104']

  # Redis Exporter
  - job_name: 'redis'
    static_configs:
      - targets: ['redis-exporter:9121']

  # Nginx Exporter
  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx:9113']
```

### Alertes Prometheus
```yaml
# docker/monitoring/prometheus/alerts.yml

groups:
  - name: onlyroll_alerts
    interval: 30s
    rules:
      # High CPU Usage
      - alert: HighCPUUsage
        expr: rate(container_cpu_usage_seconds_total[5m]) * 100 > 80
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High CPU usage detected"
          description: "Container {{ $labels.container_name }} CPU usage is above 80% (current value: {{ $value }}%)"

      # High Memory Usage
      - alert: HighMemoryUsage
        expr: (container_memory_usage_bytes / container_spec_memory_limit_bytes) * 100 > 90
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High memory usage detected"
          description: "Container {{ $labels.container_name }} memory usage is above 90%"

      # Service Down
      - alert: ServiceDown
        expr: up == 0
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "Service is down"
          description: "{{ $labels.job }} has been down for more than 2 minutes"

      # High Response Time
      - alert: HighResponseTime
        expr: histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m])) > 1
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High response time detected"
          description: "95th percentile response time is above 1 second"

      # Database Connection Pool
      - alert: DatabaseConnectionPoolExhausted
        expr: mysql_global_status_threads_connected / mysql_global_variables_max_connections > 0.8
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Database connection pool near exhaustion"
          description: "MySQL connection pool is above 80% capacity"
```

## Commandes Utiles

### Makefile
```makefile
# Makefile

.PHONY: help
help: ## Display this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $1, $2}'

.DEFAULT_GOAL := help

# Variables
DOCKER_COMPOSE = docker-compose
DOCKER_COMPOSE_DEV = $(DOCKER_COMPOSE) -f docker-compose.yml -f docker-compose.dev.yml
DOCKER_COMPOSE_PROD = $(DOCKER_COMPOSE) -f docker-compose.yml -f docker-compose.prod.yml
PHP_CONTAINER = docker-compose exec php-fpm
NODE_CONTAINER = docker-compose exec frontend

# Development
dev-start: ## Start development environment
	$(DOCKER_COMPOSE_DEV) up -d
	@echo "Development environment started"
	@echo "Frontend: http://localhost:5173"
	@echo "API: http://localhost/api"
	@echo "Mailhog: http://localhost:8025"
	@echo "Adminer: http://localhost:8080"

dev-stop: ## Stop development environment
	$(DOCKER_COMPOSE_DEV) down
	@echo "Development environment stopped"

dev-logs: ## Show development logs
	$(DOCKER_COMPOSE_DEV) logs -f

dev-build: ## Rebuild development containers
	$(DOCKER_COMPOSE_DEV) build --no-cache

# Production
prod-start: ## Start production environment
	$(DOCKER_COMPOSE_PROD) up -d
	@echo "Production environment started"

prod-stop: ## Stop production environment
	$(DOCKER_COMPOSE_PROD) down
	@echo "Production environment stopped"

prod-deploy: ## Deploy to production
	./docker/scripts/deploy.sh production

# Database
db-migrate: ## Run database migrations
	$(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

db-rollback: ## Rollback last migration
	$(PHP_CONTAINER) php bin/console doctrine:migrations:migrate prev --no-interaction

db-seed: ## Seed database with test data
	$(PHP_CONTAINER) php bin/console doctrine:fixtures:load --no-interaction

db-import-srd: ## Import SRD data
	$(PHP_CONTAINER) php bin/console onlyroll:import-srd all

db-backup: ## Create database backup
	./docker/scripts/backup.sh

db-restore: ## Restore database from backup
	@read -p "Enter backup timestamp (YYYYMMDD_HHMMSS): " timestamp; \
	./docker/scripts/restore.sh $timestamp

# Tests
test-unit: ## Run unit tests
	$(PHP_CONTAINER) php bin/phpunit --testsuite=unit

test-integration: ## Run integration tests
	$(PHP_CONTAINER) php bin/phpunit --testsuite=integration

test-e2e: ## Run end-to-end tests
	$(NODE_CONTAINER) npm run test:e2e

test-all: test-unit test-integration test-e2e ## Run all tests

# Code Quality
lint-php: ## Lint PHP code
	$(PHP_CONTAINER) vendor/bin/phpcs
	$(PHP_CONTAINER) vendor/bin/phpstan analyse

lint-js: ## Lint JavaScript code
	$(NODE_CONTAINER) npm run lint

format-php: ## Format PHP code
	$(PHP_CONTAINER) vendor/bin/php-cs-fixer fix

format-js: ## Format JavaScript code
	$(NODE_CONTAINER) npm run format

# Cache
cache-clear: ## Clear all caches
	$(PHP_CONTAINER) php bin/console cache:clear
	$(PHP_CONTAINER) redis-cli FLUSHALL

# Monitoring
monitoring-start: ## Start monitoring stack
	docker-compose -f docker-compose.monitoring.yml up -d
	@echo "Grafana: http://localhost:3001"
	@echo "Prometheus: http://localhost:9090"

monitoring-stop: ## Stop monitoring stack
	docker-compose -f docker-compose.monitoring.yml down

# Logs
logs-php: ## Show PHP logs
	$(DOCKER_COMPOSE) logs -f php-fpm

logs-nginx: ## Show Nginx logs
	$(DOCKER_COMPOSE) logs -f nginx

logs-mysql: ## Show MySQL logs
	$(DOCKER_COMPOSE) logs -f mysql

logs-websocket: ## Show WebSocket logs
	$(DOCKER_COMPOSE) logs -f websocket

# Shell Access
shell-php: ## Access PHP container shell
	$(PHP_CONTAINER) bash

shell-mysql: ## Access MySQL shell
	docker-compose exec mysql mysql -u$(DB_USER) -p$(DB_PASSWORD) $(DB_NAME)

shell-redis: ## Access Redis CLI
	docker-compose exec redis redis-cli

# Maintenance
clean: ## Clean all containers and volumes
	$(DOCKER_COMPOSE) down -v
	docker system prune -af

update-deps: ## Update all dependencies
	$(PHP_CONTAINER) composer update
	$(NODE_CONTAINER) npm update

security-check: ## Check for security vulnerabilities
	$(PHP_CONTAINER) composer audit
	$(NODE_CONTAINER) npm audit

# Info
info: ## Show environment info
	@echo "Docker version:"
	@docker --version
	@echo "\nDocker Compose version:"
	@docker-compose --version
	@echo "\nRunning containers:"
	@docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

## Troubleshooting et Optimisations

### Problèmes Courants

#### 1. Permissions sur les volumes
```bash
# Fix des permissions
docker-compose exec php-fpm chown -R www-data:www-data /var/www/var
docker-compose exec php-fpm chmod -R 775 /var/www/var
```

#### 2. Problème de connexion MySQL
```bash
# Vérifier la connexion
docker-compose exec php-fpm php -r "new PDO('mysql:host=mysql;dbname=${DB_NAME}', '${DB_USER}', '${DB_PASSWORD}');"
```

#### 3. Cache Redis
```bash
# Clear Redis cache
docker-compose exec redis redis-cli FLUSHALL
```

### Optimisations de Performance

#### 1. Build Multi-Stage Optimisé
- Utilisation d'Alpine Linux pour réduire la taille des images
- Séparation des dépendances de développement et production
- Cache des layers Docker pour accélérer les builds

#### 2. Configuration PHP-FPM
- Pool dynamique avec scaling automatique
- OPcache activé avec preloading en production
- APCu pour le cache utilisateur

#### 3. Configuration MySQL
- InnoDB buffer pool optimisé
- Query cache désactivé (utilisation de Redis)
- Binary logging pour la réplication

## Conclusion

Cette architecture Docker fournit un environnement complet et optimisé pour OnlyRoll, avec :

**Développement efficace** : Hot-reload, debugging, outils de développement
**Production robuste** : Multi-stage builds, health checks, monitoring
**Scalabilité** : Support du clustering, load balancing, cache distribué
**Sécurité** : Isolation des containers, secrets management, SSL/TLS
**Observabilité** : Métriques, logs centralisés, alerting
**Maintenance** : Scripts automatisés, backups, déploiement zero-downtime

L'architecture est conçue pour évoluer avec les besoins du projet, depuis le développement local jusqu'à la production à grande échelle.

## Scripts d'Orchestration

### Script d'Entrée Principal
```bash
#!/bin/bash
# docker/scripts/entrypoint.sh

set -e

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Fonction de log
log() {
    echo -e "${GREEN}[OnlyRoll]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Vérification des variables d'environnement requises
check_env() {
    log "Checking environment variables..."
    
    required_vars=(
        "APP_ENV"
        "DB_HOST"
        "DB_NAME"
        "DB_USER"
        "DB_PASSWORD"
    )
    
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var}" ]]; then
            error "Required environment variable $var is not set"
        fi
    done
    
    log "Environment check passed ✓"
}

# Attente de la disponibilité de MySQL
wait_for_mysql() {
    log "Waiting for MySQL to be ready..."
    
    max_retries=30
    retry_count=0
    
    until mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" &>/dev/null; do
        retry_count=$((retry_count + 1))
        
        if [[ $retry_count -gt $max_retries ]]; then
            error "MySQL is not available after $max_retries attempts"
        fi
        
        log "MySQL is unavailable - sleeping (attempt $retry_count/$max_retries)"
        sleep 2
    done
    
    log "MySQL is ready ✓"
}

# Attente de Redis
wait_for_redis() {
    log "Waiting for Redis to be ready..."
    
    max_retries=20
    retry_count=0
    
    until redis-cli -h redis ping &>/dev/null; do
        retry_count=$((retry_count + 1))
        
        if [[ $retry_count -gt $max_retries ]]; then
            error "Redis is not available after $max_retries attempts"
        fi
        
        log "Redis is unavailable - sleeping (attempt $retry_count/$max_retries)"
        sleep 1
    done
    
    log "Redis is ready ✓"
}

# Migrations de base de données
run_migrations() {
    log "Running database migrations..."
    
    cd /var/www
    
    # Création de la base de données si elle n'existe pas
    php bin/console doctrine:database:create --if-not-exists --no-interaction
    
    # Exécution des migrations
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    
    # Validation du schéma
    php bin/console doctrine:schema:validate || warning "Schema validation failed"
    
    log "Migrations completed ✓"
}

# Import des données SRD
import_srd_data() {
    log "Importing SRD data..."
    
    cd /var/www
    
    # Vérification si les données sont déjà importées
    spell_count=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
                  -sN -e "SELECT COUNT(*) FROM srd_spell" 2>/dev/null || echo "0")
    
    if [[ "$spell_count" -gt "0" ]]; then
        log "SRD data already imported (found $spell_count spells) ✓"
        return
    fi
    
    # Import des données
    php bin/console onlyroll:import-srd spells --source=/data/srd/spells.json
    php bin/console onlyroll:import-srd monsters --source=/data/srd/monsters.json
    php bin/console onlyroll:import-srd items --source=/data/srd/items.json
    php bin/console onlyroll:import-srd classes --source=/data/srd/classes.json
    php bin/console onlyroll:import-srd races --source=/data/srd/races.json
    
    log "SRD data import completed ✓"
}

# Clear cache Symfony
clear_cache() {
    log "Clearing application cache..."
    
    cd /var/www
    
    php bin/console cache:clear --env="$APP_ENV" --no-warmup
    php bin/console cache:warmup --env="$APP_ENV"
    
    # Permissions sur le cache
    chown -R www-data:www-data /var/www/var
    chmod -R 775 /var/www/var
    
    log "Cache cleared ✓"
}

# Configuration selon l'environnement
configure_environment() {
    log "Configuring for $APP_ENV environment..."
    
    case "$APP_ENV" in
        dev)
            log "Development mode enabled"
            # Activation de Xdebug
            docker-php-ext-enable xdebug || warning "Xdebug not available"
            ;;
        test)
            log "Test mode enabled"
            # Configuration pour les tests
            ;;
        prod)
            log "Production mode enabled"
            # Optimisations production
            composer dump-autoload --optimize --no-dev
            # Preloading PHP
            if [[ -f /var/www/config/preload.php ]]; then
                log "Enabling PHP preloading"
                echo "opcache.preload=/var/www/config/preload.php" >> /usr/local/etc/php/conf.d/opcache.ini
            fi
            ;;
        *)
            warning "Unknown environment: $APP_ENV"
            ;;
    esac
}

# Point d'entrée principal
main() {
    log "Starting OnlyRoll initialization..."
    
    check_env
    wait_for_mysql
    wait_for_redis
    run_migrations
    
    if [[ "$IMPORT_SRD_DATA" == "true" ]]; then
        import_srd_data
    fi
    
    clear_cache
    configure_environment
    
    log "Initialization complete! Starting application..."
    
    # Lancement de l'application
    exec "$@"
}

# Exécution
main "$@"
```

### Script de Sauvegarde
```bash
#!/bin/bash
# docker/scripts/backup.sh

set -e

# Configuration
BACKUP_DIR="/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-7}

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[BACKUP]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    exit 1
}

# Création du répertoire de sauvegarde
create_backup_dir() {
    BACKUP_PATH="$BACKUP_DIR/$TIMESTAMP"
    mkdir -p "$BACKUP_PATH"
    log "Created backup directory: $BACKUP_PATH"
}

# Sauvegarde MySQL
backup_mysql() {
    log "Starting MySQL backup..."
    
    mysqldump \
        -h"$DB_HOST" \
        -u"$DB_USER" \
        -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB_NAME" \
        | gzip > "$BACKUP_PATH/mysql_${DB_NAME}_${TIMESTAMP}.sql.gz"
    
    log "MySQL backup completed ✓"
}

# Sauvegarde Redis
backup_redis() {
    log "Starting Redis backup..."
    
    redis-cli -h redis --rdb "$BACKUP_PATH/redis_${TIMESTAMP}.rdb"
    
    log "Redis backup completed ✓"
}

# Sauvegarde des uploads
backup_uploads() {
    log "Starting uploads backup..."
    
    if [[ -d "/var/www/uploads" ]]; then
        tar -czf "$BACKUP_PATH/uploads_${TIMESTAMP}.tar.gz" -C /var/www uploads/
        log "Uploads backup completed ✓"
    else
        log "No uploads directory found, skipping..."
    fi
}

# Nettoyage des anciennes sauvegardes
cleanup_old_backups() {
    log "Cleaning old backups (older than $RETENTION_DAYS days)..."
    
    find "$BACKUP_DIR" -maxdepth 1 -type d -mtime +$RETENTION_DAYS -exec rm -rf {} \; 2>/dev/null || true
    
    log "Cleanup completed ✓"
}

# Upload vers S3 (optionnel)
upload_to_s3() {
    if [[ -n "$S3_BUCKET" ]]; then
        log "Uploading backup to S3..."
        
        aws s3 cp "$BACKUP_PATH" "s3://$S3_BUCKET/backups/$TIMESTAMP/" --recursive
        
        log "S3 upload completed ✓"
    fi
}

# Notification
send_notification() {
    if [[ -n "$SLACK_WEBHOOK_URL" ]]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"✅ OnlyRoll backup completed: $TIMESTAMP\"}" \
            "$SLACK_WEBHOOK_URL"
    fi
}

# Main
main() {
    log "Starting OnlyRoll backup process..."
    
    create_backup_dir
    backup_mysql
    backup_redis
    backup_uploads
    cleanup_old_backups
    upload_to_s3
    send_notification
    
    log "Backup process completed successfully!"
}

# Gestion des erreurs
trap 'error "Backup failed!"' ERR

# Exécution
main
```

### Script de Déploiement
```bash
#!/bin/bash
# docker/scripts/deploy.sh

set -e

# Configuration
DEPLOY_ENV=${1:-production}
DOCKER_REGISTRY=${DOCKER_REGISTRY:-"registry.gitlab.com/onlyroll"}
VERSION=${CI_COMMIT_TAG:-latest}

# Couleurs
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[DEPLOY]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    exit 1
}

# Vérification des prérequis
check_requirements() {
    log "Checking deployment requirements..."
    
    # Docker
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed"
    fi
    
    # Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed"
    fi
    
    # Environnement
    if [[ ! -f ".env.$DEPLOY_ENV" ]]; then
        error "Environment file .env.$DEPLOY_ENV not found"
    fi
    
    success "Requirements check passed ✓"
}

# Build des images
build_images() {
    log "Building Docker images..."
    
    # Build avec cache
    docker-compose \
        -f docker-compose.yml \
        -f docker-compose.prod.yml \
        build \
        --parallel \
        --build-arg VERSION="$VERSION" \
        --build-arg BUILD_DATE="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
    
    success "Images built successfully ✓"
}

# Tag et push des images
push_images() {
    log "Pushing images to registry..."
    
    services=("nginx" "php-fpm" "frontend" "websocket")
    
    for service in "${services[@]}"; do
        docker tag "onlyroll-$service:latest" "$DOCKER_REGISTRY/$service:$VERSION"
        docker tag "onlyroll-$service:latest" "$DOCKER_REGISTRY/$service:latest"
        
        docker push "$DOCKER_REGISTRY/$service:$VERSION"
        docker push "$DOCKER_REGISTRY/$service:latest"
        
        log "Pushed $service:$VERSION ✓"
    done
    
    success "All images pushed to registry ✓"
}

# Sauvegarde avant déploiement
pre_deploy_backup() {
    log "Creating pre-deployment backup..."
    
    ./docker/scripts/backup.sh
    
    success "Backup completed ✓"
}

# Déploiement avec zero-downtime
deploy_zero_downtime() {
    log "Starting zero-downtime deployment..."
    
    # Pull des nouvelles images
    docker-compose \
        -f docker-compose.yml \
        -f docker-compose.prod.yml \
        pull
    
    # Démarrage des nouveaux containers
    docker-compose \
        -f docker-compose.yml \
        -f docker-compose.prod.yml \
        up -d --no-deps --scale php-fpm=2 php-fpm
    
    # Attente de la santé des nouveaux containers
    sleep 10
    
    # Basculement du trafic
    docker-compose \
        -f docker-compose.yml \
        -f docker-compose.prod.yml \
        up -d --no-deps nginx
    
    # Arrêt des anciens containers
    docker-compose \
        -f docker-compose.yml \
        -f docker-compose.prod.yml \
        up -d --no-deps --scale php-fpm=1 php-fpm
    
    success "Zero-downtime deployment completed ✓"
}

# Tests post-déploiement
post_deploy_tests() {
    log "Running post-deployment tests..."
    
    # Test de santé
    if ! curl -f https://"$APP_DOMAIN"/health; then
        error "Health check failed"
    fi
    
    # Test API
    if ! curl -f https://"$APP_DOMAIN"/api/health; then
        error "API health check failed"
    fi
    
    # Test WebSocket
    if ! timeout 5 node -e "const io = require('socket.io-client'); const socket = io('wss://$APP_DOMAIN'); socket.on('connect', () => { console.log('Connected'); process.exit(0); }); socket.on('error', () => process.exit(1));"; then
        warning "WebSocket test failed (non-critical)"
    fi
    
    success "Post-deployment tests passed ✓"
}

# Rollback en cas d'échec
rollback() {
    error "Deployment failed! Starting rollback..."
    
    # Restauration de la version précédente
    docker-compose \
        -f docker-compose.yml \
        -f docker-compose.prod.yml \
        down
    
    # Pull de la version précédente
    PREVIOUS_VERSION=$(docker images --format "{{.Tag}}" "$DOCKER_REGISTRY/php-fpm" | head -n 2 | tail -n 1)
    
    for service in nginx php-fpm frontend websocket; do
        docker pull "$DOCKER_REGISTRY/$service:$PREVIOUS_VERSION"
        docker tag "$DOCKER_REGISTRY/$service:$PREVIOUS_VERSION" "onlyroll-$service:latest"
    done
    
    # Redémarrage avec l'ancienne version
    docker-compose \
        -f docker-compose.yml \
        -f docker-compose.prod.yml \
        up -d
    
    warning "Rollback completed. Please investigate the issue."
    exit 1
}

# Notification de déploiement
notify_deployment() {
    local status=$1
    local message=$2
    
    # Slack notification
    if [[ -n "$SLACK_WEBHOOK_URL" ]]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"🚀 OnlyRoll Deployment: $status\\n$message\"}" \
            "$SLACK_WEBHOOK_URL"
    fi
    
    # Discord notification
    if [[ -n "$DISCORD_WEBHOOK_URL" ]]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"content\":\"**OnlyRoll Deployment**: $status\\n$message\"}" \
            "$DISCORD_WEBHOOK_URL"
    fi
}

# Main deployment process
main() {
    log "Starting OnlyRoll deployment to $DEPLOY_ENV..."
    log "Version: $VERSION"
    
    # Gestion des erreurs
    trap rollback ERR
    
    check_requirements
    
    # Chargement de l'environnement
    export $(cat ".env.$DEPLOY_ENV" | xargs)
    
    if [[ "$DEPLOY_ENV" == "production" ]]; then
        pre_deploy_backup
    fi
    
    build_images
    
    if [[ -n "$DOCKER_REGISTRY" ]]; then
        push_images
    fi
    
    deploy_zero_downtime
    post_deploy_tests
    
    notify_deployment "SUCCESS ✅" "Version $VERSION deployed to $DEPLOY_ENV"
    
    success "Deployment completed successfully! 🎉"
}

# Exécution
main "$@"# Architecture Docker OnlyRoll - Documentation Technique

## Vue d'Ensemble

L'architecture Docker d'OnlyRoll fournit un environnement containerisé complet pour le développement, les tests et la production, avec une orchestration optimisée pour la scalabilité et la performance.

### Stack Container
- **Orchestration** : Docker Compose 2.23+
- **Runtime** : Docker Engine 24.0+
- **Registry** : Docker Hub / GitLab Registry
- **Reverse Proxy** : nginx 1.25 Alpine
- **Load Balancer** : Traefik 3.0
- **Monitoring** : Prometheus + Grafana
- **Logs** : ELK Stack (Elasticsearch, Logstash, Kibana)

### Philosophie de Containerisation
```
┌─────────────────────────────────────────────────┐
│                   INTERNET                      │
└─────────────────┬───────────────────────────────┘
                  │
        ┌─────────▼─────────┐
        │   Load Balancer   │ (Traefik/nginx)
        │    Port 80/443    │
        └─────────┬─────────┘
                  │
    ┌─────────────┼─────────────┬─────────────┐
    │             │             │             │
┌───▼───┐    ┌───▼───┐    ┌───▼───┐    ┌────▼────┐
│ Web 1 │    │ Web 2 │    │ Web N │    │WebSocket│
│Vue.js │    │Vue.js │    │Vue.js │    │Socket.io│
└───┬───┘    └───┬───┘    └───┬───┘    └────┬────┘
    │            │            │              │
    └────────────┼────────────┘              │
                 │                           │
         ┌───────▼────────┐         ┌───────▼────────┐
         │   API Gateway  │         │  WS Server     │
         │   (PHP-FPM)    │         │  (ReactPHP)    │
         └───────┬────────┘         └───────┬────────┘
                 │                           │
    ┌────────────┼───────────┬───────────────┘
    │            │           │
┌───▼───┐   ┌───▼───┐   ┌───▼───┐
│ MySQL │   │ Redis │   │  S3   │
│Master │   │Cache  │   │Storage│
└───────┘   └───────┘   └───────┘
```

## Structure des Fichiers Docker

```
docker/
├── compose/                      # Configurations Docker Compose
│   ├── docker-compose.yml        # Configuration principale
│   ├── docker-compose.dev.yml    # Override développement
│   ├── docker-compose.test.yml   # Override tests
│   └── docker-compose.prod.yml   # Override production
├── containers/                   # Dockerfiles par service
│   ├── nginx/
│   │   ├── Dockerfile           # Multi-stage build nginx
│   │   ├── nginx.conf           # Configuration principale
│   │   ├── conf.d/              # Virtual hosts
│   │   └── ssl/                 # Certificats SSL
│   ├── php/
│   │   ├── Dockerfile           # PHP 8.1 FPM Alpine
│   │   ├── php.ini              # Configuration PHP
│   │   ├── php-fpm.conf         # Configuration FPM
│   │   └── supervisord.conf     # Multi-process
│   ├── node/
│   │   ├── Dockerfile           # Node 20 Alpine pour Vue.js
│   │   └── entrypoint.sh        # Script d'entrée
│   ├── mysql/
│   │   ├── Dockerfile           # MySQL 9.0 custom
│   │   ├── my.cnf               # Configuration optimisée
│   │   └── init/                # Scripts d'initialisation
│   ├── redis/
│   │   ├── Dockerfile           # Redis 7.2 Alpine
│   │   └── redis.conf           # Configuration cluster
│   ├── websocket/
│   │   ├── Dockerfile           # WebSocket server
│   │   └── supervisor.conf      # Process management
│   └── tools/
│       ├── mailhog/             # Mail catcher dev
│       ├── adminer/             # DB admin interface
│       └── elasticsearch/       # Search engine
├── scripts/                     # Scripts d'orchestration
│   ├── entrypoint.sh           # Script d'entrée principal
│   ├── healthcheck.sh          # Vérification santé
│   ├── backup.sh               # Sauvegarde automatique
│   └── deploy.sh               # Déploiement production
├── volumes/                     # Volumes persistants
│   ├── mysql/                  # Données MySQL
│   ├── redis/                  # Dumps Redis
│   ├── uploads/                # Fichiers uploadés
│   └── logs/                   # Logs applicatifs
└── .env.example                # Variables d'environnement
```

## Configuration Docker Compose

### docker-compose.yml (Principal)
```yaml
version: '3.9'

x-logging: &default-logging
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"

x-restart-policy: &restart-policy
  restart: unless-stopped

networks:
  onlyroll-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16

volumes:
  mysql-data:
    driver: local
  redis-data:
    driver: local
  php-sessions:
    driver: local
  uploads:
    driver: local
  ssl-certs:
    driver: local

services:
  # ===========================================
  # REVERSE PROXY & LOAD BALANCER
  # ===========================================
  nginx:
    build:
      context: ./containers/nginx
      dockerfile: Dockerfile
      args:
        NGINX_VERSION: 1.25-alpine
    container_name: onlyroll-nginx
    <<: *restart-policy
    ports:
      - "${HTTP_PORT:-80}:80"
      - "${HTTPS_PORT:-443}:443"
    volumes:
      - ./containers/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./containers/nginx/conf.d:/etc/nginx/conf.d:ro
      - ssl-certs:/etc/nginx/ssl:ro
      - ./public:/var/www/public:ro
      - uploads:/var/www/uploads:ro
    depends_on:
      - php-fpm
      - frontend
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.2
    environment:
      - NGINX_HOST=${APP_DOMAIN}
      - NGINX_PORT=80
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 10s
    logging: *default-logging

  # ===========================================
  # FRONTEND - Vue.js Application
  # ===========================================
  frontend:
    build:
      context: ./frontend
      dockerfile: ../docker/containers/node/Dockerfile
      target: ${BUILD_TARGET:-development}
      args:
        NODE_VERSION: 20-alpine
        VITE_API_URL: ${VITE_API_URL:-http://localhost/api}
        VITE_WS_URL: ${VITE_WS_URL:-ws://localhost:3000}
    container_name: onlyroll-frontend
    <<: *restart-policy
    volumes:
      - ./frontend:/app
      - /app/node_modules
    environment:
      - NODE_ENV=${NODE_ENV:-development}
      - VITE_API_URL=${VITE_API_URL}
      - VITE_WS_URL=${VITE_WS_URL}
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.3
    command: ${FRONTEND_CMD:-npm run dev}
    logging: *default-logging

  # ===========================================
  # BACKEND API - PHP Symfony
  # ===========================================
  php-fpm:
    build:
      context: ./backend
      dockerfile: ../docker/containers/php/Dockerfile
      target: ${BUILD_TARGET:-development}
      args:
        PHP_VERSION: 8.1-fpm-alpine
        COMPOSER_VERSION: 2.6
    container_name: onlyroll-php
    <<: *restart-policy
    volumes:
      - ./backend:/var/www
      - php-sessions:/var/www/var/sessions
      - uploads:/var/www/public/uploads
      - ./docker/containers/php/php.ini:/usr/local/etc/php/php.ini:ro
      - ./docker/containers/php/php-fpm.conf:/usr/local/etc/php-fpm.d/www.conf:ro
    environment:
      - APP_ENV=${APP_ENV:-dev}
      - APP_SECRET=${APP_SECRET}
      - DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@mysql:3306/${DB_NAME}?serverVersion=9.0
      - JWT_SECRET_KEY=${JWT_SECRET_KEY}
      - JWT_PUBLIC_KEY=${JWT_PUBLIC_KEY}
      - JWT_PASSPHRASE=${JWT_PASSPHRASE}
      - REDIS_URL=redis://redis:6379
      - MAILER_DSN=${MAILER_DSN:-smtp://mailhog:1025}
      - CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-http://localhost:3000}
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.4
    healthcheck:
      test: ["CMD", "php-fpm", "-t"]
      interval: 30s
      timeout: 10s
      retries: 3
    logging: *default-logging

  # ===========================================
  # WEBSOCKET SERVER
  # ===========================================
  websocket:
    build:
      context: ./websocket
      dockerfile: ../docker/containers/websocket/Dockerfile
      args:
        NODE_VERSION: 20-alpine
    container_name: onlyroll-websocket
    <<: *restart-policy
    ports:
      - "${WS_PORT:-3000}:3000"
    volumes:
      - ./websocket:/app
      - /app/node_modules
    environment:
      - NODE_ENV=${NODE_ENV:-development}
      - WS_PORT=3000
      - REDIS_URL=redis://redis:6379
      - JWT_SECRET=${JWT_SECRET_KEY}
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_NAME=${DB_NAME}
      - DB_USER=${DB_USER}
      - DB_PASSWORD=${DB_PASSWORD}
    depends_on:
      - redis
      - mysql
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.5
    healthcheck:
      test: ["CMD", "wget", "--spider", "http://localhost:3000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
    logging: *default-logging

  # ===========================================
  # DATABASE - MySQL
  # ===========================================
  mysql:
    build:
      context: ./docker/containers/mysql
      dockerfile: Dockerfile
      args:
        MYSQL_VERSION: 9.0
    container_name: onlyroll-mysql
    <<: *restart-policy
    ports:
      - "${DB_PORT:-3306}:3306"
    volumes:
      - mysql-data:/var/lib/mysql
      - ./docker/containers/mysql/my.cnf:/etc/mysql/conf.d/my.cnf:ro
      - ./docker/containers/mysql/init:/docker-entrypoint-initdb.d:ro
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_NAME}
      - MYSQL_USER=${DB_USER}
      - MYSQL_PASSWORD=${DB_PASSWORD}
      - TZ=Europe/Paris
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.6
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
    logging: *default-logging

  # ===========================================
  # CACHE - Redis
  # ===========================================
  redis:
    build:
      context: ./docker/containers/redis
      dockerfile: Dockerfile
      args:
        REDIS_VERSION: 7.2-alpine
    container_name: onlyroll-redis
    <<: *restart-policy
    ports:
      - "${REDIS_PORT:-6379}:6379"
    volumes:
      - redis-data:/data
      - ./docker/containers/redis/redis.conf:/usr/local/etc/redis/redis.conf:ro
    command: redis-server /usr/local/etc/redis/redis.conf
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.7
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 3
    logging: *default-logging

  # ===========================================
  # DEVELOPMENT TOOLS (dev environment only)
  # ===========================================
  mailhog:
    image: mailhog/mailhog:latest
    container_name: onlyroll-mailhog
    ports:
      - "1025:1025"
      - "8025:8025"
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.10
    profiles:
      - dev
    logging: *default-logging

  adminer:
    image: adminer:4.8.1
    container_name: onlyroll-adminer
    <<: *restart-policy
    ports:
      - "8080:8080"
    environment:
      - ADMINER_DEFAULT_SERVER=mysql
      - ADMINER_DESIGN=pepa-linha-dark
    networks:
      onlyroll-network:
        ipv4_address: 172.20.0.11
    profiles:
      - dev
    logging: *default-logging
```

### docker-compose.dev.yml (Override Développement)
```yaml
version: '3.9'

services:
  frontend:
    build:
      target: development
    volumes:
      - ./frontend:/app
      - /app/node_modules
      - /app/.vite
    environment:
      - NODE_ENV=development
      - VITE_API_URL=http://localhost:8000/api
      - VITE_WS_URL=ws://localhost:3000
    command: npm run dev -- --host 0.0.0.0
    ports:
      - "5173:5173"

  php-fpm:
    build:
      target: development
    volumes:
      - ./backend:/var/www
      - ./backend/vendor:/var/www/vendor
      - ./backend/var:/var/www/var
    environment:
      - APP_ENV=dev
      - APP_DEBUG=true
      - XDEBUG_MODE=develop,debug
      - XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003
      - PHP_IDE_CONFIG=serverName=onlyroll

  websocket:
    build:
      target: development
    volumes:
      - ./websocket:/app
      - /app/node_modules
    environment:
      - NODE_ENV=development
      - DEBUG=socket.io:*
    command: npm run dev

  mysql:
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=root_dev_password
    volumes:
      - ./docker/volumes/mysql-dev:/var/lib/mysql
      - ./database/dumps:/dumps

  redis:
    ports:
      - "6379:6379"
    command: redis-server --appendonly yes
```

### docker-compose.prod.yml (Override Production)
```yaml
version: '3.9'

services:
  nginx:
    build:
      args:
        NGINX_VERSION: 1.25-alpine
    volumes:
      - ./dist:/var/www/public:ro
      - ./ssl:/etc/nginx/ssl:ro
    environment:
      - NGINX_HOST=${APP_DOMAIN}
      - NGINX_HTTPS=on
    deploy:
      replicas: 2
      update_config:
        parallelism: 1
        delay: 10s
      restart_policy:
        condition: any
        delay: 5s
        max_attempts: 3

  frontend:
    build:
      target: production
      args:
        - VITE_API_URL=https://${APP_DOMAIN}/api
        - VITE_WS_URL=wss://${APP_DOMAIN}/ws
    volumes:
      - ./dist:/app/dist:ro
    command: ["nginx", "-g", "daemon off;"]

  php-fpm:
    build:
      target: production
    environment:
      - APP_ENV=prod
      - APP_DEBUG=false
    volumes:
      - ./backend/public:/var/www/public:ro
      - ./backend/var:/var/www/var
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '1'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M

  mysql:
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD_PROD}
    volumes:
      - mysql-prod-data:/var/lib/mysql
      - ./backups/mysql:/backups
    deploy:
      placement:
        constraints:
          - node.role == manager

  redis:
    command: redis-server /usr/local/etc/redis/redis.conf --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-prod-data:/data
    deploy:
      replicas: 1

volumes:
  mysql-prod-data:
    driver: local
    driver_opts:
      type: none
      device: /mnt/storage/mysql
      o: bind
  redis-prod-data:
    driver: local
```

## Dockerfiles Optimisés

### PHP-FPM Multi-Stage
```dockerfile
# docker/containers/php/Dockerfile

# ============================================
# Stage 1: Dependencies
# ============================================
FROM php:8.1-fpm-alpine AS dependencies

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    zip \
    unzip \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    postgresql-dev \
    libzip-dev \
    linux-headers \
    $PHPIZE_DEPS

# Installation des extensions PHP
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        gd \
        intl \
        mbstring \
        xml \
        zip \
        opcache \
        bcmath \
        sockets

# Installation de Redis et APCu
RUN pecl install redis apcu \
    && docker-php-ext-enable redis apcu

# Installation de Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# ============================================
# Stage 2: Development
# ============================================
FROM dependencies AS development

# Installation de Xdebug pour le développement
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuration PHP pour le développement
COPY php.ini-development /usr/local/etc/php/php.ini
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Configuration Xdebug
RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www

# Création de l'utilisateur www-data
RUN addgroup -g 1000 -S www-data \
    && adduser -u 1000 -S www-data -G www-data

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

# ============================================
# Stage 3: Builder
# ============================================
FROM dependencies AS builder

WORKDIR /var/www

# Copie des fichiers de l'application
COPY --chown=www-data:www-data composer.json composer.lock symfony.lock ./

# Installation des dépendances sans dev
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

# Copie du code source
COPY --chown=www-data:www-data . .

# Génération du cache Symfony
RUN composer run-script post-install-cmd \
    && php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod

# ============================================
# Stage 4: Production
# ============================================
FROM php:8.1-fpm-alpine AS production

# Installation minimale des dépendances
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libzip \
    postgresql-libs

# Copie des extensions PHP depuis le stage dependencies
COPY --from=dependencies /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=dependencies /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Configuration PHP optimisée pour la production
COPY php.ini-production /usr/local/etc/php/php.ini
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Configuration OPcache pour la production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.preload=/var/www/config/preload.php" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.preload_user=www-data" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

# Copie de l'application depuis le builder
COPY --from=builder --chown=www-data:www-data /var/www /var/www

# Création de l'utilisateur www-data
RUN addgroup -g 1000 -S www-data \
    && adduser -u 1000 -S www-data -G www-data

# Permissions
RUN chown -R www-data:www-data /var/www/var \
    && chmod -R 775 /var/www/var

USER www-data

EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php-fpm -t || exit 1

CMD ["php-fpm"]
```

### Node.js Frontend Multi-Stage
```dockerfile
# docker/containers/node/Dockerfile

# ============================================
# Stage 1: Dependencies
# ============================================
FROM node:20-alpine AS dependencies

WORKDIR /app

# Installation des dépendances système
RUN apk add --no-cache python3 make g++

# Copie des fichiers de configuration
COPY package*.json ./

# Installation des dépendances
RUN npm ci --only=production

# ============================================
# Stage 2: Development
# ============================================
FROM node:20-alpine AS development

WORKDIR /app

# Installation des outils de développement
RUN apk add --no-cache git

# Copie des dépendances
COPY package*.json ./
RUN npm ci

# Copie du code source
COPY . .

# Variables d'environnement pour le développement
ENV NODE_ENV=development
ENV VITE_HMR_PORT=5173
ENV VITE_HMR_HOST=localhost

EXPOSE 5173

# Command pour le développement avec hot-reload
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# ============================================
# Stage 3: Builder
# ============================================
FROM node:20-alpine AS builder

WORKDIR /app

# Arguments de build
ARG VITE_API_URL
ARG VITE_WS_URL
ARG NODE_ENV=production

# Copie des dépendances et du code
COPY package*.json ./
RUN npm ci

COPY . .

# Build de l'application
RUN npm run build

# ============================================
# Stage 4: Production
# ============================================
FROM nginx:1.25-alpine AS production

# Installation de curl pour healthcheck
RUN apk add --no-cache curl

# Copie de la configuration nginx
COPY docker/containers/nginx/nginx-spa.conf /etc/nginx/nginx.conf

# Copie des fichiers buildés
COPY --from=builder /app/dist /usr/share/nginx/html

# Configuration des permissions
RUN chown -R nginx:nginx /usr/share/nginx/html \
    && chmod -R 755 /usr/share/nginx/html

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### WebSocket Server
```dockerfile
# docker/containers/websocket/Dockerfile

# ============================================
# Stage 1: Development
# ============================================
FROM node:20-alpine AS development

WORKDIR /app

# Installation des dépendances système
RUN apk add --no-cache python3 make g++

# Copie des fichiers de configuration
COPY package*.json ./
RUN npm ci

# Copie du code source
COPY . .

# Installation de nodemon pour le développement
RUN npm install -g nodemon

EXPOSE 3000

CMD ["nodemon", "server.js"]

# ============================================
# Stage 2: Production
# ============================================
FROM node:20-alpine AS production

WORKDIR /app

# Installation de PM2 pour la gestion des processus
RUN npm install -g pm2

# Copie des dépendances et du code
COPY package*.json ./
RUN npm ci --only=production

COPY . .

# Configuration PM2
COPY ecosystem.config.js .

# Utilisateur non-root
RUN addgroup -g 1001 -S nodejs \
    && adduser -S nodejs -u 1001 -G nodejs

USER nodejs

EXPOSE 3000

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD node healthcheck.js || exit 1

CMD ["pm2-runtime", "start", "ecosystem.config.js"]
```

### MySQL Configuration
```ini
# docker/containers/mysql/my.cnf

[mysqld]
# Basic Settings
port = 3306
bind-address = 0.0.0.0
default_storage_engine = InnoDB
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
skip-character-set-client-handshake

# Connection Limits
max_connections = 200
max_allowed_packet = 64M
thread_cache_size = 8
thread_stack = 256K

# InnoDB Configuration
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_flush_log_at_trx_commit = 2
innodb_thread_concurrency = 8
innodb_read_io_threads = 8
innodb_write_io_threads = 8
innodb_io_capacity = 2000
innodb_io_capacity_max = 3000

# Query Cache (deprecated in MySQL 8.0)
# Use ProxySQL or application-level caching instead

# Performance Schema
performance_schema = ON
performance_schema_consumer_events_statements_history = ON
performance_schema_consumer_events_stages_history = ON

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Binary Logging for Replication
server-id = 1
log_bin = mysql-bin
binlog_format = ROW
expire_logs_days = 7
max_binlog_size = 100M
sync_binlog = 1

# Replication Settings (for read replicas)
relay_log = relay-bin
relay_log_recovery = 1
slave_skip_errors = 1062

[client]
default-character-set = utf8mb4

[mysql]
default-character-set = utf8mb4
```

### Redis Configuration
```conf
# docker/containers/redis/redis.conf

# Network and Basic
bind 0.0.0.0
protected-mode yes
port 6379
tcp-backlog 511
timeout 0
tcp-keepalive 300

# Memory Management
maxmemory 512mb
maxmemory-policy allkeys-lru
maxmemory-samples 5

# Persistence
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /data

# AOF (Append Only File)
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb

# Logging
loglevel notice
logfile ""

# Slow Log
slowlog-log-slower-than 10000
slowlog-max-len 128

# Advanced
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
list-compress-depth 0
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64
activerehashing yes
client-output-buffer-limit normal 0 0 0
client-output-buffer-limit replica 256mb 64mb 60
hz 10

# Modules
# loadmodule /usr/lib/redis/modules/redisearch.so
# loadmodule /usr/lib/redis/modules/redisjson.so
```

## Configuration des Services

### nginx.conf Production
```nginx
# docker/containers/nginx/nginx.conf

user nginx;
worker_processes auto;
worker_rlimit_nofile 65535;

error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Logging
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for" '
                    'rt=$request_time uct="$upstream_connect_time" '
                    'uht="$upstream_header_time" urt="$upstream_response_time"';

    access_log /var/log/nginx/access.log main buffer=32k flush=5s;

    # Performance
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    keepalive_requests 100;
    
    # Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript 
               application/json application/javascript application/xml+rss 
               application/rss+xml application/atom+xml image/svg+xml 
               text/x-js text/x-cross-domain-policy application/x-font-ttf 
               application/x-font-opentype application/vnd.ms-fontobject 
               image/x-icon;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=auth:10m rate=5r/m;
    limit_conn_zone $binary_remote_addr zone=addr:10m;

    # Cache
    proxy_cache_path /var/cache/nginx levels=1:2 keys_zone=api_cache:10m 
                     max_size=1g inactive=60m use_temp_path=off;

    # Upstreams
    upstream php_backend {
        least_conn;
        server php-fpm:9000 max_fails=3 fail_timeout=30s;
        keepalive 32;
    }

    upstream websocket_backend {
        ip_hash;
        server websocket:3000;
        keepalive 64;
    }

    # Virtual Host Configuration
    server {
        listen 80;
        listen [::]:80;
        server_name ${NGINX_HOST};
        
        # Redirect to HTTPS
        return 301 https://$server_name$request_uri;
    }

    server {
        listen 443 ssl http2;
        listen [::]:443 ssl http2;
        server_name ${NGINX_HOST};

        # SSL Configuration
        ssl_certificate /etc/nginx/ssl/cert.pem;
        ssl_certificate_key /etc/nginx/ssl/key.pem;
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers HIGH:!aNULL:!MD5;
        ssl_prefer_server_ciphers on;
        ssl_session_cache shared:SSL:10m;
        ssl_session_timeout 10m;

        root /var/www/public;
        index index.html index.php;

        # Security Headers for HTTPS
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
        add_header Content-Security-Policy "default-src 'self' wss: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;

        # Frontend Routes (Vue.js SPA)
        location / {
            try_files $uri $uri/ /index.html;
            expires 30d;
            add_header Cache-Control "public, immutable";
        }

        # API Routes
        location /api {
            limit_req zone=api burst=20 nodelay;
            limit_conn addr 10;

            try_files $uri /index.php$is_args$args;

            # CORS Headers
            add_header 'Access-Control-Allow-Origin' '$http_origin' always;
            add_header 'Access-Control-Allow-Credentials' 'true' always;
            add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
            add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization' always;

            if ($request_method = 'OPTIONS') {
                add_header 'Access-Control-Max-Age' 1728000;
                add_header 'Content-Type' 'text/plain charset=UTF-8';
                add_header 'Content-Length' 0;
                return 204;
            }
        }

        # Authentication Rate Limiting
        location /api/auth {
            limit_req zone=auth burst=2 nodelay;
            try_files $uri /index.php$is_args$args;
        }

        # WebSocket Proxy
        location /socket.io/ {
            proxy_pass http://websocket_backend;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection "upgrade";
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
            
            # WebSocket timeouts
            proxy_connect_timeout 7d;
            proxy_send_timeout 7d;
            proxy_read_timeout 7d;
        }

        # PHP Processing
        location ~ \.php$ {
            fastcgi_pass php_backend;
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            fastcgi_param HTTPS on;
            
            # PHP Performance
            fastcgi_buffer_size 128k;
            fastcgi_buffers 256 16k;
            fastcgi_busy_buffers_size 256k;
            fastcgi_temp_file_write_size 256k;
            fastcgi_intercept_errors on;
            
            # Cache for GET requests
            fastcgi_cache api_cache;
            fastcgi_cache_key "$scheme$request_method$host$request_uri";
            fastcgi_cache_valid 200 60m;
            fastcgi_cache_bypass $http_pragma $http_authorization;
            fastcgi_no_cache $http_pragma $http_authorization;
            add_header X-Cache-Status $upstream_cache_status;
        }

        # Static Assets
        location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            access_log off;
        }

        # Uploads Directory
        location /uploads {
            alias /var/www/uploads;
            expires 7d;
            add_header Cache-Control "public";
            
            # Security for uploads
            location ~ \.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$ {
                deny all;
            }
        }

        # Health Check
        location /health {
            access_log off;
            add_header 'Content-Type' 'application/json';
            return 200 '{"status":"healthy"}';
        }

        # Deny access to hidden files
        location ~ /\. {
            deny all;
            access_log off;
            log_not_found off;
        }
    }
}