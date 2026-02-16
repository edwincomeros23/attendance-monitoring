FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers deflate

# Support legacy /attendance-monitoring paths used across the app
RUN printf '%s\n' \
    'Alias /attendance-monitoring/ /var/www/html/' \
    '<Directory /var/www/html/>' \
    '  AllowOverride All' \
    '  Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/attendance-monitoring.conf \
    && a2enconf attendance-monitoring

# Rewrite legacy /attendance-monitoring/* paths to the app root
RUN printf '%s\n' \
    'RewriteEngine On' \
    'RewriteRule ^/attendance-monitoring/(.*)$ /$1 [L,PT]' \
    > /etc/apache2/conf-available/attendance-monitoring-redirect.conf \
    && a2enconf attendance-monitoring-redirect

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create necessary directories
RUN mkdir -p /var/www/html/stream /var/www/html/known_faces /var/www/html/images/students
RUN chmod -R 755 /var/www/html/stream /var/www/html/known_faces /var/www/html/images/students

# Configure Apache
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf
RUN echo "ServerSignature Off" >> /etc/apache2/apache2.conf

# Update PHP configuration for larger uploads
RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini

# Expose port
EXPOSE 80

# Start Apache with dynamic PORT binding
COPY start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]
