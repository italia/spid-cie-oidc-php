# Dockerfile

FROM ubuntu:20.04
LABEL Maintainer="Michele Damico <michele.damico@linfaservice.it>"
LABEL Description="spid-cie-oidc-php"

ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NOWARNINGS=yes 

# Install apache and php7
RUN \
    apt-get update && \
    apt-get -yq install \
        curl \
        git \
        apache2 \
        libapache2-mod-php \
        php-sqlite3 \
        php-gmp \
        php-mbstring \
        php-curl \
        php-xml \
        php-zip
    
# config PHP
# we want a dev server which shows PHP errors
RUN sed -i -e 's/^error_reporting\s*=.*/error_reporting = E_ALL/' /etc/php/7.4/apache2/php.ini
RUN sed -i -e 's/^display_errors\s*=.*/display_errors = On/' /etc/php/7.4/apache2/php.ini
RUN sed -i -e 's/^zlib.output_compression\s*=.*/zlib.output_compression = Off/' /etc/php/7.4/apache2/php.ini

# Apache conf
# allow .htaccess with RewriteEngine
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
# autorise .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
# for production :
# RUN echo "ServerTokens Prod\n" >> /etc/apache2/apache2.conf
# RUN echo "ServerSignature Off\n" >> /etc/apache2/apache2.conf

# Install Composer
RUN cd ~ && \
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Setup spid-cie-oidc-php
RUN cd /var/www && \
    git clone https://github.com/italia/spid-cie-oidc-php.git && \
    mkdir log && \
    mv /var/www/html /var/www/html_old && \
    cd spid-cie-oidc-php && \
    mkdir data

# Cleaning
# RUN apt-get clean && apt-get autoremove -y

#change working directory to root
WORKDIR var/www/spid-cie-oidc-php

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod 755 -R /var/www
RUN find /var/www -type d -exec chmod 775 {} +
RUN find /var/www -type f -exec chmod 664 {} +

EXPOSE 80
ENTRYPOINT ["/bin/sh", "-c", "composer install && echo WEB SERVER STARTED && apache2ctl -D FOREGROUND"]
