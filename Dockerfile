FROM php:8.1-apache

# Install PHP extensions needed for MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite and mpm_prefork
RUN a2enmod rewrite
RUN a2dismod mpm_event || true && a2enmod mpm_prefork || true

# Limit Apache workers to stay under Clever Cloud's 5 connection limit
RUN echo '<IfModule mpm_prefork_module>\n\
    StartServers 1\n\
    MinSpareServers 1\n\
    MaxSpareServers 2\n\
    MaxRequestWorkers 4\n\
    MaxConnectionsPerChild 100\n\
</IfModule>' > /etc/apache2/mods-available/mpm_prefork.conf

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . /var/www/html/

# Create uploads directories and set permissions
RUN mkdir -p uploads/qr_codes uploads/vehicles uploads/profile_pictures \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80