FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    git \
    unzip \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies (cached layer)
COPY composer.json composer.lock ./
COPY libs/TaskBus/composer.json libs/TaskBus/
COPY libs/Kernel/composer.json libs/Kernel/
COPY libs/API/composer.json libs/API/
COPY libs/Cli/composer.json libs/Cli/
COPY libs/McpServer/composer.json libs/McpServer/
COPY libs/Testing/composer.json libs/Testing/
COPY libs/Adapter/ libs/Adapter/
RUN composer install --no-dev --no-scripts --prefer-dist || true

# Copy full source
COPY . .

# Create data directory
RUN mkdir -p /app/data

EXPOSE 9800
