<?php
// Laporan: pengiriman yang sudah selesai (Delivered) dan status surat jalan (SPB) kembali.
require_once __DIR__ . '/report-lib.php';
rp_boot(false);
$isAdmin = rp_is_admin();

$month = rp_month(date('Y-m'));
if (($_GET['month'] ?? '') === 'all') { $month = ''; }
$params = [];
$where = "status = 'Delivered'";
if ($month !== '') { [$from, $to] = rp_month_range($month); $where .= " AND collection_date >= ? AND collection_date < ?"; $params[] = $from; $params[] = $to; }
$where .= rp_scope($params);
$rows = [];
$stmt = dbQuery("SELECT * FROM courier WHERE $where ORDER BY collection_date DESC, cid DESC", $params);
while ($r = dbFetchAssoc($stmt)) { $rows[] = $r; }
$total = count($rows);
$belum = 0; foreach ($rows as $r) { if (rp_d($r['spb_return'] ?? '') === '') { $belum++; } }

rp_start('Pengiriman selesai', 'check-circle', $month !== '' ? 'Bulan ' . date('m-Y', strtotime($month . '-01')) : 'Semua bulan');
?>
<div class="dash-tiles" style="grid-template-columns:repeat(3,1fr)">
    <a class="dash-tile on" href="#" data-filter=""><span>Pengiriman selesai</span><b><?php echo $total; ?></b></a>
    <a class="dash-tile warn" href="#" data-filter="Belum kembali"><span>SPB belum kembali</span><b><?php echo $belum; ?></b></a>
    <div class="dash-tile ok"><span>SPB sudah kembali</span><b><?php echo $total - $belum; ?></b></div>
</div>
<section class="ac-card">
    <?php rp_month_bar('laporan-selesai.php', $month); ?>
    <div class="ac-table-wrap">
    <table id="rp-table" class="ac-table dash-table" width="100%">
        <thead><tr><th>Tracking</th><th>No. SPB</th><th>Pengirim</th><th>Penerima</th><th>Tujuan</th><th>Tgl diterima</th><th>Pembayaran</th><th>SPB kembali</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $ret = trim((string)($r['spb_return'] ?? '')); $has = rp_d($ret) !== ''; ?>
            <tr>
                <td><b><?php echo rp_e($r['cons_no']); ?></b></td>
                <td><?php echo rp_e($r['spb_no'] ?? ''); ?></td>
                <td><?php echo rp_e($r['ship_name']); ?></td>
                <td><?php echo rp_e($r['rev_name']); ?></td>
                <td><?php echo rp_e($r['pickup_time'] ?? ''); ?></td>
                <td data-order="<?php echo rp_e(ali_ship_date($r)); ?>"><?php echo rp_e(rp_d(ali_ship_date($r))); ?></td>
                <td>
                    <?php echo rp_e(rp_pay_label($r)); ?>
                    <?php if ($isAdmin): $pt = (string)($r['payment_term'] ?? ''); if (!in_array($pt, ['7','14','30'], true)) { $pt = 'cash'; } ?>
                        <div style="margin-top:6px">
                            <select class="rp-date pay-term" data-cur="<?php echo $pt; ?>">
                                <?php foreach (['cash' => 'Cash', '7' => 'Tempo 7 hari', '14' => 'Tempo 14 hari', '30' => 'Tempo 30 hari'] as $v => $l): ?>
                                    <option value="<?php echo $v; ?>"<?php echo $pt === (string)$v ? ' selected' : ''; ?>><?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="act-btn pay-save" data-cid="<?php echo (int)$r['cid']; ?>">Ubah</button>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="dash-act">
                    <span class="st <?php echo $has ? 'st-go' : 'st-hold'; ?>"><?php echo $has ? 'Kembali ' . rp_e(rp_d($ret)) : 'Belum kembali'; ?></span>
                    <?php if ($isAdmin): ?>
                        <div style="margin-top:6px">
                            <input type="date" class="rp-date spb-date" value="<?php echo $has ? rp_e(substr($ret, 0, 10)) : ''; ?>">
                            <button type="button" class="act-btn spb-save" data-cid="<?php echo (int)$r['cid']; ?>">Simpan</button>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php
rp_end(<<<'JS'
var table = $('#rp-table').DataTable({ pageLength: 20, lengthMenu: [[20, 50, 100, -1], [20, 50, 100, 'Semua']], order: [[5, 'desc']], language: RP_LANG });
$('.dash-tile[data-filter]').on('click', function(e) { e.preventDefault(); table.column(7).search($(this).data('filter') || '').draw(); $('.dash-tile').removeClass('on'); $(this).addClass('on'); });
$('#rp-table').on('click', '.pay-save', function() {
    var btn = $(this), sel = btn.siblings('.pay-term');
    if (sel.val() === String(sel.data('cur'))) { rpNotify({ icon: 'info', title: 'Metode pembayaran belum diubah.' }); return; }
    rpApi({ action: 'set_payment_term', cid: btn.data('cid'), payment_term: sel.val() }, function(res) { rpNotify({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false }).then(function() { location.reload(); }); });
});
$('#rp-table').on('click', '.spb-save', function() {
    var btn = $(this), d = btn.siblings('.spb-date').val();
    rpApi({ action: 'set_spb_return', cid: btn.data('cid'), date: d }, function(res) { rpNotify({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false }).then(function() { location.reload(); }); });
});
JS);
