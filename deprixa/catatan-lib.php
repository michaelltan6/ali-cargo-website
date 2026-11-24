<?php
// Catatan dan tugas (Kanban) untuk admin dan pegawai.
// Dua papan: 'personal' (milik satu akun, hanya dia yang lihat) dan 'company' (dipakai bersama).
// Tabel dibuat otomatis. Kartu yang selesai dipindah ke arsip tiap awal minggu (Senin).

function kb_ensure_tables() {
    global $dbConn;
    static $done = false;
    if ($done) { return; }
    $done = true;
    $sqlite = $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $idcol = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $dbConn->exec("CREATE TABLE IF NOT EXISTS kb_columns (
        id $idcol, scope VARCHAR(10) NOT NULL, owner VARCHAR(100) NOT NULL DEFAULT '',
        name VARCHAR(60) NOT NULL, position INT NOT NULL DEFAULT 0, is_done INT NOT NULL DEFAULT 0,
        color VARCHAR(7) NOT NULL DEFAULT '#8a949e')");
    $dbConn->exec("CREATE TABLE IF NOT EXISTS kb_cards (
        id $idcol, scope VARCHAR(10) NOT NULL, owner VARCHAR(100) NOT NULL DEFAULT '',
        column_id INT NOT NULL, title VARCHAR(200) NOT NULL, notes TEXT, priority VARCHAR(6) NOT NULL DEFAULT 'sedang',
        due_date VARCHAR(10) DEFAULT NULL, assignee VARCHAR(100) NOT NULL DEFAULT '', created_by VARCHAR(100) NOT NULL DEFAULT '',
        position INT NOT NULL DEFAULT 0, created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL,
        finished_at VARCHAR(19) DEFAULT NULL, archived_at VARCHAR(19) DEFAULT NULL)");
    $dbConn->exec("CREATE TABLE IF NOT EXISTS kb_files (
        id $idcol, card_id INT NOT NULL, scope VARCHAR(10) NOT NULL, owner VARCHAR(100) NOT NULL DEFAULT '',
        orig_name VARCHAR(150) NOT NULL, stored_name VARCHAR(64) NOT NULL, mime VARCHAR(100) NOT NULL,
        size INT NOT NULL DEFAULT 0, created_by VARCHAR(100) NOT NULL DEFAULT '', created_at VARCHAR(19) NOT NULL)");
}

// ---- lampiran ----
define('KB_MAX_BYTES', 5 * 1024 * 1024);
define('KB_MAX_FILES', 5);
define('KB_KEEP_DAYS', 90);   // lampiran kartu arsip dihapus setelah sekian hari

// Hanya jenis ini yang boleh. Isi file dicek dengan finfo, bukan dari namanya.
function kb_allowed() {
    return array(
        'jpg' => array('image/jpeg'), 'jpeg' => array('image/jpeg'), 'png' => array('image/png'), 'webp' => array('image/webp'),
        'pdf' => array('application/pdf'), 'txt' => array('text/plain'), 'csv' => array('text/plain', 'text/csv', 'application/csv'),
        'xlsx' => array('application/vnd.openxml-formats-package', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'),
        'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
    );
}
function kb_is_image($mime) { return in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), true); }

// Folder penyimpanan, diblokir total dari akses langsung lewat URL.
function kb_dir() {
    $d = __DIR__ . '/kb-files';
    if (!is_dir($d)) { @mkdir($d, 0750, true); }
    $h = $d . '/.htaccess';
    if (!is_file($h)) { @file_put_contents($h, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n"); }
    if (!is_file($d . '/index.html')) { @file_put_contents($d . '/index.html', ''); }
    return $d;
}

// Hapus file fisik dan barisnya untuk daftar id kartu.
function kb_remove_files($cardIds) {
    $cardIds = array_values(array_filter(array_map('intval', (array)$cardIds)));
    if (!$cardIds) { return; }
    $in = implode(',', $cardIds);
    $st = dbQuery("SELECT id, stored_name FROM kb_files WHERE card_id IN ($in)");
    while ($r = dbFetchAssoc($st)) {
        $f = kb_dir() . '/' . basename((string)$r['stored_name']);
        if (is_file($f)) { @unlink($f); }
    }
    dbQuery("DELETE FROM kb_files WHERE card_id IN ($in)");
}

function kb_files_of($scope, $owner) {
    $map = array();
    $st = dbQuery("SELECT id, card_id, orig_name, mime, size FROM kb_files WHERE scope = ? AND owner = ? ORDER BY id", [$scope, $owner]);
    while ($r = dbFetchAssoc($st)) {
        $map[(int)$r['card_id']][] = array('id' => (int)$r['id'], 'name' => $r['orig_name'], 'mime' => $r['mime'], 'size' => (int)$r['size'], 'image' => kb_is_image($r['mime']));
    }
    return $map;
}
function kb_used($scope, $owner) {
    $r = dbFetchAssoc(dbQuery("SELECT COALESCE(SUM(size),0) AS s FROM kb_files WHERE scope = ? AND owner = ?", [$scope, $owner]));
    return (int)($r['s'] ?? 0);
}

function kb_now() { return date('Y-m-d H:i:s'); }

// Senin minggu ini, jam 00:00. Kartu selesai sebelum waktu ini masuk arsip.
function kb_week_start() { return date('Y-m-d 00:00:00', strtotime('monday this week')); }

function kb_colors() { return array('#8a949e', '#3b82f6', '#f59e0b', '#22a06b', '#e11d27', '#8b5cf6'); }

// Cek papan yang diminta. Papan pribadi selalu milik akun yang sedang login.
function kb_owner($scope) {
    if ($scope === 'company') { return ''; }
    return (string)($_SESSION['user_name'] ?? '');
}
function kb_scope_valid($scope) { return $scope === 'personal' || $scope === 'company'; }

// Papan baru langsung punya empat kolom awal.
function kb_ensure_defaults($scope, $owner) {
    $r = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM kb_columns WHERE scope = ? AND owner = ?", [$scope, $owner]));
    if ((int)($r['c'] ?? 0) > 0) { return; }
    $defs = array(
        array('Belum dimulai', 0, '#8a949e'),
        array('Sedang dikerjakan', 0, '#3b82f6'),
        array('Ditunda', 0, '#f59e0b'),
        array('Selesai', 1, '#22a06b'),
    );
    foreach ($defs as $i => $d) {
        dbQuery("INSERT INTO kb_columns (scope, owner, name, position, is_done, color) VALUES (?, ?, ?, ?, ?, ?)", [$scope, $owner, $d[0], $i, $d[1], $d[2]]);
    }
}

// Pindahkan kartu selesai dari minggu-minggu lalu ke arsip.
function kb_archive_sweep($scope, $owner) {
    dbQuery("UPDATE kb_cards SET archived_at = ? WHERE scope = ? AND owner = ? AND archived_at IS NULL AND finished_at IS NOT NULL AND finished_at < ?
             AND column_id IN (SELECT id FROM kb_columns WHERE scope = ? AND owner = ? AND is_done = 1)",
        [kb_now(), $scope, $owner, kb_week_start(), $scope, $owner]);
}

// Lampiran kartu arsip yang sudah lewat masa simpan dihapus. Kartunya tetap ada.
function kb_purge_old($scope, $owner) {
    $cut = date('Y-m-d H:i:s', time() - KB_KEEP_DAYS * 86400);
    $ids = array();
    $st = dbQuery("SELECT c.id FROM kb_cards c WHERE c.scope = ? AND c.owner = ? AND c.archived_at IS NOT NULL AND c.archived_at < ? AND EXISTS (SELECT 1 FROM kb_files f WHERE f.card_id = c.id)", [$scope, $owner, $cut]);
    while ($r = dbFetchAssoc($st)) { $ids[] = (int)$r['id']; }
    kb_remove_files($ids);
}

function kb_clean($v, $max) {
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', (string)$v);
    $v = trim($v);
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

function kb_date($v) {
    $v = trim((string)$v);
    if ($v === '') { return null; }
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : false;
}

// Daftar nama akun untuk pilihan "penanggung jawab" di papan perusahaan.
function kb_people() {
    $out = array();
    try {
        $st = dbQuery("SELECT name FROM manager_admin ORDER BY name");
        while ($r = dbFetchAssoc($st)) { $out[] = (string)$r['name']; }
    } catch (Exception $e) {}
    return $out;
}

function kb_column_row($id, $scope, $owner) {
    return dbFetchAssoc(dbQuery("SELECT * FROM kb_columns WHERE id = ? AND scope = ? AND owner = ?", [(int)$id, $scope, $owner]));
}
function kb_card_row($id, $scope, $owner) {
    return dbFetchAssoc(dbQuery("SELECT * FROM kb_cards WHERE id = ? AND scope = ? AND owner = ?", [(int)$id, $scope, $owner]));
}
