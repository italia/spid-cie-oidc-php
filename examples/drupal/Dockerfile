# Dockerfile

FROM ubuntu:20.04
LABEL Maintainer="Michele Damico <michele.damico@linfaservice.it>"
LABEL Description="spid-cie-oidc-php-drupal"

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
        npm \
        apache2 \
        libapache2-mod-php \
        php-sqlite3 \
        php-gd \
        php-gmp \
        php-mbstring \
        php-curl \
        php-xml \
        php-zip

# Apache conf - set default port to 8005
RUN sed -i  's/:80/:8005/g' /etc/apache2/sites-available/000-default.conf
RUN sed -i  's/ 80/ 8005/g' /etc/apache2/ports.conf

# Apache conf - allow .htaccess with RewriteEngine
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Apache conf - autorise .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Setup drupal
RUN cd /home && \
    composer create-project drupal/recommended-project drupal && \
    cd /home/drupal && \
    composer require drush/drush && \
    composer require 'drupal/openid_connect:^1.2' && \
    composer require 'drupal/bootstrap_italia:^0.22' && \
    php vendor/bin/drush site-install standard --locale=it --db-url='sqlite://sites/default/files/.ht.db.sqlite' --site-name=Drupal8 --account-name=admin --account-pass=password --notify=0 --account-mail=contatti@linfaservice.it -y && \
    php vendor/bin/drush en locale && \
    php vendor/bin/drush language-add en


# Setup Bootstrap Italia Theme
RUN cd /home/drupal && \
    php vendor/bin/drush pm:enable inline_form_errors responsive_image && \
    composer require drupal/components drupal/ui_patterns && \
    php vendor/bin/drush pm:enable components \
        ui_patterns ui_patterns_layouts ui_patterns_library ui_patterns_views && \
    composer require drupal/bootstrap_italia 

# Copy sub-theme to destination folder
RUN cd /home/drupal/web/themes && \
    mkdir custom && \
    cp -r contrib/bootstrap_italia/var/starter_kits/italiagov custom/ 

# Enable themes and set as default
RUN cd /home/drupal && \
    php vendor/bin/drush -y theme:enable bootstrap_italia && \
    php vendor/bin/drush -y theme:enable italiagov && \
    php vendor/bin/drush config-set system.theme default italiagov

# Install assets
RUN cd /home/drupal/web/themes/custom/italiagov && \
    npm install

# Install Node.js 14 to build theme
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash - && \
    apt -y install nodejs

# Build theme
RUN cd /home/drupal/web/themes/custom/italiagov && \
    npm run build:prod

# Reload cache
RUN cd /home/drupal && \
    php vendor/bin/drush cr

# Prepare drupal
COPY examples/drupal/sites.zip /home/drupal
RUN  ls -la /home/drupal && \
     unzip -o /home/drupal/sites.zip -d /home/drupal/web/sites 

# Set permissions
RUN chown -R www-data:www-data /home/drupal/web/sites

# Create symlink
RUN rm -Rf /var/www/html && ln -s /home/drupal/web /var/www/html

WORKDIR home/drupal

EXPOSE 80
ENTRYPOINT ["/bin/sh", "-c", "echo WEB SERVER STARTED && apache2ctl -D FOREGROUND"]
