<?php
require_once __DIR__ . '/../library.php';
requireAdmin();
if (!sameOriginOk()) { http_response_code(403); exit('Permintaan ditolak. Buka dari halaman admin.'); }

$id = (int)($_GET['cid'] ?? 0);
if (($_GET['action'] ?? '') === 'del' && $id > 0) {
    dbQuery("DELETE FROM courier_online WHERE cid = ?", [$id]);
}
header('Location: ../admin.php');
exit;
