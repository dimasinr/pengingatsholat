<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/config.php';

if (function_exists('date_default_timezone_set')) { @date_default_timezone_set(APP_TIMEZONE); }
try { ensure_schema(); } catch (Throwable $e) {}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';
    if ($u === '' || $p === '' || $p2 === '') {
        $error = 'Semua kolom wajib diisi';
    } elseif ($p !== $p2) {
        $error = 'Konfirmasi password tidak cocok';
    } elseif (strlen($u) < 3) {
        $error = 'Username minimal 3 karakter';
    } elseif (strlen($p) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        try {
            $stmt = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$u]);
            if ($stmt->fetch()) {
                $error = 'Username sudah terpakai';
            } else {
                $hash = password_hash($p, PASSWORD_DEFAULT);
                $ins = db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, "user")');
                $ins->execute([$u, $hash]);
                // Auto login
                auth_login($u, $p);
                header('Location: index.php');
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Gagal register: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1 class="title">Register</h1>
      <p class="subtitle">Buat akun untuk mengaktifkan pengingat jadwal sholat.</p>
    </div>

    <div class="card">
      <?php if ($error): ?>
        <div style="color:#fecaca; margin-bottom:8px;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="form-grid" style="grid-template-columns: 1fr;">
          <input class="input" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          <input class="input" type="password" name="password" placeholder="Password (min 6)" required>
          <input class="input" type="password" name="password2" placeholder="Ulangi Password" required>
        </div>
        <div style="margin-top:12px; display:flex; gap:12px;">
          <button type="submit">Daftar</button>
          <a href="login.php" class="badge" style="text-decoration:none; display:inline-flex; align-items:center;">Sudah punya akun? Login</a>
        </div>
      </form>
    </div>

    <div class="footer">&copy; <?= date('Y') ?> Jadwal Sholat</div>
  </div>
</body>
</html>
