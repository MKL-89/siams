<?php
/**
 * Authentication and role-based access control (RBAC).
 * Every protected page starts with:  require_role('student');  (or another role)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

// Where each role lands after logging in.
const ROLE_HOME = [
    'admin'       => '/admin/index.php',
    'coordinator' => '/coordinator/index.php',
    'lecturer'    => '/lecturer/index.php',
    'industrial'  => '/industrial/index.php',
    'student'     => '/student/index.php',
];

const ROLE_LABEL = [
    'admin'       => 'Administrator',
    'coordinator' => 'Attachment Coordinator',
    'lecturer'    => 'University Supervisor',
    'industrial'  => 'Industrial Supervisor',
    'student'     => 'Student',
];

/* ---------- Several accounts in one browser ----------
 * A browser shares its cookies between all its tabs, so one login session would be shared by
 * every tab. To let different people (or roles) stay logged in side by side, each extra account
 * gets its own session "slot" with its own cookie and its own address:
 *   slot 0: /siams/...      slot 1: /siams/u/1/...   ...   slot 9: /siams/u/9/...
 * The .htaccess file maps /siams/u/N/page.php to the same page.php. Logging out ends only the
 * slot of that tab, so the other accounts stay logged in.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', defined('BASE_URL') ? BASE_URL : '/siams');   // older config files
}
const MAX_SESSION_SLOT = 9;

/** The slot named in the address of this request (0 when the address has no /u/N part). */
function slot_from_request(): int
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    return preg_match('#^' . preg_quote(BASE_PATH, '#') . '/u/([1-9])(/|$)#', $path, $m) ? (int) $m[1] : 0;
}

/** The slot this request is working in (changes when a second account logs in). */
function current_slot(): int
{
    return $GLOBALS['siams_slot'] ??= slot_from_request();
}

/** Address of the system for one slot, e.g. /siams or /siams/u/2 */
function slot_base(int $slot): string
{
    return BASE_PATH . ($slot ? '/u/' . $slot : '');
}

/** Name of the session cookie of one slot. */
function slot_session_name(int $slot): string
{
    return 'SIAMSSESSID' . ($slot ? '_' . $slot : '');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', slot_base(current_slot()));   // used in links on every page
}

/**
 * Leaves the session of this tab untouched and starts a new, empty session in a free slot,
 * so that another account can log in without logging the first one out.
 * Returns false when all slots are in use.
 */
function move_to_new_slot(): bool
{
    for ($s = 0; $s <= MAX_SESSION_SLOT; $s++) {
        if ($s !== current_slot() && !isset($_COOKIE[slot_session_name($s)])) {
            session_write_close();                 // the first account's session stays as it is
            $GLOBALS['siams_slot'] = $s;
            session_name(slot_session_name($s));
            session_id(session_create_id());
            session_start();
            $_SESSION['last_activity'] = time();
            return true;
        }
    }
    return false;
}

/** Starts the session with safe cookie settings and ends it after inactivity. */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Pages are never cached, so the Back button cannot show data after logout
    // and forms always carry a fresh security token.
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_PATH,                  // every slot's cookie is sent to every slot's address
        'httponly' => true,                       // JavaScript cannot read the cookie
        'secure'   => !empty($_SERVER['HTTPS']),  // HTTPS only when available
        'samesite' => 'Strict',
    ]);
    session_name(slot_session_name(current_slot()));
    session_start();

    if (isset($_SESSION['last_activity'])
        && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_MINUTES * 60) {
        logout_user();
        session_start();
        $_SESSION['flash'] = 'Your session expired. Please log in again.';
    }
    $_SESSION['last_activity'] = time();
}

/** Escapes text for safe display in HTML (prevents cross-site scripting). */
function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . slot_base(current_slot()) . $path);
    exit;
}

/* ---------- CSRF protection for forms ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/* ---------- Login and logout ---------- */

/** True when this email has had too many failed attempts recently. */
function is_locked_out(string $email): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE email = ? AND success = 0
           AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$email, LOCKOUT_MINUTES]);
    return (int) $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

function record_attempt(string $email, bool $success): void
{
    $stmt = db()->prepare('INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)');
    $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $success ? 1 : 0]);
}

/**
 * Checks the email and password. Returns null on success, or a message to show.
 * The same message is used for a wrong email and a wrong password, so attackers
 * cannot find out which accounts exist.
 */
function attempt_login(string $email, string $password): ?string
{
    $email = strtolower(trim($email));

    if (is_locked_out($email)) {
        return 'Too many failed attempts. Please wait ' . LOCKOUT_MINUTES . ' minutes and try again.';
    }

    $stmt = db()->prepare('SELECT user_id, full_name, password_hash, role, status FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_attempt($email, false);
        log_action('login_failed', $email, $user ? (int) $user['user_id'] : null);
        return 'Incorrect email or password.';
    }
    if ($user['status'] === 'pending') {
        record_attempt($email, false);
        return 'Your registration is awaiting approval by the attachment office.';
    }
    if ($user['status'] !== 'active') {
        record_attempt($email, false);
        return 'This account has been deactivated. Please contact the attachment office.';
    }

    // Someone else is already logged in in this tab: keep them logged in and open a new slot.
    if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== (int) $user['user_id'] && !move_to_new_slot()) {
        return 'Too many accounts are logged in on this browser. Please log out of one of them first.';
    }

    record_attempt($email, true);
    start_user_session((int) $user['user_id'], $user['full_name'], $user['role']);
    log_action('login', $email);
    return null;
}

/** Logs a user in: used after a correct password and right after a new account is created. */
function start_user_session(int $userId, string $fullName, string $role): void
{
    session_regenerate_id(true);   // new session id after login (prevents session fixation)
    $_SESSION['user_id']   = $userId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['role']      = $role;
    unset($_SESSION['csrf_token']);
    db()->prepare('UPDATE users SET last_login = NOW() WHERE user_id = ?')->execute([$userId]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    // If a session is started again in this request (to show "You have been logged out" or
    // "Your session expired"), give it a new id, so its cookie replaces the deleted one.
    unset($_COOKIE[session_name()]);
    session_id(session_create_id());
}

/* ---------- Access control ---------- */

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

/** Sends visitors who are not logged in to the login page. */
function require_login(): void
{
    start_secure_session();
    if (empty($_SESSION['user_id'])) {
        $_SESSION['flash'] = 'Please log in to continue.';
        redirect('/login.php');
    }
}

/** Allows only the listed roles to open the page; everyone else gets "403 Forbidden". */
function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
}
