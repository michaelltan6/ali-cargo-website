<?php
require_once('database.php'); // pastikan koneksi PDO
require_once('library.php'); // kalau dbQuery didefinisikan di sini
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !sameOriginOk()) { http_response_code(403); exit('Permintaan ditolak.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_ids'])) {
    foreach ($_POST['delete_ids'] as $id) {
        $id = intval($id);
        dbQuery("DELETE FROM courier_track WHERE id = ?", [$id]);
    }
}

$back = (string)($_SERVER['HTTP_REFERER'] ?? '');
header('Location: ' . (ali_same_site_click() && $back !== '' ? $back : 'admin.php'));
exit;
?>
