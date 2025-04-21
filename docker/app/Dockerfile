FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    zlib1g-dev \
    libpng-dev \
    libxslt-dev \
    libpq-dev \
    libmemcached-dev \
    libmagickwand-dev \
    libgmp-dev \
    curl \
    git \
    wget \
    unzip \
    nodejs \
    npm \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    libssl-dev \
    acl \
    && docker-php-ext-install intl pdo pdo_mysql

RUN docker-php-ext-install mysqli pcntl bcmath soap intl exif gmp xsl sockets gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app

COPY ./app /var/www/app

EXPOSE 9000

CMD ["php-fpm"]
