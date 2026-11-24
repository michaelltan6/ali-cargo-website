<?php
// Aksi untuk halaman Catatan dan tugas (Kanban). Semua balasan berupa JSON.
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode', '1'); session_start(); }
require_once('database.php');
require_once('library.php');
require_once('catatan-lib.php');
requireAdmin(true);
header('Content-Type: application/json; charset=utf-8');
ob_clean();

function kb_fail($m, $code = 200) { http_response_code($code); echo json_encode(['success' => false, 'message' => $m]); exit; }
function kb_ok($extra = []) { echo json_encode(array_merge(['success' => true], $extra)); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { kb_fail('Metode tidak diizinkan.', 405); }
if (!sameOriginOk()) { kb_fail('Permintaan ditolak.', 403); }

try {
    kb_ensure_tables();
    $act = (string)($_POST['action'] ?? '');
    $scope = (string)($_POST['scope'] ?? 'personal');
    if (!kb_scope_valid($scope)) { kb_fail('Papan tidak dikenal.'); }
    $owner = kb_owner($scope);
    if ($scope === 'personal' && $owner === '') { kb_fail('Sesi tidak valid. Login ulang.'); }
    $me = (string)($_SESSION['user_name'] ?? '');

    switch ($act) {

    case 'board': {
        kb_ensure_defaults($scope, $owner);
        kb_archive_sweep($scope, $owner);
        kb_purge_old($scope, $owner);
        $files = kb_files_of($scope, $owner);
        $cols = []; $st = dbQuery("SELECT id, name, position, is_done, color FROM kb_columns WHERE scope = ? AND owner = ? ORDER BY position, id", [$scope, $owner]);
        while ($r = dbFetchAssoc($st)) { $r['id'] = (int)$r['id']; $r['is_done'] = (int)$r['is_done']; $cols[] = $r; }
        $cards = []; $st = dbQuery("SELECT id, column_id, title, notes, priority, due_date, assignee, created_by, position, finished_at FROM kb_cards WHERE scope = ? AND owner = ? AND archived_at IS NULL ORDER BY position, id", [$scope, $owner]);
        while ($r = dbFetchAssoc($st)) { $r['id'] = (int)$r['id']; $r['column_id'] = (int)$r['column_id']; $r['files'] = $files[$r['id']] ?? []; $cards[] = $r; }
        $ar = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM kb_cards WHERE scope = ? AND owner = ? AND archived_at IS NOT NULL", [$scope, $owner]));
        kb_ok(['columns' => $cols, 'cards' => $cards, 'archived' => (int)($ar['c'] ?? 0), 'people' => $scope === 'company' ? kb_people() : [], 'me' => $me, 'used' => kb_used($scope, $owner), 'keep_days' => KB_KEEP_DAYS]);
    }

    case 'archive_list': {
        $rows = []; $st = dbQuery("SELECT c.id, c.title, c.notes, c.priority, c.assignee, c.finished_at, c.archived_at, k.name AS col FROM kb_cards c LEFT JOIN kb_columns k ON k.id = c.column_id WHERE c.scope = ? AND c.owner = ? AND c.archived_at IS NOT NULL ORDER BY c.finished_at DESC, c.id DESC LIMIT 500", [$scope, $owner]);
        while ($r = dbFetchAssoc($st)) { $r['id'] = (int)$r['id']; $rows[] = $r; }
        kb_ok(['cards' => $rows]);
    }

    case 'card_save': {
        $id = (int)($_POST['id'] ?? 0);
        $title = kb_clean($_POST['title'] ?? '', 200);
        if ($title === '') { kb_fail('Judul wajib diisi.'); }
        $notes = kb_clean($_POST['notes'] ?? '', 4000);
        $pr = (string)($_POST['priority'] ?? 'sedang');
        if (!in_array($pr, ['rendah', 'sedang', 'tinggi'], true)) { $pr = 'sedang'; }
        $due = kb_date($_POST['due_date'] ?? '');
        if ($due === false) { kb_fail('Tanggal tenggat tidak valid.'); }
        $assignee = $scope === 'company' ? kb_clean($_POST['assignee'] ?? '', 100) : '';
        $colId = (int)($_POST['column_id'] ?? 0);
        $col = kb_column_row($colId, $scope, $owner);
        if (!$col) { kb_fail('Kolom tidak ditemukan.'); }
        $now = kb_now();
        if ($id > 0) {
            $card = kb_card_row($id, $scope, $owner);
            if (!$card) { kb_fail('Kartu tidak ditemukan.'); }
            $fin = $card['finished_at'];
            if ((int)$col['is_done'] === 1 && empty($fin)) { $fin = $now; }
            if ((int)$col['is_done'] !== 1) { $fin = null; }
            $newPos = (int)$card['position'];
            if ((int)$card['column_id'] !== $colId) {
                $m = dbFetchAssoc(dbQuery("SELECT MAX(position) AS m FROM kb_cards WHERE column_id = ? AND archived_at IS NULL", [$colId]));
                $newPos = (int)($m['m'] ?? -1) + 1;
            }
            dbQuery("UPDATE kb_cards SET title = ?, notes = ?, priority = ?, due_date = ?, assignee = ?, column_id = ?, position = ?, finished_at = ?, updated_at = ? WHERE id = ?",
                [$title, $notes, $pr, $due, $assignee, $colId, $newPos, $fin, $now, $id]);
            kb_ok(['id' => $id]);
        } else {
            $m = dbFetchAssoc(dbQuery("SELECT MAX(position) AS m FROM kb_cards WHERE column_id = ? AND archived_at IS NULL", [$colId]));
            $pos = (int)($m['m'] ?? -1) + 1;
            $fin = ((int)$col['is_done'] === 1) ? $now : null;
            dbQuery("INSERT INTO kb_cards (scope, owner, column_id, title, notes, priority, due_date, assignee, created_by, position, created_at, updated_at, finished_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$scope, $owner, $colId, $title, $notes, $pr, $due, $assignee, $me, $pos, $now, $now, $fin]);
            global $dbConn;
            kb_ok(['id' => (int)$dbConn->lastInsertId()]);
        }
    }

    case 'card_move': {
        // Kartu pindah ke kolom tertentu, dengan urutan akhir kartu di kolom itu (daftar id).
        $id = (int)($_POST['id'] ?? 0);
        $colId = (int)($_POST['column_id'] ?? 0);
        $card = kb_card_row($id, $scope, $owner);
        $col = kb_column_row($colId, $scope, $owner);
        if (!$card || !$col) { kb_fail('Kartu atau kolom tidak ditemukan.'); }
        $order = array_values(array_filter(array_map('intval', (array)($_POST['order'] ?? [])), function ($v) { return $v > 0; }));
        if (!in_array($id, $order, true)) { $order[] = $id; }
        $now = kb_now();
        $fin = $card['finished_at'];
        if ((int)$col['is_done'] === 1 && ((int)$card['column_id'] !== $colId || empty($fin))) { $fin = $now; }
        if ((int)$col['is_done'] !== 1) { $fin = null; }
        dbQuery("UPDATE kb_cards SET column_id = ?, finished_at = ?, updated_at = ? WHERE id = ?", [$colId, $fin, $now, $id]);
        foreach ($order as $i => $cid) {
            dbQuery("UPDATE kb_cards SET position = ? WHERE id = ? AND scope = ? AND owner = ? AND column_id = ?", [$i, $cid, $scope, $owner, $colId]);
        }
        kb_ok();
    }

    case 'card_delete': {
        $id = (int)($_POST['id'] ?? 0);
        if (!kb_card_row($id, $scope, $owner)) { kb_fail('Kartu tidak ditemukan.'); }
        kb_remove_files([$id]);
        dbQuery("DELETE FROM kb_cards WHERE id = ?", [$id]);
        kb_ok();
    }

    case 'card_restore': {
        $id = (int)($_POST['id'] ?? 0);
        if (!kb_card_row($id, $scope, $owner)) { kb_fail('Kartu tidak ditemukan.'); }
        // finished_at dibuat baru supaya tidak langsung diarsipkan lagi di minggu yang sama
        dbQuery("UPDATE kb_cards SET archived_at = NULL, finished_at = ?, updated_at = ? WHERE id = ?", [kb_now(), kb_now(), $id]);
        kb_ok();
    }

    case 'file_upload': {
        $cid = (int)($_POST['card_id'] ?? 0);
        if (!kb_card_row($cid, $scope, $owner)) { kb_fail('Kartu tidak ditemukan.'); }
        $f = $_FILES['file'] ?? null;
        if (!$f || !is_uploaded_file($f['tmp_name'] ?? '')) {
            $e = (int)($f['error'] ?? 0);
            kb_fail(($e === UPLOAD_ERR_INI_SIZE || $e === UPLOAD_ERR_FORM_SIZE) ? 'File terlalu besar untuk server.' : 'File tidak terkirim.');
        }
        if ((int)$f['error'] !== UPLOAD_ERR_OK) { kb_fail('Upload gagal (kode ' . (int)$f['error'] . ').'); }
        if ($f['size'] <= 0) { kb_fail('File kosong.'); }
        if ($f['size'] > KB_MAX_BYTES) { kb_fail('Ukuran file maksimal 5 MB.'); }
        $cnt = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM kb_files WHERE card_id = ?", [$cid]));
        if ((int)($cnt['c'] ?? 0) >= KB_MAX_FILES) { kb_fail('Maksimal ' . KB_MAX_FILES . ' lampiran per kartu.'); }
        $orig = str_replace(['\\', '/'], '_', kb_clean($f['name'] ?? 'file', 120));
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = kb_allowed();
        if (!isset($allowed[$ext])) { kb_fail('Jenis file tidak diizinkan. Boleh: jpg, png, webp, pdf, xlsx, docx, csv, txt.'); }
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$fi->file($f['tmp_name']);
        if (!in_array($mime, $allowed[$ext], true)) { kb_fail('Isi file tidak sesuai dengan jenisnya.'); }
        if ($mime === 'application/zip' || $mime === 'application/vnd.openxml-formats-package') {
            $mime = $ext === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }
        if ($ext === 'csv') { $mime = 'text/csv'; }
        $stored = bin2hex(random_bytes(16));
        if (!@move_uploaded_file($f['tmp_name'], kb_dir() . '/' . $stored)) { kb_fail('Gagal menyimpan file di server.'); }
        @chmod(kb_dir() . '/' . $stored, 0640);
        dbQuery("INSERT INTO kb_files (card_id, scope, owner, orig_name, stored_name, mime, size, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$cid, $scope, $owner, $orig, $stored, $mime, (int)$f['size'], $me, kb_now()]);
        kb_ok();
    }

    case 'file_delete': {
        $fid = (int)($_POST['id'] ?? 0);
        $r = dbFetchAssoc(dbQuery("SELECT id, stored_name FROM kb_files WHERE id = ? AND scope = ? AND owner = ?", [$fid, $scope, $owner]));
        if (!$r) { kb_fail('Lampiran tidak ditemukan.'); }
        $path = kb_dir() . '/' . basename((string)$r['stored_name']);
        if (is_file($path)) { @unlink($path); }
        dbQuery("DELETE FROM kb_files WHERE id = ?", [$fid]);
        kb_ok();
    }

    case 'col_save': {
        $id = (int)($_POST['id'] ?? 0);
        $name = kb_clean($_POST['name'] ?? '', 60);
        if ($name === '') { kb_fail('Nama kolom wajib diisi.'); }
        $done = ((int)($_POST['is_done'] ?? 0)) === 1 ? 1 : 0;
        $color = (string)($_POST['color'] ?? '');
        if (!in_array($color, kb_colors(), true)) { $color = '#8a949e'; }
        if ($id > 0) {
            $col = kb_column_row($id, $scope, $owner);
            if (!$col) { kb_fail('Kolom tidak ditemukan.'); }
            if ($done === 0 && (int)$col['is_done'] === 1) {
                $r = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM kb_columns WHERE scope = ? AND owner = ? AND is_done = 1 AND id <> ?", [$scope, $owner, $id]));
                if ((int)($r['c'] ?? 0) === 0) { kb_fail('Harus ada minimal satu kolom penanda "selesai".'); }
            }
            dbQuery("UPDATE kb_columns SET name = ?, is_done = ?, color = ? WHERE id = ?", [$name, $done, $color, $id]);
            if ($done === 1 && (int)$col['is_done'] !== 1) {
                dbQuery("UPDATE kb_cards SET finished_at = ? WHERE column_id = ? AND finished_at IS NULL AND archived_at IS NULL", [kb_now(), $id]);
            }
            if ($done === 0 && (int)$col['is_done'] === 1) {
                dbQuery("UPDATE kb_cards SET finished_at = NULL WHERE column_id = ?", [$id]);
            }
        } else {
            $r = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c, MAX(position) AS m FROM kb_columns WHERE scope = ? AND owner = ?", [$scope, $owner]));
            if ((int)($r['c'] ?? 0) >= 12) { kb_fail('Maksimal 12 kolom per papan.'); }
            dbQuery("INSERT INTO kb_columns (scope, owner, name, position, is_done, color) VALUES (?, ?, ?, ?, ?, ?)", [$scope, $owner, $name, (int)($r['m'] ?? -1) + 1, $done, $color]);
        }
        kb_ok();
    }

    case 'col_order': {
        $order = array_values(array_filter(array_map('intval', (array)($_POST['order'] ?? [])), function ($v) { return $v > 0; }));
        foreach ($order as $i => $cid) {
            dbQuery("UPDATE kb_columns SET position = ? WHERE id = ? AND scope = ? AND owner = ?", [$i, $cid, $scope, $owner]);
        }
        kb_ok();
    }

    case 'col_delete': {
        $id = (int)($_POST['id'] ?? 0);
        $col = kb_column_row($id, $scope, $owner);
        if (!$col) { kb_fail('Kolom tidak ditemukan.'); }
        $r = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM kb_cards WHERE column_id = ?", [$id]));
        if ((int)($r['c'] ?? 0) > 0) { kb_fail('Kolom masih berisi kartu. Pindahkan atau hapus kartunya dulu.'); }
        if ((int)$col['is_done'] === 1) {
            $r = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM kb_columns WHERE scope = ? AND owner = ? AND is_done = 1 AND id <> ?", [$scope, $owner, $id]));
            if ((int)($r['c'] ?? 0) === 0) { kb_fail('Harus ada minimal satu kolom penanda "selesai".'); }
        }
        dbQuery("DELETE FROM kb_columns WHERE id = ?", [$id]);
        kb_ok();
    }

    default:
        kb_fail('Aksi tidak dikenal.');
    }
} catch (Exception $e) {
    error_log('catatan-api: ' . $e->getMessage());
    kb_fail('Terjadi kesalahan di server.', 500);
}
