<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('database.php');
require_once('library.php');
require_once('courier-lib.php');
courier_columns_ready();

try {
    requireAdmin();
    error_log("Session data in list-of-shipping-topaid.php: " . print_r($_SESSION, true));
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <title>PT. ANEKA LINTAS INDONESIA</title>
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet">
    <link href="assets/plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css" type="text/css">
    <link rel="stylesheet" href="bower_components/animate.css/animate.css" type="text/css">
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="bower_components/simple-line-icons/css/simple-line-icons.css" type="text/css">
    <link rel="stylesheet" href="css/footer-basic-centered.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css" integrity="sha512-0W1V9eN6gckE1ZLX7kW5fSSfO8mX5F6H8e1q8R3c1WqM7f5f5g5k5k5k5k5k5k5k5k5k5k5k5k5k5k5k5k5k5k==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/plugins/jquery-ui/jquery-ui.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .Finished { background: #363C56; }
        .Delayed { background: #F76063; }
        .On-Hold { background: #4ECCDB; }
        .Landed { background: #FF8A4B; }
        .In-TransitFACTransit { background: #00D96D; }
        .Paid { background: #675F99; }
        .ToPay { background: #FF8441; }
        .Cash-on-Delivery { background: #F6565A; }
        .label { padding: 5px; }
        .due-date-container { display: flex; align-items: center; gap: 5px; }
        .due-date-picker {
            width: 120px !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            background-color: #fff !important;
        }
        .input-group-addon {
            cursor: pointer;
            background: #f8f9fa;
            border: 1px solid #ced4da;
            border-left: none;
            padding: 5px;
        }
        .clear-due-date { cursor: pointer; color: #dc3545; }
        input[readonly].due-date-picker {
            background-color: #fff !important;
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        .due-date-yellow {
            color: yellow !important;
            font-weight: bold;
        }
        .ui-datepicker {
            z-index: 1051 !important;
            opacity: 1 !important;
            background-color: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.25rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
            padding: 10px !important;
            font-family: Arial, sans-serif !important;
            width: 280px !important;
        }
        .ui-datepicker .ui-datepicker-header {
            background-color: #007bff !important;
            border-bottom: 1px solid #0069d9 !important;
            color: #ffffff !important;
            font-size: 1.1rem !important;
            padding: 8px !important;
            border-top-left-radius: 0.25rem !important;
            border-top-right-radius: 0.25rem !important;
        }
        .ui-datepicker .ui-datepicker-prev,
        .ui-datepicker .ui-datepicker-next {
            cursor: pointer !important;
            color: #ffffff !important;
            font-size: 1rem !important;
        }
        .ui-datepicker .ui-datepicker-prev:hover,
        .ui-datepicker .ui-datepicker-next:hover {
            background-color: #0056b3 !important;
            border-radius: 50% !important;
        }
        .ui-datepicker .ui-datepicker-title {
            margin: 0 2rem !important;
        }
        .ui-datepicker .ui-datepicker-calendar {
            opacity: 1 !important;
            background-color: #ffffff !important;
            margin: 10px 0 !important;
        }
        .ui-datepicker .ui-datepicker-calendar th {
            padding: 10px !important;
            text-align: center !important;
            background-color: #e9ecef !important;
            color: #495057 !important;
            font-weight: 500 !important;
        }
        .ui-datepicker .ui-datepicker-calendar td {
            padding: 5px !important;
            text-align: center !important;
        }
        .ui-datepicker .ui-state-default {
            display: block !important;
            width: 2.5rem !important;
            height: 2.5rem !important;
            line-height: 2.5rem !important;
            text-align: center !important;
            color: #212529 !important;
            background-color: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 50% !important;
            transition: background-color 0.2s ease !important;
        }
        .ui-datepicker .ui-state-default:hover {
            background-color: #e9ecef !important;
            color: #000000 !important;
            text-decoration: none !important;
        }
        .ui-datepicker .ui-state-active {
            background-color: #007bff !important;
            color: #ffffff !important;
            border-color: #0069d9 !important;
        }
        .ui-datepicker .ui-state-highlight {
            border: 2px solid #007bff !important;
            color: #007bff !important;
        }
        @media (max-width: 768px) {
            .ui-datepicker {
                width: 250px !important;
            }
            .ui-datepicker .ui-state-default {
                width: 2.2rem !important;
                height: 2.2rem !important;
                line-height: 2.2rem !important;
            }
        }
    </style>
<style>
.tempo-box { text-align: left; margin-bottom: 6px; font-size: 12px; }
.tempo-label { font-weight: 600; }
.tempo-off { color: #b45309; margin-left: 4px; }
.tempo-on { color: #15803d; margin-left: 4px; }
.tempo-act { margin-top: 4px; display: flex; gap: 4px; align-items: center; flex-wrap: wrap; }
.tempo-act input { height: 26px; font-size: 12px; padding: 2px 4px; border: 1px solid #ccc; border-radius: 4px; }
</style>
</head>
<body>
<?php include("header.php"); ?>
<div class="wrapper">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="card-box table-responsive">
                    <h4 class="header-title m-t-0 m-b-20">Daftar Pengiriman untuk Membayar</h4>
                    <table border="0" align="center">
                        <tr>
                            <td><strong>    Rentang Tanggal</strong>    </td>
                            <td><i class="icon-append fa fa-calendar"></i>  <input type="date" id="bd-desde" class="gentxt1"/></td>
                            <td>    TO    </td>
                            <td><i class="icon-append fa fa-calendar"></i>  <input type="date" id="bd-hasta" class="gentxt1"/></td>
                            <td>            <a target="_blank" href="javascript:reportepaymenttopaidPDF();" class="btn btn-primary">Export ke <strong>PDF</strong></a></td>
                        </tr>
                    </table>
                    <table id="datatable-buttons" class="table table-striped table-bordered" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Cetak Invoice</th>
                                <th># Tracking</th>
                                <th>Nama Pengirim</th>
                                <th>Destinasi</th>
                                <th>Date / Time Pengiriman</th>
                                <th>Karyawan</th>
                                <th>Status Pembayaran</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $sql = "SELECT * FROM courier WHERE book_mode = 'ToPay'";
                            $params = [];
                            if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee') {
                                $sql .= " AND officename = :officename";
                                $params = ['officename' => $_SESSION['user_name']];
                            }
                            $sql .= " ORDER BY cid";
                            error_log("ToPay query: $sql, params: " . print_r($params, true));
                            $stmt = dbQuery($sql, $params);
                            $rowCount = dbNumRows($stmt);
                            error_log("ToPay query returned $rowCount rows");
                            if ($rowCount == 0) {
                                echo "<tr><td colspan='8'>No unpaid shipments found.</td></tr>";
                            }
                            while ($row = dbFetchAssoc($stmt)) {
                                error_log("ToPay shipment: cid={$row['cid']}, cons_no={$row['cons_no']}, book_mode={$row['book_mode']}, officename={$row['officename']}, status={$row['status']}, due_date={$row['due_date']}");
                        ?>
                            <tr data-cid="<?php echo htmlspecialchars($row['cid']); ?>">
                                <td align="center">
                                    <a target="_blank" href="print-invoice/invoice-print.php?cid=<?php echo htmlspecialchars($row['cid']); ?>">
                                        <i class="glyphicon glyphicon-print icon text-dark-lter"></i>
                                    </a>
                                </td>
                                <td><font color="#000"><?php echo htmlspecialchars($row['cons_no']); ?></font></td>
                                <td><?php echo htmlspecialchars($row['ship_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['rev_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['pick_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['user']); ?></td>
                                <td align="center">
                                    <?php if ($_SESSION['user_type'] == 'Administrator') { ?>
                                        <form class="update-bookmode-form" style="margin-bottom: 0;">
                                            <input type="hidden" name="cid" value="<?php echo htmlspecialchars($row['cid']); ?>">
                                            <input type="hidden" name="action" value="update_bookmode">
                                            <select name="book_mode" class="form-control form-control-sm">
                                                <option value="Paid" <?php if ($row['book_mode'] == 'Paid') echo 'selected'; ?>>Sudah Bayar</option>
                                                <option value="ToPay" <?php if ($row['book_mode'] == 'ToPay') echo 'selected'; ?>>Belum Bayar</option>
                                                <option value="Cash-on-Delivery" <?php if ($row['book_mode'] == 'Cash-on-Delivery') echo 'selected'; ?>>Cash on Delivery</option>
                                            </select>
                                        </form>
                                    <?php } else { ?>
                                        <span class="label <?php echo htmlspecialchars($row['book_mode']); ?> label-large"><?php echo htmlspecialchars($row['book_mode']); ?></span>
                                    <?php } ?>
                                </td>
                                <td align="center">
                                    <?php if ($_SESSION['user_type'] == 'Administrator') { ?>
                                        <?php
                        $term = (string)($row['payment_term'] ?? '');
                        $isTempo = in_array($term, ['7', '14', '30'], true);
                    ?>
                    <?php if ($isTempo) { ?>
                        <div class="tempo-box" data-cid="<?php echo (int)$row['cid']; ?>">
                            <span class="tempo-label"><?php echo htmlspecialchars(courier_term_label($term)); ?></span>
                            <?php if (empty($row['due_date'])) { ?>
                                <span class="tempo-off">belum aktif</span>
                                <div class="tempo-act">
                                    <input type="date" class="tempo-start" value="<?php echo date('Y-m-d'); ?>" title="Tanggal mulai hitung tempo">
                                    <button type="button" class="btn btn-xs btn-success tempo-go">Aktifkan tempo</button>
                                </div>
                            <?php } elseif (!empty($row['tempo_start'])) { ?>
                                <span class="tempo-on">aktif sejak <?php echo htmlspecialchars(date('d-m-Y', strtotime($row['tempo_start']))); ?></span>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div class="due-date-container">
                                            <form class="update-duedate-form" style="margin-bottom: 0;">
                                                <input type="hidden" name="cid" value="<?php echo htmlspecialchars($row['cid']); ?>">
                                                <input type="hidden" name="action" value="update_duedate">
                                                <div class="input-group">
                                                    <input type="text" name="due_date" class="form-control form-control-sm due-date-picker" value="<?php echo htmlspecialchars($row['due_date'] ?? ''); ?>" readonly placeholder="Select due date">
                                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </form>
                                            <?php if ($row['due_date']) { ?>
                                                <span class="clear-due-date" title="Clear due date"><i class="fa fa-times"></i></span>
                                            <?php } ?>
                                        </div>
                                    <?php } else { ?>
                                        <?php $t2 = (string)($row['payment_term'] ?? ''); if (in_array($t2, ['7','14','30'], true)) { echo '<small>' . htmlspecialchars(courier_term_label($t2)) . '</small><br>'; } ?><span class="due-date-text" data-due-date="<?php echo htmlspecialchars($row['due_date'] ?? ''); ?>"><?php echo htmlspecialchars($row['due_date'] ?? 'Not set'); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php
                            }
                        } catch (PDOException $e) {
                            error_log("ToPay query error: " . $e->getMessage());
                            echo "<tr><td colspan='8'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include("footer.php"); ?>
    </div>
</div>
<script>
    var resizefunc = [];
</script>
<script src="assets/js/jquery.min.js"></script>
<script>
    // Isolate jQuery to prevent conflicts
    (function($) {
        // Load jQuery UI with local fallback
        if (!$.ui) {
            document.write('<script src="assets/plugins/jquery-ui/jquery-ui.min.js"><\/script>');
        }
    })(jQuery);
</script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js" integrity="sha512-57D4D/3s4c/4s4eFX4VTP4D/0JBp1e0H4Gs2Pw5D3K7H3kT0k/0H3k/0H3k/0H3k/0H3k/0H3k/0H3k==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="assets/js/tether.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.nicescroll.js"></script>
<script src="assets/plugins/switchery/switchery.min.js"></script>
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables/dataTables.buttons.min.js"></script>
<script src="assets/plugins/datatables/buttons.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables/jszip.min.js"></script>
<script src="assets/plugins/datatables/pdfmake.min.js"></script>
<script src="assets/plugins/datatables/vfs_fonts.js"></script>
<script src="assets/plugins/datatables/buttons.html5.min.js"></script>
<script src="assets/plugins/datatables/buttons.print.min.js"></script>
<script src="assets/plugins/datatables/buttons.colVis.min.js"></script>
<script src="assets/plugins/datatables/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables/responsive.bootstrap4.min.js"></script>
<script src="assets/js/jquery.app.js"></script>
<script src="js/myjava.js"></script>
<script src="js/payments_list.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            // Debug jQuery loading
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded');
                Swal.fire({
                    icon: 'error',
                    title: 'jQuery Error',
                    text: 'jQuery failed to load. Please check server file permissions or contact support.'
                });
                return;
            }
            console.log('jQuery version:', $.fn.jquery);

            // Debug datepicker loading
            if (typeof $.fn.datepicker === 'undefined') {
                console.error('jQuery UI Datepicker is not loaded');
                Swal.fire({
                    icon: 'error',
                    title: 'Datepicker Error',
                    text: 'jQuery UI Datepicker failed to load. Please check network or contact support.'
                });
            } else {
                console.log('jQuery UI Datepicker is loaded');
            }

            // Initialize DataTables
            var table = $('#datatable-buttons').DataTable({
                responsive: true,
                lengthChange: false,
                buttons: ['copy', 'excel', 'pdf', 'print', 'colvis'],
                columnDefs: [
                    {
                        targets: 4, // Date / Time Pengiriman column
                        type: 'date',
                        render: function(data) {
                            return data;
                        }
                    },
                    {
                        targets: 7, // Due Date column
                        type: 'date',
                        render: function(data) {
                            return data;
                        }
                    }
                ],
                drawCallback: function() {
                    // Reinitialize datepickers and check due dates after redraw
                    $('.due-date-picker').each(function() {
                        if (!$(this).hasClass('hasDatepicker')) {
                            console.log('Reinitializing jQuery UI datepicker for:', this);
                            try {
                                $(this).datepicker({
                                    dateFormat: 'yy-mm-dd',
                                    minDate: 0,
                                    showAnim: 'slideDown'
                                }).addClass('hasDatepicker');
                            } catch (e) {
                                console.error('Datepicker reinitialization error:', e);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Datepicker Error',
                                    text: 'Failed to initialize datepicker: ' + e.message
                                });
                            }
                        }
                    });
                    checkDueDates();
                }
            });

            console.log('DataTables initialized:', table);

            table.buttons().container().appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');

            // Initialize datepicker with event delegation
            $(document).on('change', '.due-date-picker', function() {
                console.log('Date selected:', $(this).val());
                var form = $(this).closest('form');
                var row = $(this).closest('tr');
                var cid = form.find('input[name="cid"]').val();
                var dueDate = $(this).val();
                var formData = {
                    cid: cid,
                    action: 'update_duedate',
                    due_date: dueDate
                };
                console.log('Sending AJAX request with data:', formData);
                $.ajax({
                    url: 'update_duedate.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    beforeSend: function() {
                        console.log('AJAX request starting to:', this.url, 'with data:', formData);
                    },
                    success: function(res) {
                        console.log('AJAX Response:', res);
                        if (res && res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                if (!row.find('.clear-due-date').length && res.due_date) {
                                    row.find('.due-date-container').append('<span class="clear-due-date" title="Clear due date"><i class="fa fa-times"></i></span>');
                                }
                                $(this).val(res.due_date);
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: res ? res.message : 'No response from server'
                            });
                            $(this).val('');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', status, error, xhr.responseText, 'URL:', this.url, 'Data:', formData);
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan AJAX',
                            text: 'Error: ' + status + ' - ' + (xhr.responseText || 'No response') + ' (URL: ' + this.url + ')'
                        });
                        $(this).val('');
                    }
                });
            });

            // Aktifkan tempo (hitungan jatuh tempo mulai dari tanggal yang dipilih admin)
$(document).on('click', '.tempo-go', function() {
    var box = $(this).closest('.tempo-box');
    var cid = box.data('cid');
    var start = box.find('.tempo-start').val();
    if (!start) { Swal.fire({ icon: 'warning', title: 'Pilih tanggal mulai tempo' }); return; }
    var btn = $(this).prop('disabled', true);
    $.ajax({
        url: 'update_duedate.php', type: 'POST', dataType: 'json', cache: false,
        data: { action: 'activate_tempo', cid: cid, start_date: start },
        success: function(res) {
            if (res && res.success) {
                Swal.fire({ icon: 'success', title: 'Tempo aktif', text: res.message, timer: 1800, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                btn.prop('disabled', false);
                Swal.fire({ icon: 'error', title: 'Gagal', text: res ? res.message : 'Tidak ada respons dari server' });
            }
        },
        error: function() { btn.prop('disabled', false); Swal.fire({ icon: 'error', title: 'Gagal menghubungi server' }); }
    });
});

// Trigger focus on click or calendar icon
            $(document).on('click', '.due-date-picker, .input-group-addon', function() {
                console.log('Click event on:', this);
                $(this).closest('.input-group').find('.due-date-picker').trigger('focus');
            });

            // Handle clear due date
            $(document).on('click', '.clear-due-date', function() {
                var row = $(this).closest('tr');
                var form = row.find('.update-duedate-form');
                var cid = form.find('input[name="cid"]').val();
                var formData = {
                    cid: cid,
                    action: 'update_duedate',
                    due_date: ''
                };
                console.log('Sending AJAX request to clear due date:', formData);
                $.ajax({
                    url: 'update_duedate.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    beforeSend: function() {
                        console.log('AJAX request starting to:', this.url, 'with data:', formData);
                    },
                    success: function(res) {
                        console.log('AJAX Response:', res);
                        if (res && res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                row.find('.due-date-picker').val('').removeClass('hasDatepicker');
                                row.find('.clear-due-date').remove();
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: res ? res.message : 'No response from server'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', status, error, xhr.responseText, 'URL:', this.url, 'Data:', formData);
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan AJAX',
                            text: 'Error: ' + status + ' - ' + (xhr.responseText || 'No response') + ' (URL: ' + this.url + ')'
                        });
                    }
                });
            });

            // Function to check and highlight due dates within one week
            function checkDueDates() {
                var now = new Date();
                var oneWeekLater = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000); // Add 7 days
                console.log('Checking due dates, now:', now.toISOString(), 'one week later:', oneWeekLater.toISOString());

                $('.due-date-text').each(function() {
                    var dueDateStr = $(this).data('due-date');
                    if (dueDateStr && dueDateStr !== 'Not set') {
                        var dueDate = new Date(dueDateStr);
                        console.log('Due date:', dueDate.toISOString(), 'for element:', this);
                        if (dueDate >= now && dueDate <= oneWeekLater) {
                            $(this).addClass('due-date-yellow');
                            console.log('Highlighted due date:', dueDateStr);
                        } else {
                            $(this).removeClass('due-date-yellow');
                        }
                    }
                });
            }

            $('#bd-desde, #bd-hasta').on('change', function() {
                var startDate = $('#bd-desde').val();
                var endDate = $('#bd-hasta').val();
                console.log('Filtering dates:', startDate, 'to', endDate);
                table.column(4).search(function(value) {
                    if (!startDate && !endDate) return true;
                    if (!value) return false;
                    var date = new Date(value);
                    if (startDate && new Date(startDate) > date) return false;
                    if (endDate && new Date(endDate) < date) return false;
                    return true;
                }).draw();
            });

            $('.update-bookmode-form select').on('change', function() {
                var form = $(this).closest('form');
                var row = $(this).closest('tr');
                var cid = form.find('input[name="cid"]').val();
                var formData = form.serialize();
                console.log('Sending AJAX request with data:', formData);
                $.ajax({
                    url: 'update_bookmode.php',
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
                                text: res.message,
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                if (res.book_mode !== 'ToPay') {
                                    console.log('Removing row for cid:', cid);
                                    table.row(row).remove().draw(false);
                                } else {
                                    console.log('Updating row for cid:', cid, 'to book_mode:', res.book_mode);
                                    row.find('td:eq(6)').html('<span class="label ' + res.book_mode + ' label-large">' + res.book_mode + '</span>');
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: res.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', status, error, xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan AJAX',
                            text: 'Error: ' + status + ' - ' + xhr.responseText
                        });
                    }
                });
            });

            window.reportepaymenttopaidPDF = function() {
                var startDate = $('#bd-desde').val();
                var endDate = $('#bd-hasta').val();
                var url = 'generate_pdf.php?start=' + encodeURIComponent(startDate) + '&end=' + encodeURIComponent(endDate) + '&mode=ToPay';
                window.open(url, '_blank');
            };

            // Initial due date check
            checkDueDates();
        });
    })(jQuery);
</script>
</body>
</html>
<?php ob_end_flush(); ?>