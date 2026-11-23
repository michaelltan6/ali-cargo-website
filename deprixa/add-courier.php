<?php
require_once __DIR__ . '/courier-lib.php';
require_once __DIR__ . '/city-lib.php';
require_once __DIR__ . '/eta-lib.php';

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
}
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

require_once('database.php');
require_once('library.php');
require_once('funciones.php');

requireAdmin();

// (log data dimatikan) error_log("Session data in add-courier.php: " . print_r($_SESSION, true));

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
                        <label for="shipper_phone">Nomor kontak</label>
                        <input type="text" name="shipper_phone" id="shipper_phone" inputmode="tel" placeholder="0812xxxxxxx">
                    </div>
                    <div class="ac-f ac-12">
                        <label for="shipper_address">Alamat pengirim</label>
                        <input type="text" name="shipper_address" id="shipper_address" placeholder="Alamat lengkap pengirim">
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
                        <label for="receiver_phone">Nomor kontak</label>
                        <input type="text" name="receiver_phone" id="receiver_phone" inputmode="tel" placeholder="0812xxxxxxx">
                    </div>
                    <div class="ac-f ac-6">
                        <label for="receiver_email">Email</label>
                        <input type="text" name="receiver_email" id="receiver_email" placeholder="nama@perusahaan.com">
                        <span class="hint">Opsional. Kalau diisi, pastikan benar.</span>
                    </div>
                    <div class="ac-f ac-12">
                        <label for="receiver_address">Alamat penerima</label>
                        <input type="text" name="receiver_address" id="receiver_address" placeholder="Alamat lengkap penerima">
                    </div>
                </div>
            </section>

            <!-- 3. INFORMASI PENGIRIMAN -->
            <section class="ac-card">
                <h4><i class="fa fa-info-circle"></i> Informasi pengiriman</h4>
                <input type="hidden" name="office" id="office" value="<?php echo htmlspecialchars($offices[0] ?? ''); ?>">
                <div class="ac-grid">
                    <div class="ac-f ac-4">
                        <label for="payment_term">Metode pembayaran</label>
                        <select name="payment_term" id="payment_term">
                            <option value="cash" selected="selected">Cash</option>
                            <option value="7">Tempo 7 hari</option>
                            <option value="14">Tempo 14 hari</option>
                            <option value="30">Tempo 30 hari</option>
                        </select>
                        <span class="hint" id="term-hint" style="display:none">Hitungan tempo belum jalan. Aktifkan sendiri nanti di Daftar Pengiriman yang untuk dibayar.</span>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="book_code">Pembukuan</label>
                        <select name="book_code" id="book_code">
                            <option value="ALI" selected="selected">ALI</option>
                            <option value="ANK">ANK</option>
                        </select>
                        <span class="hint">Menentukan awalan nomor tracking.</span>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="spb_no">Nomor SPB (surat jalan)</label>
                        <input type="number" name="spb_no" id="spb_no" min="1" value="<?php echo (int)courier_next_spb('ALI'); ?>" data-next-ali="<?php echo (int)courier_next_spb('ALI'); ?>" data-next-ank="<?php echo (int)courier_next_spb('ANK'); ?>">
                        <span class="hint">Otomatis lanjut urut per pembukuan (ALI dan ANK punya urutan sendiri). Samakan dengan nomor cetak di surat jalan.</span>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="partner">Via / Partner</label>
                        <input type="text" name="partner" id="partner" placeholder="Ekspedisi / mitra, kosongkan kalau tidak ada">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="charge_unit">Dihitung per</label>
                        <select name="charge_unit" id="charge_unit">
                            <option value="kg" selected="selected">Kg (berat)</option>
                            <option value="m3">M&sup3; (volume)</option>
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
                        <label for="origin_city">Kota asal</label>
                        <input type="text" name="origin_city" id="origin_city" list="kota-list" autocomplete="off" value="Jakarta" placeholder="Pilih atau ketik kota">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="pickup_time">Kota tujuan <em>*</em></label>
                        <input type="text" name="pickup_time" id="pickup_time" list="kota-list" autocomplete="off" required placeholder="Pilih atau ketik kota">
                    </div>
                    <?php echo ali_city_datalist('kota-list'); ?>
                    <div class="ac-f ac-4">
                        <label for="pick_date">Tanggal barang kami terima</label>
                        <input type="date" name="pick_date" id="pick_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="schedule">Tanggal keberangkatan <span class="ac-opt">(opsional)</span></label>
                        <input type="date" name="schedule" id="schedule">
                    </div>
                    <div class="ac-f ac-4">
                        <label for="eta">Perkiraan tiba (ETA)</label>
                        <input type="date" name="eta" id="eta">
                        <small id="eta-hint" class="ac-hint"></small>
                    </div>
                    <div class="ac-f ac-4">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="In-Transit" selected="selected">On Delivery</option>
                            <option value="On-Hold">On Hold</option>
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
                                <td><input type="number" step="0.001" name="item_volume[]" class="volume" placeholder="otomatis" min="0"></td>
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
                        <input type="text" name="cons_no" id="cons_no" readonly value="ALI <?php echo (int)courier_next_spb('ALI'); ?>">
                        <span class="hint">Otomatis: pembukuan + nomor SPB.</span>
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
    <?php echo ali_eta_script(); ?>
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
                volumeField.value = parseFloat(volumeManual).toFixed(3);
                return;
            }
            const volume = hitungVolume(p, l, t);
            volumeField.value = volume.toFixed(3);
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
            document.getElementById("total-weight").value = totalWeight.toFixed(3);
            document.getElementById("total-volume").value = totalVolume.toFixed(3);
            document.formulario.qty.value = totalQuantity;
            document.formulario.weight.value = totalWeight.toFixed(3);
            document.formulario.total_volume.value = totalVolume.toFixed(3);
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
<script>
(function(){var t=document.getElementById('payment_term'),h=document.getElementById('term-hint');function u(){h.style.display=t.value==='cash'?'none':'block';}t.addEventListener('change',u);u();})();
</script>
<script>
(function(){var b=document.getElementById('book_code'),n=document.getElementById('spb_no'),c=document.getElementById('cons_no');var typed=false;function u(){c.value=b.value+' '+(n.value||'');}b.addEventListener('change',function(){if(!typed){var nx=b.value==='ANK'?n.getAttribute('data-next-ank'):n.getAttribute('data-next-ali');if(nx){n.value=nx;}}u();});n.addEventListener('input',function(){typed=true;u();});u();})();
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