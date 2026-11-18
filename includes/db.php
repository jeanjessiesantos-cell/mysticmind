<?php
/**
 * Database connection.
 *
 * - On your PC (XAMPP): no environment variables are set, so it uses localhost / root / no password.
 * - On Vercel: set DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME in the project's Environment Variables.
 *   The connection then uses SSL, which TiDB Cloud requires.
 *   Optional: DB_SSL=0 turns SSL off, DB_SSL_CA=/path/to/ca.pem sets a custom certificate file.
 */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'mysticmind');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_IS_REMOTE', getenv('DB_HOST') !== false && getenv('DB_HOST') !== '');

/** Find a trusted-certificates file for SSL (location differs per operating system). */
function dbCaFile() {
    $custom = getenv('DB_SSL_CA');
    if ($custom && is_readable($custom)) return $custom;
    foreach ([
        '/etc/pki/tls/certs/ca-bundle.crt',     // Amazon Linux (Vercel)
        '/etc/ssl/certs/ca-certificates.crt',   // Debian / Ubuntu
        '/etc/ssl/cert.pem',                    // Alpine / macOS
    ] as $path) {
        if (is_readable($path)) return $path;
    }
    return null;
}

function getDB() {
    static $conn = null;
    if ($conn !== null) return $conn;

    $conn  = mysqli_init();
    $flags = 0;
    $useSsl = DB_IS_REMOTE && getenv('DB_SSL') !== '0';
    if ($useSsl) {
        $conn->ssl_set(null, null, dbCaFile(), null, null);
        $flags = MYSQLI_CLIENT_SSL;
    }

    try {
        $ok = $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, null, $flags);
    } catch (mysqli_sql_exception $e) {
        $ok = false;
    }
    if (!$ok) {
        error_log('DB Connection failed: ' . mysqli_connect_error());
        $conn = null;
        throw new RuntimeException('Database connection failed.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
