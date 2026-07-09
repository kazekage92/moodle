#!/bin/sh
# Runs via the base image's entrypoint (from /docker-entrypoint.d/) BEFORE Apache
# starts. Must NOT start Apache itself -- the base image does that afterwards.
set -e

echo "[moodle-init] ===== startup v3 (runtime MPM enforcement + diagnostics) ====="

# --- Diagnose what is loading Apache MPM modules ---
echo "[moodle-init] MPM symlinks in mods-enabled (before):"
ls /etc/apache2/mods-enabled/ 2>/dev/null | grep -i mpm || echo "  (none)"
echo "[moodle-init] Any 'LoadModule mpm_' lines in configs:"
grep -rniE 'LoadModule[[:space:]]+mpm_' /etc/apache2/ 2>/dev/null || echo "  (none)"

# --- Enforce exactly ONE MPM at runtime (prefork; required by mod_php) ---
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true
echo "[moodle-init] MPM symlinks in mods-enabled (after enforcement):"
ls /etc/apache2/mods-enabled/ 2>/dev/null | grep -i mpm || echo "  (none)"

# --- moodledata ---
DATAROOT="${MOODLE_DATAROOT:-/var/moodledata}"
mkdir -p "$DATAROOT"
chown -R www-data:www-data "$DATAROOT" || true

# --- Install check / upgrade on deploy ---
cd /var/www/html
if php admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    echo "[moodle-init] Moodle installed - running pending upgrades..."
    php admin/cli/upgrade.php --non-interactive || true
else
    echo "[moodle-init] Moodle not installed yet. Run the installer from the Railway shell:"
    echo "  php admin/cli/install_database.php --agree-license --adminuser=admin \\"
    echo "     --adminpass=\"\$MOODLE_ADMIN_PASS\" --adminemail=\"\$MOODLE_ADMIN_EMAIL\" \\"
    echo "     --fullname=\"My Moodle\" --shortname=\"moodle\""
fi
