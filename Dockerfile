FROM php:8.4-cli

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

# PCOVのインストール（軽量なコードカバレッジドライバー）
RUN pecl install pcov \
    && docker-php-ext-enable pcov

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# PHPUnitのインストール
RUN composer require --dev phpunit/phpunit

# テスト実行用のエントリーポイント
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"] 