FROM php:8.2-apache

RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

COPY . /var/www/html/

WORKDIR /var/www/html

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

RUN echo 'Alias /assets /var/www/html/public/assets\n\
<Directory /var/www/html/public/assets>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/db \
    && chmod 777 /var/www/html/db

RUN chown -R www-data:www-data /var/www/html/db


EXPOSE 80
CMD ["apache2-foreground"]
