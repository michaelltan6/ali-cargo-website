<?php
require_once __DIR__ . '/../deprixa/portal-lib.php';
$u = pt_require();
courier_columns_ready();
$cust = (int)$u['customer_id'];

// ---- filter ----
$q = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 60));
$st = (string)($_GET['st'] ?? 'all');      if (!in_array($st, ['all', 'active', 'delivered'], true)) { $st = 'all'; }
$pay = (string)($_GET['pay'] ?? 'all');    if (!in_array($pay, ['all', 'unpaid', 'late', 'paid'], true)) { $pay = 'all'; }
$from = (string)($_GET['from'] ?? '');     if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = ''; }
$to = (string)($_GET['to'] ?? '');         if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = ''; }

$where = 'customer_id = ?'; $params = [$cust];
if ($st === 'active') { $where .= " AND status <> 'Delivered'"; }
if ($st === 'delivered') { $where .= " AND status = 'Delivered'"; }
if ($from !== '') { $where .= ' AND collection_date >= ?'; $params[] = $from; }
if ($to !== '') { $where .= ' AND collection_date <= ?'; $params[] = $to . ' 23:59:59'; }
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where .= " AND (cons_no LIKE ? OR rev_name LIKE ? OR pickup_time LIKE ? OR invoice_no LIKE ?)";
    array_push($params, $like, $like, $like, $like);
}

$rows = [];
$res = dbQuery("SELECT * FROM courier WHERE $where ORDER BY collection_date DESC, cid DESC", $params);
while ($r = dbFetchAssoc($res)) {
    $r['_pay'] = pt_pay_state($r);
    if ($pay === 'paid' && $r['_pay'][2] !== 'paid') { continue; }
    if ($pay === 'unpaid' && !in_array($r['_pay'][2], ['unpaid', 'late'], true)) { continue; }
    if ($pay === 'late' && $r['_pay'][2] !== 'late') { continue; }
    $rows[] = $r;
}

// ---- unduh CSV ----
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pengiriman-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['No. resi', 'Tgl diterima', 'Asal', 'Tujuan', 'Penerima', 'Layanan', 'Status', 'ETA', 'Tgl terkirim', 'Invoice', 'Pembayaran', 'Status pembayaran'], ';');
    foreach ($rows as $r) {
        $cell = function ($v) { $v = (string)$v; return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; };
        fputcsv($out, array_map($cell, [$r['cons_no'], substr((string)$r['collection_date'], 0, 10), $r['origin_city'] ?? '', $r['pickup_time'] ?? '', $r['rev_name'] ?? '',
            $r['mode'] ?? '', pt_status_label($r['status']), substr((string)($r['eta'] ?? ''), 0, 10), $r['status'] === 'Delivered' ? substr((string)($r['delivered_date'] ?? ''), 0, 10) : '',
            $r['invoice_no'] ?? '', pt_term_label($r), $r['_pay'][0]]), ';');
    }
    exit;
}

// ---- ringkasan (semua pengiriman customer, tanpa filter) ----
$sum = ['active' => 0, 'delivered' => 0, 'unpaid' => 0, 'late' => 0];
$res = dbQuery("SELECT status, book_mode, payment_term, due_date, paid_date FROM courier WHERE customer_id = ?", [$cust]);
while ($r = dbFetchAssoc($res)) {
    $sum[$r['status'] === 'Delivered' ? 'delivered' : 'active']++;
    $k = pt_pay_state($r)[2];
    if ($k === 'unpaid' || $k === 'late') { $sum['unpaid']++; }
    if ($k === 'late') { $sum['late']++; }
}

$per = 50;
$total = count($rows);
$pages = max(1, (int)ceil($total / $per));
$page = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$show = array_slice($rows, ($page - 1) * $per, $per);
$qs = function ($over) use ($q, $st, $pay, $from, $to) {
    return '?' . http_build_query(array_filter(array_merge(['q' => $q, 'st' => $st, 'pay' => $pay, 'from' => $from, 'to' => $to], $over), function ($v) { return $v !== '' && $v !== 'all' && $v !== 1; }));
};

pt_head('Pengiriman', $u);
?>
<div class="pt-hello">
    <h1>Halo, <?php echo pt_e($u['full_name'] !== '' ? $u['full_name'] : $u['username']); ?></h1>
    <p class="pt-muted">Semua pengiriman <?php echo pt_e($u['company_name']); ?> bersama ALI Cargo.</p>
</div>

<div class="pt-tiles">
    <a class="pt-tile<?php echo $st === 'active' ? ' on' : ''; ?>" href="<?php echo pt_e($qs(['st' => 'active', 'pay' => 'all', 'page' => 1])); ?>"><span>Dalam proses</span><b><?php echo $sum['active']; ?></b></a>
    <a class="pt-tile<?php echo $st === 'delivered' ? ' on' : ''; ?>" href="<?php echo pt_e($qs(['st' => 'delivered', 'pay' => 'all', 'page' => 1])); ?>"><span>Terkirim</span><b><?php echo $sum['delivered']; ?></b></a>
    <a class="pt-tile<?php echo $pay === 'unpaid' ? ' on' : ''; ?>" href="<?php echo pt_e($qs(['st' => 'all', 'pay' => 'unpaid', 'page' => 1])); ?>"><span>Belum lunas</span><b><?php echo $sum['unpaid']; ?></b></a>
    <a class="pt-tile late<?php echo $pay === 'late' ? ' on' : ''; ?>" href="<?php echo pt_e($qs(['st' => 'all', 'pay' => 'late', 'page' => 1])); ?>"><span>Lewat jatuh tempo</span><b><?php echo $sum['late']; ?></b></a>
</div>

<form class="pt-filter" method="get">
    <input type="search" name="q" value="<?php echo pt_e($q); ?>" placeholder="Cari resi, penerima, tujuan, invoice">
    <select name="st">
        <option value="all">Semua status</option>
        <option value="active"<?php echo $st === 'active' ? ' selected' : ''; ?>>Dalam proses</option>
        <option value="delivered"<?php echo $st === 'delivered' ? ' selected' : ''; ?>>Terkirim</option>
    </select>
    <select name="pay">
        <option value="all">Semua pembayaran</option>
        <option value="unpaid"<?php echo $pay === 'unpaid' ? ' selected' : ''; ?>>Belum lunas</option>
        <option value="late"<?php echo $pay === 'late' ? ' selected' : ''; ?>>Lewat jatuh tempo</option>
        <option value="paid"<?php echo $pay === 'paid' ? ' selected' : ''; ?>>Lunas</option>
    </select>
    <label class="pt-date">Dari <input type="date" name="from" value="<?php echo pt_e($from); ?>"></label>
    <label class="pt-date">Sampai <input type="date" name="to" value="<?php echo pt_e($to); ?>"></label>
    <button type="submit" class="pt-btn">Terapkan</button>
    <a class="pt-btn gray" href="/portal/">Reset</a>
    <a class="pt-btn gray" href="<?php echo pt_e($qs(['export' => 'csv'])); ?>">Unduh Excel (CSV)</a>
</form>

<p class="pt-muted pt-small"><?php echo $total; ?> pengiriman<?php echo $total > $per ? ' · halaman ' . $page . ' dari ' . $pages : ''; ?></p>

<?php if (!$show) { ?>
<div class="pt-empty">Tidak ada pengiriman yang cocok.</div>
<?php } else { ?>
<div class="pt-table-wrap">
<table class="pt-table">
    <thead><tr><th>No. resi</th><th>Tgl diterima</th><th>Tujuan</th><th>Penerima</th><th>Status</th><th>ETA</th><th>Invoice</th><th>Pembayaran</th></tr></thead>
    <tbody>
    <?php foreach ($show as $r) { $p = $r['_pay']; ?>
    <tr>
        <td data-l="No. resi"><a class="pt-resi" href="/portal/shipment.php?no=<?php echo rawurlencode($r['cons_no']); ?>"><?php echo pt_e($r['cons_no']); ?></a></td>
        <td data-l="Tgl diterima"><?php echo pt_e(pt_date($r['collection_date'])); ?></td>
        <td data-l="Tujuan"><?php echo pt_e($r['pickup_time'] ?? ''); ?></td>
        <td data-l="Penerima"><?php echo pt_e($r['rev_name'] ?? ''); ?></td>
        <td data-l="Status"><span class="pt-pill <?php echo pt_status_class($r['status']); ?>"><?php echo pt_e(pt_status_label($r['status'])); ?></span></td>
        <td data-l="ETA"><?php echo pt_e($r['status'] === 'Delivered' ? 'Terkirim ' . pt_date($r['delivered_date'] ?? '') : pt_date($r['eta'] ?? '')); ?></td>
        <td data-l="Invoice"><?php echo pt_e(($r['invoice_no'] ?? '') !== '' ? $r['invoice_no'] : '-'); ?></td>
        <td data-l="Pembayaran"><span class="pt-pill <?php echo $p[1]; ?>"><?php echo pt_e($p[0]); ?></span></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
</div>
<?php if ($pages > 1) { ?>
<nav class="pt-pages">
    <?php if ($page > 1) { ?><a href="<?php echo pt_e($qs(['page' => $page - 1])); ?>">&larr; Sebelumnya</a><?php } ?>
    <span><?php echo $page; ?> / <?php echo $pages; ?></span>
    <?php if ($page < $pages) { ?><a href="<?php echo pt_e($qs(['page' => $page + 1])); ?>">Berikutnya &rarr;</a><?php } ?>
</nav>
<?php } } ?>
<?php pt_foot(); ?>
