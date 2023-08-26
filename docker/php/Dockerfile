#FROM php:8.1.14-fpm
FROM ${PHP_VERSION}

# apt install
RUN apt-get update && \
    apt-get -y install \
        gnupg2 && \
    apt-key update && \
    apt-get update && \
    apt-get -y install \
            wget \
            git \
            g++ \
            curl \
            imagemagick \
            libfreetype6-dev \
            libcurl3-dev \
            libicu-dev \
            libfreetype6-dev \
            libjpeg-dev \
            libjpeg62-turbo-dev \
            libmagickwand-dev \
            libpq-dev \
            libpng-dev \
            libxml2-dev \
            zlib1g-dev \
            postgresql-server-dev-all \
            openssh-client \
            nano \
            unzip \
        --no-install-recommends && \
        apt-get clean && \
        rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*


RUN curl --silent --show-error https://getcomposer.org/installer | php
RUN mv composer.phar /bin/composer
RUN composer -V
# docker php install
RUN docker-php-ext-install pdo_pgsql \
                        gd \
                        soap \
                        intl \
                        exif \
#    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-webp-dir=/usr/include/  --with-jpeg-dir=/usr/include/

# xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

#composer
ENV COMPOSER_HOME /composer
ENV PATH /usr/local/bin:$PATH
ENV COMPOSER_ALLOW_SUPERUSER 1

COPY ./php.ini /usr/local/etc/php/
COPY ./www.conf /usr/local/etc/php/

WORKDIR /app

RUN usermod -u 1000 www-data

VOLUME /root/.composer

WORKDIR /app
EXPOSE 9000
CMD ["php-fpm"]
