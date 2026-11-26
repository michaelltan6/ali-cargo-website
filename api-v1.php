<?php
// API tracking v1 (hanya baca). Alamat: /api/v1/shipments dan /api/v1/shipments/{nomor-resi}
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
require_once __DIR__ . '/deprixa/api-lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { api_error(405, 'Hanya metode GET yang didukung.', ['Allow: GET']); }
try {
    courier_columns_ready();
    $key = api_auth();
    $cust = (int)$key['customer_id'];
    $path = trim((string)($_GET['path'] ?? ''), '/');
    $parts = $path === '' ? [] : explode('/', $path, 2);

    if (($parts[0] ?? '') !== 'shipments') { api_error(404, 'Alamat tidak dikenal. Gunakan /api/v1/shipments.'); }

    if (count($parts) === 2) {
        // detail satu resi: "ALI-7507", "ALI 7507", atau "ali7507" sama saja
        $norm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', urldecode($parts[1])));
        $c = $norm === '' ? null : dbFetchAssoc(dbQuery("SELECT * FROM courier WHERE customer_id = ? AND REPLACE(REPLACE(UPPER(cons_no), ' ', ''), '-', '') = ?", [$cust, $norm]));
        if (!$c) { api_error(404, 'Resi tidak ditemukan.'); }
        $items = []; $st = dbQuery("SELECT item_name, quantity, weight, volume FROM courier_items WHERE courier_id = ?", [(int)$c['cid']]);
        while ($r = dbFetchAssoc($st)) { $items[] = $r; }
        $hist = []; $st = dbQuery("SELECT status, pick_time, comments, bk_time FROM courier_track WHERE cid = ? ORDER BY bk_time DESC, id DESC", [(int)$c['cid']]);
        while ($r = dbFetchAssoc($st)) {
            $hist[] = ['time' => (string)$r['bk_time'], 'status' => (string)$r['status'], 'status_label' => api_status_label($r['status']), 'location' => (string)$r['pick_time'], 'note' => (string)$r['comments']];
        }
        api_json(200, ['success' => true, 'data' => api_shipment($c, $items, $hist)]);
    }

    // daftar semua resi milik customer ini
    $where = "customer_id = ?"; $params = [$cust];
    $status = trim((string)($_GET['status'] ?? ''));
    if ($status === 'active') { $where .= " AND status <> 'Delivered'"; }
    elseif ($status === 'delivered') { $where .= " AND status = 'Delivered'"; }
    elseif ($status !== '') { api_error(400, 'Parameter status hanya boleh "active" atau "delivered".'); }
    foreach (['from' => '>=', 'to' => '<='] as $p => $op) {
        $v = trim((string)($_GET[$p] ?? ''));
        if ($v !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $v);
            if (!$d || $d->format('Y-m-d') !== $v) { api_error(400, "Parameter $p harus berformat YYYY-MM-DD."); }
            $where .= " AND collection_date $op ?"; $params[] = $v;
        }
    }
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $total = (int)(dbFetchAssoc(dbQuery("SELECT COUNT(*) AS n FROM courier WHERE $where", $params))['n'] ?? 0);
    $rows = []; $ids = [];
    $st = dbQuery("SELECT * FROM courier WHERE $where ORDER BY cid DESC LIMIT " . $limit . " OFFSET " . (($page - 1) * $limit), $params);
    while ($r = dbFetchAssoc($st)) { $rows[] = $r; $ids[] = (int)$r['cid']; }
    $itemsBy = [];
    if ($ids) {
        $st = dbQuery("SELECT courier_id, item_name, quantity, weight, volume FROM courier_items WHERE courier_id IN (" . implode(',', $ids) . ")");
        while ($r = dbFetchAssoc($st)) { $itemsBy[(int)$r['courier_id']][] = $r; }
    }
    $data = [];
    foreach ($rows as $r) { $data[] = api_shipment($r, $itemsBy[(int)$r['cid']] ?? []); }
    api_json(200, ['success' => true, 'data' => $data, 'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)]]);
} catch (Exception $e) {
    error_log('api-v1: ' . $e->getMessage());
    api_error(500, 'Terjadi kesalahan di server.');
}
