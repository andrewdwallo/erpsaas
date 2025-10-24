FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    wget \
    fontconfig \
    libfreetype6 \
    libx11-6 \
    libxcb1 \
    libxext6 \
    libxrender1 \
    && rm -rf /var/lib/apt/lists/*

# Install wkhtmltopdf from source
RUN wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_arm64.deb \
    && apt-get update \
    && apt-get install -y ./wkhtmltox_0.12.6.1-3.bookworm_arm64.deb \
    && rm wkhtmltox_0.12.6.1-3.bookworm_arm64.deb \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/erpsaas erpsaas
RUN mkdir -p /home/erpsaas/.composer && \
    chown -R erpsaas:erpsaas /home/erpsaas

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=erpsaas:erpsaas . /var/www

# Change current user to erpsaas
USER erpsaas

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
