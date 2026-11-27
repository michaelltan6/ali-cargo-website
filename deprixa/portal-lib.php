<?php
// Portal customer: login terpisah dari admin. Customer hanya melihat pengiriman miliknya (courier.customer_id).
// Sesi customer memakai kunci sendiri (cust_uid, cust_fp), jadi tidak pernah dianggap sebagai admin.
require_once __DIR__ . '/library.php';
require_once __DIR__ . '/courier-lib.php';
require_once __DIR__ . '/customer-lib.php';

function pt_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function pt_ensure_table() {
    global $dbConn;
    static $done = false;
    if ($done) { return; }
    $done = true;
    $sqlite = $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $idcol = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $dbConn->exec("CREATE TABLE IF NOT EXISTS customer_users (
        id $idcol, customer_id INT NOT NULL, username VARCHAR(40) NOT NULL, full_name VARCHAR(120) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '', pwd VARCHAR(255) NOT NULL, active INT NOT NULL DEFAULT 1, must_change INT NOT NULL DEFAULT 1,
        created_at VARCHAR(19) NOT NULL, created_by VARCHAR(100) NOT NULL DEFAULT '', last_login VARCHAR(19) DEFAULT NULL)");
    try { $dbConn->exec("CREATE UNIQUE INDEX idx_cu_username ON customer_users (username)"); } catch (Exception $e) { /* sudah ada */ }
}

// Password sementara: 12 karakter tanpa huruf yang mirip (0/O, 1/l/I).
function pt_temp_password() {
    $a = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $p = '';
    for ($i = 0; $i < 12; $i++) { $p .= $a[random_int(0, strlen($a) - 1)]; }
    return $p;
}

function pt_valid_username($u) { return (bool)preg_match('/^[a-z0-9._-]{3,40}$/', $u); }

// Akun customer yang sedang login, atau null. Sesi gugur otomatis kalau akun dimatikan atau password diganti.
function pt_user() {
    static $cache = false;
    if ($cache !== false) { return $cache; }
    $cache = null;
    $uid = (int)($_SESSION['cust_uid'] ?? 0);
    if ($uid <= 0 || empty($_SESSION['cust_fp'])) { return null; }
    try {
        pt_ensure_table();
        $r = dbFetchAssoc(dbQuery("SELECT u.*, c.company_name, c.code FROM customer_users u JOIN customer_profile c ON c.id = u.customer_id WHERE u.id = ? AND u.active = 1", [$uid]));
        if ($r && hash_equals(ali_pwd_fp($r['pwd']), (string)$_SESSION['cust_fp'])) { $cache = $r; }
    } catch (Exception $e) { error_log('pt_user: ' . $e->getMessage()); }
    if (!$cache) { unset($_SESSION['cust_uid'], $_SESSION['cust_fp']); }
    return $cache;
}

function pt_require($allowMustChange = false) {
    $u = pt_user();
    if (!$u) { header('Location: /portal/login.php'); exit; }
    if (!$allowMustChange && (int)$u['must_change'] === 1) { header('Location: /portal/password.php'); exit; }
    return $u;
}

function pt_csrf() {
    if (empty($_SESSION['pt_csrf'])) { $_SESSION['pt_csrf'] = bin2hex(random_bytes(16)); }
    return $_SESSION['pt_csrf'];
}
function pt_csrf_ok() { return sameOriginOk() && hash_equals(pt_csrf(), (string)($_POST['csrf'] ?? '')); }

function pt_status_label($s) {
    $map = ['In-Transit' => 'Dalam perjalanan', 'On-Hold' => 'Ditahan', 'Landed' => 'Tiba di kota tujuan', 'Delayed' => 'Terlambat', 'Finished' => 'Proses selesai', 'Delivered' => 'Terkirim', 'Picked Up' => 'Sudah dijemput'];
    return $map[$s] ?? (string)$s;
}
function pt_status_class($s) {
    if ($s === 'Delivered') { return 'ok'; }
    if ($s === 'Delayed' || $s === 'On-Hold') { return 'warn'; }
    return 'go';
}
function pt_date($v) {
    $v = trim((string)$v);
    if ($v === '' || strpos($v, '0000') === 0) { return ''; }
    $t = strtotime($v);
    if (!$t) { return ''; }
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('j', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y', $t);
}
function pt_num($v, $dec = 2) {
    $s = number_format((float)$v, $dec, ',', '.');
    return $dec > 0 ? rtrim(rtrim($s, '0'), ',') : $s;
}

// Status pembayaran untuk satu pengiriman: [label, kelas css, kunci filter]
function pt_pay_state($r) {
    $mode = (string)($r['book_mode'] ?? '');
    $term = (string)($r['payment_term'] ?? '');
    $isTempo = in_array($term, ['7', '14', '30'], true);
    if ($mode === 'Paid') {
        $d = pt_date($r['paid_date'] ?? '');
        return [$isTempo ? ('Lunas' . ($d ? ' ' . $d : '')) : 'Lunas (cash)', 'ok', 'paid'];
    }
    if ($mode === 'ToPay') {
        $due = trim((string)($r['due_date'] ?? ''));
        if ($due === '' || strpos($due, '0000') === 0) { return ['Belum lunas', 'go', 'unpaid']; }
        $days = (int)floor((strtotime($due) - strtotime(date('Y-m-d'))) / 86400);
        if ($days < 0) { return ['Lewat jatuh tempo ' . abs($days) . ' hari', 'late', 'late']; }
        if ($days === 0) { return ['Jatuh tempo hari ini', 'late', 'late']; }
        return ['Jatuh tempo ' . pt_date($due), 'warn', 'unpaid'];
    }
    return ['-', '', ''];
}
function pt_term_label($r) {
    $t = (string)($r['payment_term'] ?? '');
    return in_array($t, ['7', '14', '30'], true) ? 'Tempo ' . $t . ' hari' : 'Cash';
}

function pt_head($title, $user = null) {
    header('X-Frame-Options: SAMEORIGIN');
    header('Cache-Control: private, no-store');
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo pt_e($title); ?> · Portal Customer ALI Cargo</title>
<link rel="icon" type="image/png" href="/logoAli.png">
<link rel="stylesheet" href="/portal/portal.css?v=1">
</head>
<body>
<header class="pt-top">
    <div class="pt-wrap pt-top-in">
        <a class="pt-brand" href="/portal/"><img src="/logoAli.png" alt=""><span>Portal Customer</span></a>
        <?php if ($user) { ?>
        <nav class="pt-nav">
            <span class="pt-co"><?php echo pt_e($user['company_name']); ?></span>
            <a href="/portal/password.php">Ganti password</a>
            <a href="/portal/logout.php">Keluar</a>
        </nav>
        <?php } ?>
    </div>
</header>
<main class="pt-wrap">
<?php
}
function pt_foot() {
    ?>
</main>
<footer class="pt-foot pt-wrap">PT. Aneka Lintas Indonesia Cargo · Butuh bantuan? Hubungi admin ALI Cargo.</footer>
</body>
</html>
<?php
}
