<?php
ob_start();
error_reporting(E_ERROR | E_WARNING | E_PARSE);
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
}
require_once('database.php');
require_once('library.php');
require_once('courier-lib.php');
try {
    requireAdmin();
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
courier_columns_ready();

$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator';
$isEmployee = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Employee';
// Pegawai hanya melihat pengiriman miliknya
$scope = $isEmployee ? " AND officename = ?" : "";
$scopeParams = $isEmployee ? [$_SESSION['user_name']] : [];

function dash_count($where, $params = []) {
    try {
        $r = dbFetchAssoc(dbQuery("SELECT COUNT(*) AS c FROM courier WHERE $where", $params));
        return (int)($r['c'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}
$cActive   = dash_count("status <> 'Delivered'" . $scope, $scopeParams);
$cTransit  = dash_count("status = 'In-Transit'" . $scope, $scopeParams);
$cHold     = dash_count("status = 'On-Hold'" . $scope, $scopeParams);
$cTempo    = dash_count("payment_term IN ('7','14','30') AND (due_date IS NULL OR due_date = '' OR due_date = '0000-00-00') AND status <> 'Delivered'" . $scope, $scopeParams);
$cDoneMon  = dash_count("status = 'Delivered' AND last_updated >= ?" . $scope, array_merge([date('Y-m-01')], $scopeParams));

function dash_status($s) {
    $map = ['In-Transit' => ['On Delivery', 'st-go'], 'On-Hold' => ['On Hold', 'st-hold'], 'Finished' => ['Finished', 'st-fin'],
            'Landed' => ['Arrived', 'st-arr'], 'Delayed' => ['Delayed', 'st-late'], 'Delivered' => ['Delivered', 'st-fin']];
    return $map[$s] ?? [$s, 'st-fin'];
}
// ETA: tampil tanggalnya saja, kosong kalau belum diisi
function dash_eta($row) {
    $v = trim((string)($row['eta'] ?? ''));
    return ($v === '' || strpos($v, '0000') === 0) ? '-' : substr($v, 0, 10);
}
// Angka format Indonesia (koma desimal), tanpa nol di belakang; 0 tampil '-'
function dash_num($v) {
    if (!is_numeric($v) || (float)$v == 0) { return '-'; }
    return rtrim(rtrim(number_format((float)$v, 3, ',', '.'), '0'), ',');
}

function dash_pay($row) {
    $t = (string)($row['payment_term'] ?? '');
    if (in_array($t, ['7', '14', '30'], true)) { return 'Tempo ' . $t . ' hari'; }
    if (($row['book_mode'] ?? '') === 'ToPay') { return 'Tempo'; }
    return 'Cash';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public_html/logoAli.png">
    <title>PT. ANEKA LINTAS INDONESIA</title>
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet">
    <link href="assets/plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.css" rel="stylesheet" type="text/css">
    <script src="assets/js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include("header.php"); ?>
<div class="wrapper">
    <div class="container">
        <?php include("icon_settings.php"); ?>
        <div class="ac-page ac-wide" style="max-width:1280px">
            <div class="ac-title">
                <h3><i class="fa fa-tachometer"></i> Dashboard</h3>
                <span class="ac-who"><?php echo htmlspecialchars(date('d-m-Y')); ?> &middot; <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
            </div>

            <div class="dash-tiles">
                <a class="dash-tile" href="#" data-filter=""><span>Pengiriman aktif</span><b><?php echo $cActive; ?></b></a>
                <a class="dash-tile" href="#" data-filter="On Delivery"><span>On Delivery</span><b><?php echo $cTransit; ?></b></a>
                <a class="dash-tile" href="#" data-filter="On Hold"><span>On Hold</span><b><?php echo $cHold; ?></b></a>
                <a class="dash-tile warn" href="laporan-piutang.php"><span>Tempo belum aktif</span><b><?php echo $cTempo; ?></b></a>
                <div class="dash-tile ok"><span>Selesai bulan ini</span><b><?php echo $cDoneMon; ?></b></div>
            </div>

            <section class="ac-card">
                <div class="dash-head">
                    <h4><i class="fa fa-truck"></i> Pengiriman berjalan</h4>
                    <div class="dash-actions">
                        <a class="ac-btn" href="add-courier.php"><i class="fa fa-plus"></i> Tambah pengiriman</a>
                        <?php if ($isAdmin) { ?><a class="ac-btn sec" href="export-monthly.php"><i class="fa fa-file-excel-o"></i> Export Excel</a><?php } ?>
                    </div>
                </div>
                <div class="ac-table-wrap">
                    <table id="dash-table" class="ac-table dash-table" width="100%">
                        <thead>
                            <tr>
                                <th>Tracking</th>
                                <th>Pengirim</th>
                                <th>Penerima</th>
                                <th>Tujuan</th>
                                <th>Tgl diterima</th>
                                <th>ETA</th>
                                <th>Berat (kg)</th>
                                <th>Volume (m³)</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $stmt = dbQuery("SELECT c.*, COALESCE(t.w, 0) AS tot_w, COALESCE(t.v, 0) AS tot_v FROM courier c LEFT JOIN (SELECT courier_id, SUM(quantity * weight) AS w, SUM(quantity * volume) AS v FROM courier_items GROUP BY courier_id) t ON t.courier_id = c.cid WHERE c.status <> 'Delivered'" . $scope . " ORDER BY c.collection_date DESC, c.cid DESC", $scopeParams);
                            while ($row = dbFetchAssoc($stmt)) {
                                [$stLabel, $stClass] = dash_status($row['status']);
                                $cid = (int)$row['cid'];
                        ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($row['cons_no']); ?></b></td>
                                <td><?php echo htmlspecialchars($row['ship_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['rev_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['pickup_time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(ali_ship_date($row)); ?></td>
                                <td><?php echo htmlspecialchars(dash_eta($row)); ?></td>
                                <td class="num"><?php echo htmlspecialchars(dash_num($row['tot_w'])); ?></td>
                                <td class="num"><?php echo htmlspecialchars(dash_num($row['tot_v'])); ?></td>
                                <td><span class="st <?php echo $stClass; ?>"><?php echo htmlspecialchars($stLabel); ?></span></td>
                                <td class="dash-act">
                                    <?php if ($isAdmin) { ?>
                                        <a class="act-btn" href="edit-courier.php?cid=<?php echo $cid; ?>" title="Buka halaman edit (pengiriman diselesaikan dari sini)"><i class="fa fa-pencil"></i> Edit / Selesaikan</a>
                                        <button type="button" class="act-btn del" data-cid="<?php echo $cid; ?>" data-no="<?php echo htmlspecialchars($row['cons_no']); ?>" title="Hapus"><i class="fa fa-trash"></i></button>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='10'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <?php include("footer.php"); ?>
    </div>
</div>
<script>var resizefunc = [];</script>
<script src="assets/js/tether.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.nicescroll.js"></script>
<script src="assets/plugins/switchery/switchery.min.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js"></script>
<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#dash-table').DataTable({
        pageLength: 20,
        lengthChange: true,
        lengthMenu: [[20, 50, 100, -1], [20, 50, 100, 'Semua']],
        order: [[4, 'desc']],
        columnDefs: [{ targets: 9, orderable: false, searchable: false }, { targets: [6, 7], className: 'text-right' }],
        language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: '_START_–_END_ dari _TOTAL_ pengiriman', infoEmpty: 'Tidak ada pengiriman berjalan', zeroRecords: 'Tidak ada data yang cocok', emptyTable: 'Tidak ada pengiriman berjalan', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } }
    });

    // Klik kotak ringkasan = filter status
    $('.dash-tile[data-filter]').on('click', function(e) {
        e.preventDefault();
        table.column(8).search($(this).data('filter') || '').draw();
        $('.dash-tile').removeClass('on'); $(this).addClass('on');
    });

    $('#dash-table').on('click', '.act-btn.del', function() {
        var cid = $(this).data('cid'), no = $(this).data('no');
        if (confirm('Hapus pengiriman ' + no + '? Data tidak bisa dikembalikan.')) {
            window.location = 'delete_list_admin.php?action=del&cid=' + cid;
        }
    });
});
</script>
</body>
</html>
