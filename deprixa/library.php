<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('database.php');

function isUser() {
    global $dbConn;
    if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_type'])) {
        error_log("Session variables user_name or user_type not set.");
        return false;
    }
    try {
        $stmt = dbQuery("SELECT COUNT(*) as cnt FROM manager_admin WHERE name = ? AND pwd = ?", [$_SESSION['user_name'], $_SESSION['pwd']]);
        $row = dbFetchAssoc($stmt);
        if ($row['cnt'] > 0) {
            return true;
        }
        error_log("User not found in manager_admin: " . $_SESSION['user_name']);
        return false;
    } catch (PDOException $e) {
        error_log("isUser query failed: " . $e->getMessage());
        return false;
    }
}

function verify_users($user, $password) {
    global $dbConn;
    if (empty($user) || empty($password)) {
        return "Email and password are required.";
    }
    try {
        $hashed_password = claves($password);
        $stmt = dbQuery("SELECT name, name_parson, 'Administrator' as user_type FROM manager_admin WHERE name = ? AND pwd = ?", [$user, $hashed_password]);
        $row = dbFetchAssoc($stmt);
        if ($row) {
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['pwd'] = $hashed_password;
            error_log("Admin login successful: " . $user);
            header("Location: deprixa/admin.php");
            exit;
        }
        error_log("Login failed for user: " . $user);
        return "Invalid email or password.";
    } catch (PDOException $e) {
        error_log("verify_users query failed: " . $e->getMessage());
        return "Login error: " . htmlspecialchars($e->getMessage());
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
