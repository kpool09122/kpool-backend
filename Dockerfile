FROM php:8.4-cli

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install zip pdo pdo_pgsql

# PCOVのインストール（軽量なコードカバレッジドライバー）
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Raise PHP memory limit for coverage-heavy test runs
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

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