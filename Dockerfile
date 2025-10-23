FROM php:8.2-apache

# SQLite ve PHP PDO kurulumu
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Proje dosyalarını kopyala
COPY . /var/www/html/

# Çalışma dizini
WORKDIR /var/www/html

# Apache DocumentRoot'u kök dizin yap
RUN sed -i 's|DocumentRoot /var/www/html/public|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Apache izinleri (tüm proje)
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Alias ile assets klasörünü aç
RUN echo 'Alias /assets /var/www/html/assets\n\
<Directory /var/www/html/assets>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Dosya izinleri ve db klasörü
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/db \
    && chmod 777 /var/www/html/db

EXPOSE 80
CMD ["apache2-foreground"]
