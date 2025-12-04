FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    libxslt-dev \
    icu-dev \
    oniguruma-dev \
    openssl-dev \
    $PHPIZE_DEPS \
    linux-headers

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        ftp \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        soap \
        sockets \
        xsl \
        zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Set user
RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www
USER www

EXPOSE 9000
CMD ["php-fpm"]
