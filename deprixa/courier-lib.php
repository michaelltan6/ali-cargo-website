<?php
// Kolom tambahan di tabel courier (dibuat otomatis, boleh kosong):
//   customer_id   : tautan ke Customer Profile
//   payment_term  : cash / 7 / 14 / 30 (hari tempo)
//   tempo_start   : tanggal admin mengaktifkan tempo (hitungan jatuh tempo mulai dari sini)
require_once __DIR__ . '/database.php';

function courier_columns_ready() {
    global $dbConn;
    static $ready = null;
    if ($ready !== null) { return $ready; }
    try {
        $want = ['customer_id' => ['INT UNSIGNED NULL', 'INTEGER NULL'],
                 'payment_term' => ['VARCHAR(10) NULL', 'TEXT NULL'],
                 'tempo_start' => ['DATE NULL', 'TEXT NULL']];
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
