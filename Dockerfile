FROM php:8.2-apache

# Instalare extensii PHP pentru MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activare mod rewrite
RUN a2enmod rewrite

# Setare director de lucru
WORKDIR /var/www/html

# Copiere proiect
COPY . /var/www/html/

# 🔥 Configurare Apache custom
RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    Alias /api /var/www/html/api\n\
\n\
    <Directory /var/www/html/api>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    DirectoryIndex login.html\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Permisiuni
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80