<?php
// API tracking untuk customer. Kunci per customer, hanya baca, dibatasi 60 permintaan per menit.
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/courier-lib.php';

define('API_RATE_PER_MIN', 60);

function api_ensure_tables() {
    global $dbConn;
    static $done = false;
    if ($done) { return; }
    $done = true;
    $sqlite = $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $idcol = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $dbConn->exec("CREATE TABLE IF NOT EXISTS api_keys (
        id $idcol, customer_id INT NOT NULL, label VARCHAR(80) NOT NULL DEFAULT '',
        key_prefix VARCHAR(16) NOT NULL, key_hash CHAR(64) NOT NULL, active INT NOT NULL DEFAULT 1,
        created_at VARCHAR(19) NOT NULL, created_by VARCHAR(100) NOT NULL DEFAULT '', last_used_at VARCHAR(19) DEFAULT NULL)");
    $dbConn->exec("CREATE TABLE IF NOT EXISTS api_hits (key_id INT NOT NULL, bucket INT NOT NULL, cnt INT NOT NULL DEFAULT 0, PRIMARY KEY (key_id, bucket))");
    try { $dbConn->exec("CREATE UNIQUE INDEX idx_api_key_hash ON api_keys (key_hash)"); } catch (Exception $e) { /* sudah ada */ }
}

// Kunci acak 48 karakter heksa. Yang disimpan hanya hash-nya, jadi kunci asli tidak bisa dilihat lagi.
function api_new_key() { return 'ali_' . bin2hex(random_bytes(24)); }
function api_hash($key) { return hash('sha256', (string)$key); }

function api_json($code, $data, $extraHeaders = []) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    foreach ($extraHeaders as $h) { header($h); }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
function api_error($code, $msg, $extraHeaders = []) { api_json($code, ['success' => false, 'error' => $msg], $extraHeaders); }

function api_header_key() {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($h === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) { if (strcasecmp($k, 'Authorization') === 0) { $h = $v; } }
    }
    if (preg_match('/^Bearer\s+(\S+)$/i', trim($h), $m)) { return $m[1]; }
    return trim((string)($_SERVER['HTTP_X_API_KEY'] ?? ''));
}

// Hitung satu permintaan ke dalam jendela 1 menit. Mengembalikan jumlah permintaan di menit ini.
function api_hit($keyId) {
    $bucket = (int)floor(time() / 60);
    $r = dbFetchAssoc(dbQuery("SELECT cnt FROM api_hits WHERE key_id = ? AND bucket = ?", [$keyId, $bucket]));
    if ($r) {
        dbQuery("UPDATE api_hits SET cnt = cnt + 1 WHERE key_id = ? AND bucket = ?", [$keyId, $bucket]);
        $n = (int)$r['cnt'] + 1;
    } else {
        try { dbQuery("INSERT INTO api_hits (key_id, bucket, cnt) VALUES (?, ?, 1)", [$keyId, $bucket]); $n = 1; }
        catch (Exception $e) { dbQuery("UPDATE api_hits SET cnt = cnt + 1 WHERE key_id = ? AND bucket = ?", [$keyId, $bucket]); $n = 2; }
    }
    if (mt_rand(1, 50) === 1) { dbQuery("DELETE FROM api_hits WHERE bucket < ?", [$bucket - 5]); }
    return $n;
}

// Cek kunci. Gagal berarti langsung keluar dengan error JSON.
function api_auth() {
    api_ensure_tables();
    $key = api_header_key();
    $row = null;
    if ($key !== '' && strlen($key) <= 100) {
        $row = dbFetchAssoc(dbQuery("SELECT * FROM api_keys WHERE key_hash = ? AND active = 1", [api_hash($key)]));
    }
    if (!$row) {
        // percobaan kunci salah dibatasi per alamat IP supaya tidak bisa ditebak
        $ipId = -((int)(crc32($_SERVER['REMOTE_ADDR'] ?? '0') % 1000000) + 1);
        if (api_hit($ipId) > 20) { api_error(429, 'Terlalu banyak percobaan dengan kunci salah. Coba lagi sebentar.', ['Retry-After: 60']); }
        api_error(401, 'API key tidak valid. Kirim lewat header "Authorization: Bearer <kunci>".', ['WWW-Authenticate: Bearer']);
    }
    $n = api_hit((int)$row['id']);
    $left = max(0, API_RATE_PER_MIN - $n);
    header('X-RateLimit-Limit: ' . API_RATE_PER_MIN);
    header('X-RateLimit-Remaining: ' . $left);
    if ($n > API_RATE_PER_MIN) { api_error(429, 'Batas ' . API_RATE_PER_MIN . ' permintaan per menit terlampaui.', ['Retry-After: 60']); }
    dbQuery("UPDATE api_keys SET last_used_at = ? WHERE id = ?", [date('Y-m-d H:i:s'), (int)$row['id']]);
    return $row;
}

function api_status_label($s) {
    $map = ['In-Transit' => 'On Delivery', 'On-Hold' => 'On Hold', 'Landed' => 'Arrived', 'Delayed' => 'Delayed', 'Finished' => 'Finished', 'Delivered' => 'Delivered'];
    return $map[$s] ?? (string)$s;
}
function api_date($v) {
    $v = trim((string)$v);
    return ($v === '' || strpos($v, '0000') === 0) ? null : substr($v, 0, 10);
}
function api_num($v) { return round((float)$v, 3); }

// Ubah satu baris courier menjadi data publik. Tidak ada harga, tagihan, alamat, atau telepon.
function api_shipment($c, $items, $history = null) {
    $qty = 0; $kg = 0; $m3 = 0; $list = [];
    foreach ($items as $i) {
        $q = (float)$i['quantity']; $qty += $q; $kg += $q * (float)$i['weight']; $m3 += $q * (float)$i['volume'];
        $list[] = ['name' => (string)$i['item_name'], 'quantity' => api_num($q), 'weight_kg' => api_num($i['weight']), 'volume_m3' => api_num($i['volume'])];
    }
    $out = [
        'tracking' => (string)$c['cons_no'],
        'status' => (string)$c['status'],
        'status_label' => api_status_label($c['status']),
        'origin' => (string)($c['origin_city'] ?? ''),
        'destination' => (string)($c['pickup_time'] ?? ''),
        'last_location' => (string)($c['pick_time'] ?? ''),
        'shipper' => (string)($c['ship_name'] ?? ''),
        'receiver' => (string)($c['rev_name'] ?? ''),
        'service' => (string)($c['mode'] ?? ''),
        'received_date' => api_date($c['collection_date'] ?? ''),
        'etd' => api_date($c['etd'] ?? ''),
        'eta' => api_date($c['eta'] ?? ''),
        'delivered_date' => ($c['status'] === 'Delivered') ? api_date($c['delivered_date'] ?? '') : null,
        'total_quantity' => api_num($qty), 'total_weight_kg' => api_num($kg), 'total_volume_m3' => api_num($m3),
        'last_updated' => (string)($c['last_updated'] ?? ''),
    ];
    if ($history !== null) {
        $out['items'] = $list;
        $out['history'] = $history;
    }
    return $out;
}
