<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

require_once('database.php');
require_once('library.php');
require_once('funciones.php');

requireAdmin();

error_log("Session data in add-courier.php: " . print_r($_SESSION, true));

// Fetch offices
$offices = [];
try {
    $stmt = dbQuery("SELECT DISTINCT(off_name) FROM offices");
    while ($row = dbFetchAssoc($stmt)) {
        $offices[] = $row['off_name'];
    }
} catch (PDOException $e) {
    error_log("Offices query failed in add-courier.php: " . $e->getMessage());
}

// Fetch customers for datalist
$customers = [];
try {
    $stmt = dbQuery("SELECT Shippername FROM customer");
    while ($row = dbFetchAssoc($stmt)) {
        $customers[] = $row['Shippername'];
    }
    if (empty($customers)) {
        error_log("No customers found in add-courier.php.");
    }
} catch (PDOException $e) {
    error_log("Customer query failed in add-courier.php: " . $e->getMessage());
}

// Fetch modes
$modes = [];
try {
    $stmt = dbQuery("SELECT name FROM mode_bookings WHERE estado = 1 GROUP BY name");
    while ($row = dbFetchAssoc($stmt)) {
        $modes[] = $row['name'];
    }
    if (empty($modes)) {
        error_log("No mode bookings found in add-courier.php.");
    }
} catch (PDOException $e) {
    error_log("Mode bookings query failed in add-courier.php: " . $e->getMessage());
}

// Fetch company details
try {
    $stmt = dbQuery("SELECT * FROM company LIMIT 1");
    $company = dbFetchAssoc($stmt);
} catch (PDOException $e) {
    error_log("Company query failed in add-courier.php: " . $e->getMessage());
    $company = ['currency' => 'IDR'];
}

ob_end_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content=""/>
    <meta name="keywords" content="" />
    <meta name="author" content="">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <title>PT. ANEKA LINTAS INDONESIA</title>
    <link rel="icon" type="image/png" href="/public_html/logoAli.png">
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/simple-line-icons/css/simple-line-icons.css" type="text/css" />
    <link rel="stylesheet" href="css/font.css" type="text/css" />
    <link href="js/css/dataTables.bootstrap.css" rel="stylesheet">
    <link href="js/plugins/sweetalert/css/sweetalert.css" rel="stylesheet">
    <link rel="stylesheet" href="css/footer-basic-centered.css">
    <link href="assets/plugins/timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
    <link href="assets/plugins/mjolnic-bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
    <link href="assets/plugins/clockpicker/bootstrap-clockpicker.min.css" rel="stylesheet">
    <link href="assets/plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="../process/countries.js"></script>
    <style>
        .input-group { display: flex; align-items: center; }
        .input-group-addon {
            cursor: pointer;
            background: #f8f9fa;
            border: 1px solid #ced4da;
            border-left: none;
            padding: 5px;
        }
        .date-input { width: 150px !important; }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<style>
    .ac-page { max-width: 1060px; margin: 0 auto; padding-bottom: 30px; }
    .ac-title { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px; margin: 8px 0 14px; }
    .ac-title h3 { margin: 0; font-size: 1.5rem; font-weight: 600; }
    .ac-title span { color: #6b7280; font-size: .9rem; }
    .ac-card { background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 18px 20px 8px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
    .ac-card > h4 { margin: 0 0 14px; font-size: 1.05rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .ac-card > h4 i { color: #1f9d55; }
    .ac-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 0 16px; }
    .ac-f { margin-bottom: 14px; min-width: 0; }
    .ac-3 { grid-column: span 3; } .ac-4 { grid-column: span 4; } .ac-5 { grid-column: span 5; } .ac-6 { grid-column: span 6; } .ac-8 { grid-column: span 8; } .ac-12 { grid-column: span 12; }
    .ac-f label { display: block; font-size: .8rem; font-weight: 600; color: #444; margin-bottom: 4px; letter-spacing: .01em; }
    .ac-f label em { color: #c0392b; font-style: normal; }
    .ac-f .hint { display: block; margin-top: 3px; font-size: .78rem; color: #8a8f98; }
    .ac-f input[type=text], .ac-f input[type=url], .ac-f input[type=date], .ac-f input[type=number], .ac-f input[type=email], .ac-f select, .ac-f textarea {
        width: 100%; box-sizing: border-box; height: 40px; padding: 8px 11px; border: 1px solid #c9ced6; border-radius: 8px; font-size: .95rem; font-family: inherit; background: #fff; color: #222; }
    .ac-f textarea { height: auto; min-height: 76px; resize: vertical; }
    .ac-f input:focus, .ac-f select:focus, .ac-f textarea:focus, .ac-search:focus { outline: 2px solid #1f9d55; outline-offset: 1px; border-color: #1f9d55; }
    .ac-f input[readonly] { background: #f3f4f6; color: #555; }
    .ac-seg { display: inline-flex; background: #eef0f3; border-radius: 10px; padding: 3px; margin-bottom: 14px; }
    .ac-seg button { border: 0; background: transparent; padding: 8px 16px; border-radius: 8px; font-size: .9rem; cursor: pointer; color: #555; }
    .ac-seg button.on { background: #fff; color: #17824a; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,.12); }
    .ac-pick { position: relative; margin-bottom: 14px; }
    .ac-search { width: 100%; box-sizing: border-box; height: 44px; padding: 8px 14px 8px 38px; border: 1px solid #c9ced6; border-radius: 10px; font-size: 1rem; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='%23888' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E") no-repeat 12px center; }
    .ac-results { position: absolute; left: 0; right: 0; top: 48px; z-index: 50; background: #fff; border: 1px solid #d5d9df; border-radius: 10px; box-shadow: 0 10px 28px rgba(0,0,0,.14); max-height: 300px; overflow-y: auto; display: none; }
    .ac-results.open { display: block; }
    .ac-res { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f0f1f3; }
    .ac-res:last-child { border-bottom: 0; }
    .ac-res.on, .ac-res:hover { background: #eaf7ef; }
    .ac-res b { display: block; font-weight: 600; }
    .ac-res small { color: #6b7280; }
    .ac-res .cd { float: right; font-size: .72rem; background: #eaf3ff; color: #1b5fb5; padding: 1px 8px; border-radius: 999px; font-weight: 600; }
    .ac-none { padding: 12px 14px; color: #6b7280; font-size: .92rem; }
    .ac-chip { display: none; align-items: center; justify-content: space-between; gap: 10px; background: #eaf7ef; border: 1px solid #b9e2c8; border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; }
    .ac-chip.on { display: flex; }
    .ac-chip b { font-weight: 600; } .ac-chip small { color: #4b6b59; display: block; }
    .ac-chip button { border: 0; background: #fff; border: 1px solid #b9e2c8; color: #17824a; border-radius: 8px; padding: 5px 12px; cursor: pointer; font-size: .85rem; }
    .ac-check { display: flex; align-items: center; gap: 8px; font-size: .9rem; color: #444; margin: 2px 0 14px; }
    .ac-check input { width: 17px; height: 17px; }
    .ac-items { overflow-x: auto; margin-bottom: 10px; }
    .ac-items table { width: 100%; border-collapse: collapse; min-width: 760px; }
    .ac-items th { text-align: left; font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; padding: 6px 6px 8px; }
    .ac-items td { padding: 4px 6px 4px 0; }
    .ac-items td input { width: 100%; box-sizing: border-box; height: 38px; padding: 6px 9px; border: 1px solid #c9ced6; border-radius: 8px; font-size: .92rem; }
    .ac-items td input:focus { outline: 2px solid #1f9d55; outline-offset: 1px; border-color: #1f9d55; }
    .ac-items .remove-row { height: 38px; width: 38px; border: 1px solid #e6b5af; background: #fff; color: #c0392b; border-radius: 8px; cursor: pointer; font-size: 1.1rem; line-height: 1; }
    .ac-items .remove-row:hover { background: #fdecea; }
    .ac-btn { border: 0; border-radius: 9px; padding: 10px 18px; font-size: .95rem; cursor: pointer; background: #1f9d55; color: #fff; }
    .ac-btn:hover { background: #17824a; }
    .ac-btn.sec { background: #eef0f3; color: #333; }
    .ac-btn.sec:hover { background: #e0e4e9; }
    .ac-totals { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 14px 0 6px; }
    .ac-save { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: space-between; }
    .ac-save .ac-f { flex: 1 1 320px; margin-bottom: 14px; }
    .ac-save .ac-btn { padding: 12px 28px; font-size: 1rem; margin-bottom: 14px; }
    .ac-who { color: #6b7280; font-size: .85rem; }
    @media (max-width: 820px) { .ac-3, .ac-4, .ac-5, .ac-6, .ac-8 { grid-column: span 12; } .ac-totals { grid-template-columns: 1fr; } }
</style>
<div class="wrapper" id="main">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <div class="ac-page">
        <div class="ac-title">
            <h3><i class="fa fa-truck"></i> Tambah pengiriman</h3>
            <span class="ac-who">Dicatat oleh <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Unknown'); ?> (<?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Unknown'); ?>)</span>
        </div>

        <form action="process.php?action=add-cons" name="formulario" method="post" id="courierForm" autocomplete="off">
            <input type="hidden" name="officename" id="officename" value="<?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Unknown'); ?>">
            <input type="hidden" name="user" id="user" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Unknown'); ?>">
            <input type="hidden" name="customer_id" id="customer_id" value="">

            <!-- 1. PENGIRIM -->
            <section class="ac-card">
                <h4><i class="fa fa-user"></i> Data pengirim</h4>
                <div class="ac-seg" role="tablist" aria-label="Cara mengisi data pengirim">
                    <button type="button" class="on" id="mode-existing" role="tab" aria-selected="true">Customer terdaftar</button>
                    <button type="button" id="mode-manual" role="tab" aria-selected="false">Isi manual</button>
                </div>

                <div id="pick-wrap">
                    <div class="ac-pick" id="pick-box">
                        <input type="text" class="ac-search" id="cp-q" placeholder="Cari nama PT, PIC, kode, atau nomor..." aria-label="Cari customer terdaftar" autocomplete="off">
                        <div class="ac-results" id="cp-results" role="listbox"></div>
                    </div>
                    <div class="ac-chip" id="cp-chip"><div><b id="chip-name"></b><small id="chip-sub"></small></div><button type="button" id="chip-change">Ganti</button></div>
                </div>

                <div class="ac-grid">
                    <div class="ac-f ac-8">
                        <label for="ship_name">Nama pengirim / PT <em>*</em></label>
                        <input type="text" name="ship_name" id="ship_name" required placeholder="Nama PT atau nama pengirim">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="shipper_cc">Kode / ID customer</label>
                        <input type="text" name="shipper_cc" id="shipper_cc" maxlength="20" placeholder="opsional, mis. KPM1">
                    </div>
                    <div class="ac-f ac-6">
                        <label for="shipper_pic">Nama PIC / Sales</label>
                        <input type="text" name="shipper_pic" id="shipper_pic" maxlength="120" placeholder="opsional">
                    </div>
                    <div class="ac-f ac-6">
                        <label for="shipper_phone">Nomor kontak <em>*</em></label>
                        <input type="text" name="shipper_phone" id="shipper_phone" required inputmode="tel" placeholder="0812xxxxxxx">
                    </div>
                    <div class="ac-f ac-12">
                        <label for="shipper_address">Alamat pengirim <em>*</em></label>
                        <input type="text" name="shipper_address" id="shipper_address" required placeholder="Alamat lengkap pengirim">
                    </div>
                </div>
                <label class="ac-check" id="save-cust-wrap" style="display:none"><input type="checkbox" name="save_customer" value="1" id="save_customer"> Simpan juga sebagai customer baru</label>
            </section>

            <!-- 2. PENERIMA -->
            <section class="ac-card">
                <h4><i class="fa fa-map-marker"></i> Data penerima</h4>
                <div class="ac-grid">
                    <div class="ac-f ac-8">
                        <label for="rev_name">Nama penerima <em>*</em></label>
                        <input type="text" name="rev_name" id="rev_name" required placeholder="Nama penerima">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="receiver_cc">Kode / ID</label>
                        <input type="text" name="receiver_cc" id="receiver_cc" maxlength="20" placeholder="opsional">
                    </div>
                    <div class="ac-f ac-6">
                        <label for="receiver_phone">Nomor kontak <em>*</em></label>
                        <input type="text" name="receiver_phone" id="receiver_phone" required inputmode="tel" placeholder="0812xxxxxxx">
                    </div>
                    <div class="ac-f ac-6">
                        <label for="receiver_email">Email <em>*</em></label>
                        <input type="text" name="receiver_email" id="receiver_email" required placeholder="nama@perusahaan.com">
                        <span class="hint">Harus benar, dipakai untuk pemberitahuan pengiriman.</span>
                    </div>
                    <div class="ac-f ac-12">
                        <label for="receiver_address">Alamat penerima <em>*</em></label>
                        <input type="text" name="receiver_address" id="receiver_address" required placeholder="Alamat lengkap penerima">
                    </div>
                </div>
            </section>

            <!-- 3. INFORMASI PENGIRIMAN -->
            <section class="ac-card">
                <h4><i class="fa fa-info-circle"></i> Informasi pengiriman</h4>
                <div class="ac-grid">
                    <div class="ac-f ac-4">
                        <label for="book_mode">Mode pembayaran</label>
                        <select name="book_mode" id="book_mode">
                            <option selected="selected" value="Paid">Dibayar</option>
                            <option value="ToPay">Untuk Dibayar</option>
                        </select>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="mode">Service (Mode) <em>*</em></label>
                        <select name="mode" id="mode" required>
                            <option value="">Pilih</option>
                            <?php foreach ($modes as $mode_option): ?>
                                <option value="<?php echo htmlspecialchars($mode_option); ?>"><?php echo htmlspecialchars($mode_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="ship_type">Produk / tipe barang <em>*</em></label>
                        <input type="text" name="ship_type" id="ship_type" required placeholder="mis. Sparepart, Elektronik">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="office">Kantor asal</label>
                        <select name="office" id="office">
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="pickup_time">Kota tujuan <em>*</em></label>
                        <input type="text" name="pickup_time" id="pickup_time" required placeholder="mis. Surabaya">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="total_declarate"><?php echo htmlspecialchars($company['currency'] ?? 'IDR'); ?> Asuransi pengiriman</label>
                        <input type="text" name="total_declarate" id="total_declarate" maxlength="20" inputmode="numeric" placeholder="0">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="pick_date">Tanggal pengumpulan</label>
                        <input type="date" name="pick_date" id="pick_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="schedule">Jadwal pengiriman</label>
                        <input type="date" name="schedule" id="schedule">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option selected="selected" value="In-Transit">On Delivery</option>
                        </select>
                    </div>
                    <div class="ac-f ac-12">
                        <label for="tracking_link">Tracking link (opsional)</label>
                        <input type="url" name="tracking_link" id="tracking_link" placeholder="https://...">
                    </div>
                    <div class="ac-f ac-12">
                        <label for="comments">Catatan pengiriman</label>
                        <textarea name="comments" id="comments" placeholder="Catatan tambahan tentang pengiriman"></textarea>
                    </div>
                </div>
            </section>

            <!-- 4. RINCIAN BARANG -->
            <section class="ac-card">
                <h4><i class="fa fa-cubes"></i> Rincian barang</h4>
                <div class="ac-items">
                    <table id="items-table">
                        <thead>
                            <tr><th style="width:26%">Nama barang</th><th>Qty</th><th>Panjang (cm)</th><th>Lebar (cm)</th><th>Tinggi (cm)</th><th>Berat (Kg)</th><th>Volume (m³)</th><th></th></tr>
                        </thead>
                        <tbody id="item-rows">
                            <tr>
                                <td><input type="text" name="item_name[]" required></td>
                                <td><input type="number" name="item_quantity[]" class="qty" value="1" min="1" required></td>
                                <td><input type="number" name="item_length[]" class="length" value="0" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_width[]" class="width" value="0" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_height[]" class="height" value="0" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_weight[]" class="weight" step="0.0001" value="0" min="0"></td>
                                <td><input type="number" step="0.0001" name="item_volume[]" class="volume" placeholder="otomatis" min="0"></td>
                                <td><button type="button" class="remove-row" aria-label="Hapus baris">&times;</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="ac-btn sec" id="add-row">+ Tambah barang</button>
                <div class="ac-totals">
                    <div class="ac-f"><label>Total quantity</label><input type="text" name="qty" id="total-quantity" readonly value="0"></div>
                    <div class="ac-f"><label>Total berat (Kg)</label><input type="text" name="weight" id="total-weight" readonly value="0"></div>
                    <div class="ac-f"><label>Total volume (m³)</label><input type="text" name="total_volume" id="total-volume" readonly value="0"></div>
                </div>
            </section>

            <!-- 5. NOMOR TRACKING + SIMPAN -->
            <section class="ac-card">
                <div class="ac-save">
                    <div class="ac-f">
                        <label for="cons_no">Nomor tracking</label>
                        <input type="text" name="cons_no" id="cons_no" value="ALI<?php echo rand(1, 1000000000000); ?>">
                    </div>
                    <input class="ac-btn" name="Submit" type="submit" id="submit" value="Simpan pengiriman">
                </div>
            </section>

            <input type="hidden" name="variable" value="0">
            <input type="hidden" name="freight" value="0">
            <input type="hidden" name="Totalfreight" value="0">
            <input type="hidden" name="shipping_subtotal" value="0">
            <input type="hidden" name="status_delivered" value="Pending">
        </form>
        </div>
    </div>
        <?php include("footer.php"); ?>
    </div>
    <script src="assets/js/tether.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.nicescroll.js"></script>
    <script src="assets/plugins/switchery/switchery.min.js"></script>
    <script src="assets/plugins/moment/moment.js"></script>
    <script src="assets/plugins/timepicker/bootstrap-timepicker.min.js"></script>
    <script src="assets/plugins/mjolnic-bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
    <script src="assets/plugins/clockpicker/bootstrap-clockpicker.js"></script>
    <script src="assets/plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
    <script language="javascript" type="text/javascript">
        function calcula() {
            with (document.formulario) {
                var tempResult = Math.round(qty.value * weight.value * variable.value * Totalfreight.value * 100);
                var integerDigits = Math.floor(tempResult/100);
                var decimalDigits = "" + (tempResult - integerDigits * 100);
                while (decimalDigits.length<2) {
                    decimalDigits = "0"+decimalDigits;
                }
                shipping_subtotal.value = integerDigits + "," + decimalDigits + " ";
            }
        }            
    </script>
    <script>
        function hitungVolume(p, l, t) {
            return (p * l * t) / 1000000; // cm³ to m³
        }
        function updateVolumeForRow(row) {
            const p = parseFloat(row.querySelector(".length").value) || 0;
            const l = parseFloat(row.querySelector(".width").value) || 0;
            const t = parseFloat(row.querySelector(".height").value) || 0;
            const volumeField = row.querySelector(".volume");
            const volumeManual = volumeField.dataset.manual;
            if (volumeManual && volumeManual !== "") {
                volumeField.value = parseFloat(volumeManual).toFixed(10); // Increased precision to 10 decimals
                return;
            }
            const volume = hitungVolume(p, l, t);
            volumeField.value = volume.toFixed(10); // Increased precision to 10 decimals
        }
        function updateTotals() {
            let totalQuantity = 0;
            let totalWeight = 0;
            let totalVolume = 0;
            document.querySelectorAll("#item-rows tr").forEach(row => {
                const qty = parseFloat(row.querySelector(".qty").value) || 0;
                const berat = parseFloat(row.querySelector(".weight").value) || 0;
                const volumeField = row.querySelector(".volume");
                const volume = parseFloat(volumeField.value) || 0; // Ensure volume is parsed as float
                totalQuantity += qty;
                totalWeight += qty * berat;
                totalVolume += qty * volume; // Aggregate even small volumes
            });
            document.getElementById("total-quantity").value = totalQuantity.toFixed(0);
            document.getElementById("total-weight").value = totalWeight.toFixed(6);
            document.getElementById("total-volume").value = totalVolume.toFixed(10); // Increased precision to 10 decimals
            document.formulario.qty.value = totalQuantity;
            document.formulario.weight.value = totalWeight.toFixed(6);
            document.formulario.total_volume.value = totalVolume.toFixed(10);
        }
        document.addEventListener("input", function (e) {
            const row = e.target.closest("tr");
            if (!row) return;
            if (
                e.target.classList.contains("length") ||
                e.target.classList.contains("width") ||
                e.target.classList.contains("height")
            ) {
                row.querySelector(".volume").removeAttribute("data-manual");
                updateVolumeForRow(row);
            }
            if (e.target.classList.contains("volume")) {
                if (e.target.value !== "") {
                    e.target.dataset.manual = e.target.value;
                } else {
                    e.target.removeAttribute("data-manual");
                    updateVolumeForRow(row);
                }
            }
            updateTotals(); // Ensure totals update on every input change
        });
        document.getElementById("add-row").addEventListener("click", () => {
            const firstRow = document.querySelector("#item-rows tr");
            const newRow = firstRow.cloneNode(true);
            newRow.querySelectorAll("input").forEach(input => {
                if (input.classList.contains("qty")) input.value = 1;
                else if (input.name === "item_name[]") input.value = "";
                else input.value = 0;
                input.removeAttribute("data-manual");
            });
            // Update step attribute for new row inputs
            newRow.querySelector(".length").setAttribute("step", "0.0001");
            newRow.querySelector(".width").setAttribute("step", "0.0001");
            newRow.querySelector(".height").setAttribute("step", "0.0001");
            newRow.querySelector(".weight").setAttribute("step", "0.0001");
            document.getElementById("item-rows").appendChild(newRow);
            updateVolumeForRow(newRow);
            updateTotals();
        });
        document.getElementById("items-table").addEventListener("click", function (e) {
            if (e.target.classList.contains("remove-row")) {
                const rows = document.querySelectorAll("#item-rows tr");
                if (rows.length > 1) {
                    e.target.closest("tr").remove();
                    updateTotals();
                }
            }
        });
        $(document).ready(function() {
            updateVolumeForRow(document.querySelector("#item-rows tr"));
            updateTotals();
            // Handle form submission with AJAX
            $('#courierForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                console.log('Submitting form with data:', formData);
                $.ajax({
                    url: 'process.php?action=add-cons',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    success: function(res) {
                        console.log('AJAX Response:', res);
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message || 'Pengiriman berhasil ditambahkan.',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Refresh the page
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: res.message || 'Terjadi kesalahan saat menyimpan data.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', status, error, xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan AJAX',
                            text: 'Error: ' + status + ' - ' + (xhr.responseText || 'No response')
                        });
                    }
                });
            });
        });
    </script>
<script>
// Pilih customer terdaftar atau isi manual
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var q = $('cp-q'), box = $('cp-results'), chip = $('cp-chip'), pickBox = $('pick-box');
  var fields = { name: $('ship_name'), phone: $('shipper_phone'), addr: $('shipper_address'), pic: $('shipper_pic'), code: $('shipper_cc') };
  var items = [], active = -1, timer = null, seq = 0, mode = 'existing', selectedId = '';

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function render(list, term) {
    items = list; active = -1;
    if (!list.length) {
      box.innerHTML = '<div class="ac-none">' + (term ? 'Tidak ada customer yang cocok. Pindah ke "Isi manual" untuk customer baru.' : 'Belum ada customer terdaftar. Isi manual, atau tambah dulu di menu CUSTOMER.') + '</div>';
    } else {
      box.innerHTML = list.map(function (c, i) {
        var sub = [c.pic_name, c.pic_phone].filter(Boolean).join(' · ');
        return '<div class="ac-res" role="option" data-i="' + i + '">' + (c.code ? '<span class="cd">' + esc(c.code) + '</span>' : '') + '<b>' + esc(c.company_name) + '</b><small>' + esc(sub || c.address || '') + '</small></div>';
      }).join('');
    }
    box.classList.add('open');
  }

  function search(term) {
    var my = ++seq;
    fetch('customer-profile-api.php?action=search&limit=8&q=' + encodeURIComponent(term), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) { if (my === seq && res.success) render(res.items || [], term); })
      .catch(function () { box.innerHTML = '<div class="ac-none">Tidak bisa menghubungi server. Coba lagi, atau isi manual.</div>'; box.classList.add('open'); });
  }

  function pick(c) {
    selectedId = String(c.id); $('customer_id').value = selectedId;
    fields.name.value = c.company_name || '';
    fields.phone.value = c.pic_phone || '';
    fields.addr.value = c.address || '';
    fields.pic.value = c.pic_name || '';
    fields.code.value = c.code || '';
    $('chip-name').textContent = c.company_name + (c.code ? ' (' + c.code + ')' : '');
    $('chip-sub').textContent = [c.pic_name, c.pic_phone].filter(Boolean).join(' · ') || 'Data customer dimuat';
    box.classList.remove('open'); pickBox.style.display = 'none'; chip.classList.add('on'); q.value = '';
    // lanjut ke isian yang masih kosong, kalau tidak ada ke penerima
    var empty = [fields.phone, fields.addr].filter(function (f) { return !f.value; })[0];
    (empty || $('rev_name')).focus();
  }

  function clearPick(focus) {
    selectedId = ''; $('customer_id').value = '';
    chip.classList.remove('on'); pickBox.style.display = '';
    if (focus) { q.focus(); }
  }

  function setMode(m) {
    mode = m;
    var ex = m === 'existing';
    $('mode-existing').classList.toggle('on', ex); $('mode-manual').classList.toggle('on', !ex);
    $('mode-existing').setAttribute('aria-selected', ex); $('mode-manual').setAttribute('aria-selected', !ex);
    $('pick-wrap').style.display = ex ? '' : 'none';
    $('save-cust-wrap').style.display = ex ? 'none' : '';
    if (!ex) { $('customer_id').value = ''; box.classList.remove('open'); fields.name.focus(); }
    else { $('save_customer').checked = false; if (!chip.classList.contains('on')) { q.focus(); } else { $('customer_id').value = selectedId; } }
  }

  q.addEventListener('input', function () { clearTimeout(timer); var t = q.value.trim(); timer = setTimeout(function () { search(t); }, 180); });
  q.addEventListener('focus', function () { search(q.value.trim()); });
  q.addEventListener('keydown', function (e) {
    var rows = box.querySelectorAll('.ac-res');
    if (e.key === 'ArrowDown' && rows.length) { e.preventDefault(); active = (active + 1) % rows.length; }
    else if (e.key === 'ArrowUp' && rows.length) { e.preventDefault(); active = (active - 1 + rows.length) % rows.length; }
    else if (e.key === 'Enter') { e.preventDefault(); if (items[active]) { pick(items[active]); } else if (items.length === 1) { pick(items[0]); } return; }
    else if (e.key === 'Escape') { box.classList.remove('open'); return; }
    else { return; }
    Array.prototype.forEach.call(rows, function (r, i) { r.classList.toggle('on', i === active); if (i === active) { r.scrollIntoView({ block: 'nearest' }); } });
  });
  box.addEventListener('mousedown', function (e) {
    var r = e.target.closest ? e.target.closest('.ac-res') : null;
    if (r && items[+r.getAttribute('data-i')]) { e.preventDefault(); pick(items[+r.getAttribute('data-i')]); }
  });
  document.addEventListener('click', function (e) { if (!pickBox.contains(e.target)) { box.classList.remove('open'); } });
  $('chip-change').addEventListener('click', function () { clearPick(true); });
  $('mode-existing').addEventListener('click', function () { setMode('existing'); });
  $('mode-manual').addEventListener('click', function () { setMode('manual'); });
})();
</script>
</body>
</html>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= htmlspecialchars($_GET["msg"] ?? "Data berhasil disimpan.") ?>',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    </script>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top:15px;">
        <strong>Sukses!</strong> <?= isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Pengiriman berhasil ditambahkan.' ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
            <span aria-hidden="true">×</span>
        </button>
    </div>
<?php endif; ?>