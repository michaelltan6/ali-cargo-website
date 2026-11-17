<?php
// Laporan: pengiriman yang sudah dibayar (Cash dan tempo yang sudah lunas).
require_once __DIR__ . '/report-lib.php';
rp_boot(true);

$month = rp_month(date('Y-m'));
if (($_GET['month'] ?? '') === 'all') { $month = ''; }
$params = [];
$where = "book_mode = 'Paid'";
if ($month !== '') { [$from, $to] = rp_month_range($month); $where .= " AND COALESCE(NULLIF(paid_date, ''), collection_date) >= ? AND COALESCE(NULLIF(paid_date, ''), collection_date) < ?"; $params[] = $from; $params[] = $to; }
$rows = [];
$stmt = dbQuery("SELECT * FROM courier WHERE $where ORDER BY COALESCE(NULLIF(paid_date, ''), collection_date) DESC, cid DESC", $params);
while ($r = dbFetchAssoc($stmt)) { $rows[] = $r; }
$cash = 0; foreach ($rows as $r) { if (!in_array((string)($r['payment_term'] ?? ''), ['7', '14', '30'], true)) { $cash++; } }

rp_start('Sudah dibayar', 'money', $month !== '' ? 'Bulan ' . date('m-Y', strtotime($month . '-01')) : 'Semua bulan');
?>
<div class="dash-tiles" style="grid-template-columns:repeat(3,1fr)">
    <div class="dash-tile on"><span>Sudah dibayar</span><b><?php echo count($rows); ?></b></div>
    <div class="dash-tile"><span>Cash</span><b><?php echo $cash; ?></b></div>
    <div class="dash-tile ok"><span>Tempo lunas</span><b><?php echo count($rows) - $cash; ?></b></div>
</div>
<section class="ac-card">
    <?php rp_month_bar('laporan-lunas.php', $month); ?>
    <div class="ac-table-wrap">
    <table id="rp-table" class="ac-table dash-table" width="100%">
        <thead><tr><th>Tracking</th><th>Invoice</th><th>Customer</th><th>Tujuan</th><th>Tgl diterima</th><th>Pembayaran</th><th>Tgl lunas</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $isTempo = in_array((string)($r['payment_term'] ?? ''), ['7', '14', '30'], true);
            $paid = rp_d($r['paid_date'] ?? '') ?: rp_d(ali_ship_date($r));
            $paidKey = trim((string)($r['paid_date'] ?? '')) !== '' ? substr($r['paid_date'], 0, 10) : ali_ship_date($r);
        ?>
            <tr>
                <td><b><?php echo rp_e($r['cons_no']); ?></b></td>
                <td><?php echo rp_e($r['invoice_no'] ?? ''); ?></td>
                <td><?php echo rp_e($r['ship_name']); ?></td>
                <td><?php echo rp_e($r['pickup_time'] ?? ''); ?></td>
                <td data-order="<?php echo rp_e(ali_ship_date($r)); ?>"><?php echo rp_e(rp_d(ali_ship_date($r))); ?></td>
                <td><?php echo rp_e(rp_pay_label($r)); ?></td>
                <td data-order="<?php echo rp_e($paidKey); ?>"><?php echo rp_e($paid); ?></td>
                <td class="dash-act"><?php if ($isTempo): ?><button type="button" class="act-btn del un-paid" data-cid="<?php echo (int)$r['cid']; ?>">Batalkan lunas</button><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php
rp_end(<<<'JS'
$('#rp-table').DataTable({ pageLength: 20, lengthMenu: [[20, 50, 100, -1], [20, 50, 100, 'Semua']], order: [[6, 'desc']], columnDefs: [{ targets: 7, orderable: false, searchable: false }], language: RP_LANG });
$('#rp-table').on('click', '.un-paid', function() {
    var cid = $(this).data('cid');
    var go = function() { rpApi({ action: 'unmark_paid', cid: cid }, function() { location.reload(); }); };
    if (window.Swal) { Swal.fire({ title: 'Batalkan status lunas?', text: 'Pengiriman kembali ke daftar piutang.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Tidak' }).then(function(r) { if (r.isConfirmed) { go(); } }); }
    else if (confirm('Batalkan status lunas?')) { go(); }
});
JS);
