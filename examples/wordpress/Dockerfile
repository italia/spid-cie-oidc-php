# Dockerfile

FROM ubuntu:20.04
LABEL Maintainer="Michele Damico <michele.damico@linfaservice.it>"
LABEL Description="spid-cie-oidc-php-wordpress"

ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NOWARNINGS=yes 

# Install apache and php7
RUN \
    apt-get update && \
    apt-get -yq install \
    wget \
    unzip \
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

# Apache conf - set default port to 8004
RUN sed -i  's/:80/:8004/g' /etc/apache2/sites-available/000-default.conf
RUN sed -i  's/ 80/ 8004/g' /etc/apache2/ports.conf

# Apache conf - allow .htaccess with RewriteEngine
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Apache conf - autorise .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Prepare wordpress
COPY examples/wordpress/wp-config.php /home/
COPY examples/wordpress/.ht.sqlite /home/

# Setup wordpress
RUN cd /home && \
    wget https://wordpress.org/wordpress-5.9.tar.gz -O wordpress.tar.gz && \
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

# Set permissions
RUN chown -R www-data:www-data /home/wordpress

# Create symlink
RUN rm -Rf /var/www/html && ln -s /home/wordpress /var/www/html

WORKDIR home/wordpress

EXPOSE 80
ENTRYPOINT ["/bin/sh", "-c", "echo WEB SERVER STARTED && apache2ctl -D FOREGROUND"]
