# Production-ish image for Railway, built FROM your own Moodle fork.
# Base is Moodle's official PHP+Apache image: PHP 8.3 with every extension
# Moodle needs (pgsql, intl, gd, zip, soap, exif, opcache, ...) already installed.
#
# Pinned to a specific digest (NOT the mutable :8.3 tag) for reproducible builds.
# The mutable tag was pulling a newer image on Railway that loaded two Apache MPMs
# ("More than one MPM loaded") and crash-looped. This digest is a known-good build.
FROM moodlehq/moodle-php-apache@sha256:946c42935f491ae3726cbee40a8de1209affb4926748a1da54764660ce3cfc5c

# Belt-and-suspenders: mod_php requires exactly one MPM (prefork). Guarantee it
# regardless of what the base image ships, so Apache never sees a duplicate MPM.
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true; a2enmod mpm_prefork 2>/dev/null || true

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
