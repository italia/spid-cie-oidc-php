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
    
# Apache conf - set default port to 8003
RUN sed -i  's/:80/:8003/g' /etc/apache2/sites-available/000-default.conf
RUN sed -i  's/ 80/ 8003/g' /etc/apache2/ports.conf

# Apache conf - allow .htaccess with RewriteEngine
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Apache conf - autorise .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Prepare spid-cie-oidc-php
RUN cd /home && \
    git clone https://github.com/italia/spid-cie-oidc-php.git && \
    mkdir /home/spid-cie-oidc-php/data && \
    cp -R /home/spid-cie-oidc-php/config_sample /home/spid-cie-oidc-php/config && \
    cp -R /home/spid-cie-oidc-php/cert_sample /home/spid-cie-oidc-php/cert
    
# Setup spid-cie-oidc-php
RUN cd /home/spid-cie-oidc-php && composer install

# Set permissions
RUN chown -R www-data:www-data /home/spid-cie-oidc-php/data /home/spid-cie-oidc-php/www

# Create symlink
RUN rm -Rf /var/www/html && ln -s /home/spid-cie-oidc-php/www /var/www/html

WORKDIR /home/spid-cie-oidc-php

EXPOSE 80
ENTRYPOINT ["/bin/sh", "-c", "echo WEB SERVER STARTED && apache2ctl -D FOREGROUND"]
