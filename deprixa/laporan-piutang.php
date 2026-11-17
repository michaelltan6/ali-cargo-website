<?php
// Laporan: piutang / tempo. Pengiriman tempo yang belum lunas, aktivasi tempo, dan penandaan lunas.
require_once __DIR__ . '/report-lib.php';
rp_boot(true);
require_once __DIR__ . '/reminder-lib.php';
courier_columns_ready();
$remInv = rem_collect();
$mailOk = ali_mail_ready();

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
    <div class="rp-bar" style="gap:8px;flex-wrap:wrap;margin-bottom:10px">
        <input type="text" id="inv-no" class="rp-date" maxlength="60" placeholder="Nomor invoice" style="min-width:200px">
        <button type="button" class="act-btn finish" id="inv-set">Pasang ke yang dicentang</button>
        <button type="button" class="act-btn" id="inv-clear">Hapus invoice dari yang dicentang</button>
        <span style="flex:1"></span>
        <button type="button" class="act-btn" id="mail-test"<?php echo $mailOk ? '' : ' disabled title="Pengaturan email belum diisi di server"'; ?>>Kirim email tes</button>
    </div>
    <?php if (!$mailOk): ?><p class="ac-sub" style="color:#b0372b;margin-top:0">Pengaturan email belum diisi di server (config.local.php), jadi pengingat belum bisa terkirim.</p><?php endif; ?>
    <p class="ac-sub" style="margin-top:0">Tempo mulai dihitung setelah kamu klik <b>Aktifkan tempo</b> (pilih tanggal mulainya: sejak pengiriman selesai atau sejak SPB kembali). Kalau pembayaran sudah masuk, klik <b>Tandai lunas</b>.</p>
    <div class="ac-table-wrap">
    <table id="rp-table" class="ac-table dash-table" width="100%">
        <thead><tr><th><input type="checkbox" id="chk-all" title="Pilih semua yang tampil"></th><th>Tracking</th><th>Invoice</th><th>Customer</th><th>Tujuan</th><th>Tgl diterima</th><th>Pengiriman</th><th>SPB kembali</th><th>Tempo</th><th>Status tempo</th><th>Jatuh tempo</th><th>Pengingat email</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            [$lab, $cls, $key] = rp_tempo_state($r);
            $term = (string)($r['payment_term'] ?? '');
            $isTempo = in_array($term, ['7', '14', '30'], true);
            $active = $key !== '9999-99-99';
            $ret = rp_d($r['spb_return'] ?? '');
            [$stLab] = [($r['status'] ?? '') === 'Delivered' ? 'Selesai' : (($r['status'] ?? '') === 'On-Hold' ? 'On Hold' : 'On Delivery')];
        ?>
            <?php $invNo = trim((string)($r['invoice_no'] ?? '')); $ri = $invNo !== '' ? ($remInv[$invNo] ?? null) : null; ?>
            <tr>
                <td><input type="checkbox" class="chk-row" value="<?php echo (int)$r['cid']; ?>"></td>
                <td><b><?php echo rp_e($r['cons_no']); ?></b></td>
                <td><?php echo $invNo !== '' ? '<b>' . rp_e($invNo) . '</b>' : '<span class="ac-sub">belum</span>'; ?></td>
                <td><?php echo rp_e($r['ship_name']); ?></td>
                <td><?php echo rp_e($r['pickup_time'] ?? ''); ?></td>
                <td data-order="<?php echo rp_e(ali_ship_date($r)); ?>"><?php echo rp_e(rp_d(ali_ship_date($r))); ?></td>
                <td><?php echo rp_e($stLab); ?></td>
                <td><?php echo $ret !== '' ? rp_e($ret) : '<span class="ac-sub">belum</span>'; ?></td>
                <td><?php echo rp_e(rp_pay_label($r)); ?></td>
                <td><span class="st <?php echo $cls; ?>"><?php echo rp_e($lab); ?></span></td>
                <td data-order="<?php echo rp_e($key); ?>"><?php echo $active ? rp_e(rp_d($r['due_date'])) : '-'; ?></td>
                <td>
                    <?php if (!$ri): ?><span class="ac-sub"><?php echo $invNo === '' ? 'isi nomor invoice dulu' : '-'; ?></span>
                    <?php else: $okc = ($ri['state'] === 'due_now' || $ri['state'] === 'repeat_wait') ? 'st-go' : (($ri['state'] === 'wait') ? 'st-wait' : 'st-hold'); ?>
                        <span class="st <?php echo $okc; ?>"><?php echo rp_e($ri['label']); ?></span>
                        <?php if (count($ri['members']) > 1): ?><div class="ac-sub"><?php echo count($ri['members']); ?> SPB dalam invoice ini</div><?php endif; ?>
                        <?php if ($ri['state'] !== 'no_due' && $ri['state'] !== 'wait'): ?><div style="margin-top:4px"><button type="button" class="act-btn r-send" data-inv="<?php echo rp_e($invNo); ?>">Kirim sekarang</button></div><?php endif; ?>
                    <?php endif; ?>
                </td>
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
var table = $('#rp-table').DataTable({ pageLength: 20, lengthMenu: [[20, 50, 100, -1], [20, 50, 100, 'Semua']], order: [[10, 'asc']], columnDefs: [{ targets: [0, 12], orderable: false, searchable: false }], language: RP_LANG });
$('.dash-tile[data-filter]').on('click', function(e) { e.preventDefault(); var f = $(this).data('filter') || ''; table.column(9).search(f, true, false).draw(); $('.dash-tile').removeClass('on'); $(this).addClass('on'); });
function checkedIds() { return $('#rp-table .chk-row:checked').map(function() { return this.value; }).get(); }
$('#chk-all').on('change', function() { var on = this.checked; $('#rp-table tbody tr:visible .chk-row').prop('checked', on); });
$('#inv-set').on('click', function() {
    var ids = checkedIds(), no = $.trim($('#inv-no').val());
    if (!ids.length) { rpNotify({ icon: 'info', title: 'Centang pengiriman dulu.' }); return; }
    if (!no) { rpNotify({ icon: 'info', title: 'Isi nomor invoice dulu.' }); return; }
    rpApi({ action: 'set_invoice', cids: ids.join(','), invoice_no: no }, function(res) { rpNotify({ icon: 'success', title: res.message, timer: 1400, showConfirmButton: false }).then(function() { location.reload(); }); });
});
$('#inv-clear').on('click', function() {
    var ids = checkedIds();
    if (!ids.length) { rpNotify({ icon: 'info', title: 'Centang pengiriman dulu.' }); return; }
    rpApi({ action: 'set_invoice', cids: ids.join(','), invoice_no: '' }, function(res) { location.reload(); });
});
$('#rp-table').on('click', '.r-send', function() {
    var inv = $(this).data('inv');
    var go = function() { rpApi({ action: 'send_reminder', invoice_no: inv }, function(res) { rpNotify({ icon: 'success', title: 'Terkirim', text: res.message }).then(function() { location.reload(); }); }); };
    if (window.Swal) { Swal.fire({ title: 'Kirim pengingat sekarang?', text: 'Invoice ' + inv + ' dikirim ke customer (salinan ke finance).', icon: 'question', showCancelButton: true, confirmButtonText: 'Kirim', cancelButtonText: 'Batal' }).then(function(r) { if (r.isConfirmed) { go(); } }); }
    else if (confirm('Kirim pengingat invoice ' + inv + ' sekarang?')) { go(); }
});
$('#mail-test').on('click', function() { rpApi({ action: 'test_mail' }, function(res) { rpNotify({ icon: 'success', title: res.message }); }); });
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
