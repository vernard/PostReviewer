# Base stage with common components
FROM serversideup/php:8.4-fpm-apache AS base

USER root

RUN apt-get update && apt-get install -y \
    wget \
    vim \
    nano \
    ffmpeg \
    git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions gd intl bcmath pcntl

# Production stage
FROM base AS production

# Install Node.js and NPM
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Switch back to the unprivileged user for security
USER www-data

# Show node and npm version
RUN node --version && npm --version

# Copy application code
COPY --chown=www-data:www-data . /var/www/html/

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Install NPM dependencies and build assets
RUN npm install && npm run build

# Clean up node_modules to reduce image size
RUN rm -rf /var/www/html/node_modules

# Development stage with XDebug
FROM base AS development

USER root

# Install Node.js and NPM for development
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install XDebug
RUN install-php-extensions xdebug

# Configure XDebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.trigger_value=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Expose port 9003 for Xdebug
EXPOSE 9003

USER www-data

# Default target stage
FROM production
