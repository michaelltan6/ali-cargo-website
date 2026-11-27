<?php
require_once __DIR__ . '/../deprixa/portal-lib.php';
pt_ensure_table();
if (pt_user()) { header('Location: /portal/'); exit; }

$err = '';
$uname = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = strtolower(trim((string)($_POST['user'] ?? '')));
    $pass = (string)($_POST['password'] ?? '');
    $key = 'cust:' . $uname;
    if (!pt_csrf_ok()) {
        $err = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($uname === '' || $pass === '') {
        $err = 'Isi username dan password.';
    } elseif (ali_login_blocked($key)) {
        $err = 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.';
    } else {
        $r = dbFetchAssoc(dbQuery("SELECT * FROM customer_users WHERE username = ? AND active = 1", [$uname]));
        if ($r && password_verify($pass, $r['pwd'])) {
            session_regenerate_id(true);
            $_SESSION['cust_uid'] = (int)$r['id'];
            $_SESSION['cust_fp'] = ali_pwd_fp($r['pwd']);
            unset($_SESSION['pt_csrf']);
            ali_login_clear($key);
            dbQuery("UPDATE customer_users SET last_login = ? WHERE id = ?", [date('Y-m-d H:i:s'), (int)$r['id']]);
            header('Location: ' . ((int)$r['must_change'] === 1 ? '/portal/password.php' : '/portal/'));
            exit;
        }
        ali_login_fail($key);
        $err = 'Username atau password salah.';
    }
}
pt_head('Masuk');
?>
<section class="pt-login">
    <h1>Masuk ke Portal Customer</h1>
    <p class="pt-muted">Lihat status pengiriman, invoice, dan pembayaran perusahaan Anda.</p>
    <?php if ($err) { ?><div class="pt-alert err"><?php echo pt_e($err); ?></div><?php } ?>
    <form method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo pt_e(pt_csrf()); ?>">
        <label>Username</label>
        <input type="text" name="user" value="<?php echo pt_e($uname); ?>" autocapitalize="none" autocomplete="username" required autofocus>
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <button type="submit" class="pt-btn">Masuk</button>
    </form>
    <p class="pt-muted pt-small">Belum punya akun atau lupa password? Hubungi admin ALI Cargo.</p>
</section>
<?php pt_foot(); ?>
