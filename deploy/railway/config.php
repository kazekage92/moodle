<?php
// Moodle production config for Railway. Everything comes from environment
// variables set in the Railway service — no secrets committed to git.
unset($CFG);
global $CFG;
$CFG = new stdClass();

// --- Database (Railway PostgreSQL) ---
// In Railway, set these as reference variables to your Postgres service, e.g.
//   DB_HOST = ${{Postgres.PGHOST}}   (use the *private* host: <name>.railway.internal)
//   DB_PORT = ${{Postgres.PGPORT}}
//   DB_NAME = ${{Postgres.PGDATABASE}}
//   DB_USER = ${{Postgres.PGUSER}}
//   DB_PASS = ${{Postgres.PGPASSWORD}}
$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('DB_HOST');
$CFG->dbname    = getenv('DB_NAME');
$CFG->dbuser    = getenv('DB_USER');
$CFG->dbpass    = getenv('DB_PASS');
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport'    => getenv('DB_PORT') ?: 5432,
    'dbsocket'  => '',
);

// --- Site URL and data ---
// MOODLE_WWWROOT must exactly match your public URL, e.g.
//   https://your-app.up.railway.app   (or your custom domain, https, no trailing slash)
$CFG->wwwroot   = getenv('MOODLE_WWWROOT');
$CFG->dataroot  = getenv('MOODLE_DATAROOT') ?: '/var/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 02777;

// Railway terminates TLS at its edge and forwards HTTP to the container.
// This tells Moodle the outside world is https so links/cookies are correct.
$CFG->sslproxy  = true;

require_once(__DIR__ . '/lib/setup.php');
// There should be no PHP code after this line.
