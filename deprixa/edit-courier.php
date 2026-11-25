<?php
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Enable for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

// Initialize session
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }

// Include required files with output buffering
ob_start();
require_once('database.php');
$databaseOutput = ob_get_clean();

ob_start();
require_once('library.php');
$libraryOutput = ob_get_clean();

ob_start();
require_once('funciones.php');
$funcionesOutput = ob_get_clean();

// Log any unexpected output from included files
if (!empty($databaseOutput)) {
    error_log("Unexpected output from database.php: " . $databaseOutput);
}
if (!empty($libraryOutput)) {
    error_log("Unexpected output from library.php: " . $libraryOutput);
}
if (!empty($funcionesOutput)) {
    error_log("Unexpected output from funciones.php: " . $funcionesOutput);
}

// Authenticate user
ob_start();
requireAdmin();
$userOutput = ob_get_clean();
if (!empty($userOutput)) {
    error_log("Unexpected output from isUser(): " . $userOutput);
}

// Clear any existing output buffer
ob_clean();

// Main logic
require_once __DIR__ . '/courier-lib.php';
require_once __DIR__ . '/city-lib.php';
require_once __DIR__ . '/eta-lib.php';
courier_columns_ready();
$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
if ($cid <= 0 && !empty($_POST['cons'])) {
    $cons_no = limpiar($_POST['cons']);
    error_log("Retrieving courier with cons_no: $cons_no");
    $sql = "SELECT * FROM courier WHERE cons_no = ?";
    $stmt = dbQuery($sql, [$cons_no]);
} else {
    error_log("Retrieving courier with cid: $cid");
    $sql = "SELECT * FROM courier WHERE cid = ?";
    $stmt = dbQuery($sql, [$cid]);
}
$count = dbNumRows($stmt);

// Check if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if ($count == 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'No courier found with the provided ID or tracking number.']);
        exit;
    }
    exit;
}

if ($count > 0) {
    $data = dbFetchAssoc($stmt);
    // (log data dimatikan) error_log("Retrieved data from courier table: " . print_r($data, true));
    extract($data);

    // Fetch company details
    $sql_company = "SELECT * FROM company LIMIT 1";
    $stmt_company = dbQuery($sql_company);
    $company = dbFetchAssoc($stmt_company);

    // Fetch items
    $sql_items = "SELECT * FROM courier_items WHERE courier_id = ?";
    $stmt_items = dbQuery($sql_items, [$cid]);
    $items = [];
    $total_quantity = 0;
    $total_weight = 0;
    $total_volume = 0;
    while ($item = dbFetchAssoc($stmt_items)) {
        $items[] = $item;
        $total_quantity += $item['quantity'];
        $total_weight += $item['quantity'] * $item['weight'];
        $total_volume += $item['quantity'] * $item['volume'];
    }
    // (log data dimatikan) error_log("Retrieved items: " . print_r($items, true));

    // Fetch modes
    $modes = [];
    try {
        $stmt_modes = dbQuery("SELECT name FROM mode_bookings WHERE estado = 1 GROUP BY name");
        while ($row = dbFetchAssoc($stmt_modes)) {
            $modes[] = $row['name'];
        }
        if (empty($modes)) {
            error_log("No active mode bookings found in edit_courier.php.");
        }
    } catch (PDOException $e) {
        error_log("Mode bookings query failed in edit_courier.php: " . $e->getMessage());
    }
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
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/plugins/timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
    <link href="assets/plugins/mjolnic-bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
    <link href="assets/plugins/clockpicker/bootstrap-clockpicker.min.css" rel="stylesheet">
    <link href="assets/plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="../process/countries.js"></script>
    <style>
        .input-group { display: flex; align-items: center; }
        .input-group-addon { cursor: pointer; background: #f8f9fa; border: 1px solid #ced4da; border-left: none; padding: 5px; }
        .date-input { width: 150px !important; }
        .datetime-input { width: 200px !important; }
        .error { border: 1px solid red; }
        .progress-container { position: relative; width: 100%; height: 30px; margin-bottom: 10px; }
        .progress { height: 100%; background-color: #f0f0f0; border-radius: 5px; overflow: hidden; position: relative; }
        .progress-bar { height: 100%; background-color: #4CAF50; transition: width 1s ease-in-out; position: relative; display: flex; align-items: center; justify-content: flex-end; }
        .progress-text { color: white; margin-right: 10px; font-weight: bold; }
        .cartoon-box { position: absolute; height: 40px; width: auto; top: -10px; transform: translateX(-50%); transition: left 1s ease-in-out; }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="wrapper">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <?php
            $pt = (string)($payment_term ?? '');
            $isLegacyTempo = ($book_mode ?? '') === 'ToPay' && !in_array($pt, ['7', '14', '30'], true);
            $curTerm = in_array($pt, ['7', '14', '30'], true) ? $pt : (($book_mode ?? '') === 'ToPay' ? 'keep' : 'cash');
            $shipDate = ali_ship_date(['collection_date' => $collection_date ?? '', 'pick_date' => $pick_date ?? '']);
        ?>
        <div class="ac-page">
        <div class="ac-title">
            <h3><i class="fa fa-pencil-square-o"></i> Edit pengiriman <small style="color:#6b7280;font-weight:400"><?php echo htmlspecialchars($cons_no ?? ''); ?></small></h3>
            <span class="ac-who">Login sebagai <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Unknown'); ?></span>
        </div>

        <form action="process.php?action=update-addcourier" name="formulario" id="formulario" method="post" autocomplete="off">
            <input type="hidden" name="cid" value="<?php echo (int)$cid; ?>">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status ?? ''); ?>">
            <input type="hidden" name="book_mode" value="<?php echo htmlspecialchars($book_mode ?? ''); ?>">
            <input type="hidden" name="ship_type" value="<?php echo htmlspecialchars($ship_type ?? ''); ?>">
            <input type="hidden" name="office" value="<?php echo htmlspecialchars($office ?? ''); ?>">
            <input type="hidden" name="total_declarate" value="<?php echo htmlspecialchars((string)($total_declarate ?? 0)); ?>">
            <input type="hidden" name="shipping_subtotal" value="<?php echo htmlspecialchars((string)($shipping_subtotal ?? 0)); ?>">

            <section class="ac-card">
                <h4><i class="fa fa-user"></i> Data pengirim</h4>
                <div class="ac-grid">
                    <div class="ac-f ac-6">
                        <label for="ship_name">Nama pengirim / PT <em>*</em></label>
                        <input type="text" name="ship_name" id="ship_name" required value="<?php echo htmlspecialchars($ship_name ?? ''); ?>">
                    </div>
                    <div class="ac-f ac-3">
                        <label for="shipper_phone">Nomor kontak</label>
                        <input type="text" name="shipper_phone" id="shipper_phone" inputmode="tel" value="<?php echo htmlspecialchars($shipper_phone ?? ''); ?>">
                    </div>
                    <div class="ac-f ac-3">
                        <label for="shipper_cc">Kode customer</label>
                        <input type="text" name="shipper_cc" id="shipper_cc" value="<?php echo htmlspecialchars($shipper_cc ?? ''); ?>">
                    </div>
                    <div class="ac-f ac-12">
                        <label for="shipper_address">Alamat pengirim</label>
                        <input type="text" name="shipper_address" id="shipper_address" value="<?php echo htmlspecialchars($shipper_address ?? ''); ?>">
                    </div>
                </div>
            </section>

            <section class="ac-card">
                <h4><i class="fa fa-map-marker"></i> Data penerima</h4>
                <div class="ac-grid">
                    <div class="ac-f ac-6">
                        <label for="rev_name">Nama penerima <em>*</em></label>
                        <input type="text" name="rev_name" id="rev_name" required value="<?php echo htmlspecialchars($rev_name ?? ''); ?>">
                    </div>
                    <div class="ac-f ac-3">
                        <label for="receiver_phone">Nomor kontak</label>
                        <input type="text" name="receiver_phone" id="receiver_phone" inputmode="tel" value="<?php echo htmlspecialchars($receiver_phone ?? ''); ?>">
                    </div>
                    <div class="ac-f ac-3">
                        <label for="receiver_cc">Kode customer</label>
                        <input type="text" name="receiver_cc" id="receiver_cc" value="<?php echo htmlspecialchars($receiver_cc ?? ''); ?>">
                    </div>
                    <div class="ac-f ac-6">
                        <label for="receiver_email">Email</label>
                        <input type="text" name="receiver_email" id="receiver_email" value="<?php echo htmlspecialchars($receiver_email ?? ''); ?>">
                    </div>
                    <div class="ac-f ac-6">
                        <label for="receiver_address">Alamat penerima</label>
                        <input type="text" name="receiver_address" id="receiver_address" value="<?php echo htmlspecialchars($receiver_address ?? ''); ?>">
                    </div>
                </div>
            </section>

            <section class="ac-card">
                <h4><i class="fa fa-info-circle"></i> Informasi pengiriman</h4>
                <div class="ac-grid">
                    <div class="ac-f ac-3">
                        <label for="book_code">Pembukuan</label>
                        <select name="book_code" id="book_code">
                            <option value="ALI" <?php echo (($book_code ?? 'ALI') !== 'ANK') ? 'selected' : ''; ?>>ALI</option>
                            <option value="ANK" <?php echo (($book_code ?? '') === 'ANK') ? 'selected' : ''; ?>>ANK</option>
                        </select>
                    </div>
                    <div class="ac-f ac-3">
                        <label for="spb_no">Nomor SPB</label>
                        <input type="number" min="1" name="spb_no" id="spb_no" value="<?php echo htmlspecialchars((string)($spb_no ?? '')); ?>">
                    </div>
                    <div class="ac-f ac-3">
                        <label for="spb_return">SPB kembali (tanggal)</label>
                        <input type="date" name="spb_return" id="spb_return" value="<?php echo htmlspecialchars((string)($spb_return ?? '')); ?>">
                    </div>
                    <div class="ac-f ac-3">
                        <label for="cons_no">Nomor tracking</label>
                        <input type="text" name="cons_no" id="cons_no" readonly value="<?php echo htmlspecialchars($cons_no ?? ''); ?>">
                        <span class="hint">Ikut pembukuan + nomor SPB.</span>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="payment_term">Metode pembayaran</label>
                        <select name="payment_term" id="payment_term">
                            <option value="cash" <?php echo $curTerm === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="7" <?php echo $curTerm === '7' ? 'selected' : ''; ?>>Tempo 7 hari</option>
                            <option value="14" <?php echo $curTerm === '14' ? 'selected' : ''; ?>>Tempo 14 hari</option>
                            <option value="30" <?php echo $curTerm === '30' ? 'selected' : ''; ?>>Tempo 30 hari</option>
                            <?php if ($isLegacyTempo): ?><option value="keep" selected>Tempo (lama belum diatur)</option><?php endif; ?>
                        </select>
                        <span class="hint">Tempo diaktifkan dari Daftar Pengiriman yang untuk dibayar.</span>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="mode">Service (Mode) <em>*</em></label>
                        <select name="mode" id="mode" required>
                            <option value="">Pilih</option>
                            <?php foreach ($modes as $mode_option): ?>
                                <option value="<?php echo htmlspecialchars($mode_option); ?>" <?php echo (($mode ?? '') === $mode_option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mode_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="origin_city">Kota asal</label>
                        <input type="text" name="origin_city" id="origin_city" list="kota-list" autocomplete="off" value="<?php echo htmlspecialchars((string)($origin_city ?? '')); ?>" placeholder="Pilih atau ketik kota">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="pickup_time">Kota tujuan <em>*</em></label>
                        <input type="text" name="pickup_time" id="pickup_time" list="kota-list" autocomplete="off" required value="<?php echo htmlspecialchars($pickup_time ?? ''); ?>" placeholder="Pilih atau ketik kota">
                    </div>
                    <?php echo ali_city_datalist('kota-list'); ?>
                    <div class="ac-f ac-4">
                        <label for="partner">Via / Partner</label>
                        <input type="text" name="partner" id="partner" value="<?php echo htmlspecialchars((string)($partner ?? '')); ?>" placeholder="Ekspedisi / mitra">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="charge_unit">Dihitung per</label>
                        <select name="charge_unit" id="charge_unit">
                            <option value="kg" <?php echo (($charge_unit ?? 'kg') !== 'm3') ? 'selected' : ''; ?>>Kg (berat)</option>
                            <option value="m3" <?php echo (($charge_unit ?? '') === 'm3') ? 'selected' : ''; ?>>M&sup3; (volume)</option>
                        </select>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="pick_date">Tanggal barang kami terima</label>
                        <input type="date" name="pick_date" id="pick_date" value="<?php echo htmlspecialchars($shipDate); ?>">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="schedule">Tanggal keberangkatan <span class="ac-opt">(opsional)</span></label>
                        <input type="date" name="schedule" id="schedule" value="<?php echo htmlspecialchars((string)($etd ?? '')); ?>">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="eta">Perkiraan tiba (ETA)</label>
                        <input type="date" name="eta" id="eta" value="<?php echo htmlspecialchars((string)($eta ?? '')); ?>">
                        <small id="eta-hint" class="ac-hint"></small>
                    </div>
                    <div class="ac-f ac-8">
                        <label for="tracking_link">Tracking link (opsional)</label>
                        <input type="text" name="tracking_link" id="tracking_link" value="<?php echo htmlspecialchars($tracking_link ?? ''); ?>" placeholder="https://...">
                    </div>
                    <div class="ac-f ac-12">
                        <label>Progres pengiriman</label>
                        <div class="ac-prog">
                            <div id="progress-bar" class="progress-bar" role="progressbar" style="width: <?php echo htmlspecialchars($progress_percentage ?? 0); ?>%;" aria-valuenow="<?php echo htmlspecialchars($progress_percentage ?? 0); ?>" aria-valuemin="0" aria-valuemax="100">
                                <span class="progress-text"><?php echo htmlspecialchars($progress_percentage ?? 0); ?>%</span>
                            </div>
                            <img id="cartoon-box" src="assets/images/cartoon-box.png" alt="" class="cartoon-box">
                        </div>
                        <input type="hidden" name="progress_percentage" id="progress_percentage" value="<?php echo htmlspecialchars($progress_percentage ?? 0); ?>">
                        <span class="hint">Dihitung dari jadwal pengiriman sampai ETA. Kalau dikosongkan, sistem mengisi otomatis.</span>
                    </div>
                    <div class="ac-f ac-12">
                        <label for="comments">Catatan pengiriman</label>
                        <textarea name="comments" id="comments"><?php echo htmlspecialchars($comments ?? ''); ?></textarea>
                    </div>
                </div>
            </section>

            <section class="ac-card">
                <h4><i class="fa fa-cubes"></i> Rincian barang</h4>
                <div class="ac-items">
                    <table id="items-table">
                        <thead>
                            <tr><th>Nama barang</th><th style="width:80px">Qty</th><th style="width:100px">P (cm)</th><th style="width:100px">L (cm)</th><th style="width:100px">T (cm)</th><th style="width:110px">Berat (kg)</th><th style="width:120px">Volume (m&sup3;)</th><th style="width:44px"></th></tr>
                        </thead>
                        <tbody id="item-rows">
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><input type="text" name="item_name[]" required value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>"></td>
                                <td><input type="number" name="item_quantity[]" class="qty" value="<?php echo htmlspecialchars($item['quantity'] ?? ''); ?>" min="1" required></td>
                                <td><input type="number" name="item_length[]" class="length" value="<?php echo htmlspecialchars($item['length'] ?? ''); ?>" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_width[]" class="width" value="<?php echo htmlspecialchars($item['width'] ?? ''); ?>" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_height[]" class="height" value="<?php echo htmlspecialchars($item['height'] ?? ''); ?>" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_weight[]" class="weight" step="0.0001" value="<?php echo htmlspecialchars($item['weight'] ?? ''); ?>" min="0"></td>
                                <td><input type="number" step="0.001" name="item_volume[]" class="volume" min="0" value="<?php echo ($item['volume'] ?? '') === '' ? '' : number_format((float)$item['volume'], 3, '.', ''); ?>"></td>
                                <td><button type="button" class="remove-row" title="Hapus baris">&times;</button></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($items)): ?>
                            <tr>
                                <td><input type="text" name="item_name[]" required value=""></td>
                                <td><input type="number" name="item_quantity[]" class="qty" value="1" min="1" required></td>
                                <td><input type="number" name="item_length[]" class="length" value="0" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_width[]" class="width" value="0" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_height[]" class="height" value="0" min="0" step="0.0001"></td>
                                <td><input type="number" name="item_weight[]" class="weight" step="0.0001" value="0" min="0"></td>
                                <td><input type="number" step="0.001" name="item_volume[]" class="volume" min="0" value="0"></td>
                                <td><button type="button" class="remove-row" title="Hapus baris">&times;</button></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="ac-btn sec" id="add-row">+ Tambah barang</button>
                <div class="ac-totals">
                    <div class="ac-f"><label>Total jumlah (koli)</label><input type="text" name="qty" id="total-quantity" readonly value="<?php echo $total_quantity; ?>"></div>
                    <div class="ac-f"><label>Total berat (kg)</label><input type="text" name="weight" id="total-weight" readonly value="<?php echo number_format((float)$total_weight, 3, '.', ''); ?>"></div>
                    <div class="ac-f"><label>Total volume (m&sup3;)</label><input type="text" name="total_volume" id="total-volume" readonly value="<?php echo number_format((float)$total_volume, 3, '.', ''); ?>"></div>
                </div>
            </section>

            <section class="ac-card">
                <div class="ac-save">
                    <span class="ac-sub" style="margin:0">Status pengiriman diubah di bagian "Perbarui status" di bawah.</span>
                    <input type="submit" class="ac-btn" name="Submit" id="submit" value="UPDATE TRACKING">
                </div>
            </section>
        </form>

        <section class="ac-card" id="finish-card">
            <h4><i class="fa fa-check-circle"></i> Selesaikan pengiriman</h4>
            <?php if (($status ?? '') === 'Delivered'): ?>
                <p class="ac-sub">Pengiriman ini sudah selesai (Delivered).</p>
            <?php else: ?>
                <p class="ac-sub">Tandai pengiriman <b><?php echo htmlspecialchars($cons_no ?? ''); ?></b> sudah sampai dan selesai. Status jadi Delivered, progres 100%, dan tercatat di riwayat.</p>
                <button type="button" class="ac-btn" id="btn-finish" data-cid="<?php echo (int)$cid; ?>">Selesaikan pengiriman</button>
            <?php endif; ?>
        </section>

        <section class="ac-card">
            <h4><i class="fa fa-flag"></i> Perbarui status</h4>
            <form action="process.php?action=update-status" method="post" name="frmShipment" id="frmShipment">
                <div class="ac-grid">
                    <div class="ac-f ac-4">
                        <label for="pick_time">Lokasi baru <em>*</em></label>
                        <textarea id="pick_time" required name="pick_time"><?php echo htmlspecialchars($pickup_time ?? ''); ?></textarea>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="st_status">Status baru</label>
                        <select name="status" id="st_status" required>
                            <?php if (!in_array($status ?? '', ['Finished', 'In-Transit', 'On-Hold', 'Landed', 'Delayed'], true)): ?>
                            <option value="" selected disabled>&mdash; pilih status &mdash;</option>
                            <?php endif; ?>
                            <option value="Finished" <?php echo ($status == 'Finished') ? 'selected' : ''; ?>>Finished</option>
                            <option value="In-Transit" <?php echo ($status == 'In-Transit') ? 'selected' : ''; ?>>On Delivery</option>
                            <option value="On-Hold" <?php echo ($status == 'On-Hold') ? 'selected' : ''; ?>>On Hold</option>
                            <option value="Landed" <?php echo ($status == 'Landed') ? 'selected' : ''; ?>>Arrived</option>
                            <option value="Delayed" <?php echo ($status == 'Delayed') ? 'selected' : ''; ?>>Delayed</option>
                        </select>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="bk_time">Tanggal dan jam <em>*</em></label>
                        <input type="datetime-local" name="bk_time" id="bk_time" required value="<?php echo htmlspecialchars(date('Y-m-d\TH:i')); ?>">
                    </div>
                    <div class="ac-f ac-12">
                        <label for="st_comments">Catatan</label>
                        <textarea name="comments" id="st_comments"><?php echo htmlspecialchars($comments ?? ''); ?></textarea>
                    </div>
                </div>
                <input type="submit" name="submit" class="ac-btn" value="UPDATE STATUS">
                <input name="user" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" type="hidden">
                <input name="cid" value="<?php echo (int)$cid; ?>" type="hidden">
                <input name="cons_no" value="<?php echo htmlspecialchars($cons_no ?? ''); ?>" type="hidden">
            </form>
        </section>

        <section class="ac-card">
            <h4><i class="fa fa-history"></i> Riwayat pengiriman</h4>
            <form method="post" action="delete_shipping_history.php" onsubmit="return confirm('Yakin ingin menghapus data terpilih?');">
                <div class="ac-table-wrap">
                    <table class="ac-table">
                        <thead>
                            <tr><th style="width:30px"><input type="checkbox" id="select-all"></th><th>Tracking</th><th>Lokasi</th><th>Status</th><th>Tanggal dan jam</th><th>Catatan</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = dbQuery("SELECT * FROM courier_track WHERE cid = ? AND cons_no = ? ORDER BY bk_time", [$cid, $cons_no]);
                            while ($row = dbFetchAssoc($stmt)) {
                            ?>
                            <tr>
                                <td><input type="checkbox" name="delete_ids[]" value="<?php echo (int)$row['id']; ?>"></td>
                                <td><?php echo htmlspecialchars($row['cons_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['pick_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td><?php echo htmlspecialchars($row['bk_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['comments']); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="ac-btn danger" style="margin:14px 0 8px">Hapus yang dipilih</button>
            </form>
            <?php echo ali_eta_script(); ?>
    <script>
                document.getElementById("select-all").onclick = function() {
                    document.getElementsByName("delete_ids[]").forEach(function(box) { box.checked = this.checked; }, this);
                };
            </script>
        </section>
        </div>
        <?php include("footer.php"); ?>
    </div>
</div>

    <script>
        $(document).ready(function() {
            console.log("Script loaded at <?php echo date('h:i A T, F d, Y', time()); ?>");

            // Function to update progress bar
            function updateProgress() {
                const etd = new Date('<?php echo htmlspecialchars(date('Y-m-d', strtotime($etd ?? 'now'))); ?>T00:00:00+07:00');
                const eta = new Date('<?php echo htmlspecialchars(date('Y-m-d', strtotime($eta ?? 'now + 1 day'))); ?>T00:00:00+07:00');
                const now = new Date();
                const totalDuration = eta - etd;
                let elapsed = now - etd;
                if (elapsed < 0) elapsed = 0; // Progress starts at 0% before ETD
                if (elapsed >= totalDuration || totalDuration <= 0) elapsed = totalDuration; // 100% at or after ETA
                let progress = totalDuration > 0 ? (elapsed / totalDuration) * 100 : 0;
                progress = Math.min(100, Math.max(0, progress));
                if ('<?php echo htmlspecialchars($status); ?>' === 'Delivered') progress = 100;
                if ('<?php echo htmlspecialchars($eta ?? ''); ?>' === '') progress = 20; // Default if ETA not set

                const progressBar = document.getElementById('progress-bar');
                const cartoonBox = document.getElementById('cartoon-box');
                if (progressBar && cartoonBox) {
                    progress = Math.round(progress);
                    progressBar.style.width = progress + '%';
                    progressBar.setAttribute('aria-valuenow', progress);
                    progressBar.querySelector('.progress-text').textContent = progress + '%';
                    const progressWidth = progressBar.offsetWidth;
                    const containerWidth = progressBar.parentElement.offsetWidth;
                    const boxPosition = (progress / 100) * containerWidth - (cartoonBox.offsetWidth / 2);
                    cartoonBox.style.left = Math.max(0, Math.min(boxPosition, containerWidth - cartoonBox.offsetWidth)) + 'px';
                }
                console.log('Progress updated - ETD:', etd, 'ETA:', eta, 'Now:', now, 'Progress:', progress + '%');
            }

            // Initial update and set interval
            updateProgress();
            setInterval(updateProgress, 60000); // Update every minute

            function hitungVolume(p, l, t) {
                return (p * l * t) / 1000000; // cm³ to m³
            }

            function updateVolumeForRow(row) {
                const p = parseFloat(row.find(".length").val()) || 0;
                const l = parseFloat(row.find(".width").val()) || 0;
                const t = parseFloat(row.find(".height").val()) || 0;
                const volumeField = row.find(".volume");
                const volumeManual = volumeField.data("manual");
                if (volumeManual && volumeManual !== "") {
                    volumeField.val(parseFloat(volumeManual).toFixed(3));
                    return;
                }
                const volume = hitungVolume(p, l, t);
                volumeField.val(volume.toFixed(3));
            }

            function updateTotals() {
                let totalQuantity = 0;
                let totalWeight = 0;
                let totalVolume = 0;
                $("#item-rows tr").each(function() {
                    const qty = parseFloat($(this).find(".qty").val()) || 0;
                    const berat = parseFloat($(this).find(".weight").val()) || 0;
                    const volume = parseFloat($(this).find(".volume").val()) || 0;
                    totalQuantity += qty;
                    totalWeight += qty * berat;
                    totalVolume += qty * volume;
                });
                $("#total-quantity").val(totalQuantity.toFixed(0));
                $("#total-weight").val(totalWeight.toFixed(3));
                $("#total-volume").val(totalVolume.toFixed(3));
                $("#formulario input[name='qty']").val(totalQuantity.toFixed(0));
                $("#formulario input[name='weight']").val(totalWeight.toFixed(3));
                $("#formulario input[name='total_volume']").val(totalVolume.toFixed(3));
            }

            // Add row button
            $("#add-row").on("click", function() {
                console.log("Add button clicked");
                const firstRow = $("#item-rows tr").first().clone();
                firstRow.find("input").each(function() {
                    if ($(this).hasClass("qty")) $(this).val(1);
                    else $(this).val(0);
                    $(this).removeAttr("data-manual");
                });
                firstRow.find(".length").attr("step", "0.0001");
                firstRow.find(".width").attr("step", "0.0001");
                firstRow.find(".height").attr("step", "0.0001");
                firstRow.find(".weight").attr("step", "0.0001");
                $("#item-rows").append(firstRow);
                updateVolumeForRow(firstRow);
                updateTotals();
            });

            // Remove row button
            $("#item-rows").on("click", ".remove-row", function() {
                console.log("Remove button clicked");
                if ($("#item-rows tr").length > 1) {
                    $(this).closest("tr").remove();
                    updateTotals();
                } else {
                    console.warn("Cannot remove the last row");
                }
            });

            // Input change handlers
            $("#item-rows").on("input", "input", function() {
                const row = $(this).closest("tr");
                if (row.length) {
                    if ($(this).hasClass("length") || $(this).hasClass("width") || $(this).hasClass("height")) {
                        row.find(".volume").removeAttr("data-manual");
                        updateVolumeForRow(row);
                    }
                    if ($(this).hasClass("volume")) {
                        if ($(this).val() !== "") {
                            $(this).data("manual", $(this).val());
                        } else {
                            $(this).removeAttr("data-manual");
                            updateVolumeForRow(row);
                        }
                    }
                    updateTotals();
                }
            });

            updateVolumeForRow($("#item-rows tr").first());
            updateTotals();

            // Form submission
            $('#formulario').on('submit', function(e) {
                e.preventDefault();
                if (!validateForm()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Please fill all required fields and ensure valid dates.'
                    });
                    return;
                }
                var formData = $(this).serialize();
                console.log('Submitting main form data:', formData);
                $.ajax({
                    url: 'process.php?action=update-addcourier',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    timeout: 30000,
                    beforeSend: function() {
                        $('#submit').prop('disabled', true).val('Processing...');
                    },
                    success: function(response) {
                        console.log('Main form AJAX Success:', response);
                        $('#submit').prop('disabled', false).val('UPDATE TRACKING');
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses!',
                                text: 'Update Berhasil',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Reload to reflect updated data
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message || 'Terjadi kesalahan. Silakan coba lagi.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Main form AJAX Error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            statusCode: xhr.status
                        });
                        $('#submit').prop('disabled', false).val('UPDATE TRACKING');
                        let errorMsg = 'Terjadi kesalahan. Silakan coba lagi. Check console (F12) for details. Status: ' + status + ', Code: ' + xhr.status;
                        if (xhr.responseText && xhr.responseText.includes('Fatal error')) {
                            errorMsg += ' Server error detected. Check php_error.log.';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan AJAX',
                            text: errorMsg
                        });
                    }
                });
            });

            function validateForm() {
                let isValid = true;
                const requiredFields = ['ship_name', 'rev_name', 'mode', 'pickup_time'];
                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (input && !input.value.trim()) {
                        input.classList.add('error');
                        isValid = false;
                    } else if (false) {
                        const date = new Date(input.value);
                        if (isNaN(date.getTime())) {
                            input.classList.add('error');
                            isValid = false;
                        } else {
                            input.classList.remove('error');
                        }
                    } else {
                        input.classList.remove('error');
                    }
                });
                document.querySelectorAll('#item-rows input[required]').forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('error');
                        isValid = false;
                    } else {
                        input.classList.remove('error');
                    }
                });
                const schedule = document.getElementById('schedule')?.value;
                const eta = document.getElementById('eta')?.value;
                if (schedule && eta && new Date(eta) <= new Date(schedule)) {
                    document.getElementById('eta').classList.add('error');
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'ETA must be after Schedule Delivery.'
                    });
                    isValid = false;
                }
                return isValid;
            }
        });
    </script>
    <script>
    // Perbarui status lewat AJAX supaya hasilnya tampil sebagai notifikasi, bukan data mentah.
    $(document).on('submit', '#frmShipment', function(e) {
        e.preventDefault();
        var f = $(this), btn = f.find('input[type=submit]');
        btn.prop('disabled', true).val('MENYIMPAN...');
        $.ajax({ url: 'process.php?action=update-status', type: 'POST', dataType: 'json', data: f.serialize(),
            success: function(res) {
                if (res && res.success) {
                    Swal.fire({ icon: 'success', title: 'Status diperbarui', text: res.message, timer: 1600, showConfirmButton: false }).then(function() { location.href = res.redirect || location.href; });
                } else {
                    btn.prop('disabled', false).val('UPDATE STATUS');
                    Swal.fire({ icon: 'error', title: 'Gagal memperbarui status', text: res ? String(res.message || '').replace(/^Error:\s*/, '') : 'Tidak ada respons dari server' });
                }
            },
            error: function() { btn.prop('disabled', false).val('UPDATE STATUS'); Swal.fire({ icon: 'error', title: 'Gagal menghubungi server' }); } });
    });
    </script>
    <script>
    $(document).on('click', '#btn-finish', function() {
        var btn = $(this), cid = btn.data('cid');
        var d = new Date(), pad = function(n) { return (n < 10 ? '0' : '') + n; };
        var todayYmd = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        var todayLbl = pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
        var send = function(dateYmd) {
            btn.prop('disabled', true);
            $.ajax({ url: 'finish-shipment.php', type: 'POST', dataType: 'json', data: { cid: cid, finish_date: dateYmd },
                success: function(res) {
                    if (res && res.success) {
                        (window.Swal ? Swal.fire({ icon: 'success', title: 'Selesai', text: res.message, timer: 1800, showConfirmButton: false }) : Promise.resolve()).then(function() { location.href = 'admin.php'; });
                    } else {
                        btn.prop('disabled', false);
                        window.Swal ? Swal.fire({ icon: 'error', title: 'Gagal', text: res ? res.message : 'Tidak ada respons' }) : alert(res ? res.message : 'Gagal');
                    }
                },
                error: function() { btn.prop('disabled', false); alert('Gagal menghubungi server'); } });
        };
        if (window.Swal) {
            Swal.fire({
                title: 'Pengiriman selesai hari ini?',
                html: 'Tanggal hari ini: <b>' + todayLbl + '</b>',
                icon: 'question', showDenyButton: true, showCancelButton: true,
                confirmButtonText: 'Ya, hari ini', denyButtonText: 'Tidak, pilih tanggal', cancelButtonText: 'Batal'
            }).then(function(r) {
                if (r.isConfirmed) { send(todayYmd); }
                else if (r.isDenied) {
                    Swal.fire({
                        title: 'Tanggal pengiriman selesai', input: 'date', inputValue: todayYmd,
                        inputAttributes: { max: todayYmd }, showCancelButton: true, confirmButtonText: 'Selesaikan', cancelButtonText: 'Batal',
                        inputValidator: function(v) { if (!v) { return 'Pilih tanggal dulu.'; } if (v > todayYmd) { return 'Tanggal tidak boleh di masa depan.'; } }
                    }).then(function(r2) { if (r2.isConfirmed && r2.value) { send(r2.value); } });
                }
            });
        } else if (confirm('Pengiriman selesai hari ini (' + todayLbl + ')?\nOK = ya, Batal = pilih tanggal lain')) { send(todayYmd); }
        else { var v = prompt('Tanggal selesai (format YYYY-MM-DD):', todayYmd); if (v) { send(v); } }
    });
    </script>
</body>
</html>
<?php
} else {
    echo "No courier found with the provided ID.";
}
ob_end_flush();
?>