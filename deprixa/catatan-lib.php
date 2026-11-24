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
