# Dockerfile

FROM ubuntu:20.04
LABEL Maintainer="Michele Damico <michele.damico@linfaservice.it>"
LABEL Description="spid-cie-oidc-php-wp"

ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NOWARNINGS=yes 

# Install php
RUN \
    apt-get update && \
    apt-get -y install software-properties-common && \
    add-apt-repository ppa:ondrej/php && \
    apt-get -yq install \
        wget \
        unzip \
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
        php7.4-xml 

# Install Composer
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Prepare workdir
RUN mkdir /workdir
COPY wp-config.php /workdir
COPY .ht.sqlite /workdir

# Setup wordpress
RUN cd /workdir && \
    wget https://wordpress.org/latest.tar.gz -O wordpress.tar.gz && \
    wget https://downloads.wordpress.org/plugin/sqlite-integration.1.8.1.zip -O sqlite-integration.zip && \
    wget https://github.com/oidc-wp/openid-connect-generic/archive/refs/tags/3.9.0.zip -O openid-connect-generic.zip && \
    wget https://raw.githubusercontent.com/italia/design-wordpress-theme-italiaWP/master/italiawp.zip -O italiawp.zip && \
    tar -xf wordpress.tar.gz && \
    unzip sqlite-integration.zip -d wordpress/wp-content/plugins/ && \
    unzip openid-connect-generic.zip -d wordpress/wp-content/plugins/ && \
    unzip italiawp.zip -d wordpress/wp-content/themes/ && \
    mv wordpress/wp-content/plugins/sqlite-integration/db.php wordpress/wp-content/ && \
    mkdir wordpress/wp-content/database && \
    mv wp-config.php wordpress/ && \
    mv .ht.sqlite wordpress/wp-content/database && \
    rm wordpress.tar.gz && \
    rm sqlite-integration.zip && \
    rm openid-connect-generic.zip && \
    rm italiawp.zip

WORKDIR workdir/wordpress

EXPOSE 80
ENTRYPOINT ["/bin/sh", "-c", "php -S 0.0.0.0:80"]