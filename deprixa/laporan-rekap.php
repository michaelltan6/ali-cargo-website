<?php
// Laporan: rekap bulanan per wilayah, pengirim, partner, dan metode pembayaran.
require_once __DIR__ . '/report-lib.php';
rp_boot(true);

$month = rp_month(date('Y-m'));
if ($month === '') { $month = date('Y-m'); }
$book = courier_book_code($_GET['book'] ?? 'ALI');
[$from, $to] = rp_month_range($month);

$tot = ['n' => 0, 'koli' => 0, 'kg' => 0.0, 'm3' => 0.0, 'selesai' => 0, 'spb_belum' => 0, 'tempo_off' => 0, 'belum_lunas' => 0];
$byRegion = ['JAWA' => [0, 0, 0.0, 0.0], 'TIMUR' => [0, 0, 0.0, 0.0], 'SUMATERA' => [0, 0, 0.0, 0.0]];
$bySender = []; $byPartner = []; $byPay = ['Cash' => 0, 'Tempo 7 hari' => 0, 'Tempo 14 hari' => 0, 'Tempo 30 hari' => 0, 'Tempo (lama belum diatur)' => 0];

$stmt = dbQuery("SELECT * FROM courier WHERE collection_date >= ? AND collection_date < ? AND COALESCE(book_code, 'ALI') = ?", [$from, $to, $book]);
while ($c = dbFetchAssoc($stmt)) {
    $koli = 0; $kg = 0.0; $m3 = 0.0;
    $iq = dbQuery("SELECT quantity, weight, volume FROM courier_items WHERE courier_id = ?", [$c['cid']]);
    while ($i = dbFetchAssoc($iq)) { $q = (float)$i['quantity']; $koli += $q; $kg += $q * (float)$i['weight']; $m3 += $q * (float)$i['volume']; }
    $tot['n']++; $tot['koli'] += $koli; $tot['kg'] += $kg; $tot['m3'] += $m3;
    if (($c['status'] ?? '') === 'Delivered') { $tot['selesai']++; if (rp_d($c['spb_return'] ?? '') === '') { $tot['spb_belum']++; } }
    if (($c['book_mode'] ?? '') === 'ToPay') { $tot['belum_lunas']++; [$lab] = rp_tempo_state($c); if ($lab === 'Belum aktif') { $tot['tempo_off']++; } }
    $rg = courier_region($c['pickup_time'] ?? '');
    $byRegion[$rg][0]++; $byRegion[$rg][1] += $koli; $byRegion[$rg][2] += $kg; $byRegion[$rg][3] += $m3;
    $s = trim((string)$c['ship_name']); if ($s === '') { $s = '(tanpa nama)'; }
    $bySender[$s] = ($bySender[$s] ?? 0) + 1;
    $pt = trim((string)($c['partner'] ?? '')); if ($pt === '') { $pt = '(tanpa partner)'; }
    $byPartner[$pt] = ($byPartner[$pt] ?? 0) + 1;
    $lbl = rp_pay_label($c); $byPay[$lbl] = ($byPay[$lbl] ?? 0) + 1;
}
arsort($bySender); arsort($byPartner);
$bln = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
$nm = $bln[substr($month, 5, 2)] . ' ' . substr($month, 0, 4);
function rp_n($v, $d = 0) { return number_format((float)$v, $d, ',', '.'); }

rp_start('Rekap bulanan', 'bar-chart', $nm . ' · pembukuan ' . $book);
?>
<section class="ac-card">
    <form method="get" action="laporan-rekap.php" class="rp-bar">
        <label for="month">Bulan</label> <input type="month" id="month" name="month" value="<?php echo rp_e($month); ?>">
        <label for="book">Pembukuan</label>
        <select id="book" name="book"><option value="ALI"<?php echo $book === 'ALI' ? ' selected' : ''; ?>>ALI</option><option value="ANK"<?php echo $book === 'ANK' ? ' selected' : ''; ?>>ANK</option></select>
        <button type="submit" class="act-btn">Tampilkan</button>
        <a class="ac-btn sec" style="padding:6px 12px;font-size:.85rem;text-decoration:none" href="export-monthly.php?download=1&month=<?php echo rp_e($month); ?>&book=<?php echo rp_e($book); ?>"><i class="fa fa-file-excel-o"></i> Download Excel bulan ini</a>
    </form>
</section>

<div class="dash-tiles">
    <div class="dash-tile on"><span>Total pengiriman</span><b><?php echo rp_n($tot['n']); ?></b></div>
    <div class="dash-tile"><span>Total koli</span><b><?php echo rp_n($tot['koli']); ?></b></div>
    <div class="dash-tile"><span>Total berat (kg)</span><b><?php echo rp_n($tot['kg'], 1); ?></b></div>
    <div class="dash-tile"><span>Total volume (m&sup3;)</span><b><?php echo rp_n($tot['m3'], 3); ?></b></div>
    <div class="dash-tile ok"><span>Selesai</span><b><?php echo rp_n($tot['selesai']); ?></b></div>
</div>
<div class="dash-tiles" style="grid-template-columns:repeat(3,1fr)">
    <a class="dash-tile warn" href="laporan-selesai.php?month=<?php echo rp_e($month); ?>"><span>Selesai, SPB belum kembali</span><b><?php echo rp_n($tot['spb_belum']); ?></b></a>
    <a class="dash-tile warn" href="laporan-piutang.php"><span>Belum lunas (tempo)</span><b><?php echo rp_n($tot['belum_lunas']); ?></b></a>
    <a class="dash-tile warn" href="laporan-piutang.php"><span>Tempo belum diaktifkan</span><b><?php echo rp_n($tot['tempo_off']); ?></b></a>
</div>

<div class="rp-two">
    <section class="ac-card">
        <h4><i class="fa fa-map"></i> Per wilayah</h4>
        <table class="ac-table"><thead><tr><th>Wilayah</th><th>Kirim</th><th>Koli</th><th>Kg</th><th>m&sup3;</th></tr></thead><tbody>
        <?php foreach ($byRegion as $k => $v): ?>
            <tr><td><?php echo rp_e($k === 'TIMUR' ? 'Kalimantan & Timur' : ucfirst(strtolower($k))); ?></td><td><?php echo rp_n($v[0]); ?></td><td><?php echo rp_n($v[1]); ?></td><td><?php echo rp_n($v[2], 1); ?></td><td><?php echo rp_n($v[3], 3); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </section>
    <section class="ac-card">
        <h4><i class="fa fa-credit-card"></i> Metode pembayaran</h4>
        <table class="ac-table"><thead><tr><th>Metode</th><th>Kirim</th></tr></thead><tbody>
        <?php foreach ($byPay as $k => $v): if ($v === 0 && $k === 'Tempo (lama belum diatur)') { continue; } ?>
            <tr><td><?php echo rp_e($k); ?></td><td><?php echo rp_n($v); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </section>
    <section class="ac-card">
        <h4><i class="fa fa-building"></i> Customer teratas</h4>
        <table class="ac-table"><thead><tr><th>Pengirim</th><th>Kirim</th></tr></thead><tbody>
        <?php foreach (array_slice($bySender, 0, 10, true) as $k => $v): ?>
            <tr><td><?php echo rp_e($k); ?></td><td><?php echo rp_n($v); ?></td></tr>
        <?php endforeach; if (!$bySender): ?><tr><td colspan="2" class="ac-sub">Belum ada data bulan ini.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
    <section class="ac-card">
        <h4><i class="fa fa-handshake-o"></i> Via / Partner</h4>
        <table class="ac-table"><thead><tr><th>Partner</th><th>Kirim</th></tr></thead><tbody>
        <?php foreach (array_slice($byPartner, 0, 10, true) as $k => $v): ?>
            <tr><td><?php echo rp_e($k); ?></td><td><?php echo rp_n($v); ?></td></tr>
        <?php endforeach; if (!$byPartner): ?><tr><td colspan="2" class="ac-sub">Belum ada data bulan ini.</td></tr><?php endif; ?>
        </tbody></table>
    </section>
</div>
<?php rp_end('');
