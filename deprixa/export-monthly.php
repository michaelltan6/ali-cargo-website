<?php
// Export Laporan Penjualan 1 bulan ke Excel, mengikuti format file "Laporan Penjualan (PPh)" yang dipakai admin.
error_reporting(E_ALL);
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once('database.php');
require_once('library.php');
requireAdmin();
require_once('courier-lib.php');
require_once('xlsx-lib.php');
courier_columns_ready();

$book = courier_book_code($_GET['book'] ?? 'ALI');
$month = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) { $month = date('Y-m'); }

if (isset($_GET['download'])) {
    $from = $month . '-01';
    $to = date('Y-m-d', strtotime($from . ' +1 month'));
    $q = dbQuery("SELECT * FROM courier WHERE collection_date >= ? AND collection_date < ? AND COALESCE(book_code, 'ALI') = ? ORDER BY (spb_no IS NULL), spb_no, collection_date, cid", [$from, $to, $book]);
    $groups = ['JAWA' => [], 'TIMUR' => [], 'SUMATERA' => []];
    while ($c = dbFetchAssoc($q)) {
        $items = []; $koli = 0; $kg = 0.0; $m3 = 0.0;
        $iq = dbQuery("SELECT * FROM courier_items WHERE courier_id = ?", [$c['cid']]);
        while ($i = dbFetchAssoc($iq)) {
            $n = trim((string)$i['item_name'] ?? '');
            if ($n === '' && isset($i['name'])) { $n = trim((string)$i['name']); }
            if ($n !== '' && !in_array($n, $items, true)) { $items[] = $n; }
            $qty = (float)$i['quantity'];
            $koli += $qty; $kg += $qty * (float)$i['weight']; $m3 += $qty * (float)$i['volume'];
        }
        $unit = ($c['charge_unit'] ?? '') === 'm3' ? 'm3' : (($c['charge_unit'] ?? '') === 'kg' ? 'kg' : ($kg > 0 ? 'kg' : 'm3'));
        $c['_items'] = implode(', ', $items);
        $c['_koli'] = $koli;
        $c['_qty'] = round($unit === 'kg' ? $kg : $m3, 3);
        $c['_unit'] = $unit;
        $groups[courier_region($c['pickup_time'] ?? '')][] = $c;
    }

    $titles = ['JAWA' => 'JAWA', 'TIMUR' => 'KALIMANTAN & WILAYAH TIMUR', 'SUMATERA' => 'SUMATERA'];
    $headers1 = [1 => 'Sales', 2 => 'No.', 3 => 'Sender Information', 8 => 'Receiver', 9 => 'Via / Partner', 10 => 'Destination', 11 => "Sub \nDestination", 12 => "Qty / \nKoli", 13 => 'Unit', 15 => 'COGS', 21 => 'Selling Price', 27 => 'GPM', 28 => 'Percentage', 30 => 'Customer Payment Information', 37 => 'Partner Payment Information'];
    $headers2 = [3 => 'Date SPB', 4 => 'No. SPB', 5 => 'SPB Return', 6 => 'Nama Barang', 7 => 'Sender', 13 => 'Qty', 14 => 'Type',
        15 => 'Kg', 16 => 'Cubic', 17 => 'Koli', 18 => 'Price Kg / Cubic / Koli ', 19 => 'Others', 20 => 'Total Price',
        21 => 'Kg', 22 => 'Cubic', 23 => 'Koli', 24 => 'Price Kg / Cubic / Koli', 25 => 'Others', 26 => 'Total Price',
        30 => 'Invoice Date', 31 => 'No. Invoice', 32 => 'Payment Date', 33 => 'Credit', 34 => 'Lunas', 35 => 'Tunai',
        37 => 'Invoice Date', 38 => 'No. Invoice', 39 => 'Payment Date', 40 => 'Debit', 41 => 'Lunas', 42 => 'Tunai'];
    $widths = [1 => 13.6, 2 => 5, 3 => 17.7, 4 => 16.3, 5 => 20.1, 6 => 50, 7 => 37, 8 => 34, 9 => 22, 10 => 16.6, 11 => 12, 12 => 9, 13 => 10.7, 14 => 11.3,
        15 => 16, 16 => 13.4, 17 => 15, 18 => 33, 19 => 15.7, 20 => 19.3, 21 => 13.9, 22 => 14.7, 23 => 15.7, 24 => 32, 25 => 15.7, 26 => 19.3, 27 => 16.3, 28 => 14.4, 29 => 0.6,
        30 => 21.3, 31 => 19.6, 32 => 23, 33 => 17.1, 34 => 16, 35 => 15, 36 => 0.6, 37 => 21.3, 38 => 32.6, 39 => 22.3, 40 => 16, 41 => 16.7, 42 => 13.4];

    $x = new SimpleXlsx();
    foreach ($groups as $key => $rows) {
        $sh = $x->addSheet($key);
        foreach ([1, 9, 16, 23, 30, 37] as $col) { $x->set($sh, 1, $col, $titles[$key], 1); }
        foreach ([[1, 8], [9, 15], [16, 22], [23, 29], [30, 36], [37, 42]] as [$a, $b]) { $x->merge($sh, SimpleXlsx::colName($a) . '1:' . SimpleXlsx::colName($b) . '3'); }
        // baris header (4 dan 5) lengkap dengan border
        for ($c = 1; $c <= 28; $c++) { $x->set($sh, 4, $c, $headers1[$c] ?? '', 2); $x->set($sh, 5, $c, $headers2[$c] ?? '', 2); }
        for ($c = 30; $c <= 42; $c++) { if ($c == 36) { continue; } $x->set($sh, 4, $c, $headers1[$c] ?? '', 2); $x->set($sh, 5, $c, $headers2[$c] ?? '', 2); }
        foreach (['A4:A5', 'B4:B5', 'H4:H5', 'I4:I5', 'J4:J5', 'K4:K5', 'L4:L5', 'AA4:AA5', 'AB4:AB5', 'C4:G4', 'M4:N4', 'O4:T4', 'U4:Z4', 'AD4:AI4', 'AK4:AP4'] as $m) { $x->merge($sh, $m); }
        $x->height($sh, 4, 24); $x->height($sh, 5, 30);
        foreach ($widths as $col => $w) { $x->width($sh, $col, $w); }
        $x->freeze($sh, 'C6');
        $r = 6; $no = 1;
        foreach ($rows as $c) {
            $x->set($sh, $r, 1, '', 3);
            $x->set($sh, $r, 2, $no++, 5);
            $d = SimpleXlsx::dateSerial($c['collection_date'] ?? '');
            $x->set($sh, $r, 3, $d, 4);
            $x->set($sh, $r, 4, !empty($c['spb_no']) ? (int)$c['spb_no'] : '', 5);
            $rd = !empty($c['spb_return']) ? SimpleXlsx::dateSerial($c['spb_return']) : null;
            $x->set($sh, $r, 5, $rd, 4);
            $x->set($sh, $r, 6, $c['_items'], 8);
            $x->set($sh, $r, 7, $c['ship_name'] ?? '', 8);
            $x->set($sh, $r, 8, $c['rev_name'] ?? '', 8);
            $x->set($sh, $r, 9, $c['partner'] ?? '', 3);
            $x->set($sh, $r, 10, strtolower((string)($c['pickup_time'] ?? '')), 3);
            $x->set($sh, $r, 11, '', 3);
            $x->set($sh, $r, 12, $c['_koli'] + 0, 5);
            $x->set($sh, $r, 13, $c['_qty'] + 0, 5);
            $x->set($sh, $r, 14, $c['_unit'], 3);
            for ($col = 15; $col <= 20; $col++) { $x->set($sh, $r, $col, '', $col == 20 ? 6 : 3); }
            for ($col = 21; $col <= 26; $col++) { $x->set($sh, $r, $col, '', $col == 26 ? 6 : 3); }
            $x->set($sh, $r, 27, '', 6); $x->set($sh, $r, 28, '', 7);
            foreach ([30, 32, 37, 39] as $col) { $x->set($sh, $r, $col, '', 4); }
            foreach ([31, 33, 34, 35, 38, 40, 41, 42] as $col) { $x->set($sh, $r, $col, '', 3); }
            $r++;
        }
    }
    $tmp = tempnam(sys_get_temp_dir(), 'lp');
    if (!$x->save($tmp)) { http_response_code(500); echo 'Gagal membuat file Excel.'; exit; }
    $bulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
    $fn = 'Laporan Penjualan (PPh) ' . $bulan[substr($month, 5, 2)] . ' ' . substr($month, 0, 4) . ' - ' . ($book === 'ANK' ? 'ANK' : 'PT ALI') . '.xlsx';
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fn . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <title>PT. ANEKA LINTAS INDONESIA</title>
    <link href="assets/css/style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css" type="text/css">
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="css/footer-basic-centered.css">
</head>
<body>
<?php include("header.php"); ?>
<div class="wrapper">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <div class="row"><div class="col-sm-12">
            <div class="card-box" style="max-width:640px">
                <h4 class="header-title m-t-0 m-b-20"><i class="fa fa-file-excel-o"></i> Export Laporan Penjualan (Excel)</h4>
                <p>Hasilnya satu file Excel untuk satu bulan, dengan sheet JAWA, TIMUR, dan SUMATERA seperti format yang dipakai admin. Kolom COGS, harga jual, dan pembayaran dibiarkan kosong untuk diisi finance.</p>
                <form method="get" action="export-monthly.php" class="form-inline">
                    <input type="hidden" name="download" value="1">
                    <label for="book">Pembukuan&nbsp;</label>
                    <select class="form-control" name="book" id="book"><option value="ALI"<?php echo $book === 'ALI' ? ' selected' : ''; ?>>ALI</option><option value="ANK"<?php echo $book === 'ANK' ? ' selected' : ''; ?>>ANK</option></select>
                    &nbsp;<label for="month">Bulan&nbsp;</label>
                    <input type="month" class="form-control" name="month" id="month" value="<?php echo htmlspecialchars($month); ?>" required>
                    &nbsp;<button type="submit" class="btn btn-success"><i class="fa fa-download"></i> Download Excel</button>
                </form>
                <p class="text-muted" style="margin-top:14px;font-size:12px">Pengiriman dimasukkan berdasarkan "Tanggal barang kami terima". Wilayah ditentukan otomatis dari kota tujuan.</p>
            </div>
        </div></div>
        <?php include("footer.php"); ?>
    </div>
</div>
</body>
</html>
