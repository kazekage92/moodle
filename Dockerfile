# Production-ish image for Railway, built FROM your own Moodle fork.
# Base is Moodle's official PHP+Apache image: PHP 8.3 with every extension
# Moodle needs (pgsql, intl, gd, zip, soap, exif, opcache, ...) already installed
# and Apache pre-configured with docroot /var/www/html.
FROM moodlehq/moodle-php-apache:8.3

# Copy your Moodle code into the webroot.
# .dockerignore keeps out .git, node_modules and the local dev config.php.
COPY --chown=www-data:www-data . /var/www/html

# Production config that reads everything from environment variables.
COPY deploy/railway/config.php /var/www/html/config.php

# moodledata lives here. On Railway, mount a Volume at this path so uploads
# and caches survive redeploys (Phase 1). Phase 2 moves the big files to S3/R2.
ENV MOODLE_DATAROOT=/var/moodledata
RUN mkdir -p /var/moodledata && chown -R www-data:www-data /var/moodledata

# Hook our upgrade check into the base image's own startup sequence instead of
# replacing it. The base entrypoint runs every *.sh in /docker-entrypoint.d/ first
# (which sets APACHE_DOCUMENT_ROOT -> /var/www/html/public and finalizes Apache),
# then starts Apache. Overriding ENTRYPOINT would skip all of that.
COPY deploy/railway/entrypoint.sh /docker-entrypoint.d/50-moodle.sh
RUN chmod +x /docker-entrypoint.d/50-moodle.sh

EXPOSE 80
# ENTRYPOINT / CMD intentionally left as the base image defaults
# (moodle-docker-php-entrypoint -> apache2-foreground).
