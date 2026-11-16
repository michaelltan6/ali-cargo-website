<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
require_once('database.php');
require_once('library.php');

try {
    isUser();
    error_log("Session data in Shippinglist_paid.php: " . print_r($_SESSION, true));
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include("header.php"); ?>
<div class="wrapper">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="card-box table-responsive">
                    <h4 class="header-title m-t-0 m-b-20">Daftar Pengiriman Sudah Dibayar</h4>
                    <table border="0" align="center">
                        <tr>
                            <td><strong>    Rentang Tanggal</strong>    </td>
                            <td><i class="icon-append fa fa-calendar"></i>  <input type="date" id="bd-desde" class="gentxt1"/></td>
                            <td>    TO    </td>
                            <td><i class="icon-append fa fa-calendar"></i>  <input type="date" id="bd-hasta" class="gentxt1"/></td>
                            <td>            <a target="_blank" href="javascript:reportepaymentpaidPDF();" class="btn btn-primary">Export ke <strong>PDF</strong></a></td>
                        </tr>
                    </table>
                    <table id="datatable-buttons" class="table table-striped table-bordered" cellspacing="0" width="100%">
                        <style>
                            .Finished { background: #363C56; }
                            .Delayed { background: #F76063; }
                            .On-Hold { background: #4ECCDB; }
                            .Landed { background: #FF8A4B; }
                            .In-Transit { background: #00D96D; }
                            .Paid { background: #675F99; }
                            .ToPay { background: #FF8441; }
                            .Cash-on-Delivery { background: #F6565A; }
                            .label { padding: 5px; }
                        </style>
                        <thead>
                            <tr>
                                <th>Cetak Invoice</th>
                                <th># Tracking</th>
                                <th>Nama Pengirim</th>
                                <th>Destinasi</th>
                                <th>Date / Time Pengiriman</th>
                                <th>Karyawan</th>
                                <th>Status Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            // Debug specific shipment
                            $debug = dbFetchAssoc(dbQuery("SELECT cid, cons_no, book_mode, officename, status FROM courier WHERE cid = :cid", ['cid' => 29]));
                            error_log("Debug cid=29: " . print_r($debug, true));
                            // Main query
                            $sql = "SELECT * FROM courier WHERE book_mode = 'Paid'";
                            $params = [];
                            if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee') {
                                $sql .= " AND officename = :officename";
                                $params = ['officename' => $_SESSION['user_name']];
                            }
                            $sql .= " ORDER BY cid";
                            error_log("Paid query: $sql, params: " . print_r($params, true));
                            $stmt = dbQuery($sql, $params);
                            $rowCount = dbNumRows($stmt);
                            error_log("Paid query returned $rowCount rows");
                            if ($rowCount == 0) {
                                echo "<tr><td colspan='7'>No paid shipments found.</td></tr>";
                            }
                            while ($row = dbFetchAssoc($stmt)) {
                                error_log("Paid shipment found: cid={$row['cid']}, cons_no={$row['cons_no']}, book_mode={$row['book_mode']}, officename={$row['officename']}, status={$row['status']}");
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
                                    <span class="label <?php echo htmlspecialchars($row['book_mode']); ?> label-large"><?php echo htmlspecialchars($row['book_mode']); ?></span>
                                </td>
                            </tr>
                        <?php
                            }
                        } catch (PDOException $e) {
                            error_log("Paid query error: " . $e->getMessage());
                            echo "<tr><td colspan='7'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
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
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js"></script>
<script src="js/myjava.js"></script>
<script src="js/payments_list.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(document).ready(function() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }
        console.log('jQuery version:', jQuery.fn.jquery);

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
                }
            ]
        });

        console.log('DataTables initialized:', table);

        table.buttons().container().appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');

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

        window.reportepaymentpaidPDF = function() {
            var startDate = $('#bd-desde').val();
            var endDate = $('#bd-hasta').val();
            var url = 'generate_pdf.php?start=' + encodeURIComponent(startDate) + '&end=' + encodeURIComponent(endDate) + '&mode=Paid';
            window.open(url, '_blank');
        };
    });
</script>
</body>
</html>
<?php ob_end_flush(); ?>