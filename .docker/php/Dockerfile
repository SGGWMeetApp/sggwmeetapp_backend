FROM php:8.1.13-fpm-bullseye
ARG TIMEZONE

COPY php.ini /usr/local/etc/php/conf.d/docker-php-config.ini

RUN apt-get update && apt-get install -y \
    gnupg \
    g++ \
    procps \
    openssl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev  \
    libonig-dev \
    libxslt1-dev \
    libpq-dev \
    libxml2-dev \
    librabbitmq-dev \
    acl \
    supervisor \
    && echo 'alias sf="php bin/console"' >> ~/.bashrc

RUN docker-php-ext-configure gd --with-jpeg --with-freetype 

RUN docker-php-ext-install \
    pdo pdo_mysql pdo_pgsql pgsql zip xsl gd intl opcache exif mbstring

RUN pecl install amqp

RUN docker-php-ext-enable amqp

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/symfony

COPY messenger-worker.conf /etc/supervisor/conf.d/messenger-worker.conf

COPY startup.sh /home/root/
RUN chmod +x /home/root/startup.sh
CMD ["/bin/bash", "/home/root/startup.sh"]

EXPOSE 9000