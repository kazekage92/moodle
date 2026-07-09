# Deploying this Moodle to Railway (+ S3/R2 for moodledata)

A prototype deployment guide. Two phases:

- **Phase 1** — get a live HTTPS Moodle on Railway backed by managed Postgres + a Volume.
- **Phase 2** — move `moodledata` file storage to S3/R2 so you're not locked to one web node.

You can do the **console/account steps** (accounts, Postgres, bucket, keys) at the same time
as everything else — they need no code.

---

## What you do in the browser (do these concurrently)

### A. Railway
1. Sign up at https://railway.app (GitHub login is easiest — it also lets Railway build from your fork).
2. **New Project** → **Deploy from GitHub repo** → pick `kazekage92/moodle`.
   - Railway will detect the `Dockerfile` at the repo root and build from it.
3. In the project, **+ New** → **Database** → **Add PostgreSQL**.
4. On the Moodle service → **Settings** → **Networking** → **Generate Domain**.
   Copy the URL (e.g. `https://moodle-production-xxxx.up.railway.app`).

### B. Object storage bucket (for Phase 2)
Recommended: **Cloudflare R2** (S3-compatible, no egress fees, generous free tier).
AWS S3 also works — the plugin is S3-compatible either way.

**Cloudflare R2:**
1. Cloudflare dashboard → **R2** → **Create bucket** (e.g. `moodle-data-prototype`).
2. **Manage R2 API Tokens** → **Create API token** (Object Read & Write) → save the
   **Access Key ID** and **Secret Access Key**.
3. Note your **endpoint**: `https://<accountid>.r2.cloudflarestorage.com`.

Save these five values for Phase 2: bucket name, region (`auto` for R2 / e.g. `us-east-1` for S3),
endpoint, access key id, secret key.

---

## Phase 1 — deploy (code + Railway variables)

### 1. Commit the deploy files (already generated in your repo)
```
Dockerfile
.dockerignore
deploy/railway/config.php
deploy/railway/entrypoint.sh
```
```bash
git add Dockerfile .dockerignore deploy/
git commit -m "Add Railway deploy scaffold"
git push origin main
```

### 2. Set environment variables on the Moodle service (Railway → Variables)
Reference the Postgres service (rename `Postgres` if your service is named differently):
```
DB_HOST = ${{Postgres.PGHOST}}
DB_PORT = ${{Postgres.PGPORT}}
DB_NAME = ${{Postgres.PGDATABASE}}
DB_USER = ${{Postgres.PGUSER}}
DB_PASS = ${{Postgres.PGPASSWORD}}
MOODLE_WWWROOT = https://<your-generated-domain>        # exact, https, no trailing slash
MOODLE_ADMIN_PASS = <a-strong-password>
MOODLE_ADMIN_EMAIL = app@tedoptimus.com
```

### 3. Add a persistent Volume (so uploads/caches survive redeploys)
Moodle service → **Settings** → **Volumes** → mount path: `/var/moodledata`.

### 4. Deploy, then run the one-time installer
After the deploy goes green, open the Moodle service → **Shell** (or use `railway run`) and run:
```bash
php admin/cli/install_database.php --agree-license \
  --adminuser=admin --adminpass="$MOODLE_ADMIN_PASS" \
  --adminemail="$MOODLE_ADMIN_EMAIL" \
  --fullname="My Moodle" --shortname="moodle"
```
Then open your Railway URL and log in as `admin`. Redeploys auto-run `upgrade.php` (see entrypoint).

### 5. Add cron (Moodle needs it every minute)
**+ New** → **Empty Service** → same repo/image → set its **Start Command** to a loop, or add a
Railway **Cron** schedule (`* * * * *`) running:
```bash
php /var/www/html/admin/cli/cron.php
```

---

## Phase 2 — move moodledata files to S3/R2 (tool_objectfs)

Object storage offloads the large stored files to S3/R2 so any number of web nodes can share
them. `moodledata` on the Volume then only holds cache/session/temp.

### 1. Add the plugin + AWS SDK to your fork
```bash
# from repo root
git clone --depth 1 https://github.com/catalyst/moodle-tool_objectfs.git admin/tool/objectfs
# The S3 client dependency (aws-sdk-php) — commit it into the plugin's vendor dir,
# or add via composer if you use composer in the build. Simplest for a prototype:
#   composer require aws/aws-sdk-php  (then commit vendor/), per the plugin README.
git add admin/tool/objectfs
git commit -m "Add objectfs plugin for S3/R2 file storage"
git push
```
Redeploy so the plugin is in the image, then in the Railway shell run `php admin/cli/upgrade.php`
to install the plugin's tables.

### 2. Configure it (set once via CLI, using env vars for the secrets)
Add these Railway variables first:
```
OBJECTFS_KEY, OBJECTFS_SECRET, OBJECTFS_BUCKET, OBJECTFS_REGION, OBJECTFS_ENDPOINT
```
Then in the Railway shell:
```bash
php admin/cli/cfg.php --component=tool_objectfs --name=filesystem --set='\tool_objectfs\local\store\s3\file_system'
php admin/cli/cfg.php --component=tool_objectfs --name=s3_key       --set="$OBJECTFS_KEY"
php admin/cli/cfg.php --component=tool_objectfs --name=s3_secret    --set="$OBJECTFS_SECRET"
php admin/cli/cfg.php --component=tool_objectfs --name=s3_bucket    --set="$OBJECTFS_BUCKET"
php admin/cli/cfg.php --component=tool_objectfs --name=s3_region    --set="$OBJECTFS_REGION"
# For Cloudflare R2 (or any S3-compatible endpoint), also set the base URL:
php admin/cli/cfg.php --component=tool_objectfs --name=s3_base_url  --set="$OBJECTFS_ENDPOINT"
php admin/cli/cfg.php --component=tool_objectfs --name=enabletasks  --set=1
```
Exact setting names can vary by plugin version — confirm against the plugin's admin settings page
(Site administration → Plugins → Object storage file system). Start in a safe mode (copy to S3,
keep local) and only enable "delete local" once you've verified files land in the bucket.

---

## Storage model recap
- **Postgres** = managed Railway service (never an ephemeral container).
- **moodledata Volume** = cache/sessions/temp + files until objectfs is on.
- **S3/R2 via objectfs** = the durable, shareable home for uploaded files — the thing that
  unlocks horizontal scaling later.
