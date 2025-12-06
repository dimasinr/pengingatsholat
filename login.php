<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/config.php';

if (function_exists('date_default_timezone_set')) { @date_default_timezone_set(APP_TIMEZONE); }
try { ensure_schema(); } catch (Throwable $e) {}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === '' || $p === '') {
        $error = 'Mohon isi username dan password';
    } else {
        if (auth_login($u, $p)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Login gagal. Periksa username / password';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1 class="title">Login</h1>
      <p class="subtitle">Masuk untuk mengaktifkan pengingat jadwal sholat.</p>
    </div>

    <div class="card">
      <?php if ($error): ?>
        <div style="color:#fecaca; margin-bottom:8px;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="form-grid" style="grid-template-columns: 1fr;">
          <input class="input" name="username" placeholder="Username" required>
          <input class="input" type="password" name="password" placeholder="Password" required>
        </div>
        <div style="margin-top:12px; display:flex; gap:12px;">
          <button type="submit">Masuk</button>
          <a href="index.php" class="badge" style="text-decoration:none; display:inline-flex; align-items:center;">Kembali</a>
        </div>
        <div class="meta" style="margin-top:8px;">User default: admin / admin123</div>
      </form>
    </div>

    <div class="footer">&copy; <?= date('Y') ?> Jadwal Sholat</div>
  </div>
</body>
</html>
