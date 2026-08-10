# =============================================================================
# Stage 0: Node builder
# =============================================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --prefer-offline

COPY . .
RUN npm run build


# =============================================================================
# Stage 1: PHP builder
# =============================================================================
FROM php:8.3-fpm-bookworm AS builder

LABEL maintainer="FreeAmir"

# Basic build dependencies + Microsoft repository prerequisites
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        ca-certificates \
        gnupg \
        unzip \
        libpng-dev \
        libonig-dev \
        libssl-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        libicu-dev \
        libzip-dev \
        libpq-dev \
        unixodbc-dev \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /etc/apt/keyrings/microsoft.gpg \
    && echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
        > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 \
        mssql-tools18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        gd \
        intl \
        zip \
        bcmath \
        soap \
        mbstring \
        xml \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www/html

COPY . /var/www/html

COPY --from=node-builder /app/public/build /var/www/html/public/build

RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chmod -R 775 bootstrap/cache storage \
    && curl -sS https://getcomposer.org/installer \
        | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-progress \
        --prefer-dist


# =============================================================================
# Stage 2: Production
# =============================================================================
FROM php:8.3-fpm-bookworm AS production

ARG APP_VERSION=nightly-local
ENV APP_VERSION="${APP_VERSION}"

# Runtime dependencies + Microsoft ODBC Driver 18
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        ca-certificates \
        gnupg \
        libpng16-16 \
        libonig5 \
        libxml2 \
        libicu72 \
        libzip4 \
        libpq5 \
        libfcgi-bin \
        procps \
        unixodbc \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /etc/apt/keyrings/microsoft.gpg \
    && echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
        > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 \
        mssql-tools18 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*


# PHP-FPM healthcheck
RUN curl -fsSL \
        https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
        -o /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck


# Copy PHP extensions and configuration from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/bin/docker-php-ext-* /usr/local/bin/


# PHP production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/production/php-fpm/php.ini \
    /usr/local/etc/php/conf.d/99-app.ini

COPY docker/production/php-fpm/php-fpm.conf \
    /usr/local/etc/php-fpm.d/zz-app.conf


# Bootstrap
COPY docker/production/php-fpm/bootstrap.sh \
    /usr/local/bin/bootstrap.sh

RUN chmod +x /usr/local/bin/bootstrap.sh


# Application
COPY --from=builder --chown=www-data:www-data \
    /var/www/html /var/www/html


# Storage/cache permissions
RUN mkdir -p \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

WORKDIR /var/www/html

USER www-data

EXPOSE 9000

HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

ENTRYPOINT ["/usr/local/bin/bootstrap.sh"]

CMD ["php-fpm"]