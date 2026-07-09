#!/bin/sh
# Runs via the base image's entrypoint (from /docker-entrypoint.d/) BEFORE Apache
# starts. It must NOT start Apache itself -- the base image does that afterwards.
# Purpose: ensure moodledata is writable and apply pending Moodle upgrades on deploy.
set -e

DATAROOT="${MOODLE_DATAROOT:-/var/moodledata}"
mkdir -p "$DATAROOT"
chown -R www-data:www-data "$DATAROOT" || true

cd /var/www/html

# If Moodle is already installed (DB has config), apply any pending upgrades a new
# code deploy may require. On a fresh, uninstalled DB cfg.php exits non-zero -> skip.
if php admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    echo "[moodle-init] Installed - running pending upgrades..."
    php admin/cli/upgrade.php --non-interactive || true
else
    echo "[moodle-init] Not installed yet. Run the one-time installer from the Railway shell:"
    echo "  php admin/cli/install_database.php --agree-license --adminuser=admin \\"
    echo "     --adminpass=\"\$MOODLE_ADMIN_PASS\" --adminemail=\"\$MOODLE_ADMIN_EMAIL\" \\"
    echo "     --fullname=\"My Moodle\" --shortname=\"moodle\""
fi
