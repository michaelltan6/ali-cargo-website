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
<div class="wrapper" id="main">
    <div class="container">
        <?php include("icon_settings.php"); ?> 
        <div class="row">
            <div class="col-sm-12">
                <div class="card-box table-responsive">
                    <tbody>
                        <tr>                
                            <h3 class="classic-title"><span><strong><i class="fa fa-truck icon text-default-lter"></i> Tambahkan pengiriman</strong></h3>
                            <form action="process.php?action=add-cons" name="formulario" method="post" id="courierForm"> 
                                <table border="0" align="center" width="100%">
                                    <div class="row">
                                        <fieldset class="col-md-6">
                                            <legend>Data pengirim :</legend>    
                                            <div class="row">
                                                <div class="col-sm-2 form-group">
                                                    <label class="control-label"><i class="fa fa-user icon text-default-lter"></i> Staff Role<span class="required-field">*</span></label>
                                                    <input type="text" name="officename" id="officename" value="<?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Unknown'); ?>" class="form-control" readonly="true">
                                                </div>
                                                <div class="col-sm-2 form-group">
                                                    <label class="control-label"><i class="fa fa-user icon text-default-lter"></i> Staff User<span class="required-field">*</span></label>
                                                    <input type="text" name="user" id="user" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Unknown'); ?>" class="form-control" readonly="true">
                                                </div>
                                                <div class="col-sm-8 form-group">
                                                    <label class="control-label">NAMA PENGIRIM<span class="required-field">*</span></label>                                
                                                    <input type="text" name="ship_name" id="ship_name" class="form-control" autocomplete="off" required placeholder="Masukkan Nama Anda" list="browsers">
                                                    <datalist id="browsers">
                                                        <?php foreach ($customers as $customer): ?>
                                                            <option value="<?php echo htmlspecialchars($customer); ?>">
                                                        <?php endforeach; ?>
                                                    </datalist>                                      
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6 form-group">
                                                    <label class="control-label">ALAMAT<span class="required-field">*</span></label>
                                                    <input type="text" name="shipper_address" id="shipper_address" class="form-control" autocomplete="off" required placeholder="Alamat pengirim">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label"><i class="fa fa-phone icon text-default-lter"></i> PHONE</label>
                                                    <input type="text" class="form-control" name="shipper_phone" id="shipper_phone" autocomplete="off" required placeholder="Nomor handphone pengirim">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label">ID</label>
                                                    <input type="text" name="shipper_cc" id="shipper_cc" class="form-control" maxlength="20" placeholder="ID phone Pengirim" autocomplete="off" required>
                                                </div>                                    
                                            </div>    
                                            <legend>Informasi Pengiriman :</legend>
                                            <div class="row">
                                                <div class="col-sm-5 form-group">
                                                    <label class="control-label"><i class="fa fa-database icon text-default-lter"></i> <strong>Mode pembayaran</strong></label>
                                                    <select name="book_mode" class="form-control" id="book_mode">
                                                        <option selected="selected" value="Paid">Dibayar</option>
                                                        <option value="ToPay">Untuk Dibayar</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4 form-group">
                                                    <label class="control-label">Product/Type</label>
                                                    <input name="ship_type" class="form-control" id="ship_type" placeholder="masukan Tipe dari Produk">                                            
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label"><i class="fa fa-plane icon text-default-lter"></i> Service(Mode)</label>
                                                    <select name="mode" class="form-control" id="mode">
                                                        <option value="0">Pilih</option>
                                                        <?php 
                                                        foreach ($modes as $mode_option): 
                                                        ?>
                                                            <option value="<?php echo htmlspecialchars($mode_option); ?>"><?php echo htmlspecialchars($mode_option); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-4 form-group">
                                                    <label for="ccv" class="control-label"><?php echo htmlspecialchars($company['currency'] ?? 'IDR'); ?> Asuransi Pengiriman</label>
                                                    <input name="total_declarate" class="form-control" id="total_declarate" maxlength="20" placeholder="0,00"/>
                                                </div>
                                                <div class="col-sm-4 form-group">
                                                    <label for="zipcode" class="control-label"><i class="fa fa-angle-double-right icon text-default-lter"></i> KANTOR ASAL</label>
                                                    <select name="office" id="office" class="form-control">
                                                        <?php 
                                                        foreach ($offices as $office): 
                                                        ?>
                                                            <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-4 form-group">
                                                    <label for="zipcode" class="control-label"><i class="fa fa-angle-double-right icon text-default-lter"></i> KOTA TUJUAN</label>
                                                    <span id="inter_origin" style="display: block;">     
                                                        <textarea id="pickup_time" name="pickup_time" class="form-control" placeholder="Masukkan kota tujuan"></textarea>                            
                                                    </span>        
                                                </div>    
                                            </div>
                                            <div class="form-group">
                                                <label for="dtp_input1" class="control-label"><i class="fa fa-calendar icon text-default-lter"></i> TANGGAL DAN WAKTU PENGUMPULAN</label>
                                                <div class="input-group">
                                                    <input type="date" class="form-control date-input" name="pick_date" id="pick_date">
                                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </div>                                            
                                            <div class="row">
                                                <div class="col-sm-4 form-group">
                                                    <label for="month" class="control-label"><i class="fa fa-sort-amount-asc icon text-default-lter"></i> STATUS</label>
                                                    <select class="form-control" name="status" id="status">
                                                        <option selected="selected" value="In-Transit">On Delivery</option>
                                                    </select>
                                                </div>                                
                                                <div class="col-sm-8 form-group">
                                                    <label for="dtp_input1" class="control-label"><i class="fa fa-calendar icon text-default-lter"></i> Jadwalkan pengiriman</label>
                                                    <div class="input-group">
                                                        <input type="date" class="form-control date-input" name="schedule" id="schedule">
                                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12 form-group">
                                                    <label class="control-label"><i class="fa fa-link icon text-default-lter"></i> Tracking Link (opsional)</label>
                                                    <input type="url" name="tracking_link" class="form-control" placeholder="https://www.myshiptracking.com/?mmsi=525016160">
                                                </div>
                                            </div>
                                            <div class="row mt-3">
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
                                                            <tr>
                                                                <td><input type="text" name="item_name[]" class="form-control" required></td>
                                                                <td><input type="number" name="item_quantity[]" class="form-control qty" value="1" min="1" required></td>
                                                                <td><input type="number" name="item_length[]" class="form-control length" value="0" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_width[]" class="form-control width" value="0" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_height[]" class="form-control height" value="0" min="0" step="0.0001"></td>
                                                                <td><input type="number" name="item_weight[]" class="form-control weight" step="0.0001" value="0" min="0"></td>
                                                                <td><input type="number" step="0.0001" name="item_volume[]" class="form-control volume" placeholder="manual/otomatis" min="0"></td>
                                                                <td><button type="button" class="btn btn-danger remove-row">×</button></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <button type="button" class="btn btn-primary" id="add-row">+ Tambah Barang</button>
                                                    <div class="row mt-3">
                                                        <div class="col-sm-4 form-group">
                                                            <label><strong>Total Quantity</strong></label>
                                                            <input type="text" name="qty" id="total-quantity" class="form-control" readonly value="0">
                                                        </div>
                                                        <div class="col-sm-4 form-group">
                                                            <label><strong>Total Berat Semua (Kg)</strong></label>
                                                            <input type="text" name="weight" id="total-weight" class="form-control" readonly value="0">
                                                        </div>
                                                        <div class="col-sm-4 form-group">
                                                            <label><strong>Total Volume Semua (m³)</strong></label>
                                                            <input type="text" name="total_volume" id="total-volume" class="form-control" readonly value="0">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="inputTextarea" class="control-label"><i class="fa fa-comments icon text-default-lter"></i> Detail Pengiriman</label>
                                                <textarea class="form-control" name="comments" id="comments" placeholder="tulis Detail tentang Pengiriman"></textarea>
                                            </div>
                                        </fieldset>
                                        <fieldset class="col-md-6">
                                            <legend>Data penerima :</legend>
                                            <div class="form-group">
                                                <label class="control-label">NAMA PENERIMA<span class="required-field">*</span></label>
                                                <input type="text" class="form-control" name="rev_name" id="rev_name" placeholder="Masukkan Nama Penerima" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-6 form-group">
                                                    <label class="control-label">ALAMAT <span class="required-field">*</span></label>
                                                    <input type="text" name="receiver_address" id="receiver_address" class="form-control" autocomplete="off" required placeholder="Alamat Penerima">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label"><i class="fa fa-phone icon text-default-lter"></i> PHONE</label>
                                                    <input type="text" class="form-control" name="receiver_phone" id="receiver_phone" autocomplete="off" required placeholder="Recipient phone">
                                                </div>
                                                <div class="col-sm-3 form-group">
                                                    <label class="control-label">ID</label>
                                                    <input type="text" name="receiver_cc" id="receiver_cc" class="form-control" maxlength="20" placeholder="Recipient id" autocomplete="off" required>
                                                </div>
                                                <div class="col-sm-12 form-group">
                                                    <label class="control-label">EMAIL <font color="#FF6100">Note: (Email harus benar untuk pemberitahuan pengiriman)</font></label>
                                                    <input type="text" name="receiver_email" id="receiver_email" class="form-control" placeholder="company@gmail.com" autocomplete="off" required>
                                                </div>
                                            </div>                            
                                            <br>
                                            <br>
                                            <div class="form-group">
                                                <label for="name-card" class="text-success"><strong>NOMOR TRACKING</strong></label>
                                                <input type="text" class="form-control" name="cons_no" id="cons_no" value="<?php 
                                                    $DesdeLetra = "A";                                        
                                                    $DesdeLetra1 = "L";                                        
                                                    $DesdeLetra2 = "I";
                                                    $DesdeNumero2 = 1;
                                                    $HastaNumero2 = 1;
                                                    $DesdeNumero3 = 87;
                                                    $HastaNumero3 = 87;
                                                    $DesdeNumero = 1;
                                                    $HastaNumero = 1000000000000;                                        
                                                    $letraAleatoria = ($DesdeLetra);
                                                    $letraAleatoria1 = ($DesdeLetra1);
                                                    $letraAleatoria2 = ($DesdeLetra2);
                                                    $numeroAleatorio2 = chr(rand(ord($DesdeNumero2), ord($HastaNumero2)));
                                                    $numeroAleatorio3 = ($DesdeNumero3);
                                                    $numeroAleatorio = rand($DesdeNumero, $HastaNumero);
                                                    echo "".$letraAleatoria."".$letraAleatoria1."".$letraAleatoria2."".$numeroAleatorio."";
                                                ?>" />
                                            </div>
                                            <div class="col-md-6 text-left">
                                                <br>
                                                <br>
                                                <input class="btn btn-success" name="Submit" type="submit" id="submit" value="SAVE TRACKING">
                                            </div>
                                            <input type="hidden" name="variable" value="0">
                                            <input type="hidden" name="freight" value="0">
                                            <input type="hidden" name="Totalfreight" value="0">
                                            <input type="hidden" name="shipping_subtotal" value="0">
                                            <input type="hidden" name="status_delivered" value="Pending">
                                        </fieldset>
                                    </div>
                                </table>
                            </form>
                        </tr>
                    </tbody>                            
                </div>
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