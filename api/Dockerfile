FROM php:8.2-cli

# Dependencias minimas para rodar CodeIgniter 4 com SQLite e Composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install intl pdo pdo_sqlite zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Composer dentro da imagem
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Variaveis de ambiente esperadas pelo projeto (defina no docker-compose/.env)
# O CodeIgniter aceita chaves com ponto (app.baseURL) ou com underscore (app_baseURL).
# CI_ENVIRONMENT=development
# app_baseURL=http://localhost:8080/
# database_default_DBDriver=SQLite3
# database_default_database=/var/www/html/writable/database.sqlite
# database_default_foreignKeys=true
# cache_redis_host=redis   -> nome do servico do Redis no docker-compose
# cache_redis_port=6379
# REDIS_HOST=redis         -> opcional para codigo custom
# REDIS_PORT=6379

EXPOSE 8080
