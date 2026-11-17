<?php
if (session_status() === PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); } }
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
require_once('database.php');
require_once('library.php');
require_once('funciones.php');
require_once('customer-lib.php');
requireAdmin();

$q = cp_clean($_GET['q'] ?? '', 100);
$rows = [];
$total = 0;
try {
    $rows = cp_search($q, 200);
    $total = (int)(dbFetchAssoc(dbQuery("SELECT COUNT(*) AS n FROM customer_profile"))['n'] ?? 0);
} catch (Exception $e) {
    error_log('customer-profile.php: ' . $e->getMessage());
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile - PT. ANEKA LINTAS INDONESIA</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/simple-line-icons/css/simple-line-icons.css" type="text/css" />
    <link rel="stylesheet" href="css/font.css" type="text/css" />
    <script src="js/jquery-1.11.1.min.js"></script>
    <style>
        .cp-page { max-width: 1100px; margin: 0 auto; }
        .cp-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin: 6px 0 16px; }
        .cp-head h3 { margin: 0; font-size: 1.5rem; font-weight: 600; }
        .cp-head small { color: #777; font-weight: 400; }
        .cp-tools { display: flex; gap: 10px; flex-wrap: wrap; }
        .cp-tools input[type=search] { width: 280px; max-width: 100%; padding: 9px 12px; border: 1px solid #c9ced6; border-radius: 8px; font-size: .95rem; }
        .cp-btn { border: 0; border-radius: 8px; padding: 9px 16px; font-size: .95rem; cursor: pointer; background: #1f9d55; color: #fff; }
        .cp-btn:hover { background: #17824a; }
        .cp-btn.sec { background: #eef0f3; color: #333; }
        .cp-btn.sec:hover { background: #e0e4e9; }
        .cp-btn.dng { background: #fff; color: #c0392b; border: 1px solid #e6b5af; padding: 5px 10px; font-size: .85rem; }
        .cp-btn.dng:hover { background: #fdecea; }
        .cp-btn.sm { padding: 5px 10px; font-size: .85rem; }
        .cp-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border: 1px solid #e3e6ea; border-radius: 10px; overflow: hidden; }
        .cp-table th { text-align: left; font-size: .78rem; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; background: #f6f7f9; padding: 10px 14px; border-bottom: 1px solid #e3e6ea; }
        .cp-table td { padding: 12px 14px; border-bottom: 1px solid #eef0f3; vertical-align: top; }
        .cp-table tr:last-child td { border-bottom: 0; }
        .cp-name { font-weight: 600; }
        .cp-code { display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 999px; background: #eaf3ff; color: #1b5fb5; font-size: .75rem; font-weight: 600; }
        .cp-muted { color: #6b7280; font-size: .9rem; }
        .cp-actions { white-space: nowrap; text-align: right; }
        .cp-empty { padding: 34px 16px; text-align: center; color: #6b7280; background: #fff; border: 1px dashed #cfd5dc; border-radius: 10px; }
        .cp-modal { position: fixed; inset: 0; background: rgba(20,24,30,.55); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 16px; }
        .cp-modal.open { display: flex; }
        .cp-box { background: #fff; border-radius: 12px; width: 560px; max-width: 100%; max-height: 92vh; overflow: auto; padding: 22px 24px; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
        .cp-box h4 { margin: 0 0 14px; font-weight: 600; }
        .cp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 14px; }
        .cp-grid .full { grid-column: 1 / -1; }
        .cp-grid label { display: block; font-size: .82rem; font-weight: 600; color: #444; margin-bottom: 4px; }
        .cp-grid input, .cp-grid textarea { width: 100%; box-sizing: border-box; padding: 9px 11px; border: 1px solid #c9ced6; border-radius: 8px; font-size: .95rem; font-family: inherit; }
        .cp-grid textarea { min-height: 84px; resize: vertical; }
        .cp-grid input:focus, .cp-grid textarea:focus, .cp-tools input:focus { outline: 2px solid #1f9d55; outline-offset: 1px; border-color: #1f9d55; }
        .cp-foot { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; align-items: center; }
        .cp-msg { margin-right: auto; font-size: .9rem; color: #c0392b; }
        .cp-toast { position: fixed; right: 18px; bottom: 18px; background: #1f2937; color: #fff; padding: 11px 16px; border-radius: 8px; font-size: .92rem; opacity: 0; transform: translateY(8px); transition: .25s; z-index: 10000; pointer-events: none; }
        .cp-toast.show { opacity: 1; transform: none; }
        @media (max-width: 640px) { .cp-grid { grid-template-columns: 1fr; } .cp-table th:nth-child(3), .cp-table td:nth-child(3) { display: none; } }
    </style>
</head>
<body>
<?php include("header.php"); ?>
<div class="wrapper" id="main">
    <div class="container">
        <div class="cp-page">
            <div class="cp-head">
                <h3>Customer Profile <small>(<?php echo $total; ?> customer)</small></h3>
                <form class="cp-tools" method="get" action="customer-profile.php">
                    <input type="search" name="q" value="<?php echo h($q); ?>" placeholder="Cari nama PT, PIC, kode, atau nomor...">
                    <button class="cp-btn sec" type="submit">Cari</button>
                    <button class="cp-btn" type="button" id="cp-add">+ Tambah Customer</button>
                </form>
            </div>

            <?php if (!$rows): ?>
                <div class="cp-empty">
                    <?php echo $q !== '' ? 'Tidak ada customer yang cocok dengan pencarian "' . h($q) . '".' : 'Belum ada customer. Klik "Tambah Customer" untuk menambah yang pertama.'; ?>
                </div>
            <?php else: ?>
            <table class="cp-table">
                <thead><tr><th>Perusahaan</th><th>PIC / Sales</th><th>Alamat</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr data-id="<?php echo (int)$r['id']; ?>" data-row="<?php echo h(json_encode($r, JSON_UNESCAPED_UNICODE)); ?>">
                        <td><span class="cp-name"><?php echo h($r['company_name']); ?></span><?php if ($r['code'] !== ''): ?><span class="cp-code"><?php echo h($r['code']); ?></span><?php endif; ?></td>
                        <td><?php echo h($r['pic_name']); ?><?php if ($r['pic_phone'] !== ''): ?><div class="cp-muted"><?php echo h($r['pic_phone']); ?></div><?php endif; ?><?php if (!empty($r['email'])): ?><div class="cp-muted"><?php echo h($r['email']); ?></div><?php endif; ?></td>
                        <td class="cp-muted"><?php echo nl2br(h($r['address'])); ?></td>
                        <td class="cp-actions">
                            <button type="button" class="cp-btn sec sm cp-edit">Ubah</button>
                            <button type="button" class="cp-btn dng cp-del">Hapus</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php include("footer.php"); ?>
</div>

<div class="cp-modal" id="cp-modal" role="dialog" aria-modal="true" aria-labelledby="cp-title">
    <form class="cp-box" id="cp-form" autocomplete="off">
        <h4 id="cp-title">Tambah Customer</h4>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="cp-id" value="">
        <div class="cp-grid">
            <div class="full"><label for="cp-company">Nama PT / Perusahaan *</label><input type="text" name="company_name" id="cp-company" maxlength="190" required></div>
            <div><label for="cp-pic">Nama PIC / Sales</label><input type="text" name="pic_name" id="cp-pic" maxlength="120"></div>
            <div><label for="cp-phone">Nomor kontak PIC</label><input type="text" name="pic_phone" id="cp-phone" maxlength="40" inputmode="tel" placeholder="0812xxxxxxx"></div>
            <div class="full"><label for="cp-email">Email penagihan (untuk pengingat jatuh tempo)</label><input type="text" name="email" id="cp-email" maxlength="255" placeholder="finance@perusahaan.com (boleh beberapa, pisah koma)"></div>
            <div class="full"><label for="cp-address">Alamat PT</label><textarea name="address" id="cp-address" maxlength="500"></textarea></div>
            <div><label for="cp-code">Kode customer (opsional)</label><input type="text" name="code" id="cp-code" maxlength="30" placeholder="mis. KPM1" style="text-transform:uppercase"></div>
        </div>
        <div class="cp-foot">
            <span class="cp-msg" id="cp-msg"></span>
            <button type="button" class="cp-btn sec" id="cp-cancel">Batal</button>
            <button type="submit" class="cp-btn" id="cp-save">Simpan</button>
        </div>
    </form>
</div>
<div class="cp-toast" id="cp-toast"></div>

<script src="assets/js/tether.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.nicescroll.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js"></script>
<script>
(function () {
  var modal = document.getElementById('cp-modal'), form = document.getElementById('cp-form'), msg = document.getElementById('cp-msg');
  var toastEl = document.getElementById('cp-toast');
  function toast(t) { toastEl.textContent = t; toastEl.classList.add('show'); setTimeout(function () { toastEl.classList.remove('show'); }, 2200); }
  function openModal(row) {
    form.reset(); msg.textContent = '';
    document.getElementById('cp-title').textContent = row ? 'Ubah Customer' : 'Tambah Customer';
    document.getElementById('cp-id').value = row ? row.id : '';
    document.getElementById('cp-company').value = row ? row.company_name : '';
    document.getElementById('cp-pic').value = row ? row.pic_name : '';
    document.getElementById('cp-phone').value = row ? row.pic_phone : '';
    document.getElementById('cp-email').value = row ? (row.email || '') : '';
    document.getElementById('cp-address').value = row ? row.address : '';
    document.getElementById('cp-code').value = row ? row.code : '';
    modal.classList.add('open');
    document.getElementById('cp-company').focus();
  }
  function closeModal() { modal.classList.remove('open'); }
  function post(data) {
    return fetch('customer-profile-api.php', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data) }).then(function (r) { return r.json(); });
  }
  document.getElementById('cp-add').addEventListener('click', function () { openModal(null); });
  document.getElementById('cp-cancel').addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
  document.addEventListener('click', function (e) {
    var tr = e.target.closest ? e.target.closest('tr[data-row]') : null;
    if (!tr) return;
    var row = JSON.parse(tr.getAttribute('data-row'));
    if (e.target.classList.contains('cp-edit')) { openModal(row); }
    if (e.target.classList.contains('cp-del')) {
      if (!confirm('Hapus customer "' + row.company_name + '"?\nPengiriman lama tetap tersimpan.')) return;
      post({ action: 'delete', id: row.id }).then(function (res) {
        if (res.success) { tr.parentNode.removeChild(tr); toast(res.message); } else { toast(res.message || 'Gagal menghapus.'); }
      }).catch(function () { toast('Gagal menghubungi server.'); });
    }
  });
  form.addEventListener('submit', function (e) {
    e.preventDefault(); msg.textContent = '';
    var btn = document.getElementById('cp-save'); btn.disabled = true;
    var fd = {}; new FormData(form).forEach(function (v, k) { fd[k] = v; });
    post(fd).then(function (res) {
      btn.disabled = false;
      if (res.success) { closeModal(); toast(res.message); setTimeout(function () { location.reload(); }, 500); }
      else { msg.textContent = res.message || 'Gagal menyimpan.'; }
    }).catch(function () { btn.disabled = false; msg.textContent = 'Gagal menghubungi server.'; });
  });
})();
</script>
</body>
</html>
