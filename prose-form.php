<?php
// Menerima pesan dari formulir "Hubungi Kami" dan mengirimkannya ke email perusahaan.
// Pengunjung selalu dikembalikan ke halaman Hubungi Kami dengan status hasilnya.
error_reporting(E_ALL);
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
require_once __DIR__ . '/deprixa/database.php';

function pf_back($status) {
    header('Location: contact-us?status=' . $status . '#kirim-pesan');
    exit;
}
function pf_line($s, $max) {                 // satu baris, tanpa karakter kontrol (mencegah header injection)
    $s = preg_replace('/[\r\n\t]+/', ' ', (string)$s);
    $s = preg_replace('/[\x00-\x1F\x7F]/', '', $s);
    return mb_substr(trim($s), 0, $max, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { pf_back('lengkapi'); }

// Kolom jebakan untuk robot: manusia tidak mengisinya
if (!empty($_POST['website'])) { pf_back('ok'); }

// Batasi: satu pesan per 30 detik per pengunjung
$now = time();
if (isset($_SESSION['pf_last']) && $now - (int)$_SESSION['pf_last'] < 30) { pf_back('tunggu'); }

$name    = pf_line(isset($_POST['name']) ? $_POST['name'] : '', 100);
$email   = pf_line(isset($_POST['email']) ? $_POST['email'] : '', 150);
$subject = pf_line(isset($_POST['subject']) ? $_POST['subject'] : '', 150);
$comment = isset($_POST['comment']) ? trim((string)$_POST['comment']) : '';
$comment = mb_substr(preg_replace('/\r\n?/', "\n", $comment), 0, 3000, 'UTF-8');

if ($name === '' || $subject === '' || $comment === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { pf_back('lengkapi'); }

// Alamat tujuan: email perusahaan di database
$to = '';
try {
    $stmt = dbQuery('SELECT cemail FROM company LIMIT 1');
    $row = dbFetchAssoc($stmt);
    if ($row && !empty($row['cemail'])) { $to = trim($row['cemail']); }
} catch (Exception $e) {
    error_log('prose-form: gagal membaca email perusahaan: ' . $e->getMessage());
}
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    error_log('prose-form: email perusahaan belum diisi atau tidak valid');
    pf_back('gagal');
}

$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/^www\./', '', preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['HTTP_HOST'])) : 'anekalintasindcargo.com';
$body  = "Pesan baru dari formulir Hubungi Kami\n\n";
$body .= "Nama   : $name\n";
$body .= "Email  : $email\n";
$body .= "Subjek : $subject\n\n";
$body .= "Pesan:\n$comment\n";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: Website ALI Cargo <no-reply@$host>\r\n";
$headers .= "Reply-To: $email\r\n";

$ok = @mail($to, '=?UTF-8?B?' . base64_encode('Pesan website: ' . $subject) . '?=', $body, $headers);
if ($ok) { $_SESSION['pf_last'] = $now; pf_back('ok'); }
error_log('prose-form: mail() gagal mengirim pesan');
pf_back('gagal');
