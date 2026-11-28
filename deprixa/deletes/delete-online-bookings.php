<?php
require_once __DIR__ . '/../library.php';
requireAdmin();
if (!sameOriginOk()) { http_response_code(403); exit('Permintaan ditolak. Buka dari halaman admin.'); }

$id = (int)($_GET['id'] ?? 0);
if (($_GET['action'] ?? '') === 'del' && $id > 0 && ali_same_site_click()) {
    dbQuery("DELETE FROM online_booking WHERE id = ?", [$id]);
}
header('Location: ../online-bookings.php');
exit;
