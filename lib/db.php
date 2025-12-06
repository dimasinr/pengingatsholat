<?php
require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}

function ensure_schema(): void {
    $sql = "CREATE TABLE IF NOT EXISTS prayer_timings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        city VARCHAR(100) NOT NULL,
        country VARCHAR(100) NOT NULL,
        method INT NOT NULL,
        date DATE NOT NULL,
        fajr VARCHAR(10) NULL,
        sunrise VARCHAR(10) NULL,
        dhuhr VARCHAR(10) NULL,
        asr VARCHAR(10) NULL,
        maghrib VARCHAR(10) NULL,
        isha VARCHAR(10) NULL,
        imsak VARCHAR(10) NULL,
        midnight VARCHAR(10) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_loc_method_date (city, country, method, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    db()->exec($sql);

    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    db()->exec($sqlUsers);

    // Try to add role column for older schemas (ignore error if exists)
    try {
        db()->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
    } catch (Throwable $e) {}

    // Site info table to store editable content for index page
    $sqlInfo = "CREATE TABLE IF NOT EXISTS site_info (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        content TEXT NOT NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    db()->exec($sqlInfo);

    // Seed single row for site_info if empty
    $cntInfo = (int)db()->query("SELECT COUNT(*) AS c FROM site_info")->fetch()['c'];
    if ($cntInfo === 0) {
        $stmtInfo = db()->prepare("INSERT INTO site_info (id, content) VALUES (1, ?)");
        $stmtInfo->execute([
            'Selamat datang di aplikasi Jadwal Sholat. Informasi ini dapat diedit oleh admin.'
        ]);
    }

    // Seed default user if table empty
    $count = (int)db()->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'];
    if ($count === 0) {
        $username = 'admin';
        $password = 'admin123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$username, $hash]);
    }
}

function find_timing(string $city, string $country, int $method, string $dateYmd): ?array {
    $stmt = db()->prepare("SELECT * FROM prayer_timings WHERE city=? AND country=? AND method=? AND date=? LIMIT 1");
    $stmt->execute([$city, $country, $method, $dateYmd]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function save_timing(string $city, string $country, int $method, string $dateYmd, array $timings): void {
    $fields = ['Fajr','Sunrise','Dhuhr','Asr','Maghrib','Isha','Imsak','Midnight'];
    $vals = [];
    foreach ($fields as $f) {
        $vals[strtolower($f)] = isset($timings[$f]) ? substr($timings[$f], 0, 10) : null;
    }
    $stmt = db()->prepare("INSERT INTO prayer_timings
        (city,country,method,date,fajr,sunrise,dhuhr,asr,maghrib,isha,imsak,midnight)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE fajr=VALUES(fajr), sunrise=VALUES(sunrise), dhuhr=VALUES(dhuhr), asr=VALUES(asr), maghrib=VALUES(maghrib), isha=VALUES(isha), imsak=VALUES(imsak), midnight=VALUES(midnight)");
    $stmt->execute([
        $city,
        $country,
        $method,
        $dateYmd,
        $vals['fajr'] ?? null,
        $vals['sunrise'] ?? null,
        $vals['dhuhr'] ?? null,
        $vals['asr'] ?? null,
        $vals['maghrib'] ?? null,
        $vals['isha'] ?? null,
        $vals['imsak'] ?? null,
        $vals['midnight'] ?? null,
    ]);
}

function get_site_info(): string {
    $row = db()->query("SELECT content FROM site_info WHERE id = 1 LIMIT 1")->fetch();
    return $row ? (string)$row['content'] : '';
}

function update_site_info(string $content): void {
    $stmt = db()->prepare("UPDATE site_info SET content = ? WHERE id = 1");
    $stmt->execute([$content]);
}
