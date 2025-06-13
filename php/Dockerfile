FROM php:8.4-fpm-alpine AS app_prod

# Useful PHP extension installer image, copy binary into your container
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install php extensions
RUN set -eux; \
    install-php-extensions \
        pdo \
        pdo_mysql \
        pcntl

# Allow super user - set this if you use Composer as a super user at all times like in docker containers
ENV COMPOSER_ALLOW_SUPERUSER=1

# Obtain composer using multi-stage build
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Copy composer.json and composer.lock (instead of copying the entire source)
COPY ./app/composer.* ./

# Install production dependencies (no dev dependencies)
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction

# Copy application files to the working directory
COPY ./app ./

# Run composer dump-autoload --optimize
RUN composer dump-autoload --optimize

# Copy custom entrypoint script
COPY php/docker-php-entrypoint /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-php-entrypoint

# Set the entrypoint and default command
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php-fpm"]

# Development environment setup
FROM app_prod AS app_dev

ENV XDEBUG_MODE=off

# Install Xdebug
RUN set -eux; \
    install-php-extensions xdebug pcntl

# Copy xdebug.ini after xdebug is installed
COPY ./php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Install dev dependencies
RUN composer require --dev phpunit/phpunit

# Install dev packages
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction --dev

# Reuse the same entrypoint and default command in dev
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php-fpm"]
