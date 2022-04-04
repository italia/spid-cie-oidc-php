# Dockerfile

FROM ubuntu:20.04
LABEL Maintainer="Michele Damico <michele.damico@linfaservice.it>"
LABEL Description="spid-cie-oidc-php"

ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NOWARNINGS=yes 

# Install php
RUN \
    apt-get update && \
    apt-get -y install software-properties-common && \
    add-apt-repository ppa:ondrej/php && \
    apt-get -yq install \
        curl \
        git \
        php7.4 \
        php7.4-cli \
        php7.4-fpm \
        php7.4-gd \
        php7.4-sqlite3 \
        php7.4-gmp \
        php7.4-mbstring \
        php7.4-curl \
        php7.4-xml \
        php7.4-zip
    
# Install Composer
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Prepare spid-cie-oidc-php
RUN cd /home && \
    git clone https://github.com/italia/spid-cie-oidc-php.git && \
    mkdir /home/spid-cie-oidc-php/data && \
    mkdir /home/spid-cie-oidc-php/config

# Setup spid-cie-oidc-php
RUN cd /home/spid-cie-oidc-php && composer install

WORKDIR /home/spid-cie-oidc-php/www

EXPOSE 80
ENTRYPOINT ["/bin/sh", "-c", "php -S 0.0.0.0:80"]
