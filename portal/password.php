<?php
require_once __DIR__ . '/../deprixa/portal-lib.php';
$u = pt_require(true);
$must = (int)$u['must_change'] === 1;
$err = ''; $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = (string)($_POST['current'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    $rep = (string)($_POST['repeat'] ?? '');
    if (!pt_csrf_ok()) { $err = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'; }
    elseif (!password_verify($cur, $u['pwd'])) { $err = 'Password saat ini salah.'; }
    elseif (strlen($new) < 8) { $err = 'Password baru minimal 8 karakter.'; }
    elseif ($new !== $rep) { $err = 'Ulangi password baru dengan tepat.'; }
    elseif (hash_equals($cur, $new)) { $err = 'Password baru harus berbeda dari yang sekarang.'; }
    else {
        $h = password_hash($new, PASSWORD_BCRYPT);
        dbQuery("UPDATE customer_users SET pwd = ?, must_change = 0 WHERE id = ?", [$h, (int)$u['id']]);
        session_regenerate_id(true);
        $_SESSION['cust_fp'] = ali_pwd_fp($h);
        if ($must) { header('Location: /portal/'); exit; }
        $ok = 'Password sudah diganti.';
    }
}
pt_head('Ganti password', $must ? null : $u);
?>
<section class="pt-login">
    <h1><?php echo $must ? 'Buat password baru' : 'Ganti password'; ?></h1>
    <?php if ($must) { ?><p class="pt-muted">Ini login pertama Anda. Ganti password sementara dari admin dengan password milik Anda sendiri.</p><?php } ?>
    <?php if ($err) { ?><div class="pt-alert err"><?php echo pt_e($err); ?></div><?php } ?>
    <?php if ($ok) { ?><div class="pt-alert ok"><?php echo pt_e($ok); ?></div><?php } ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo pt_e(pt_csrf()); ?>">
        <input type="text" name="username" value="<?php echo pt_e($u['username']); ?>" autocomplete="username" hidden>
        <label>Password saat ini<?php echo $must ? ' (password sementara)' : ''; ?></label>
        <input type="password" name="current" autocomplete="current-password" required>
        <label>Password baru <span class="pt-muted pt-small">(minimal 8 karakter)</span></label>
        <input type="password" name="new" minlength="8" autocomplete="new-password" required>
        <label>Ulangi password baru</label>
        <input type="password" name="repeat" minlength="8" autocomplete="new-password" required>
        <button type="submit" class="pt-btn">Simpan password</button>
    </form>
    <?php if ($must) { ?><p class="pt-small"><a href="/portal/logout.php">Keluar</a></p><?php } else { ?><p class="pt-small"><a href="/portal/">&larr; Kembali ke daftar pengiriman</a></p><?php } ?>
</section>
<?php pt_foot(); ?>
