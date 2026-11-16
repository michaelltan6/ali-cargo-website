<?php
// Laporan: piutang / tempo. Pengiriman tempo yang belum lunas, aktivasi tempo, dan penandaan lunas.
require_once __DIR__ . '/report-lib.php';
rp_boot(true);

$rows = [];
$stmt = dbQuery("SELECT * FROM courier WHERE book_mode = 'ToPay' ORDER BY collection_date DESC, cid DESC");
while ($r = dbFetchAssoc($stmt)) { $rows[] = $r; }
$cOff = $cRun = $cLate = 0;
foreach ($rows as $r) { [$lab] = rp_tempo_state($r); if ($lab === 'Belum aktif' || $lab === 'Lama tempo belum diatur') { $cOff++; } elseif (strpos($lab, 'Lewat') === 0 || strpos($lab, 'hari ini') !== false) { $cLate++; } else { $cRun++; } }

rp_start('Piutang dan tempo', 'credit-card', 'Pengiriman tempo yang belum lunas');
?>
<div class="dash-tiles" style="grid-template-columns:repeat(4,1fr)">
    <a class="dash-tile on" href="#" data-filter=""><span>Belum lunas</span><b><?php echo count($rows); ?></b></a>
    <a class="dash-tile warn" href="#" data-filter="Belum aktif|belum diatur"><span>Tempo belum aktif</span><b><?php echo $cOff; ?></b></a>
    <a class="dash-tile" href="#" data-filter="hari lagi"><span>Tempo berjalan</span><b><?php echo $cRun; ?></b></a>
    <a class="dash-tile warn" href="#" data-filter="Lewat|hari ini"><span>Jatuh tempo / lewat</span><b style="color:#b0372b"><?php echo $cLate; ?></b></a>
</div>
<section class="ac-card">
    <p class="ac-sub" style="margin-top:0">Tempo mulai dihitung setelah kamu klik <b>Aktifkan tempo</b> (pilih tanggal mulainya: sejak pengiriman selesai atau sejak SPB kembali). Kalau pembayaran sudah masuk, klik <b>Tandai lunas</b>.</p>
    <div class="ac-table-wrap">
    <table id="rp-table" class="ac-table dash-table" width="100%">
        <thead><tr><th>Tracking</th><th>Customer</th><th>Tujuan</th><th>Tgl diterima</th><th>Pengiriman</th><th>SPB kembali</th><th>Tempo</th><th>Status tempo</th><th>Jatuh tempo</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            [$lab, $cls, $key] = rp_tempo_state($r);
            $term = (string)($r['payment_term'] ?? '');
            $isTempo = in_array($term, ['7', '14', '30'], true);
            $active = $key !== '9999-99-99';
            $ret = rp_d($r['spb_return'] ?? '');
            [$stLab] = [($r['status'] ?? '') === 'Delivered' ? 'Selesai' : (($r['status'] ?? '') === 'On-Hold' ? 'On Hold' : 'On Delivery')];
        ?>
            <tr>
                <td><b><?php echo rp_e($r['cons_no']); ?></b></td>
                <td><?php echo rp_e($r['ship_name']); ?></td>
                <td><?php echo rp_e($r['pickup_time'] ?? ''); ?></td>
                <td data-order="<?php echo rp_e(ali_ship_date($r)); ?>"><?php echo rp_e(rp_d(ali_ship_date($r))); ?></td>
                <td><?php echo rp_e($stLab); ?></td>
                <td><?php echo $ret !== '' ? rp_e($ret) : '<span class="ac-sub">belum</span>'; ?></td>
                <td><?php echo rp_e(rp_pay_label($r)); ?></td>
                <td><span class="st <?php echo $cls; ?>"><?php echo rp_e($lab); ?></span></td>
                <td data-order="<?php echo rp_e($key); ?>"><?php echo $active ? rp_e(rp_d($r['due_date'])) : '-'; ?></td>
                <td class="dash-act" data-cid="<?php echo (int)$r['cid']; ?>">
                    <?php if ($isTempo && !$active): ?>
                        <div><input type="date" class="rp-date t-start" value="<?php echo date('Y-m-d'); ?>"> <button type="button" class="act-btn finish t-go">Aktifkan tempo</button></div>
                    <?php endif; ?>
                    <div style="margin-top:6px"><input type="date" class="rp-date p-date" value="<?php echo date('Y-m-d'); ?>"> <button type="button" class="act-btn p-go">Tandai lunas</button></div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php
rp_end(<<<'JS'
var table = $('#rp-table').DataTable({ pageLength: 20, lengthMenu: [[20, 50, 100, -1], [20, 50, 100, 'Semua']], order: [[8, 'asc']], columnDefs: [{ targets: 9, orderable: false, searchable: false }], language: RP_LANG });
$('.dash-tile[data-filter]').on('click', function(e) { e.preventDefault(); var f = $(this).data('filter') || ''; table.column(7).search(f, true, false).draw(); $('.dash-tile').removeClass('on'); $(this).addClass('on'); });
$('#rp-table').on('click', '.t-go', function() {
    var td = $(this).closest('td'), d = td.find('.t-start').val();
    $.ajax({ url: 'update_duedate.php', type: 'POST', dataType: 'json', data: { action: 'activate_tempo', cid: td.data('cid'), start_date: d },
        success: function(res) { if (res && res.success) { rpNotify({ icon: 'success', title: 'Tempo aktif', text: res.message, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); }); } else { rpNotify({ icon: 'error', title: 'Gagal', text: res ? res.message : '' }); } },
        error: function() { rpNotify({ icon: 'error', title: 'Gagal menghubungi server' }); } });
});
$('#rp-table').on('click', '.p-go', function() {
    var td = $(this).closest('td'), d = td.find('.p-date').val();
    var go = function() { rpApi({ action: 'mark_paid', cid: td.data('cid'), date: d }, function() { location.reload(); }); };
    if (window.Swal) { Swal.fire({ title: 'Tandai lunas?', text: 'Pembayaran diterima tanggal ' + d, icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, lunas', cancelButtonText: 'Batal' }).then(function(r) { if (r.isConfirmed) { go(); } }); }
    else if (confirm('Tandai lunas, pembayaran diterima ' + d + '?')) { go(); }
});
JS);
