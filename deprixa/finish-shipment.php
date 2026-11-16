<?php
// Menyelesaikan pengiriman (status Delivered) langsung dari dashboard.
ob_start();
if (session_status() === PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); } }
require_once('database.php');
require_once('library.php');
requireAdmin(true);
header('Content-Type: application/json; charset=utf-8');
ob_clean();

function fs_fail($m, $code = 200) { http_response_code($code); echo json_encode(['success' => false, 'message' => $m]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fs_fail('Metode tidak diizinkan.', 405); }
if (!sameOriginOk()) { fs_fail('Permintaan ditolak.', 403); }

$cid = (int)($_POST['cid'] ?? 0);
$row = $cid > 0 ? dbFetchAssoc(dbQuery("SELECT * FROM courier WHERE cid = ?", [$cid])) : null;
if (!$row) { fs_fail('Pengiriman tidak ditemukan.'); }
if (($_SESSION['user_type'] ?? '') === 'Employee' && ($row['officename'] ?? '') !== ($_SESSION['user_name'] ?? '')) {
    fs_fail('Pengiriman ini bukan milik akun Anda.', 403);
}
if (($row['status'] ?? '') === 'Delivered') { fs_fail('Pengiriman ini sudah selesai.'); }

try {
    $user = $_SESSION['user_name'] ?? 'Unknown';
    dbQuery("UPDATE courier SET status = 'Delivered', progress_percentage = 100, last_updated = NOW() WHERE cid = ?", [$cid]);
    dbQuery("INSERT INTO courier_track (cid, cons_no, status, pick_time, comments, user, bk_time) VALUES (?, ?, 'Delivered', ?, ?, ?, NOW())",
        [$cid, $row['cons_no'], $row['pickup_time'] ?? '', 'Diselesaikan dari dashboard', $user]);
    echo json_encode(['success' => true, 'message' => 'Pengiriman ' . $row['cons_no'] . ' ditandai selesai.']);
} catch (Exception $e) {
    error_log('finish-shipment: ' . $e->getMessage());
    fs_fail('Gagal menyimpan. Coba lagi.');
}
