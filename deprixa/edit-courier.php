<?php
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Enable for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

// Initialize session
session_start();

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
isUser();
$userOutput = ob_get_clean();
if (!empty($userOutput)) {
    error_log("Unexpected output from isUser(): " . $userOutput);
}

// Clear any existing output buffer
ob_clean();

// Main logic
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
    error_log("Retrieved data from courier table: " . print_r($data, true));
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
    error_log("Retrieved items: " . print_r($items, true));

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
        <div class="row">
            <div class="col-xs-12 col-lg-12 col-xl-7">
                <div class="card-box">
                    <div class="text-xs-center">
                        <tbody>
                            <h3 class="classic-title"><span><strong><i class="fa fa-truck icon text-default-lter"></i> UPDATE Pengiriman</strong></h3>                                                                                    
                            <form action="process.php?action=update-addcourier" name="formulario" id="formulario" method="post"> 
                                <table border="0" align="center" width="100%">
                                    <div class="row">
                                        <fieldset class="col-md-6">
                                            <legend><strong>Data pengirim:</strong></legend>    
                                            <div class="row">
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label" for="officename"><i class="fa fa-user icon text-default-lter"></i> Staff Role<span class="required-field">*</span></label>
                                                    <input type="text" name="officename" id="officename" value="<?php echo htmlspecialchars($_SESSION['user_type'] ?? ''); ?>" class="form-control" readonly="true">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label" for="user"><i class="fa fa-user icon text-default-lter"></i> Staff User<span class="required-field">*</span></label>
                                                    <input type="text" name="user" id="user" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" class="form-control" readonly="true">
                                                </div>
                                                <div class="col-sm-6 form-group">
                                                    <label class="control-label" for="ship_name">NAMA PENGIRIM<span class="required-field">*</span></label>                                
                                                    <input type="text" name="ship_name" id="ship_name" class="form-control" autocomplete="off" required value="<?php echo htmlspecialchars($ship_name ?? ''); ?>">                                                                      
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-5 form-group">
                                                    <label class="control-label" for="shipper_address">ALAMAT<span class="required-field">*</span></label>
                                                    <input type="text" name="shipper_address" id="shipper_address" class="form-control" required value="<?php echo htmlspecialchars($shipper_address ?? ''); ?>">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label" for="shipper_phone"><i class="fa fa-phone icon text-default-lter"></i> PHONE</label>
                                                    <input type="text" class="form-control" name="shipper_phone" id="shipper_phone" required value="<?php echo htmlspecialchars($shipper_phone ?? ''); ?>">
                                                </div>
                                                <div class="col-sm-4 form-group">
                                                    <label class="control-label" for="shipper_cc">ID</label>
                                                    <input type="text" name="shipper_cc" id="shipper_cc" class="form-control" value="<?php echo htmlspecialchars($shipper_cc ?? ''); ?>" required>
                                                </div>                                    
                                            </div>    
                                            <legend><strong>Informasi Pengiriman:</strong></legend>
                                            <div class="row">
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label" for="book_mode"><i class="fa fa-database icon text-default-lter"></i> <strong>Pembayaran</strong></label>
                                                    <select name="book_mode" class="form-control" id="book_mode">
                                                        <option value="ToPay" <?php echo ($book_mode == 'ToPay') ? 'selected' : ''; ?>>Untuk Dibayar</option>
                                                        <option value="Paid" <?php echo ($book_mode == 'Paid') ? 'selected' : ''; ?>>Dibayar</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-5 form-group">
                                                    <label class="control-label" for="ship_type">Product/Type</label>
                                                    <input name="ship_type" id="ship_type" class="form-control" value="<?php echo htmlspecialchars($ship_type ?? ''); ?>">
                                                </div>
                                                <div class="col-sm-4 form-group">
                                                    <label class="control-label" for="mode"><i class="fa fa-plane icon text-default-lter"></i> Service</label>
                                                    <select name="mode" class="form-control" id="mode" required>
                                                        <option value="0">Pilih</option>
                                                        <?php foreach ($modes as $mode_option): ?>
                                                            <option value="<?php echo htmlspecialchars($mode_option); ?>" <?php echo (isset($mode) && $mode == $mode_option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mode_option); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label" for="total_declarate"><?php echo htmlspecialchars($company['currency'] ?? 'IDR'); ?> Insurance</label>
                                                    <input name="total_declarate" id="total_declarate" class="form-control" value="<?php echo htmlspecialchars($total_declarate ?? ''); ?>"/>
                                                </div>
                                                <div class="col-sm-4 form-group">
                                                    <label class="control-label" for="office"><i class="fa fa-angle-double-right icon text-default-lter"></i> ORIGIN</label>
                                                    <input name="office" id="office" class="form-control" value="<?php echo htmlspecialchars($office ?? ''); ?>">
                                                </div>
                                                <div class="col-sm-5 form-group">
                                                    <label class="control-label" for="pickup_time"><i class="fa fa-angle-double-right icon text-default-lter"></i> DESTINATION</label>
                                                    <input name="pickup_time" id="pickup_time" class="form-control" value="<?php echo htmlspecialchars($pickup_time ?? ''); ?>">
                                                </div>        
                                            </div>    
                                            <div class="row">
                                                <div class="col-sm-11 form-group">
                                                    <label class="control-label" for="pick_date"><i class="fa fa-calendar icon text-default-lter"></i> COLLECTION DATE AND TIME</label>
                                                    <div class="input-group">
                                                        <input type="date" class="form-control date-input" name="pick_date" id="pick_date" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($collection_date ?? 'now'))); ?>" required>
                                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                    </div>
                                                </div>                                                        
                                            </div>                                                        
                                            <div class="row">
                                                <div class="col-sm-4 form-group">
                                                    <label class="control-label" for="status"><i class="fa fa-sort-amount-asc icon text-default-lter"></i> STATUS</label>
                                                    <input class="form-control" name="status" id="status" value="<?php echo htmlspecialchars($status ?? ''); ?>" readonly="true">
                                                </div>                                
                                                <div class="col-sm-7 form-group">
                                                    <label class="control-label" for="schedule"><i class="fa fa-calendar icon text-default-lter"></i> Schedule Delivery (ETD)</label>
                                                    <div class="input-group">
                                                        <input type="date" class="form-control date-input" name="schedule" id="schedule" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($etd ?? 'now'))); ?>" required>
                                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                    </div>        
                                                </div>                    
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 form-group">
                                                    <label class="control-label" for="tracking_link"><i class="fa fa-link icon text-default-lter"></i> Tracking Link</label>
                                                    <input type="text" class="form-control" name="tracking_link" id="tracking_link" value="<?php echo htmlspecialchars($tracking_link ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 form-group">
                                                    <label class="control-label"><i class="fa fa-bar-chart icon text-default-lter"></i> Progress Tracker</label>
                                                    <div class="progress-container">
                                                        <div class="progress">
                                                            <div id="progress-bar" class="progress-bar" role="progressbar" style="width: <?php echo htmlspecialchars($progress_percentage ?? 0); ?>%;" aria-valuenow="<?php echo htmlspecialchars($progress_percentage ?? 0); ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <span class="progress-text"><?php echo htmlspecialchars($progress_percentage ?? 0); ?>%</span>
                                                            </div>
                                                            <img id="cartoon-box" src="assets/images/cartoon-box.png" alt="Cartoon Box" class="cartoon-box">
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="progress_percentage" id="progress_percentage" value="<?php echo htmlspecialchars($progress_percentage ?? 0); ?>">
                                                    <div class="row">
                                                        <div class="col-sm-6 form-group">
                                                            <label class="control-label" for="eta"><i class="fa fa-clock-o icon text-default-lter"></i> ETA (Estimated Time of Arrival)</label>
                                                            <div class="input-group">
                                                                <input type="date" class="form-control date-input" name="eta" id="eta" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($eta ?? '')) ?: date('Y-m-d', strtotime('now + 1 day'))); ?>" required>
                                                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <small>Progress updates from Schedule Delivery (ETD) to ETA. Set to 100% when status is Delivered.</small>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 form-group">
                                                    <label class="control-label" for="comments"><i class="fa fa-comments icon text-default-lter"></i> Shipping Detail</label>
                                                    <textarea class="form-control" name="comments" id="comments"><?php echo htmlspecialchars($comments ?? ''); ?></textarea>
                                                </div>
                                                <div class="col-sm-12 form-group">
                                                    <h4><strong>Rincian Barang</strong></h4>
                                                    <table class="table table-bordered" id="items-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Nama Barang</th>
                                                                <th>Qty</th>
                                                                <th>Panjang (cm)</th>
                                                                <th>Lebar (cm)</th>
                                                                <th>Tinggi (cm)</th>
                                                                <th>Berat (Kg)</th>
                                                                <th>Volume (m³)</th>
                                                                <th></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="item-rows">
                                                            <?php foreach ($items as $item): ?>
                                                            <tr>
                                                                <td><input type="text" name="item_name[]" class="form-control" required value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>"></td>
                                                                <td><input type="number" name="item_quantity[]" class="form-control qty" value="<?php echo htmlspecialchars($item['quantity'] ?? ''); ?>" min="1" required></td>
                                                                <td><input type="number" name="item_length[]" class="form-control length" value="<?php echo htmlspecialchars($item['length'] ?? ''); ?>" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_width[]" class="form-control width" value="<?php echo htmlspecialchars($item['width'] ?? ''); ?>" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_height[]" class="form-control height" value="<?php echo htmlspecialchars($item['height'] ?? ''); ?>" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_weight[]" class="form-control weight" step="0.0001" value="<?php echo htmlspecialchars($item['weight'] ?? ''); ?>" min="0"></td>
                                                                <td><input type="number" step="0.0001" name="item_volume[]" class="form-control volume" placeholder="manual/otomatis" min="0" value="<?php echo htmlspecialchars($item['volume'] ?? ''); ?>" data-manual="<?php echo htmlspecialchars($item['volume'] ?? ''); ?>"></td>
                                                                <td><button type="button" class="btn btn-danger remove-row">×</button></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                            <?php if (empty($items)): ?>
                                                            <tr>
                                                                <td><input type="text" name="item_name[]" class="form-control" required value=""></td>
                                                                <td><input type="number" name="item_quantity[]" class="form-control qty" value="1" min="1" required></td>
                                                                <td><input type="number" name="item_length[]" class="form-control length" value="0" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_width[]" class="form-control width" value="0" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_height[]" class="form-control height" value="0" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_weight[]" class="form-control weight" step="0.0001" value="0" min="0"></td>
                                                                <td><input type="number" step="0.0001" name="item_volume[]" class="form-control volume" placeholder="manual/otomatis" min="0" value="0"></td>
                                                                <td><button type="button" class="btn btn-danger remove-row">×</button></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                    <button type="button" class="btn btn-primary" id="add-row">+ Tambah Barang</button>
                                                    <div class="row mt-3">
                                                        <div class="col-sm-4 form-group">
                                                            <label><strong>Total Jumlah</strong></label>
                                                            <input type="text" name="qty" id="total-quantity" class="form-control" readonly value="<?php echo $total_quantity; ?>">
                                                        </div>
                                                        <div class="col-sm-4 form-group">
                                                            <label><strong>Total Berat (Kg)</strong></label>
                                                            <input type="text" name="weight" id="total-weight" class="form-control" readonly value="<?php echo $total_weight; ?>">
                                                        </div>
                                                        <div class="col-sm-4 form-group">
                                                            <label><strong>Total Volume (m³)</strong></label>
                                                            <input type="text" name="total_volume" id="total-volume" class="form-control" readonly value="<?php echo $total_volume; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 form-group">
                                                <br>
                                                <br>
                                                <input class="btn btn-success" name="Submit" type="submit" id="submit" value="UPDATE TRACKING">
                                                <input name="cid" id="cid" value="<?php echo $cid; ?>" type="hidden">
                                            </div>
                                        </fieldset>
                                        <fieldset class="col-md-6">
                                            <legend><strong>Data Recipient:</strong></legend>
                                            <div class="row">
                                                <div class="col-sm-11 form-group">
                                                    <label class="control-label" for="rev_name">RECIPIENT NAME<span class="required-field">*</span></label>
                                                    <input type="text" class="form-control" name="rev_name" id="rev_name" value="<?php echo htmlspecialchars($rev_name ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-5 form-group">
                                                    <label class="control-label" for="receiver_address">ADDRESS <span class="required-field">*</span></label>
                                                    <input type="text" name="receiver_address" id="receiver_address" class="form-control" required value="<?php echo htmlspecialchars($receiver_address ?? ''); ?>">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label" for="receiver_phone"><i class="fa fa-phone icon text-default-lter"></i> PHONE</label>
                                                    <input type="text" class="form-control" name="receiver_phone" id="receiver_phone" required value="<?php echo htmlspecialchars($receiver_phone ?? ''); ?>">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label" for="receiver_cc">ID</label>
                                                    <input type="text" name="receiver_cc" id="receiver_cc" class="form-control" value="<?php echo htmlspecialchars($receiver_cc ?? ''); ?>" required>
                                                </div>
                                                <div class="col-sm-11 form-group">
                                                    <label class="control-label" for="receiver_email">EMAIL <font color="#FF6100">Note: (The email must be real to be notified shipping)</font></label>
                                                    <input type="text" name="receiver_email" id="receiver_email" class="form-control" value="<?php echo htmlspecialchars($receiver_email ?? ''); ?>" required>
                                                </div>
                                            </div>                            
                                            <br>
                                            <br>
                                            <div class="row">
                                                <div class="col-sm-11 form-group">
                                                    <label class="control-label" for="cons_no"><strong>TRACKING NUMBER</strong></label>
                                                    <input type="text" class="form-control" name="cons_no" id="cons_no" value="<?php echo htmlspecialchars($cons_no ?? ''); ?>" placeholder="Masukkan Nomor Tracking" required />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </table>
                            </form>
                        </tbody>                            
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-lg-12 col-xl-5">
                <div class="card-box">
                    <h4 class="header-title m-t-0 m-b-30">PERBARUI STATUS</h4>
                    <table class="table table-bordered m-b-0">
                        <form action="process.php?action=update-status" method="post" name="frmShipment" id="frmShipment">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="pick_time" class="control-label">LOKASI BARU:</label>
                                    <span id="inter_origin" style="display: block;">     
                                        <textarea id="pick_time" required name="pick_time" class="form-control"><?php echo htmlspecialchars($pickup_time ?? ''); ?></textarea>
                                    </span>
                                </div>    
                                <div class="col-md-4 form-group">
                                    <label for="status" class="control-label">NEW STATE:</label>
                                    <select name="status" class="form-control" id="status">
                                        <option value="Finished" <?php echo ($status == 'Finished') ? 'selected' : ''; ?>>Finished</option>
                                        <option value="In-Transit" <?php echo ($status == 'In-Transit') ? 'selected' : ''; ?>>On Delivery</option>
                                        <option value="On-Hold" <?php echo ($status == 'On-Hold') ? 'selected' : ''; ?>>On Hold</option>
                                        <option value="Landed" <?php echo ($status == 'Landed') ? 'selected' : ''; ?>>Arrived</option>
                                        <option value="Delayed" <?php echo ($status == 'Delayed') ? 'selected' : ''; ?>>Delayed</option>
                                    </select>
                                </div>    
                                <div class="col-md-4 form-group">
                                    <label for="bk_time" class="control-label"><i class="fa fa-calendar icon text-default-lter"></i> DATE AND TIME</label>
                                    <div class="input-group">
                                        <input type="datetime-local" class="form-control datetime-input" name="bk_time" id="bk_time" required value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($bk_time ?? 'now'))); ?>">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="comments" class="control-label">COMMENTS:</label>
                                    <textarea class="form-control" name="comments" id="comments"><?php echo htmlspecialchars($comments ?? ''); ?></textarea>
                                </div>                            
                                <div class="col-md-3 form-group">
                                    <p><font color="#FF6100"><strong>Update if necessary</strong></font></p>
                                    <input name="submit" type="submit" class="btn btn-success" value="UPDATE STATUS">
                                    <input name="user" id="user" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" type="hidden">
                                    <input name="cid" id="cid" value="<?php echo $cid; ?>" type="hidden">
                                    <input name="cons_no" id="cons_no" value="<?php echo htmlspecialchars($cons_no ?? ''); ?>" type="hidden">                            
                                </div>                        
                            </div>
                        </form>                                                
                        <div class="table-responsive">
                            <br><br><br>
                            <h4 class="header-title m-t-0 m-b-30">SHIPPING HISTORY</h4>
                            <div class="card mt-2">
                                <div class="card-body">
                                    <form method="post" action="delete_shipping_history.php" onsubmit="return confirm('Yakin ingin menghapus data terpilih?');">
                                        <table class="table table-bordered m-b-0">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="select-all" /></th>
                                                    <th># Tracking</th>
                                                    <th>Location</th>
                                                    <th>STATUS</th>
                                                    <th>Date and Time</th>
                                                    <th>Observation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM courier_track WHERE cid = ? AND cons_no = ? ORDER BY bk_time";
                                                $stmt = dbQuery($sql, [$cid, $cons_no]);
                                                while ($row = dbFetchAssoc($stmt)) {
                                                ?>
                                                <tr>
                                                    <td><input type="checkbox" name="delete_ids[]" value="<?php echo $row['id']; ?>"></td>
                                                    <td><?php echo htmlspecialchars($row['cons_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['pick_time']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['bk_time']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['comments']); ?></td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                        <br><br><br>
                                        <button type="submit" class="btn btn-danger" style="margin-top: 15px;">Hapus yang dipilih</button>
                                        <script>
                                            document.getElementById("select-all").onclick = function() {
                                                const checkboxes = document.getElementsByName("delete_ids[]");
                                                for (let box of checkboxes) {
                                                    box.checked = this.checked;
                                                }
                                            };
                                        </script>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include("footer.php"); ?>
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
                    volumeField.val(parseFloat(volumeManual).toFixed(10));
                    return;
                }
                const volume = hitungVolume(p, l, t);
                volumeField.val(volume.toFixed(10));
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
                $("#total-weight").val(totalWeight.toFixed(6));
                $("#total-volume").val(totalVolume.toFixed(10));
                $("#formulario input[name='qty']").val(totalQuantity.toFixed(0));
                $("#formulario input[name='weight']").val(totalWeight.toFixed(6));
                $("#formulario input[name='total_volume']").val(totalVolume.toFixed(10));
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
                const requiredFields = ['ship_name', 'shipper_address', 'shipper_phone', 'shipper_cc',
                    'receiver_address', 'receiver_phone', 'receiver_cc', 'receiver_email',
                    'rev_name', 'cons_no', 'pick_date', 'schedule', 'mode', 'eta'];
                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (input && !input.value.trim()) {
                        input.classList.add('error');
                        isValid = false;
                    } else if (input && ['pick_date', 'schedule', 'eta'].includes(field)) {
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
</body>
</html>
<?php
} else {
    echo "No courier found with the provided ID.";
}
ob_end_flush();
?>