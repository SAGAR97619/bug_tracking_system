# Use the official PHP image with Apache
FROM php:8.2-apache

# Install PostgreSQL extension for PHP
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Copy your project files to the container
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html

# Expose port 80 for Apache
EXPOSE 80
