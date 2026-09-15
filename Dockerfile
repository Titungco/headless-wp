# syntax=docker/dockerfile:1

# ---- Stage 1: resolve WordPress core + plugins via Composer (ADR-0003: baked in at build time) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ---- Stage 2: runtime image — nginx + PHP-FPM under supervisord, single container ----
# 8.4, not 8.3: the resolved dependency graph (auth0/wordpress -> auth0/auth0-php)
# requires PHP >= 8.4.1 — confirmed by actually building and running the image.
FROM php:8.4-fpm-alpine AS runtime

RUN set -eux; \
	apk add --no-cache nginx supervisor bash curl libpng libjpeg-turbo freetype; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev linux-headers; \
	docker-php-ext-configure gd --with-jpeg --with-freetype; \
	docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql gd exif opcache; \
	pecl install redis; \
	docker-php-ext-enable redis; \
	apk del .build-deps; \
	curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
	chmod +x /usr/local/bin/wp

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html
COPY --from=vendor /app/web ./web
COPY --from=vendor /app/vendor ./vendor
COPY web/wp-config.php ./web/wp-config.php
COPY web/index.php ./web/index.php
COPY config/ ./config/
COPY wp-cli.yml ./wp-cli.yml
COPY mu-plugins/ ./web/app/mu-plugins/

# Redis object-cache drop-in: copied at build time, not via the plugin's runtime
# "enable" button, since the filesystem is read-only at runtime.
RUN cp ./web/app/mu-plugins/redis-cache/includes/object-cache.php ./web/app/object-cache.php

# Everything the app needs to write goes to /tmp, which is mounted as tmpfs at
# run time (see docker-compose.yml / ecs/task-definition.json) — the rest of
# the filesystem stays read-only (ADR-0003). nginx's whole default prefix
# (logs/, tmp/client_body, tmp/uwsgi, ...) is symlinked into /tmp so every
# path it opens on its own — not just the ones nginx.conf names explicitly —
# lands on the writable mount; entrypoint.sh recreates the target directories
# on each boot, since tmpfs starts empty.
RUN rm -rf /var/lib/nginx && ln -s /tmp/nginx-lib /var/lib/nginx \
	&& chown -R www-data:www-data /var/www/html

ENV HOME=/tmp
USER www-data
EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf", "-n"]
