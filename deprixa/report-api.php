<?php
// Aksi singkat dari halaman Laporan: SPB kembali, tandai lunas, batalkan lunas.
ob_start();
if (session_status() === PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); } }
require_once('database.php');
require_once('library.php');
require_once('courier-lib.php');
requireAdmin(true);
header('Content-Type: application/json; charset=utf-8');
ob_clean();

function api_fail($m, $code = 200) { http_response_code($code); echo json_encode(['success' => false, 'message' => $m]); exit; }
function api_date($v, $allowEmpty = false) {
    $v = trim((string)$v);
    if ($v === '' && $allowEmpty) { return null; }
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) { api_fail('Tanggal tidak valid.'); }
    return $v;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { api_fail('Metode tidak diizinkan.', 405); }
if (!sameOriginOk()) { api_fail('Permintaan ditolak.', 403); }
if (($_SESSION['user_type'] ?? '') !== 'Administrator') { api_fail('Hanya admin yang boleh mengubah data ini.', 403); }
if (!courier_columns_ready()) { api_fail('Kolom data belum siap. Muat ulang halaman.'); }

$cid = (int)($_POST['cid'] ?? 0);
$row = $cid > 0 ? dbFetchAssoc(dbQuery("SELECT * FROM courier WHERE cid = ?", [$cid])) : null;
if (!$row) { api_fail('Pengiriman tidak ditemukan.'); }

try {
    switch ($_POST['action'] ?? '') {
        case 'set_spb_return':
            $d = api_date($_POST['date'] ?? '', true);
            dbQuery("UPDATE courier SET spb_return = ? WHERE cid = ?", [$d, $cid]);
            echo json_encode(['success' => true, 'message' => $d ? 'SPB kembali dicatat.' : 'Tanggal SPB kembali dikosongkan.']);
            break;
        case 'set_payment_term':
            $t = (string)($_POST['payment_term'] ?? '');
            if (!in_array($t, ['cash', '7', '14', '30'], true)) { api_fail('Metode pembayaran tidak valid.'); }
            if ($t === 'cash') {
                // cash = sudah dibayar; tanggal lunas diisi hari ini kalau belum ada
                dbQuery("UPDATE courier SET payment_term = 'cash', book_mode = 'Paid', paid_date = COALESCE(paid_date, ?), tempo_start = NULL, due_date = NULL WHERE cid = ?", [date('Y-m-d'), $cid]);
            } else {
                $start = trim((string)($row['tempo_start'] ?? ''));
                $due = null;
                if ($start !== '' && strpos($start, '0000') !== 0) {
                    $dt = DateTime::createFromFormat('Y-m-d', substr($start, 0, 10));
                    if ($dt) { $due = $dt->modify('+' . $t . ' days')->format('Y-m-d'); }
                }
                dbQuery("UPDATE courier SET payment_term = ?, book_mode = 'ToPay', paid_date = NULL, due_date = ? WHERE cid = ?", [$t, $due, $cid]);
            }
            echo json_encode(['success' => true, 'message' => 'Metode pembayaran diubah.']);
            break;
        case 'mark_paid':
            $d = api_date($_POST['date'] ?? '');
            dbQuery("UPDATE courier SET book_mode = 'Paid', paid_date = ? WHERE cid = ?", [$d, $cid]);
            echo json_encode(['success' => true, 'message' => 'Ditandai lunas.']);
            break;
        case 'unmark_paid':
            if (!in_array((string)($row['payment_term'] ?? ''), ['7', '14', '30'], true)) { api_fail('Pengiriman Cash tidak bisa dikembalikan ke belum lunas.'); }
            dbQuery("UPDATE courier SET book_mode = 'ToPay', paid_date = NULL WHERE cid = ?", [$cid]);
            echo json_encode(['success' => true, 'message' => 'Status lunas dibatalkan.']);
            break;
        default:
            api_fail('Aksi tidak dikenal.');
    }
} catch (Exception $e) {
    error_log('report-api: ' . $e->getMessage());
    api_fail('Gagal menyimpan. Coba lagi.');
}
