<?php
// Customer Profile: data pelanggan tetap (nama PT, PIC/sales, kontak, alamat).
// Tabel dibuat otomatis saat pertama kali dipakai, jadi tidak perlu menjalankan SQL manual.
require_once __DIR__ . '/database.php';

function cp_ensure_table() {
    global $dbConn;
    static $done = false;
    if ($done) { return; }
    $driver = $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $dbConn->exec("CREATE TABLE IF NOT EXISTS customer_profile (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL DEFAULT '',
            company_name TEXT NOT NULL,
            pic_name TEXT NOT NULL DEFAULT '',
            pic_phone TEXT NOT NULL DEFAULT '',
            address TEXT NOT NULL DEFAULT '',
            created_at TEXT, updated_at TEXT)");
    } else {
        $dbConn->exec("CREATE TABLE IF NOT EXISTS customer_profile (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(30) NOT NULL DEFAULT '',
            company_name VARCHAR(190) NOT NULL,
            pic_name VARCHAR(120) NOT NULL DEFAULT '',
            pic_phone VARCHAR(40) NOT NULL DEFAULT '',
            address TEXT NOT NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            KEY idx_company (company_name), KEY idx_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $done = true;
}

// Kolom customer_id di tabel courier dibuat oleh courier-lib.php
require_once __DIR__ . '/courier-lib.php';
function cp_courier_has_customer_col() { return courier_columns_ready(); }

function cp_clean($v, $max) {
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$v);
    $v = trim(preg_replace('/\s+/u', ' ', $v));
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

function cp_fields($src) {
    return [
        'code'         => strtoupper(cp_clean($src['code'] ?? '', 30)),
        'company_name' => cp_clean($src['company_name'] ?? '', 190),
        'pic_name'     => cp_clean($src['pic_name'] ?? '', 120),
        'pic_phone'    => cp_clean($src['pic_phone'] ?? '', 40),
        'address'      => cp_clean($src['address'] ?? '', 500),
    ];
}

function cp_validate($f) {
    if ($f['company_name'] === '') { return 'Nama PT wajib diisi.'; }
    if ($f['pic_phone'] !== '' && !preg_match('/^[0-9+()\-.\/ ]{5,40}$/', $f['pic_phone'])) { return 'Nomor kontak hanya boleh angka, spasi, dan tanda + - ( ).'; }
    return '';
}

function cp_like($q) { return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%'; }

function cp_search($q, $limit = 10) {
    cp_ensure_table();
    $limit = max(1, min(100, (int)$limit));
    $q = cp_clean($q, 100);
    if ($q === '') {
        $st = dbQuery("SELECT * FROM customer_profile ORDER BY company_name LIMIT $limit");
    } else {
        $l = cp_like($q);
        $st = dbQuery("SELECT * FROM customer_profile WHERE company_name LIKE ? OR code LIKE ? OR pic_name LIKE ? OR pic_phone LIKE ? ORDER BY company_name LIMIT $limit", [$l, $l, $l, $l]);
    }
    $rows = [];
    while ($r = dbFetchAssoc($st)) { $rows[] = $r; }
    return $rows;
}

function cp_get($id) {
    cp_ensure_table();
    $st = dbQuery("SELECT * FROM customer_profile WHERE id = ?", [(int)$id]);
    return dbFetchAssoc($st) ?: null;
}

function cp_create($f) {
    global $dbConn;
    cp_ensure_table();
    $now = date('Y-m-d H:i:s');
    dbQuery("INSERT INTO customer_profile (code, company_name, pic_name, pic_phone, address, created_at, updated_at) VALUES (?,?,?,?,?,?,?)",
        [$f['code'], $f['company_name'], $f['pic_name'], $f['pic_phone'], $f['address'], $now, $now]);
    return (int)$dbConn->lastInsertId();
}

function cp_update($id, $f) {
    cp_ensure_table();
    dbQuery("UPDATE customer_profile SET code=?, company_name=?, pic_name=?, pic_phone=?, address=?, updated_at=? WHERE id=?",
        [$f['code'], $f['company_name'], $f['pic_name'], $f['pic_phone'], $f['address'], date('Y-m-d H:i:s'), (int)$id]);
}

function cp_delete($id) {
    cp_ensure_table();
    dbQuery("DELETE FROM customer_profile WHERE id = ?", [(int)$id]);
}
