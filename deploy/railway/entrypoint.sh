#!/bin/sh
# Railway container entrypoint for Moodle.
set -e

DATAROOT="${MOODLE_DATAROOT:-/var/moodledata}"
mkdir -p "$DATAROOT"
chown -R www-data:www-data "$DATAROOT"

cd /var/www/html

# If Moodle is already installed (DB has config), apply any pending upgrades
# that a new code deploy may require. Safe no-op on a fresh, uninstalled DB.
if php admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    echo "[entrypoint] Moodle already installed - running pending upgrades..."
    php admin/cli/upgrade.php --non-interactive || true
else
    echo "[entrypoint] Moodle not installed yet."
    echo "[entrypoint] Run the one-time installer from the Railway shell:"
    echo "  php admin/cli/install_database.php --agree-license \\"
    echo "     --adminuser=admin --adminpass=\"\$MOODLE_ADMIN_PASS\" \\"
    echo "     --adminemail=\"\$MOODLE_ADMIN_EMAIL\" \\"
    echo "     --fullname=\"My Moodle\" --shortname=\"moodle\""
fi

exec apache2-foreground
