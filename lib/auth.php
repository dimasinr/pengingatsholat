<?php
require_once __DIR__ . '/../lib/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function auth_login(string $username, string $password): bool {
    $stmt = db()->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user) return false;
    if (!password_verify($password, $user['password_hash'])) return false;
    $_SESSION['user'] = ['id' => (int)$user['id'], 'username' => $user['username'], 'role' => $user['role'] ?? 'user'];
    return true;
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_is_admin(): bool {
    $u = auth_current_user();
    return $u && ($u['role'] ?? 'user') === 'admin';
}
