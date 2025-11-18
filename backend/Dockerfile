# ===========================================
# STAGE 1: Base PHP avec extensions
# ===========================================
FROM php:8.3-fpm-alpine AS php_base

ARG APP_ENV=prod
ARG APP_DEBUG=0

ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    PHP_MEMORY_LIMIT=256M \
    PHP_MAX_EXECUTION_TIME=30 \
    PHP_UPLOAD_MAX_FILESIZE=10M

# Installation dépendances système
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    mysql-client \
    redis \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        opcache \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier configuration PHP
COPY docker/php-fpm/php.ini /usr/local/etc/php/php.ini

# ===========================================
# STAGE 2: Installation des dépendances
# ===========================================
FROM php_base AS php_dependencies

COPY composer.json composer.lock symfony.lock ./

RUN if [ "$APP_ENV" = "prod" ]; then \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction; \
    else \
        composer install --no-scripts --no-autoloader --prefer-dist --no-interaction; \
    fi

# ===========================================
# STAGE 3: Application
# ===========================================
FROM php_dependencies AS php_app

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative \
    && mkdir -p var/cache var/log var/sessions \
    && chmod -R 777 var

# Copier l'entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]

# ===========================================
# STAGE 4: Development
# ===========================================
FROM php_app AS php_dev

ENV APP_ENV=dev \
    APP_DEBUG=1 \
    XDEBUG_MODE=develop,debug

# Installer Xdebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Configuration dev
COPY docker/php-fpm/php-dev.ini /usr/local/etc/php/conf.d/99-dev.ini

# ===========================================
# STAGE 5: Production
# ===========================================
FROM php_app AS php_prod

ENV APP_ENV=prod \
    APP_DEBUG=0

# Optimisation OPcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Warmup cache
RUN php bin/console cache:warmup --env=prod --no-debug || true

# Permissions strictes
RUN chown -R www-data:www-data var \
    && chmod -R 755 var

USER www-data
