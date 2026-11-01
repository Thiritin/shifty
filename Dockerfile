FROM php:8.4-alpine AS base
WORKDIR /app

ENV COMPOSER_MEMORY_LIMIT=-1
######################################################
# Step 1 | Install Dependencies
######################################################
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apk update \
    && apk add --no-cache curl git unzip openssl tar ca-certificates \
    && install-php-extensions gd bcmath pdo_mysql pdo_pgsql zip intl opcache pcntl redis @composer \
    && rm -rf /var/cache/apk/*

RUN chown -R www-data:www-data /app
USER www-data

######################################################
# Copy Configuration
######################################################
COPY .github/docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY .github/docker/php/php.ini $PHP_INI_DIR/conf.d/php.ini
######################################################
# Local Stage
######################################################
FROM base AS local
######################################################
# Wayfinder Build - generates typed routes/actions for Vite
# (needs the full app + PHP, unlike Ziggy which resolved routes at runtime)
######################################################
FROM base AS wayfinder-build
WORKDIR /app
COPY --chown=www-data:www-data composer.json composer.lock /app/
RUN composer install --optimize-autoloader --no-cache --no-scripts
COPY --chown=www-data:www-data . /app/
COPY --chown=www-data:www-data ./.env.build /app/.env
RUN composer dump-autoload --optimize \
    && php artisan wayfinder:generate
######################################################
# NodeJS Stage
######################################################
FROM node:26-alpine AS vite
WORKDIR /app
COPY package.json package-lock.json vite.config.ts ./
RUN npm install --force
COPY ./resources /app/resources
COPY ./.env.build /app/.env
COPY --from=wayfinder-build /app/resources/js/wayfinder /app/resources/js/wayfinder
COPY --from=wayfinder-build /app/resources/js/actions /app/resources/js/actions
COPY --from=wayfinder-build /app/resources/js/routes /app/resources/js/routes
ENV WAYFINDER_COMMAND=true
RUN npm run build
######################################################
# Production Stage
######################################################
FROM base AS production
COPY --chown=www-data:www-data composer.json composer.lock /app/
RUN composer install --no-dev --optimize-autoloader --no-cache --no-scripts
COPY --chown=www-data:www-data . /app/
RUN composer dump-autoload --optimize
COPY --from=vite --chown=www-data:www-data /app/public/build ./public/build
RUN php artisan octane:install --server=frankenphp --no-interaction
COPY --chmod=0755 .github/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
ENV PORT=80
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD curl -fsS "http://127.0.0.1:${PORT}/up" || exit 1
CMD ["sh", "-c", "exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=${PORT} --admin-port=2020"]
