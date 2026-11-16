<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
}
require_once('database.php');
require_once(__DIR__ . '/funciones.php');

// Tolak permintaan POST yang datang dari situs lain (perlindungan CSRF untuk semua halaman yang memuat library ini).
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_SERVER['HTTP_ORIGIN'])) {
    $oh = parse_url($_SERVER['HTTP_ORIGIN']);
    $oh = ($oh['host'] ?? '') . (isset($oh['port']) ? ':' . $oh['port'] : '');
    if (strcasecmp($oh, $_SERVER['HTTP_HOST'] ?? '') !== 0) {
        http_response_code(403);
        exit('Permintaan ditolak.');
    }
}

function isUser() {
    global $dbConn;
    if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_type']) || empty($_SESSION['pwd'])) {
        return false;
    }
    try {
        $stmt = dbQuery("SELECT pwd FROM manager_admin WHERE name = ?", [$_SESSION['user_name']]);
        $row = dbFetchAssoc($stmt);
        if ($row && hash_equals(ali_pwd_fp($row['pwd']), (string)$_SESSION['pwd'])) {
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("isUser query failed: " . $e->getMessage());
        return false;
    }
}

// ---- Batas percobaan login ----
function ali_client_ip() {
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function ali_login_table() {
    static $done = false;
    if ($done) { return; }
    $done = true;
    try {
        global $dbConn;
        $driver = $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME);
        $idcol = ($driver === 'sqlite') ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
        dbQuery("CREATE TABLE IF NOT EXISTS login_attempts (id $idcol, ip VARCHAR(45) NOT NULL, uname VARCHAR(190) NOT NULL, ts INT NOT NULL)");
        if ($driver !== 'sqlite') { try { dbQuery("CREATE INDEX idx_la_ip ON login_attempts (ip, ts)"); } catch (Exception $e) {} }
    } catch (Exception $e) {
        error_log('ali_login_table: ' . $e->getMessage());
    }
}

// true kalau login harus ditahan: 5 gagal untuk akun+IP yang sama, atau 20 gagal dari IP yang sama, dalam 15 menit.
function ali_login_blocked($user) {
    ali_login_table();
    try {
        $since = time() - 900;
        $ip = ali_client_ip();
        $stmt = dbQuery("SELECT COUNT(*) AS c FROM login_attempts WHERE ip = ? AND ts > ?", [$ip, $since]);
        $r = dbFetchAssoc($stmt);
        if ($r && (int)$r['c'] >= 20) { return true; }
        $stmt = dbQuery("SELECT COUNT(*) AS c FROM login_attempts WHERE ip = ? AND uname = ? AND ts > ?", [$ip, strtolower(substr($user, 0, 190)), $since]);
        $r = dbFetchAssoc($stmt);
        return $r && (int)$r['c'] >= 5;
    } catch (Exception $e) {
        return false;
    }
}

function ali_login_fail($user) {
    ali_login_table();
    try {
        dbQuery("INSERT INTO login_attempts (ip, uname, ts) VALUES (?, ?, ?)", [ali_client_ip(), strtolower(substr($user, 0, 190)), time()]);
        dbQuery("DELETE FROM login_attempts WHERE ts < ?", [time() - 86400]);
    } catch (Exception $e) {}
}

function ali_login_clear($user) {
    try { dbQuery("DELETE FROM login_attempts WHERE ip = ? AND uname = ?", [ali_client_ip(), strtolower(substr($user, 0, 190))]); } catch (Exception $e) {}
}

function verify_users($user, $password) {
    global $dbConn;
    if (empty($user) || empty($password)) {
        return "Email and password are required.";
    }
    if (ali_login_blocked($user)) {
        return "Terlalu banyak percobaan login. Coba lagi dalam 15 menit.";
    }
    try {
        $stmt = dbQuery("SELECT name, name_parson, pwd FROM manager_admin WHERE name = ?", [$user]);
        $row = dbFetchAssoc($stmt);
        if ($row && ali_check_password($password, $row['pwd'])) {
            $stored = ali_upgrade_hash('manager_admin', $row['name'], $password, $row['pwd']);
            session_regenerate_id(true);
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_type'] = 'Administrator';
            $_SESSION['pwd'] = ali_pwd_fp($stored);
            ali_login_clear($user);
            error_log("Admin login successful: " . $row['name']);
            header("Location: deprixa/admin.php");
            exit;
        }
        if (!$row) { claves($password); } // waktu respon relatif sama walau akun tidak ada
        ali_login_fail($user);
        error_log("Login failed for user: " . $user);
        return "Invalid email or password.";
    } catch (PDOException $e) {
        error_log("verify_users query failed: " . $e->getMessage());
        return "Login error. Coba lagi nanti.";
    }
}

// Hentikan halaman kalau pengunjung belum login sebagai admin.
// $json = true untuk endpoint AJAX: balas JSON 401, bukan redirect.
function requireAdmin($json = false) {
    if (isUser()) { return; }
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Silakan login dulu.']);
        exit;
    }
    header('Location: /login.php');
    exit;
}

// Untuk aksi yang mengubah/menghapus data: permintaan harus datang dari halaman website sendiri.
function sameOriginOk() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $k) {
        if (!empty($_SERVER[$k])) {
            $u = parse_url($_SERVER[$k]);
            if (empty($u['host'])) { return false; }
            $h = $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
            return strcasecmp($h, $host) === 0;
        }
    }
    return false;
}

// Tanggal pengiriman (tanggal barang kami terima) untuk daftar. Pakai collection_date, kalau kosong pakai pick_date.
function ali_ship_date($row) {
    foreach (['collection_date', 'pick_date'] as $k) {
        $v = trim((string)($row[$k] ?? ''));
        if ($v !== '' && strpos($v, '0000') !== 0) { return substr($v, 0, 10); }
    }
    return '';
}
