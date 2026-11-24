<?php
// Mengirim lampiran Catatan setelah cek login dan hak akses.
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode', '1'); session_start(); }
require_once('database.php');
require_once('library.php');
require_once('catatan-lib.php');
requireAdmin(true);
kb_ensure_tables();
$me = (string)($_SESSION['user_name'] ?? '');
$r = dbFetchAssoc(dbQuery("SELECT * FROM kb_files WHERE id = ?", [(int)($_GET['id'] ?? 0)]));
// papan pribadi hanya untuk pemiliknya, papan perusahaan untuk semua admin
if (!$r || ($r['scope'] === 'personal' && $r['owner'] !== $me)) { http_response_code(404); exit('Tidak ditemukan.'); }
$path = kb_dir() . '/' . basename((string)$r['stored_name']);
if (!is_file($path)) { http_response_code(404); exit('File sudah dihapus.'); }
$inline = kb_is_image($r['mime']) && isset($_GET['v']);
$name = preg_replace('/[^A-Za-z0-9._ -]/', '_', (string)$r['orig_name']);
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: ' . $r['mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; sandbox");
header('Cache-Control: private, max-age=300');
readfile($path);
