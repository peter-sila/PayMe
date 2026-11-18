# Use Apache-enabled PHP image
FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Enable Apache mod_rewrite (optional)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Expose Apache HTTP port
EXPOSE 80

# Start Apache (only one CMD!)
CMD ["apache2-foreground"]

