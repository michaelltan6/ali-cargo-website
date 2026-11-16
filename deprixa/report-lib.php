<?php
// Kerangka bersama halaman Laporan.
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/library.php';
require_once __DIR__ . '/courier-lib.php';
date_default_timezone_set('Asia/Jakarta');

function rp_is_admin() { return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Administrator'; }

// $adminOnly = true: pegawai dialihkan ke dashboard.
function rp_boot($adminOnly = true) {
    if (session_status() === PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); } }
    requireAdmin();
    if ($adminOnly && !rp_is_admin()) { header('Location: admin.php'); exit; }
    courier_columns_ready();
}

// Pegawai hanya melihat pengiriman miliknya.
function rp_scope(&$params) {
    if (!rp_is_admin()) { $params[] = $_SESSION['user_name'] ?? ''; return " AND officename = ?"; }
    return "";
}

function rp_month($default = '') {
    $m = (string)($_GET['month'] ?? $default);
    return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m) ? $m : '';
}
function rp_month_range($m) { return [$m . '-01', date('Y-m-d', strtotime($m . '-01 +1 month'))]; }
function rp_d($ymd) { $ymd = trim((string)$ymd); if ($ymd === '' || strpos($ymd, '0000') === 0) { return ''; } $t = strtotime($ymd); return $t ? date('d-m-Y', $t) : ''; }
function rp_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function rp_pay_label($row) {
    $t = (string)($row['payment_term'] ?? '');
    if (in_array($t, ['7', '14', '30'], true)) { return 'Tempo ' . $t . ' hari'; }
    return ($row['book_mode'] ?? '') === 'ToPay' ? 'Tempo (lama belum diatur)' : 'Cash';
}

// Status tempo: [label, kelas, kunci urut]
function rp_tempo_state($row) {
    $t = (string)($row['payment_term'] ?? '');
    $due = trim((string)($row['due_date'] ?? ''));
    if ($due === '' || strpos($due, '0000') === 0) {
        return in_array($t, ['7', '14', '30'], true) ? ['Belum aktif', 'st-hold', '9999-99-99'] : ['Lama tempo belum diatur', 'st-hold', '9999-99-99'];
    }
    $days = (int)floor((strtotime($due) - strtotime(date('Y-m-d'))) / 86400);
    if ($days < 0) { return ['Lewat ' . abs($days) . ' hari', 'st-late', $due]; }
    if ($days === 0) { return ['Jatuh tempo hari ini', 'st-late', $due]; }
    return [$days . ' hari lagi', 'st-go', $due];
}

function rp_start($title, $icon, $subtitle = '') {
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
<?php include(__DIR__ . "/header.php"); ?>
<div class="wrapper">
    <div class="container">
        <?php include(__DIR__ . "/icon_settings.php"); ?>
        <div class="ac-page ac-wide" style="max-width:1280px">
            <div class="ac-title">
                <h3><i class="fa fa-<?php echo rp_e($icon); ?>"></i> <?php echo rp_e($title); ?></h3>
                <span class="ac-who"><?php echo rp_e($subtitle); ?></span>
            </div>
<?php
}

function rp_end($js = '') {
    ?>
        </div>
        <?php include(__DIR__ . "/footer.php"); ?>
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
var RP_LANG = { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data', info: '_START_–_END_ dari _TOTAL_ data', infoEmpty: 'Tidak ada data', infoFiltered: '(dari _MAX_ data)', zeroRecords: 'Tidak ada data yang cocok', emptyTable: 'Tidak ada data', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } };
function rpNotify(o) { if (window.Swal) { return Swal.fire(o); } alert((o.title ? o.title + ': ' : '') + (o.text || '')); return Promise.resolve({ isConfirmed: true }); }
function rpApi(data, done) {
    $.ajax({ url: 'report-api.php', type: 'POST', dataType: 'json', data: data,
        success: function(res) { if (res && res.success) { done(res); } else { rpNotify({ icon: 'error', title: 'Gagal', text: res ? res.message : 'Tidak ada respons dari server' }); } },
        error: function() { rpNotify({ icon: 'error', title: 'Gagal menghubungi server' }); } });
}
$(function() {
<?php echo $js; ?>
});
</script>
</body>
</html>
<?php
}

// Bar bulan: <input month> + tautan "Semua bulan"
function rp_month_bar($page, $month, $extraHidden = []) {
    echo '<form method="get" action="' . rp_e($page) . '" class="rp-bar">';
    foreach ($extraHidden as $k => $v) { echo '<input type="hidden" name="' . rp_e($k) . '" value="' . rp_e($v) . '">'; }
    echo '<label for="month">Bulan</label> <input type="month" id="month" name="month" value="' . rp_e($month) . '"> ';
    echo '<button type="submit" class="act-btn">Tampilkan</button> <a class="act-btn" href="' . rp_e($page) . '?month=all">Semua bulan</a></form>';
}
