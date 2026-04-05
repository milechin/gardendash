FROM php:8.4-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install system deps, then compile PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create writable directories and set permissions
RUN mkdir -p /var/www/html/data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/uploads \
    && chmod 755 /var/www/html/data /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
