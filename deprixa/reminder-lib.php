<?php
// Pengingat jatuh tempo pembayaran lewat email.
// Satu invoice bisa untuk beberapa SPB. Aturan kirim:
//   - hanya pengiriman tempo yang belum lunas dan sudah punya nomor invoice
//   - jatuh tempo invoice = jatuh tempo paling akhir di antara SPB yang belum lunas
//   - kirim pertama pada hari jatuh tempo (atau begitu skrip jalan setelahnya), lalu diulang tiap 3 hari
//   - berhenti kalau semua SPB invoice sudah lunas, atau setelah 5 kali pengingat
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/courier-lib.php';
require_once __DIR__ . '/customer-lib.php';
require_once __DIR__ . '/mail-lib.php';

if (!defined('ALI_REM_REPEAT_DAYS')) { define('ALI_REM_REPEAT_DAYS', 3); }
if (!defined('ALI_REM_MAX')) { define('ALI_REM_MAX', 5); }

function rem_today() { return (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d'); }

function rem_ensure_table() {
    global $dbConn;
    static $done = false;
    if ($done) { return; }
    $done = true;
    $sqlite = $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $idcol = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $dbConn->exec("CREATE TABLE IF NOT EXISTS invoice_reminders (
        id $idcol, invoice_no VARCHAR(60) NOT NULL, sent_on VARCHAR(10) NOT NULL, sent_at VARCHAR(19) NOT NULL,
        to_email VARCHAR(500) NOT NULL DEFAULT '', status VARCHAR(10) NOT NULL, note VARCHAR(300) NOT NULL DEFAULT '', manual INT NOT NULL DEFAULT 0)");
}

function rem_clean_invoice($v) {
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$v);
    $v = trim(preg_replace('/\s+/u', ' ', $v));
    return function_exists('mb_substr') ? mb_substr($v, 0, 60) : substr($v, 0, 60);
}

// Kumpulkan semua invoice yang punya SPB tempo belum lunas, lengkap dengan status pengingatnya.
// Hasil: array invoice_no => array(members, due, overdue, customer, emails, sent, last, state, label)
function rem_collect() {
    courier_columns_ready(); rem_ensure_table(); cp_ensure_table();
    $today = rem_today();
    $inv = array();
    $stmt = dbQuery("SELECT * FROM courier WHERE book_mode = 'ToPay' AND invoice_no IS NOT NULL AND invoice_no <> '' ORDER BY collection_date, cid");
    while ($r = dbFetchAssoc($stmt)) {
        $k = $r['invoice_no'];
        if (!isset($inv[$k])) { $inv[$k] = array('invoice_no' => $k, 'members' => array(), 'customer_id' => 0, 'customer' => '', 'emails' => array(), 'due' => '', 'missing_due' => false); }
        $inv[$k]['members'][] = $r;
        if (!$inv[$k]['customer_id'] && !empty($r['customer_id'])) { $inv[$k]['customer_id'] = (int)$r['customer_id']; }
        if ($inv[$k]['customer'] === '') { $inv[$k]['customer'] = (string)$r['ship_name']; }
        $due = substr(trim((string)($r['due_date'] ?? '')), 0, 10);
        if ($due === '' || strpos($due, '0000') === 0) { $inv[$k]['missing_due'] = true; }
        elseif ($due > $inv[$k]['due']) { $inv[$k]['due'] = $due; }
    }
    foreach ($inv as $k => &$v) {
        if ($v['customer_id']) {
            $c = cp_get($v['customer_id']);
            if ($c) { $v['emails'] = ali_mail_split($c['email'] ?? ''); if (!empty($c['company_name'])) { $v['customer'] = $c['company_name']; } }
        }
        $st = dbQuery("SELECT COUNT(*) AS n, MAX(sent_on) AS last FROM invoice_reminders WHERE invoice_no = ? AND status = 'sent'", array($k));
        $a = dbFetchAssoc($st);
        $v['sent'] = (int)($a['n'] ?? 0); $v['last'] = (string)($a['last'] ?? '');
        $v['overdue'] = ($v['due'] !== '' && !$v['missing_due']) ? (int)floor((strtotime($today) - strtotime($v['due'])) / 86400) : null;
        // state: wait | no_due | no_email | due_now | repeat_wait | done_max
        if ($v['missing_due'] || $v['due'] === '') { $v['state'] = 'no_due'; $v['label'] = 'Tempo belum aktif'; }
        elseif ($v['overdue'] < 0) { $v['state'] = 'wait'; $v['label'] = 'Belum jatuh tempo'; }
        elseif (!$v['emails']) { $v['state'] = 'no_email'; $v['label'] = 'Email customer belum diisi'; }
        elseif ($v['sent'] >= ALI_REM_MAX) { $v['state'] = 'done_max'; $v['label'] = 'Sudah ' . $v['sent'] . 'x dikirim, tindak lanjut manual'; }
        elseif ($v['sent'] === 0) { $v['state'] = 'due_now'; $v['label'] = 'Akan dikirim berikutnya'; }
        else {
            $since = (int)floor((strtotime($today) - strtotime($v['last'])) / 86400);
            if ($since >= ALI_REM_REPEAT_DAYS) { $v['state'] = 'due_now'; $v['label'] = 'Akan dikirim berikutnya'; }
            else { $v['state'] = 'repeat_wait'; $v['label'] = 'Terkirim ke-' . $v['sent'] . ' (' . date('d-m-Y', strtotime($v['last'])) . ')'; }
        }
        if ($v['sent'] > 0 && $v['state'] === 'due_now') { $v['label'] = 'Terkirim ke-' . $v['sent'] . ', berikutnya dikirim'; }
    }
    unset($v);
    return $inv;
}

function rem_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function rem_compose($v) {
    $due = date('d-m-Y', strtotime($v['due']));
    $late = (int)$v['overdue'];
    $subject = 'Pengingat jatuh tempo: Invoice ' . $v['invoice_no'] . ' - PT. Aneka Lintas Indonesia Cargo';
    $lead = $late <= 0 ? "Invoice nomor {$v['invoice_no']} jatuh tempo hari ini ($due)." : "Invoice nomor {$v['invoice_no']} sudah jatuh tempo sejak $due (terlambat $late hari).";
    $rows = ''; $txt = '';
    foreach ($v['members'] as $m) {
        $d = substr(trim((string)($m['due_date'] ?? '')), 0, 10);
        $dd = $d !== '' ? date('d-m-Y', strtotime($d)) : '-';
        $rows .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #e6eaec">' . rem_e($m['cons_no']) . '</td><td style="padding:6px 10px;border-bottom:1px solid #e6eaec">' . rem_e($m['pickup_time']) . '</td><td style="padding:6px 10px;border-bottom:1px solid #e6eaec">' . $dd . '</td></tr>';
        $txt .= ' - ' . $m['cons_no'] . ' (tujuan ' . $m['pickup_time'] . ', jatuh tempo ' . $dd . ")\n";
    }
    $c = ali_mail_config();
    $contact = $c['reply_to'] !== '' ? ' hubungi ' . $c['reply_to'] : ' hubungi tim finance kami';
    $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#263238;max-width:600px">'
        . '<p>Yth. ' . rem_e($v['customer']) . ',</p>'
        . '<p><b>' . rem_e($lead) . '</b> Mohon untuk dicek kembali.</p>'
        . '<table style="border-collapse:collapse;width:100%;font-size:13px"><thead><tr style="background:#f3f5f6;text-align:left"><th style="padding:6px 10px">Pengiriman</th><th style="padding:6px 10px">Tujuan</th><th style="padding:6px 10px">Jatuh tempo</th></tr></thead><tbody>' . $rows . '</tbody></table>'
        . '<p>Jika pembayaran sudah dilakukan, abaikan email ini dan mohon maaf atas gangguannya. Untuk pertanyaan,' . rem_e($contact) . '.</p>'
        . '<p style="color:#78909c;font-size:12px;margin-top:22px">Email ini dikirim otomatis oleh sistem PT. Aneka Lintas Indonesia Cargo.</p></div>';
    $text = "Yth. {$v['customer']},\n\n$lead Mohon untuk dicek kembali.\n\nPengiriman:\n$txt\nJika pembayaran sudah dilakukan, abaikan email ini.\nUntuk pertanyaan,$contact.\n\nEmail ini dikirim otomatis oleh sistem PT. Aneka Lintas Indonesia Cargo.\n";
    return array($subject, $html, $text);
}

function rem_log($invoice, $to, $status, $note, $manual) {
    rem_ensure_table();
    dbQuery("INSERT INTO invoice_reminders (invoice_no, sent_on, sent_at, to_email, status, note, manual) VALUES (?,?,?,?,?,?,?)",
        array($invoice, rem_today(), (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s'), mb_substr($to, 0, 500), $status, mb_substr($note, 0, 300), $manual ? 1 : 0));
}

// Kirim pengingat untuk satu invoice (sudah dihitung rem_collect). Hasil: array(ok, pesan)
function rem_send($v, $manual = false) {
    if (!$v['emails']) { return array(false, 'Email customer belum diisi.'); }
    if ($v['due'] === '' || $v['missing_due']) { return array(false, 'Tempo belum aktif untuk semua SPB di invoice ini.'); }
    $cc = ali_mail_split(ali_mail_config()['cc']);
    $cc = array_values(array_udiff($cc, $v['emails'], 'strcasecmp'));
    list($subject, $html, $text) = rem_compose($v);
    try {
        ali_mail_send($v['emails'], $cc, $subject, $html, $text);
        rem_log($v['invoice_no'], implode(', ', $v['emails']), 'sent', $cc ? 'cc: ' . implode(', ', $cc) : '', $manual);
        return array(true, 'Terkirim ke ' . implode(', ', $v['emails']));
    } catch (Exception $e) {
        rem_log($v['invoice_no'], implode(', ', $v['emails']), 'failed', $e->getMessage(), $manual);
        return array(false, 'Gagal kirim: ' . $e->getMessage());
    }
}

// Dipanggil cron tiap hari. $dry = true hanya menghitung, tidak mengirim.
function rem_run($dry = false) {
    $res = array('sent' => 0, 'failed' => 0, 'skipped' => 0, 'lines' => array());
    if (!$dry && !ali_mail_ready()) { $res['lines'][] = 'Pengaturan email belum diisi.'; $res['failed']++; return $res; }
    foreach (rem_collect() as $v) {
        if ($v['state'] !== 'due_now') { $res['skipped']++; continue; }
        if ($dry) { $res['lines'][] = 'AKAN KIRIM ' . $v['invoice_no'] . ' -> ' . implode(', ', $v['emails']); $res['sent']++; continue; }
        list($ok, $msg) = rem_send($v, false);
        $res['lines'][] = ($ok ? 'OK ' : 'GAGAL ') . $v['invoice_no'] . ': ' . $msg;
        $ok ? $res['sent']++ : $res['failed']++;
    }
    return $res;
}

// Pasang nomor invoice ke beberapa SPB. Kosongkan nomor untuk menghapus.
function rem_set_invoice(array $cids, $invoice) {
    courier_columns_ready();
    $invoice = rem_clean_invoice($invoice);
    $n = 0;
    foreach ($cids as $cid) {
        $cid = (int)$cid; if ($cid <= 0) { continue; }
        dbQuery("UPDATE courier SET invoice_no = ? WHERE cid = ?", array($invoice === '' ? null : $invoice, $cid));
        $n++;
    }
    return $n;
}
