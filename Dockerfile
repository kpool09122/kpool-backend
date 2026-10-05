# Native extensions are built for the requested target (including linux/arm64).
FROM php:8.5-fpm AS extensions
RUN set -eux; apt-get update; apt-get install -y --no-install-recommends \
    libzip-dev libpq-dev libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev; \
    docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype; \
    docker-php-ext-install -j"$(nproc)" zip pdo_pgsql gd pcntl; \
    pecl install redis-6.3.0; docker-php-ext-enable redis; \
    rm -rf /tmp/pear /var/lib/apt/lists/*

FROM php:8.5-fpm AS runtime
RUN set -eux; apt-get update; apt-get install -y --no-install-recommends \
    bash curl libzip5 libpq5 libpng16-16t64 libjpeg62-turbo libwebp7 libfreetype6 \
    mecab mecab-ipadic-utf8 nginx tini; \
    rm -rf /var/lib/apt/lists/*; \
    groupadd -g 1000 app; useradd -u 1000 -g app -M app
COPY --from=extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
WORKDIR /var/www/html

FROM runtime AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY application/ application/
COPY src/ src/
COPY database/ database/
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts

FROM runtime AS production
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=info
COPY --from=dependencies /var/www/html/vendor/ vendor/
COPY composer.json composer.lock artisan ./
COPY application/ application/
COPY src/ src/
COPY database/ database/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY public/ public/
COPY resources/ resources/
COPY routes/ routes/
COPY --chmod=644 docker/production/nginx.conf /etc/nginx/nginx.conf
COPY --chmod=644 docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf
COPY --chmod=644 docker/production/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY --chmod=755 docker/production/entrypoint.sh docker/production/api.sh /usr/local/bin/
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; \
    rm -f bootstrap/cache/*.php; chown -R app:app storage bootstrap/cache
USER 1000:1000
EXPOSE 8080
STOPSIGNAL SIGTERM
ENTRYPOINT ["/usr/bin/tini", "--", "/usr/local/bin/entrypoint.sh"]
CMD ["api"]

# Keep the default stage compatible with existing local builds.
FROM extensions AS development
RUN set -eux; apt-get update; apt-get install -y --no-install-recommends bash curl unzip mecab mecab-ipadic-utf8; \
    pecl install pcov; docker-php-ext-enable pcov; rm -rf /tmp/pear /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]
