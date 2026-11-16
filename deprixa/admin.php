<?php
ob_start();
error_reporting(E_ERROR | E_WARNING | E_PARSE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('database.php');
require_once('library.php');

$company = dbFetchAssoc(dbQuery("SELECT * FROM company")) ?: ['currency' => 'IDR'];

try {
    requireAdmin();
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
    <link rel="icon" type="image/png" href="public_html/logoAli.png">
    <title>PT. ANEKA LINTAS INDONESIA</title>
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet">
    <link href="assets/plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.css" rel="stylesheet" type="text/css">
    <style>
        .delivered { background: #363C56; }
        .Delayed { background: #F76063; }
        .On-Hold { background: #4ECCDB; }
        .Landed { background: #FF8A4B; }
        .Finished { background: #333333; }
        .label { padding: 5px; }
        .In-Transit { background: #00D96D; }
        .OK { background: #00D96D; }
        .space { background: #F1B53D; }
        .Paid { background: #675F99; }
        .ToPay { background: #FF8441; }
        .Cash-on-Delivery { background: #F6565A; }
        .Shipment-arrived { background: #FFC734; }
        .Returned { background: #F76063; }
        .Pending { background: #FF5D48; }
        .Bank { background: #999; }
        .Paypal { background: #4DD2FF; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        function del_list_admin(cid) {
            if (window.confirm("Aviso:\n Sure you want to delete the selected file?")) {
                window.location = "delete_list_admin.php?action=del&cid=" + cid;
            }
        }

        function updateDeliveryStatus(cid) {
            if (window.confirm("Sure like to change the status of shipping?")) {
                $.ajax({
                    url: 'process.php',
                    method: 'GET',
                    data: { action: 'delivered', cid: cid },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.notification,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Refresh the page
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred: ' + error
                        });
                    }
                });
            }
        }
    </script>
</head>
<body>
<?php include("header.php"); ?>
<div class="wrapper">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <!-- Administrator Dashboard -->
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator') { ?>
            <div class="row">
                <?php
                $stmt = dbQuery("SELECT * FROM courier WHERE status = 'In-Transit'");
                $numero1 = $stmt ? dbNumRows($stmt) : 0;
                ?>
                <div class="col-xs-6 col-md-3 col-lg-3 col-xl-2">
                    <div class="card-box tilebox-one">
                        <i class="icon-plane pull-xs-right text-muted"></i>
                        <h6 class="text-muted text-uppercase m-b-20">Pengiriman Sedang Transit</h6>
                        <h2 class="m-b-20" data-plugin="counterup"><?php echo $numero1; ?></h2>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- Employee Dashboard -->
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee') { ?>
            <div class="row">
                <?php
                $stmt = dbQuery("SELECT * FROM courier WHERE status = 'In-Transit' AND officename = :officename", ['officename' => $_SESSION['user_name']]);
                $numero1 = $stmt ? dbNumRows($stmt) : 0;
                ?>
                <div class="col-xs-6 col-md-3 col-lg-3 col-xl-2">
                    <div class="card-box tilebox-one">
                        <i class="icon-plane pull-xs-right text-muted"></i>
                        <h6 class="text-muted text-uppercase m-b-20">Shipping In Transit</h6>
                        <h2 class="m-b-20" data-plugin="counterup"><?php echo $numero1; ?></h2>
                        <span class="text-muted">Pengiriman yang Transit</span>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="row">
            <div class="col-xs-12 col-lg-12 col-xl-9">
                <div class="card-box">
                    <h4 class="header-title m-t-0 m-b-20">Daftar Pengiriman</h4>
                    <table border="0" align="center">
                        <tr>
                            <td><strong>    Rentang Tanggal</strong>    </td>
                            <td><i class="icon-append fa fa-calendar"></i>  <input type="date" id="bd-desde" class="gentxt1"/></td>
                            <td>    TO    </td>
                            <td><i class="icon-append fa fa-calendar"></i>  <input type="date" id="bd-hasta" class="gentxt1"/></td>
                            <td>            <a target="_blank" href="javascript:reportepaymentPDF();" class="btn btn-primary">Export ke <strong>PDF</strong></a></td>
                        </tr>
                    </table>
                    <div class="table-responsive">
                        <div class="col-xs-12 col-lg-12 col-xl-12">
                            <ul class="nav nav-tabs m-b-10" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-expanded="true">
                                        <i class="icon-plane"></i> Daftar Utama Pengiriman</a>
                                </li>
                            </ul>
                            <br><br>
                            <div class="tab-content" id="myTabContent">
                                <div role="tabpanel" class="tab-pane fade in active" id="home" aria-labelledby="home-tab">
                                    <table id="datatable-buttons" class="table table-striped table-bordered" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator') { ?>
                                                    <th>Edit</th>
                                                    <th>Delete</th>
                                                <?php } ?>
                                                <th>Diterima</th>
                                                <th>Print</th>
                                                <th>Barcode</th>
                                                <th>Tracking</th>
                                                <th>Metode Pembayaran</th>
                                                <th>Pengirim</th>
                                                <th>Penerima</th>
                                                <th>Tanggal</th>
                                                <th>Pegawai</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            try {
                                                $sql = "SELECT * FROM courier WHERE status != :status";
                                                $params = ['status' => 'delivered'];
                                                if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee') {
                                                    $sql .= " AND officename = :officename";
                                                    $params['officename'] = $_SESSION['user_name'];
                                                }
                                                $sql .= " ORDER BY cid DESC";
                                                $stmt = dbQuery($sql, $params);
                                                $rowCount = dbNumRows($stmt);
                                                if ($rowCount === 0) {
                                                    echo "<tr><td colspan='12'>No shipments found.</td></tr>";
                                                }
                                                while ($row = dbFetchAssoc($stmt)) {
                                            ?>
                                                    <tr>
                                                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator') { ?>
                                                            <td align="center">
                                                                <a href="edit-courier.php?cid=<?php echo htmlspecialchars($row['cid']); ?>">
                                                                    <img src="images/edit.png" height="20" width="20" alt="Edit">
                                                                </a>
                                                            </td>
                                                            <td align="center">
                                                                <a href="#" onclick="del_list_admin(<?php echo htmlspecialchars($row['cid']); ?>); return false;">
                                                                    <img src="images/delete.png" height="20" width="18" alt="Delete">
                                                                </a>
                                                            </td>
                                                        <?php } ?>
                                                        <td align="center">
                                                            <a href="#" onclick="updateDeliveryStatus(<?php echo htmlspecialchars($row['cid']); ?>); return false;">
                                                                <img src="images/delivery.png" height="20" width="20" alt="Confirm">
                                                            </a>
                                                        </td>
                                                        <td align="center">
                                                            <a target="_blank" href="print-invoice/invoice-print.php?cid=<?php echo htmlspecialchars($row['cid']); ?>">
                                                                <img src="images/print.png" height="20" width="20" alt="Print">
                                                            </a>
                                                        </td>
                                                        <td align="center">
                                                            <a href="barcode/html/BCGcode39.php?cons_no=<?php echo htmlspecialchars($row['cons_no']); ?>" target="_blank">
                                                                <img src="images/barcode.png" height="20" width="20" alt="Barcode">
                                                            </a>
                                                        </td>
                                                        <td><font color="#000"><?php echo htmlspecialchars($row['cons_no']); ?></font></td>
                                                        <td><span class="label <?php echo htmlspecialchars($row['book_mode']); ?> label-large"><?php echo htmlspecialchars($row['book_mode']); ?></span></td>
                                                        <td><?php echo htmlspecialchars($row['ship_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['rev_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['pick_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['user']); ?></td>
                                                        <td><span class="label <?php echo htmlspecialchars($row['status']); ?> label-large"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                                    </tr>
                                            <?php
                                                }
                                            } catch (PDOException $e) {
                                                echo "<tr><td colspan='12'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-lg-12 col-xl-3">
                    <div class="card-box">
                        <h4 class="header-title m-t-0 m-b-30">Pengiriman Terakhir</h4>
                        <div class="table-responsive">
                            <table id="datatable-recent" class="table table-striped table-bordered" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Tracking</th>
                                        <th>Tanggal Dimulai</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $sql = "SELECT * FROM courier WHERE LEFT(book_date, 10) = CURDATE()";
                                        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee') {
                                            $sql .= " AND officename = :officename";
                                            $stmt = dbQuery($sql, ['officename' => $_SESSION['user_name']]);
                                        } else {
                                            $stmt = dbQuery($sql);
                                        }
                                        $rowCount = dbNumRows($stmt);
                                        if ($rowCount === 0) {
                                            echo "<tr><td colspan='3'>No recent shipments found.</td></tr>";
                                        }
                                        while ($row = dbFetchAssoc($stmt)) {
                                    ?>
                                            <tr>
                                                <td><font color="#000"><?php echo htmlspecialchars($row['cons_no']); ?></font></td>
                                                <td><?php echo htmlspecialchars($row['book_date']); ?></td>
                                                <td><span class="label <?php echo htmlspecialchars($row['status']); ?> label-large"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                            </tr>
                                    <?php
                                        }
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='3'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include("footer.php"); ?>
        </div>
    </div>
    <script>
        var resizefunc = [];
    </script>
    <script src="assets/js/tether.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.nicescroll.js"></script>
    <script src="assets/plugins/switchery/switchery.min.js"></script>
    <script src="assets/plugins/waypoints/lib/jquery.waypoints.js"></script>
    <script src="assets/plugins/counterup/jquery.counterup.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
    <script src="assets/pages/jquery.dashboard.js"></script>
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
    <script>
        $(document).ready(function() {
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded');
                return;
            }
            console.log('jQuery version:', jQuery.fn.jquery);

            var tableMain = $('#datatable-buttons').DataTable({
                responsive: true,
                lengthChange: false,
                buttons: ['copy', 'excel', 'pdf', 'print', 'colvis'],
                columnDefs: [
                    {
                        targets: [0, 1],
                        visible: <?php echo isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator' ? 'true' : 'false'; ?>,
                        searchable: true
                    },
                    {
                        targets: 9, // Tanggal column
                        type: 'date',
                        render: function(data) {
                            return data;
                        }
                    }
                ]
            });

            var tableRecent = $('#datatable-recent').DataTable({
                responsive: true,
                lengthChange: false,
                buttons: ['copy', 'excel', 'pdf', 'print', 'colvis'],
                columnDefs: [
                    {
                        targets: 1, // Tanggal Dimulai column
                        type: 'date',
                        render: function(data) {
                            return data;
                        }
                    }
                ]
            });

            tableMain.buttons().container().appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
            tableRecent.buttons().container().appendTo('#datatable-recent_wrapper .col-md-6:eq(0)');

            // Date filter for main table
            $('#bd-desde, #bd-hasta').on('change', function() {
                var startDate = $('#bd-desde').val();
                var endDate = $('#bd-hasta').val();
                tableMain.column(9).search(function(value) {
                    if (!startDate && !endDate) return true;
                    if (!value) return false;
                    var date = new Date(value);
                    if (startDate && new Date(startDate) > date) return false;
                    if (endDate && new Date(endDate) < date) return false;
                    return true;
                }).draw();
                tableRecent.column(1).search(function(value) {
                    if (!startDate && !endDate) return true;
                    if (!value) return false;
                    var date = new Date(value);
                    if (startDate && new Date(startDate) > date) return false;
                    if (endDate && new Date(endDate) < date) return false;
                    return true;
                }).draw();
            });

            window.reportepaymentPDF = function() {
                var startDate = $('#bd-desde').val();
                var endDate = $('#bd-hasta').val();
                var url = 'generate_pdf.php?start=' + encodeURIComponent(startDate) + '&end=' + encodeURIComponent(endDate) + '&mode=All';
                window.open(url, '_blank');
            };
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>