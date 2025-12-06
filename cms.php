<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';

if (function_exists('date_default_timezone_set')) { @date_default_timezone_set(APP_TIMEZONE); }
try { ensure_schema(); } catch (Throwable $e) {}

$user = auth_current_user();
if (!$user || ($user['role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$content = '';
try { $content = get_site_info(); } catch (Throwable $e) { $error = $e->getMessage(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = trim($_POST['content'] ?? '');
    try {
        update_site_info($new);
        $content = $new;
        $success = 'Informasi berhasil diperbarui.';
    } catch (Throwable $e) {
        $error = 'Gagal memperbarui informasi: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CMS - Kelola Informasi</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1 class="title">CMS</h1>
      <p class="subtitle">Kelola informasi yang ditampilkan pada halaman utama.</p>
      <div style="margin-top:8px; display:flex; gap:8px; align-items:center; justify-content:flex-end;">
        <span class="badge">Admin: <?= htmlspecialchars($user['username']) ?></span>
        <a class="badge" href="index.php" style="text-decoration:none;">Kembali</a>
        <a class="badge" href="logout.php" style="text-decoration:none;">Logout</a>
      </div>
    </div>

    <div class="card">
      <?php if ($success): ?>
        <div style="color:#bbf7d0; margin-bottom:8px;">✅ <?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div style="color:#fecaca; margin-bottom:8px;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="form-grid" style="grid-template-columns: 1fr;">
          <textarea name="content" class="input" style="min-height:200px;" required><?= htmlspecialchars($content) ?></textarea>
        </div>
        <div style="margin-top:12px; display:flex; gap:12px;">
          <button type="submit">Simpan</button>
          <a href="index.php" class="badge" style="text-decoration:none; display:inline-flex; align-items:center;">Lihat Halaman Utama</a>
        </div>
      </form>
    </div>

    <div class="card">
      <h3 style="margin:0 0 8px 0;">Pratinjau</h3>
      <div class="meta" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($content)) ?></div>
    </div>

    <div class="footer">&copy; <?= date('Y') ?> Jadwal Sholat</div>
  </div>
</body>
</html>
