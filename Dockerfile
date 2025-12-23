FROM php:8.4-cli

# 必要なパッケージのインストール
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        build-essential \
        autoconf \
        libzip-dev \
        libpq-dev \
        zip \
        unzip \
        mecab \
        mecab-ipadic-utf8; \
    docker-php-ext-install zip pdo pdo_pgsql; \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# PCOVとRedis拡張のインストール
RUN set -eux; \
    pecl install pcov redis; \
    docker-php-ext-enable pcov redis; \
    apt-get purge -y --auto-remove build-essential autoconf; \
    rm -rf /tmp/pear /var/lib/apt/lists/*

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# PHPUnitのインストール
RUN composer require --dev phpunit/phpunit

# テスト実行用のエントリーポイント
COPY docker-entrypoint.sh /usr/local/bin/
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"] 