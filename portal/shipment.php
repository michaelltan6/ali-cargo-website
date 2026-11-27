<?php
require_once __DIR__ . '/../deprixa/portal-lib.php';
$u = pt_require();
courier_columns_ready();

$no = trim((string)($_GET['no'] ?? ''));
// hanya pengiriman milik perusahaan customer ini
$c = $no === '' ? null : dbFetchAssoc(dbQuery("SELECT * FROM courier WHERE cons_no = ? AND customer_id = ?", [$no, (int)$u['customer_id']]));
if (!$c) {
    http_response_code(404);
    pt_head('Tidak ditemukan', $u);
    echo '<div class="pt-empty">Pengiriman tidak ditemukan. <a href="/portal/">Kembali ke daftar</a></div>';
    pt_foot();
    exit;
}
$items = []; $qty = 0; $kg = 0; $m3 = 0;
$res = dbQuery("SELECT item_name, quantity, weight, volume FROM courier_items WHERE courier_id = ?", [(int)$c['cid']]);
while ($r = dbFetchAssoc($res)) { $items[] = $r; $qty += (float)$r['quantity']; $kg += (float)$r['quantity'] * (float)$r['weight']; $m3 += (float)$r['quantity'] * (float)$r['volume']; }
$hist = [];
$res = dbQuery("SELECT status, pick_time, comments, bk_time FROM courier_track WHERE cid = ? ORDER BY bk_time DESC, id DESC", [(int)$c['cid']]);
while ($r = dbFetchAssoc($res)) { $hist[] = $r; }
$p = pt_pay_state($c);

pt_head($c['cons_no'], $u);
?>
<p class="pt-small"><a href="/portal/">&larr; Semua pengiriman</a></p>
<div class="pt-detail-head">
    <h1><?php echo pt_e($c['cons_no']); ?></h1>
    <span class="pt-pill big <?php echo pt_status_class($c['status']); ?>"><?php echo pt_e(pt_status_label($c['status'])); ?></span>
</div>

<div class="pt-grid">
    <section class="pt-card">
        <h2>Pengiriman</h2>
        <dl class="pt-dl">
            <dt>Rute</dt><dd><?php echo pt_e(trim(($c['origin_city'] ?? '') . ' → ' . ($c['pickup_time'] ?? ''), ' →')); ?></dd>
            <dt>Penerima</dt><dd><?php echo pt_e($c['rev_name'] ?? ''); ?></dd>
            <dt>Layanan</dt><dd><?php echo pt_e($c['mode'] ?? '-'); ?></dd>
            <dt>Lokasi terakhir</dt><dd><?php echo pt_e(($c['pick_time'] ?? '') !== '' ? $c['pick_time'] : '-'); ?></dd>
            <dt>Barang diterima</dt><dd><?php echo pt_e(pt_date($c['collection_date']) ?: '-'); ?></dd>
            <dt>Berangkat (ETD)</dt><dd><?php echo pt_e(pt_date($c['etd'] ?? '') ?: '-'); ?></dd>
            <dt>Perkiraan tiba (ETA)</dt><dd><?php echo pt_e(pt_date($c['eta'] ?? '') ?: '-'); ?></dd>
            <?php if ($c['status'] === 'Delivered') { ?><dt>Terkirim</dt><dd><?php echo pt_e(pt_date($c['delivered_date'] ?? '') ?: '-'); ?></dd><?php } ?>
        </dl>
    </section>
    <section class="pt-card">
        <h2>Invoice dan pembayaran</h2>
        <dl class="pt-dl">
            <dt>No. invoice</dt><dd><?php echo pt_e(($c['invoice_no'] ?? '') !== '' ? $c['invoice_no'] : 'Belum diterbitkan'); ?></dd>
            <dt>Cara bayar</dt><dd><?php echo pt_e(pt_term_label($c)); ?></dd>
            <?php if (($c['due_date'] ?? '') !== '' && strpos((string)$c['due_date'], '0000') !== 0) { ?><dt>Jatuh tempo</dt><dd><?php echo pt_e(pt_date($c['due_date'])); ?></dd><?php } ?>
            <dt>Status</dt><dd><span class="pt-pill <?php echo $p[1]; ?>"><?php echo pt_e($p[0]); ?></span></dd>
        </dl>
        <p class="pt-muted pt-small">Untuk rincian tagihan atau konfirmasi pembayaran, hubungi admin ALI Cargo.</p>
    </section>
</div>

<section class="pt-card">
    <h2>Barang</h2>
    <?php if (!$items) { ?><p class="pt-muted">Belum ada rincian barang.</p><?php } else { ?>
    <div class="pt-table-wrap"><table class="pt-table">
        <thead><tr><th>Nama barang</th><th>Jumlah</th><th>Berat (kg)</th><th>Volume (m³)</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i) { ?>
        <tr><td data-l="Nama barang"><?php echo pt_e($i['item_name']); ?></td><td data-l="Jumlah"><?php echo pt_e(pt_num($i['quantity'], 0)); ?></td>
            <td data-l="Berat (kg)"><?php echo pt_e(pt_num((float)$i['quantity'] * (float)$i['weight'])); ?></td><td data-l="Volume (m³)"><?php echo pt_e(pt_num((float)$i['quantity'] * (float)$i['volume'], 3)); ?></td></tr>
        <?php } ?>
        <tr class="pt-total"><td>Total</td><td data-l="Jumlah"><?php echo pt_e(pt_num($qty, 0)); ?></td><td data-l="Berat (kg)"><?php echo pt_e(pt_num($kg)); ?></td><td data-l="Volume (m³)"><?php echo pt_e(pt_num($m3, 3)); ?></td></tr>
        </tbody>
    </table></div>
    <?php } ?>
</section>

<section class="pt-card">
    <h2>Riwayat</h2>
    <?php if (!$hist) { ?><p class="pt-muted">Belum ada riwayat.</p><?php } else { ?>
    <ol class="pt-timeline">
        <?php foreach ($hist as $h) { ?>
        <li><span class="pt-dot"></span>
            <div><b><?php echo pt_e(pt_status_label($h['status'])); ?></b><?php echo ($h['pick_time'] ?? '') !== '' ? ' · ' . pt_e($h['pick_time']) : ''; ?>
                <div class="pt-muted pt-small"><?php echo pt_e(pt_date($h['bk_time']) . ' ' . substr((string)$h['bk_time'], 11, 5)); ?></div>
                <?php if (trim((string)$h['comments']) !== '') { ?><div class="pt-note"><?php echo pt_e($h['comments']); ?></div><?php } ?>
            </div></li>
        <?php } ?>
    </ol>
    <?php } ?>
</section>
<?php pt_foot(); ?>
