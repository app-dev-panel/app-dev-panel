FROM php:8.4-cli

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libsqlite3-dev \
    && docker-php-ext-install \
        zip \
        intl \
        pdo_sqlite \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Default: run tests with coverage
ENTRYPOINT ["php", "vendor/bin/phpunit"]
CMD ["--coverage-clover=coverage.xml", "--coverage-text"]
