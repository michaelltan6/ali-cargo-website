<?php
// Dijalankan setiap hari oleh Cron Job di hPanel Hostinger (jenis PHP), misalnya jam 08.00:
//   /usr/bin/php /home/<akun>/domains/<domain>/public_html/deprixa/cron-reminder.php
// Tambahkan argumen "dry" untuk mencoba tanpa mengirim email.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/reminder-lib.php';
$dry = in_array('dry', $argv ?? array(), true);
$r = rem_run($dry);
echo date('Y-m-d H:i:s') . ($dry ? ' [dry]' : '') . ' terkirim=' . $r['sent'] . ' gagal=' . $r['failed'] . ' dilewati=' . $r['skipped'] . "\n";
foreach ($r['lines'] as $l) { echo $l . "\n"; }
exit($r['failed'] > 0 ? 1 : 0);
