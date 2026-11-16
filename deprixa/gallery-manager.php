<?php
/**
 * Kelola Gallery Website — halaman admin untuk menambah / menghapus foto gallery.
 * Hanya bisa dibuka oleh akun Administrator yang sudah login.
 * Fungsi-fungsinya ada di ../gallery-lib.php
 */
ob_start();
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('database.php');
require_once('library.php');
require_once(dirname(__DIR__) . '/gallery-lib.php');
@ini_set('display_errors', '0');

function gm_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function gm_cut($s, $n) { return function_exists('mb_substr') ? mb_substr($s, 0, $n, 'UTF-8') : substr($s, 0, $n); }

function gm_clear_output() { while (ob_get_level() > 0) { ob_end_clean(); } }

function gm_json($code, array $data) {
    gm_clear_output();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function gm_flash($type, $msg) { $_SESSION['gm_flash'] = ['type' => $type, 'msg' => $msg]; }

function gm_back($anchor = '') {
    gm_clear_output();
    header('Location: gallery-manager.php' . ($anchor !== '' ? '#' . $anchor : ''));
    exit;
}

function gm_ini_bytes($v) {
    $v = trim((string)$v); $n = (int)$v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1073741824; break;
        case 'm': $n *= 1048576; break;
        case 'k': $n *= 1024; break;
    }
    return $n;
}

function gm_upload_error_text($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: return 'Ukuran file melebihi batas server (' . ini_get('upload_max_filesize') . ').';
        case UPLOAD_ERR_PARTIAL:   return 'File hanya terunggah sebagian. Coba lagi.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE: return 'Server tidak bisa menyimpan file sementara.';
        default:                   return 'Gagal mengunggah file (kode ' . (int)$code . ').';
    }
}

/* ---------- Hanya Administrator yang sudah login ---------- */
$isAjax  = (($_POST['ajax'] ?? '') === '1');
$isAdmin = function_exists('isUser') && isUser() && (($_SESSION['user_type'] ?? '') === 'Administrator');
if (!$isAdmin) {
    if ($isAjax) {
        gm_json(401, ['ok' => false, 'message' => 'Sesi login sudah habis. Silakan login lagi.']);
    }
    gm_clear_output();
    header('Location: ../login.php');
    exit;
}

if (empty($_SESSION['gm_csrf'])) {
    $_SESSION['gm_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['gm_csrf'];

/* ---------- Proses aksi (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kalau isi kiriman melebihi post_max_size, PHP membuang semua data POST.
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST) && empty($_FILES)) {
        $msg = 'Ukuran kiriman melebihi batas server (' . ini_get('post_max_size') . '). Unggah lebih sedikit foto sekaligus.';
        if ($isAjax) gm_json(413, ['ok' => false, 'message' => $msg]);
        gm_flash('err', $msg);
        gm_back();
    }
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $msg = 'Sesi halaman tidak valid. Muat ulang halaman lalu coba lagi.';
        if ($isAjax) gm_json(403, ['ok' => false, 'message' => $msg]);
        gm_flash('err', $msg);
        gm_back();
    }

    $action = (string)($_POST['action'] ?? '');
    $album  = (string)($_POST['album'] ?? '');
    $err    = '';

    switch ($action) {
        case 'upload':
            $f = $_FILES['photos'] ?? null;
            $names = $f ? (array)$f['name'] : [];
            $tmps  = $f ? (array)$f['tmp_name'] : [];
            $errs  = $f ? (array)$f['error'] : [];
            $saved = 0;
            $failed = [];
            foreach ($names as $i => $orig) {
                if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                if ($saved + count($failed) >= 30) { $failed[] = ['name' => 'Sisanya', 'message' => 'Maksimal 30 foto per unggahan.']; break; }
                $label = gm_cut(basename((string)$orig), 60);
                if ($errs[$i] !== UPLOAD_ERR_OK) {
                    $failed[] = ['name' => $label, 'message' => gm_upload_error_text($errs[$i])];
                    continue;
                }
                if (!is_uploaded_file($tmps[$i])) {
                    $failed[] = ['name' => $label, 'message' => 'File tidak valid.'];
                    continue;
                }
                $e = '';
                $new = gallery_save_upload($tmps[$i], $album, $e);
                if ($new === null) $failed[] = ['name' => $label, 'message' => $e];
                else $saved++;
            }
            if ($isAjax) {
                gm_json($saved > 0 || !$failed ? 200 : 422, ['ok' => $saved > 0, 'saved' => $saved, 'failed' => $failed,
                    'message' => $saved === 0 && !$failed ? 'Tidak ada file yang dipilih.' : '']);
            }
            if ($saved > 0) gm_flash('ok', $saved . ' foto berhasil ditambahkan.' . ($failed ? ' Yang gagal: ' . implode('; ', array_map(function ($x) { return $x['name'] . ' (' . $x['message'] . ')'; }, $failed)) : ''));
            else gm_flash('err', $failed ? 'Tidak ada foto yang berhasil: ' . implode('; ', array_map(function ($x) { return $x['name'] . ' (' . $x['message'] . ')'; }, $failed)) : 'Tidak ada file yang dipilih.');
            gm_back('album-' . $album);
            break;

        case 'delete_photo':
            if (gallery_delete_photo($album, (string)($_POST['file'] ?? ''), $err)) gm_flash('ok', 'Foto dihapus.');
            else gm_flash('err', $err);
            gm_back('album-' . $album);
            break;

        case 'add_album':
            $id = gallery_add_album((string)($_POST['title'] ?? ''), $err);
            if ($id) { gm_flash('ok', 'Album baru dibuat. Sekarang tambahkan foto ke dalamnya.'); gm_back('album-' . $id); }
            gm_flash('err', $err);
            gm_back();
            break;

        case 'rename_album':
            if (gallery_rename_album($album, (string)($_POST['title'] ?? ''), $err)) gm_flash('ok', 'Judul album disimpan.');
            else gm_flash('err', $err);
            gm_back('album-' . $album);
            break;

        case 'move_album':
            $dir = (($_POST['dir'] ?? '') === 'up') ? 'up' : 'down';
            if (!gallery_move_album($album, $dir, $err)) gm_flash('err', $err);
            gm_back('album-' . $album);
            break;

        case 'delete_album':
            if (gallery_delete_album($album, $err)) { gm_flash('ok', 'Album dihapus.'); gm_back(); }
            gm_flash('err', $err);
            gm_back('album-' . $album);
            break;

        default:
            gm_flash('err', 'Aksi tidak dikenal.');
            gm_back();
    }
}

/* ---------- Tampilan ---------- */
$flash = $_SESSION['gm_flash'] ?? null;
unset($_SESSION['gm_flash']);

$albums = gallery_load_albums();
$totalPhotos = 0;
$albumPhotos = [];
foreach ($albums as $a) {
    $albumPhotos[$a['id']] = gallery_images($a['id']);
    $totalPhotos += count($albumPhotos[$a['id']]);
}
$maxFile = min(gm_ini_bytes(ini_get('upload_max_filesize')), gm_ini_bytes(ini_get('post_max_size')));
$galleryWritable = is_dir(gallery_dir()) && is_writable(gallery_dir());
$hasGd = function_exists('imagecreatetruecolor') && function_exists('imagejpeg');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <title>Kelola Gallery - PT. ANEKA LINTAS INDONESIA</title>
    <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.css" type="text/css" />
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css" type="text/css" />
    <style>
        .gm-wrap { margin: 20px 0 40px; }
        .gm-intro { background: #fff; border-left: 4px solid #d00; border-radius: 4px; padding: 14px 16px; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .gm-intro p { margin: 0 0 6px; }
        .gm-intro p:last-child { margin: 0; }
        .gm-top { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .gm-top h3 { margin: 0; }
        .gm-flash { padding: 12px 14px; border-radius: 4px; margin-bottom: 16px; font-size: 14px; }
        .gm-flash.ok { background: #e6f6ea; border: 1px solid #9ad3a7; color: #1d5a2b; }
        .gm-flash.err { background: #fdeaea; border: 1px solid #e3a0a0; color: #8a1f1f; }
        .gm-warn { background: #fff6e0; border: 1px solid #f0cf85; color: #6b4b00; padding: 12px 14px; border-radius: 4px; margin-bottom: 16px; }
        .gm-album { background: #fff; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,.14); margin-bottom: 22px; padding: 16px; }
        .gm-album-head { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; }
        .gm-title-form { display: flex; flex: 1 1 280px; gap: 8px; margin: 0; }
        .gm-title-form input[type=text] { flex: 1; min-width: 0; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .gm-tools { display: flex; gap: 6px; align-items: center; margin: 0; }
        .gm-tools form { margin: 0; }
        .gm-btn { display: inline-block; min-height: 38px; padding: 8px 14px; border: 1px solid #bbb; border-radius: 4px; background: #f6f6f6; color: #333; font-size: 14px; cursor: pointer; line-height: 1.2; }
        .gm-btn:hover { background: #ebebeb; }
        .gm-btn.primary { background: #d00; border-color: #b00; color: #fff; font-weight: 600; }
        .gm-btn.primary:hover { background: #b80000; }
        .gm-btn.danger { color: #a00; }
        .gm-btn[disabled] { opacity: .5; cursor: not-allowed; }
        .gm-count { color: #777; font-size: 13px; margin-left: 2px; }
        .gm-upload { border: 2px dashed #c4c4c4; border-radius: 6px; padding: 12px; margin-bottom: 14px; background: #fafafa; }
        .gm-upload form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 0; }
        .gm-upload input[type=file] { flex: 1 1 240px; min-width: 0; max-width: 100%; }
        .gm-status { flex: 1 1 100%; font-size: 13px; color: #555; min-height: 18px; }
        .gm-status .bad { color: #a00; display: block; }
        .gm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .gm-photo { position: relative; border-radius: 4px; overflow: hidden; background: #e9e9e9; aspect-ratio: 4 / 3; }
        .gm-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .gm-photo form { position: absolute; top: 6px; right: 6px; margin: 0; }
        .gm-photo button { width: 32px; height: 32px; border: 0; border-radius: 50%; background: rgba(190,0,0,.92); color: #fff; font-size: 18px; line-height: 1; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,.4); }
        .gm-empty { color: #888; padding: 10px 2px; font-size: 14px; }
        .gm-new { background: #fff; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,.14); padding: 14px 16px; margin-bottom: 22px; }
        .gm-new form { display: flex; flex-wrap: wrap; gap: 8px; margin: 0; }
        .gm-new input[type=text] { flex: 1 1 240px; min-width: 0; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; }
        @media (max-width: 600px) { .gm-grid { grid-template-columns: repeat(2, 1fr); } .gm-btn { min-height: 42px; } }
    </style>
</head>
<body>
<?php include("header.php"); ?>

<div class="wrapper">
    <div class="container">
        <div class="gm-wrap">
            <div class="gm-top">
                <h3><i class="fa fa-picture-o"></i> Kelola Gallery Website</h3>
                <a class="gm-btn" href="../gallery.php" target="_blank" rel="noopener">Lihat gallery di website &rarr;</a>
            </div>

            <?php if ($flash): ?>
                <div class="gm-flash <?php echo $flash['type'] === 'ok' ? 'ok' : 'err'; ?>"><?php echo gm_h($flash['msg']); ?></div>
            <?php endif; ?>

            <?php if (!$galleryWritable): ?>
                <div class="gm-warn"><strong>Folder gallery belum bisa ditulis.</strong> Di File Manager hosting, ubah izin (permission) folder <code>gallery</code> menjadi 755 atau 775, lalu muat ulang halaman ini.</div>
            <?php endif; ?>
            <?php if (!$hasGd): ?>
                <div class="gm-warn"><strong>Ekstensi GD belum aktif di PHP hosting.</strong> Foto belum bisa diunggah. Aktifkan "gd" di pengaturan PHP hosting (PHP Extensions / Select PHP Version).</div>
            <?php endif; ?>

            <div class="gm-intro">
                <p>Satu album = satu kotak foto yang berganti-ganti otomatis di halaman Gallery. Foto yang baru ditambahkan tampil paling depan.</p>
                <p>Foto dikecilkan otomatis (sisi terpanjang 1600 px) dan disimpan sebagai JPG, jadi foto langsung dari HP boleh dipakai. Format yang bisa dipakai: JPG, PNG, WebP, GIF. Batas per foto: <?php echo gm_h(round(min($maxFile, ALI_GALLERY_MAX_BYTES) / 1048576)); ?> MB. Album yang masih kosong tidak ditampilkan di website.</p>
            </div>

            <?php if (!$albums): ?>
                <div class="gm-empty">Belum ada album. Buat album pertama di bawah.</div>
            <?php endif; ?>

            <?php foreach ($albums as $idx => $a):
                $id = $a['id']; $imgs = $albumPhotos[$id]; $label = $a['title'] !== '' ? $a['title'] : 'Album ' . ($idx + 1); ?>
                <div class="gm-album" id="album-<?php echo gm_h($id); ?>">
                    <div class="gm-album-head">
                        <form class="gm-title-form" method="post" action="gallery-manager.php">
                            <input type="hidden" name="csrf" value="<?php echo gm_h($csrf); ?>">
                            <input type="hidden" name="action" value="rename_album">
                            <input type="hidden" name="album" value="<?php echo gm_h($id); ?>">
                            <input type="text" name="title" maxlength="60" value="<?php echo gm_h($a['title']); ?>" placeholder="Judul album (boleh dikosongkan) — <?php echo gm_h($label); ?>" aria-label="Judul album">
                            <button type="submit" class="gm-btn">Simpan judul</button>
                        </form>
                        <span class="gm-count"><?php echo count($imgs); ?> foto</span>
                        <div class="gm-tools">
                            <form method="post" action="gallery-manager.php">
                                <input type="hidden" name="csrf" value="<?php echo gm_h($csrf); ?>">
                                <input type="hidden" name="action" value="move_album">
                                <input type="hidden" name="album" value="<?php echo gm_h($id); ?>">
                                <input type="hidden" name="dir" value="up">
                                <button type="submit" class="gm-btn" title="Pindah ke atas" <?php echo $idx === 0 ? 'disabled' : ''; ?>>&uarr;</button>
                            </form>
                            <form method="post" action="gallery-manager.php">
                                <input type="hidden" name="csrf" value="<?php echo gm_h($csrf); ?>">
                                <input type="hidden" name="action" value="move_album">
                                <input type="hidden" name="album" value="<?php echo gm_h($id); ?>">
                                <input type="hidden" name="dir" value="down">
                                <button type="submit" class="gm-btn" title="Pindah ke bawah" <?php echo $idx === count($albums) - 1 ? 'disabled' : ''; ?>>&darr;</button>
                            </form>
                            <form method="post" action="gallery-manager.php" onsubmit="return confirm('Hapus album ini dari website?');">
                                <input type="hidden" name="csrf" value="<?php echo gm_h($csrf); ?>">
                                <input type="hidden" name="action" value="delete_album">
                                <input type="hidden" name="album" value="<?php echo gm_h($id); ?>">
                                <button type="submit" class="gm-btn danger" title="Hanya bisa kalau album sudah kosong" <?php echo $imgs ? 'disabled' : ''; ?>>Hapus album</button>
                            </form>
                        </div>
                    </div>

                    <div class="gm-upload">
                        <form class="gm-upload-form" method="post" action="gallery-manager.php" enctype="multipart/form-data" data-album="<?php echo gm_h($id); ?>">
                            <input type="hidden" name="csrf" value="<?php echo gm_h($csrf); ?>">
                            <input type="hidden" name="action" value="upload">
                            <input type="hidden" name="album" value="<?php echo gm_h($id); ?>">
                            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple <?php echo ($galleryWritable && $hasGd) ? '' : 'disabled'; ?>>
                            <button type="submit" class="gm-btn primary" <?php echo ($galleryWritable && $hasGd) ? '' : 'disabled'; ?>>Unggah foto</button>
                            <div class="gm-status" aria-live="polite"></div>
                        </form>
                    </div>

                    <?php if ($imgs): ?>
                        <div class="gm-grid">
                            <?php foreach ($imgs as $name): ?>
                                <div class="gm-photo">
                                    <img src="../gallery/<?php echo gm_h($id); ?>/<?php echo gm_h(rawurlencode($name)); ?>" alt="" loading="lazy">
                                    <form method="post" action="gallery-manager.php" onsubmit="return confirm('Hapus foto ini dari website? Tindakan ini tidak bisa dibatalkan.');">
                                        <input type="hidden" name="csrf" value="<?php echo gm_h($csrf); ?>">
                                        <input type="hidden" name="action" value="delete_photo">
                                        <input type="hidden" name="album" value="<?php echo gm_h($id); ?>">
                                        <input type="hidden" name="file" value="<?php echo gm_h($name); ?>">
                                        <button type="submit" title="Hapus foto" aria-label="Hapus foto">&times;</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="gm-empty">Album ini masih kosong, jadi belum tampil di website.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="gm-new">
                <form method="post" action="gallery-manager.php">
                    <input type="hidden" name="csrf" value="<?php echo gm_h($csrf); ?>">
                    <input type="hidden" name="action" value="add_album">
                    <input type="text" name="title" maxlength="60" placeholder="Judul album baru (misalnya: Pengiriman ke Papua)" aria-label="Judul album baru">
                    <button type="submit" class="gm-btn primary" <?php echo $galleryWritable ? '' : 'disabled'; ?>>+ Buat album baru</button>
                </form>
            </div>
            <p class="gm-count">Total <?php echo (int)$totalPhotos; ?> foto di <?php echo count($albums); ?> album.</p>
        </div>

        <?php include("footer.php"); ?>
    </div>
</div>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js"></script>
<script>
(function () {
    "use strict";
    var CSRF = <?php echo json_encode($csrf); ?>;
    var MAX_BYTES = <?php echo (int)min($maxFile, ALI_GALLERY_MAX_BYTES); ?>;

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // Unggah satu per satu supaya tidak kena batas ukuran total kiriman di server
    // dan supaya foto yang gagal bisa kelihatan alasannya.
    async function uploadAll(files, album, status, button, input) {
        var ok = 0, failed = [], stop = false;
        button.disabled = true; input.disabled = true;
        for (var i = 0; i < files.length && !stop; i++) {
            var f = files[i];
            status.textContent = 'Mengunggah ' + (i + 1) + ' dari ' + files.length + ': ' + f.name + ' …';
            if (f.size > MAX_BYTES) { failed.push(f.name + ' (lebih dari ' + Math.round(MAX_BYTES / 1048576) + ' MB)'); continue; }
            if (f.type && f.type.indexOf('image/') !== 0) { failed.push(f.name + ' (bukan gambar)'); continue; }
            var fd = new FormData();
            fd.append('csrf', CSRF); fd.append('action', 'upload'); fd.append('album', album); fd.append('ajax', '1');
            fd.append('photos[]', f, f.name);
            try {
                var res = await fetch('gallery-manager.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                var data = null;
                try { data = await res.json(); } catch (e) { data = null; }
                if (res.status === 401 || res.status === 403) { failed.push((data && data.message) || 'Sesi habis, login ulang.'); stop = true; }
                else if (data && data.saved > 0) { ok += data.saved; }
                else if (data && data.failed && data.failed.length) { failed.push(f.name + ' (' + data.failed[0].message + ')'); }
                else { failed.push(f.name + ' (' + ((data && data.message) || 'gagal, kode ' + res.status) + ')'); }
            } catch (e) {
                failed.push(f.name + ' (koneksi terputus)');
            }
        }
        if (!failed.length) {
            status.textContent = ok + ' foto berhasil diunggah. Memuat ulang …';
            history.replaceState(null, '', '#album-' + album);
            setTimeout(function () { location.reload(); }, 700);
            return;
        }
        var html = '<strong>' + ok + ' berhasil, ' + failed.length + ' gagal:</strong>';
        failed.forEach(function (m) { html += '<span class="bad">• ' + esc(m) + '</span>'; });
        if (ok > 0) html += '<span><a href="#" id="gm-reload">Muat ulang halaman untuk melihat foto yang sudah masuk</a></span>';
        status.innerHTML = html;
        var r = status.querySelector('#gm-reload');
        if (r) r.addEventListener('click', function (e) { e.preventDefault(); history.replaceState(null, '', '#album-' + album); location.reload(); });
        button.disabled = false; input.disabled = false; input.value = '';
    }

    var forms = document.querySelectorAll('.gm-upload-form');
    Array.prototype.forEach.call(forms, function (form) {
        var input = form.querySelector('input[type=file]');
        var button = form.querySelector('button[type=submit]');
        var status = form.querySelector('.gm-status');
        form.addEventListener('submit', function (e) {
            if (!input.files || !input.files.length) { e.preventDefault(); status.textContent = 'Pilih foto dulu.'; return; }
            if (!window.fetch || !window.FormData) return;   // browser lama: kirim biasa
            e.preventDefault();
            uploadAll(Array.prototype.slice.call(input.files), form.getAttribute('data-album'), status, button, input);
        });
    });
})();
</script>
</body>
</html>
