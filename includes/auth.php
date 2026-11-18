<?php
require_once __DIR__ . '/db.php';

/**
 * Stores login sessions in the database (table `sessions`) instead of files.
 * Needed on Vercel, where files are wiped between requests.
 * Used automatically when the site runs online (DB_HOST is set) or when SESSION_DRIVER=db.
 * On XAMPP it keeps using normal PHP session files, so nothing changes on your PC.
 */
class DbSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    #[\ReturnTypeWillChange] public function open($path, $name) { return true; }
    #[\ReturnTypeWillChange] public function close() { return true; }

    #[\ReturnTypeWillChange] public function read($id) {
        $s = getDB()->prepare('SELECT data FROM sessions WHERE id = ?');
        $s->bind_param('s', $id);
        $s->execute();
        $row = $s->get_result()->fetch_row();
        return $row ? $row[0] : '';
    }
    #[\ReturnTypeWillChange] public function write($id, $data) {
        $t = time();
        $s = getDB()->prepare('REPLACE INTO sessions (id, data, updated_at) VALUES (?, ?, ?)');
        $s->bind_param('ssi', $id, $data, $t);
        return $s->execute();
    }
    #[\ReturnTypeWillChange] public function destroy($id) {
        $s = getDB()->prepare('DELETE FROM sessions WHERE id = ?');
        $s->bind_param('s', $id);
        return $s->execute();
    }
    #[\ReturnTypeWillChange] public function gc($maxLifetime) {
        $old = time() - $maxLifetime;
        $s = getDB()->prepare('DELETE FROM sessions WHERE updated_at < ?');
        $s->bind_param('i', $old);
        $s->execute();
        return $s->affected_rows;
    }
    // Used by strict mode: only accept session IDs that exist in the table
    #[\ReturnTypeWillChange] public function validateId($id) {
        $s = getDB()->prepare('SELECT 1 FROM sessions WHERE id = ?');
        $s->bind_param('s', $id);
        $s->execute();
        return (bool)$s->get_result()->fetch_row();
    }
    #[\ReturnTypeWillChange] public function updateTimestamp($id, $data) {
        $t = time();
        $s = getDB()->prepare('UPDATE sessions SET updated_at = ? WHERE id = ?');
        $s->bind_param('is', $t, $id);
        return $s->execute();
    }
}

if (session_status() === PHP_SESSION_NONE) {
    if (DB_IS_REMOTE || getenv('SESSION_DRIVER') === 'db') {
        session_set_save_handler(new DbSessionHandler(), true);
    }
    $lifetime = 60 * 60 * 24 * 30;
    $isHttps  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');   // Vercel sits behind an HTTPS proxy
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => $isHttps,   // automatically on when served over HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', $lifetime);
    ini_set('session.use_strict_mode', '1'); // reject session IDs the server didn't create
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /mysticmind/login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email'    => $_SESSION['email'] ?? null,
    ];
}

/**
 * Clean user input for STORAGE: trims and removes invisible control characters.
 * It does NOT HTML-escape — text is stored exactly as the user typed it.
 * Always escape when OUTPUTTING, with e() / htmlspecialchars().
 */
function sanitize($input) {
    $input = (string)$input;
    // strip control chars except tab, newline, carriage return
    $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
    return trim($input);
}

/** Escape a value for safe HTML output. */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ── CSRF protection ───────────────────────────────────────────────────────────

/** Get (or create) this session's CSRF token. */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to put inside every <form method="POST">. */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** <meta> tag for the page <head>; main.js reads it and sends it with every AJAX POST. */
function csrf_meta() {
    return '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
}

/** True if the request carries a valid CSRF token (form field or X-CSRF-Token header). */
function verify_csrf() {
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

/** For AJAX endpoints: stop with a JSON 403 error if the CSRF token is missing/invalid. */
function require_csrf_json() {
    if (!verify_csrf()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Your session expired. Please refresh the page and try again.']);
        exit;
    }
}
