<?php
// Pengirim email lewat SMTP (tanpa pustaka tambahan). Pengaturan ada di config.local.php (hanya di server):
//   'mail' => array(
//       'host' => 'smtp.hostinger.com', 'port' => 465, 'secure' => 'ssl',   // 'ssl' (465) atau 'tls' (587)
//       'user' => 'noreply@domainmu.com', 'pass' => '...',
//       'from' => 'noreply@domainmu.com', 'from_name' => 'ALI Cargo',
//       'reply_to' => 'finance@domainmu.com',   // balasan customer masuk ke sini
//       'cc' => 'finance@domainmu.com',          // salinan setiap pengingat (boleh beberapa, pisah koma)
//   ),

function ali_mail_config() {
    static $cfg = null;
    if ($cfg !== null) { return $cfg; }
    $all = @include __DIR__ . '/config.local.php';
    $m = (is_array($all) && isset($all['mail']) && is_array($all['mail'])) ? $all['mail'] : array();
    $cfg = array_merge(array('host' => '', 'port' => 465, 'secure' => 'ssl', 'user' => '', 'pass' => '', 'from' => '', 'from_name' => 'ALI Cargo', 'reply_to' => '', 'cc' => ''), $m);
    return $cfg;
}

function ali_mail_ready() {
    $c = ali_mail_config();
    return $c['host'] !== '' && $c['user'] !== '' && $c['pass'] !== '' && $c['from'] !== '';
}

// Pecah "a@x.com, b@y.com; c@z.com" jadi daftar alamat yang valid.
function ali_mail_split($s) {
    $out = array();
    foreach (preg_split('/[,;\s]+/', (string)$s) as $a) {
        $a = trim($a);
        if ($a !== '' && filter_var($a, FILTER_VALIDATE_EMAIL) && !in_array(strtolower($a), array_map('strtolower', $out), true)) { $out[] = $a; }
    }
    return $out;
}

function ali_mail_hdr($s) { return preg_replace('/[\r\n]+/', ' ', (string)$s); }
function ali_mail_enc($s) { return '=?UTF-8?B?' . base64_encode((string)$s) . '?='; }

function ali_smtp_read($fp) {
    $resp = '';
    while (($line = fgets($fp, 1024)) !== false) {
        $resp .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') { break; }
    }
    return $resp;
}

function ali_smtp_cmd($fp, $cmd, $okPrefixes) {
    if ($cmd !== null) { fwrite($fp, $cmd . "\r\n"); }
    $r = ali_smtp_read($fp);
    foreach ((array)$okPrefixes as $p) { if (strncmp($r, (string)$p, strlen((string)$p)) === 0) { return $r; } }
    $r = trim(substr($r, 0, 200));
    throw new Exception('SMTP: ' . ($r !== '' ? $r : 'tidak ada balasan dari server (cek host, port, user, dan password email)'));
}

// Kirim satu email (HTML + teks). $to dan $cc berupa daftar alamat. Melempar Exception kalau gagal.
function ali_mail_send(array $to, array $cc, $subject, $html, $text) {
    $c = ali_mail_config();
    if (!ali_mail_ready()) { throw new Exception('Pengaturan email belum diisi di config.local.php.'); }
    $to = array_values(array_filter($to)); $cc = array_values(array_filter($cc));
    if (!$to) { throw new Exception('Tidak ada alamat tujuan.'); }
    $secure = strtolower((string)$c['secure']);
    $host = $c['host']; $port = (int)$c['port'];
    $errno = 0; $errstr = '';
    $ctx = stream_context_create(array('ssl' => array('verify_peer' => true, 'verify_peer_name' => true)));
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { throw new Exception("SMTP tidak bisa tersambung ($errstr)"); }
    stream_set_timeout($fp, 30);
    try {
        ali_smtp_cmd($fp, null, '220');
        $ehlo = 'EHLO ' . (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : 'localhost');
        ali_smtp_cmd($fp, $ehlo, '250');
        if ($secure === 'tls') {
            ali_smtp_cmd($fp, 'STARTTLS', '220');
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { throw new Exception('SMTP: STARTTLS gagal'); }
            ali_smtp_cmd($fp, $ehlo, '250');
        }
        if ($c['user'] !== '') {
            ali_smtp_cmd($fp, 'AUTH LOGIN', '334');
            ali_smtp_cmd($fp, base64_encode($c['user']), '334');
            ali_smtp_cmd($fp, base64_encode($c['pass']), '235');
        }
        ali_smtp_cmd($fp, 'MAIL FROM:<' . ali_mail_hdr($c['from']) . '>', '250');
        foreach (array_merge($to, $cc) as $rcpt) { ali_smtp_cmd($fp, 'RCPT TO:<' . ali_mail_hdr($rcpt) . '>', array('250', '251')); }
        ali_smtp_cmd($fp, 'DATA', '354');

        $b = 'b' . md5(uniqid('', true));
        $dom = substr(strrchr($c['from'], '@'), 1) ?: 'localhost';
        $h = array();
        $h[] = 'Date: ' . date('r');
        $h[] = 'From: ' . ali_mail_enc($c['from_name']) . ' <' . ali_mail_hdr($c['from']) . '>';
        $h[] = 'To: ' . implode(', ', array_map('ali_mail_hdr', $to));
        if ($cc) { $h[] = 'Cc: ' . implode(', ', array_map('ali_mail_hdr', $cc)); }
        if ($c['reply_to'] !== '') { $h[] = 'Reply-To: ' . ali_mail_hdr($c['reply_to']); }
        $h[] = 'Subject: ' . ali_mail_enc($subject);
        $h[] = 'Message-ID: <' . md5(uniqid('', true)) . '@' . $dom . '>';
        $h[] = 'Auto-Submitted: auto-generated';
        $h[] = 'MIME-Version: 1.0';
        $h[] = 'Content-Type: multipart/alternative; boundary="' . $b . '"';
        $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text)) .
                "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) .
                "--$b--\r\n";
        $data = implode("\r\n", $h) . "\r\n\r\n" . $body;
        $data = preg_replace('/^\./m', '..', str_replace(array("\r\n", "\r"), "\n", $data));
        $data = str_replace("\n", "\r\n", $data);
        fwrite($fp, $data . "\r\n.\r\n");
        ali_smtp_cmd($fp, null, '250');
        @fwrite($fp, "QUIT\r\n");
    } catch (Exception $e) {
        @fwrite($fp, "QUIT\r\n"); @fclose($fp);
        throw $e;
    }
    @fclose($fp);
    return true;
}
