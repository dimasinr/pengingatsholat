<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/api.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';

// Timezone
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set(APP_TIMEZONE);
}

 

// Ensure DB schema exists
try { ensure_schema(); } catch (Throwable $e) { /* handled below in UI */ }
$user = auth_current_user();

$methods = aladhan_methods();
$now = new DateTime('now');
$defaultDate = $now->format('Y-m-d');

$city = $_POST['city'] ?? 'Jakarta';
$country = $_POST['country'] ?? 'Indonesia';
$method = (int)($_POST['method'] ?? 5); // Umm Al-Qura default
$dateYmd = $_POST['date'] ?? $defaultDate;
$dateDMY = DateTime::createFromFormat('Y-m-d', $dateYmd);
$dateDMY = $dateDMY ? $dateDMY->format('d-m-Y') : date('d-m-Y');

$error = '';
$success = '';
$result = null;
$fromDb = false;

// Load site info content
$siteInfo = '';
try { $siteInfo = get_site_info(); } catch (Throwable $e) { $siteInfo = ''; }

// Handle admin CMS update (after user and DB are ready)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'update_info')) {
    if (!$user || ($user['role'] ?? 'user') !== 'admin') {
        $error = 'Tidak memiliki izin untuk update informasi.';
    } else {
        $newContent = trim($_POST['content'] ?? '');
        try {
            update_site_info($newContent);
            $siteInfo = $newContent;
            $success = 'Informasi berhasil diperbarui.';
        } catch (Throwable $e) {
            $error = 'Gagal memperbarui informasi: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'search') === 'search') {
    try {
        $existing = find_timing($city, $country, $method, $dateYmd);
        if ($existing) {
            $fromDb = true;
            $result = [
                'timings' => [
                    'Fajr' => $existing['fajr'],
                    'Sunrise' => $existing['sunrise'],
                    'Dhuhr' => $existing['dhuhr'],
                    'Asr' => $existing['asr'],
                    'Maghrib' => $existing['maghrib'],
                    'Isha' => $existing['isha'],
                    'Imsak' => $existing['imsak'],
                    'Midnight' => $existing['midnight'],
                ],
                'meta' => [],
                'date' => ['gregorian' => ['date' => $dateYmd]],
            ];
        } else {
            $api = fetch_prayer_times($city, $country, $method, $dateDMY);
            $result = $api;
            // Persist to DB
            save_timing($city, $country, $method, DateTime::createFromFormat('d-m-Y', $dateDMY)->format('Y-m-d'), $api['timings']);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Jadwal Sholat</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1 class="title">Jadwal Sholat</h1>
      <p class="subtitle">Dimas Indra Pratama - 221011403152 - 07TPLP018</p>
      <div style="margin-top:8px; display:flex; gap:8px; align-items:center; justify-content:flex-end;">
        <?php if ($user): ?>
          <span class="badge">Login: <?= htmlspecialchars($user['username']) ?></span>
          <?php if (($user['role'] ?? 'user') === 'admin'): ?>
            <a class="badge" href="#cms" style="text-decoration:none;">CMS</a>
          <?php endif; ?>
          <a class="badge" href="logout.php" style="text-decoration:none;">Logout</a>
        <?php else: ?>
          <a class="badge" href="login.php" style="text-decoration:none;">Login</a>
          <a class="badge" href="register.php" style="text-decoration:none;">Register</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <form method="post">
        <input type="hidden" name="action" value="search">
        <div class="form-grid">
          <input class="input" type="text" name="city" placeholder="Kota" value="<?= htmlspecialchars($city) ?>" required>
          <input class="input" type="text" name="country" placeholder="Negara" value="<?= htmlspecialchars($country) ?>" required>
          <input class="input" type="date" name="date" value="<?= htmlspecialchars($dateYmd) ?>" required>
          <select name="method" required>
            <?php foreach ($methods as $id=>$name): ?>
              <option value="<?= (int)$id ?>" <?= $method===$id? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="margin-top:12px; display:flex; gap:12px;">
          <button type="submit">Tampilkan</button>
          <?php if ($fromDb): ?><?php endif; ?>
          <label style="display:flex; align-items:center; gap:6px; margin-left:auto; font-size:13px; color:#cbd5e1;">
            <input type="checkbox" name="remind" value="1" <?= isset($_POST['remind']) ? 'checked' : '' ?> <?= $user ? '' : 'disabled title="Login untuk mengaktifkan"' ?>> Pengingat harian
          </label>
        </div>
      </form>
    </div>

    <div class="card">
      <h3 style="margin:0 0 8px 0;">Informasi</h3>
      <div class="meta" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($siteInfo)) ?></div>
    </div>

    <?php if ($user && ($user['role'] ?? 'user') === 'admin'): ?>
      <div class="card" id="cms">
        <h3 style="margin:0 0 8px 0;">CMS: Kelola Informasi</h3>
        <?php if ($success): ?>
          <div style="color:#bbf7d0; margin-bottom:8px;">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="update_info">
          <textarea name="content" class="input" style="min-height:120px;" required><?= htmlspecialchars($siteInfo) ?></textarea>
          <div style="margin-top:12px; display:flex; gap:12px;">
            <button type="submit">Simpan</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="card" style="border-color:#ef4444;">
        <div style="color:#fecaca;">Error: <?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($result): $t=$result['timings']; ?>
      <div class="card">
        <div class="meta">Lokasi: <strong><?= htmlspecialchars($city) ?>, <?= htmlspecialchars($country) ?></strong> · Tanggal: <strong><?= htmlspecialchars($dateYmd) ?></strong> · Metode: <strong><?= htmlspecialchars($methods[$method] ?? (string)$method) ?></strong></div>
        <table class="table">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Jam</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ([
              'Imsak','Fajr','Sunrise','Dhuhr','Asr','Maghrib','Isha','Midnight'
            ] as $k): if (!isset($t[$k])) continue; ?>
              <tr>
                <td><?= htmlspecialchars($k) ?></td>
                <td><?= htmlspecialchars($t[$k]) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <script>
        (function(){
          const loggedIn = <?= $user ? 'true' : 'false' ?>;
          const enabled = loggedIn && <?= isset($_POST['remind']) ? 'true' : 'false' ?>;
          if (!enabled) return;
          const timings = <?= json_encode($t, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;
          const date = "<?= htmlspecialchars($dateYmd, ENT_QUOTES) ?>";
          const titleBase = "Jadwal Sholat";
          const keys = ["Imsak","Fajr","Dhuhr","Asr","Maghrib","Isha"]; 
          function parseTime(hm){
            if(!hm) return null;
            const m = hm.match(/^(\d{1,2}):(\d{2})/);
            if(!m) return null;
            const h = String(m[1]).padStart(2,'0');
            const mm = m[2];
            return new Date(`${date}T${h}:${mm}:00`);
          }
          function chime(){
            try{
              const ctx = new (window.AudioContext||window.webkitAudioContext)();
              const o = ctx.createOscillator();
              const g = ctx.createGain();
              o.type = 'sine';
              o.frequency.setValueAtTime(880, ctx.currentTime);
              o.connect(g); g.connect(ctx.destination);
              g.gain.setValueAtTime(0.0001, ctx.currentTime);
              g.gain.exponentialRampToValueAtTime(0.3, ctx.currentTime+0.05);
              g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime+1.2);
              o.start(); o.stop(ctx.currentTime+1.25);
            }catch(e){}
          }
          function notify(name){
            const body = `${name} - ${timings[name] || ''}`;
            if ('Notification' in window) {
              if (Notification.permission === 'granted') {
                new Notification(titleBase, { body });
              } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(p=>{ if(p==='granted') new Notification(titleBase, { body }); });
              }
            }
            chime();
          }
          function schedule(){
            const now = new Date();
            keys.forEach(k=>{
              const dt = parseTime(timings[k]);
              if(!dt) return;
              const diff = dt.getTime() - now.getTime();
              if (diff > 0 && diff < 24*60*60*1000) {
                setTimeout(()=>notify(k), diff);
              }
            });
          }
          if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().finally(schedule);
          } else {
            schedule();
          }
        })();
      </script>
    <?php endif; ?>

    <div class="footer">&copy; <?= date('Y') ?> Jadwal Sholat · Dimas Indra Pratama UTS</div>
  </div>
</body>
</html>
