<?php
// Kolom tambahan di tabel courier (dibuat otomatis, boleh kosong):
//   customer_id   : tautan ke Customer Profile
//   payment_term  : cash / 7 / 14 / 30 (hari tempo)
//   tempo_start   : tanggal admin mengaktifkan tempo (hitungan jatuh tempo mulai dari sini)
//   spb_no        : nomor SPB / surat jalan (urut, mengikuti nomor cetakan di surat jalan)
//   spb_return    : tanggal surat jalan (SPB) kembali dari customer
//   partner       : ekspedisi / mitra yang dipakai (Via / Partner)
//   charge_unit   : hitungan ukuran, 'kg' atau 'm3'
//   book_code     : pembukuan, 'ALI' atau 'ANK' (awalan nomor tracking)
//   paid_date     : tanggal pembayaran diterima (tempo ditandai lunas)
require_once __DIR__ . '/database.php';

function courier_columns_ready() {
    global $dbConn;
    static $ready = null;
    if ($ready !== null) { return $ready; }
    try {
        $want = ['customer_id' => ['INT UNSIGNED NULL', 'INTEGER NULL'],
                 'payment_term' => ['VARCHAR(10) NULL', 'TEXT NULL'],
                 'tempo_start' => ['DATE NULL', 'TEXT NULL'],
                 'spb_no' => ['INT UNSIGNED NULL', 'INTEGER NULL'],
                 'spb_return' => ['DATE NULL', 'TEXT NULL'],
                 'partner' => ['VARCHAR(100) NULL', 'TEXT NULL'],
                 'charge_unit' => ['VARCHAR(5) NULL', 'TEXT NULL'],
                 'book_code' => ['VARCHAR(5) NULL', 'TEXT NULL'],
                 'paid_date' => ['DATE NULL', 'TEXT NULL']];
        $sqlite = $dbConn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $have = [];
        if ($sqlite) { foreach ($dbConn->query("PRAGMA table_info(courier)") as $c) { $have[$c['name']] = true; } }
        else { foreach ($dbConn->query("SHOW COLUMNS FROM courier") as $c) { $have[$c['Field']] = true; } }
        foreach ($want as $col => $types) {
            if (!empty($have[$col])) { continue; }
            $dbConn->exec("ALTER TABLE courier ADD COLUMN $col " . ($sqlite ? $types[1] : $types[0]));
        }
        $ready = true;
    } catch (Exception $e) {
        error_log('courier_columns_ready: ' . $e->getMessage());
        $ready = false;
    }
    return $ready;
}

function courier_term_label($term) {
    $term = (string)$term;
    if ($term === '7' || $term === '14' || $term === '30') { return 'Tempo ' . $term . ' hari'; }
    return 'Cash';
}

// Nomor SPB berikutnya = nomor tertinggi yang sudah dipakai + 1.
// Kalau belum ada satu pun, mulai dari 7507 (lanjutan dari file laporan September).
function courier_next_spb() {
    if (!courier_columns_ready()) { return 7507; }
    $r = dbFetchAssoc(dbQuery("SELECT MAX(spb_no) AS m FROM courier"));
    $m = (int)($r['m'] ?? 0);
    return $m > 0 ? $m + 1 : 7507;
}

// Nomor SPB sudah dipakai pengiriman lain? ($exceptCid = pengiriman yang sedang diedit)
function courier_spb_taken($spb, $exceptCid = 0) {
    $r = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM courier WHERE spb_no = ? AND cid <> ?", [(int)$spb, (int)$exceptCid]));
    return (int)($r['c'] ?? 0) > 0;
}

// Wilayah untuk pengelompokan laporan, ditentukan dari kota tujuan. Selain Sumatera dan Timur dianggap Jawa.
function courier_region($dest) {
    $d = strtolower(trim((string)$dest));
    $sumatera = ['aceh','medan','sumatera','sumut','sumbar','sumsel','binjai','stabat','pematang','siantar','tebing','padang','bukittinggi','pekanbaru','riau','dumai','batam','tanjung pinang','tanjungpinang','kepri','jambi','bungo','palembang','lubuk','bengkulu','lampung','bangka','belitung','pangkal','kerinci','sibolga','rantau','kisaran','lhokseumawe','tanjung balai','tanjungbalai','metro'];
    $timur = ['kalimantan','banjarmasin','banjarbaru','pontianak','balikpapan','samarinda','palangkaraya','palangka','tarakan','sampit','singkawang','bontang','bali','denpasar','nusa','lombok','mataram','ntb','ntt','kupang','sulawesi','makassar','manado','kendari','palu','gorontalo','ambon','maluku','ternate','papua','sorong','jayapura','merauke','timika','mamuju','bau-bau','flores','labuan bajo','sumbawa','bima','biak','manokwari','halmahera','kolaka','morowali','luwuk','palopo','parepare','toraja'];
    foreach ($sumatera as $k) { if ($d !== '' && strpos($d, $k) !== false) { return 'SUMATERA'; } }
    foreach ($timur as $k) { if ($d !== '' && strpos($d, $k) !== false) { return 'TIMUR'; } }
    return 'JAWA';
}

// Nomor tracking = awalan pembukuan + nomor SPB, contoh "ALI 7507" atau "ANK 7508".
function courier_book_code($v) { return strtoupper(trim((string)$v)) === 'ANK' ? 'ANK' : 'ALI'; }
function courier_tracking_no($book, $spb) { return courier_book_code($book) . ' ' . (int)$spb; }
