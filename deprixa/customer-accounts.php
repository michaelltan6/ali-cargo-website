<?php
// Kelola akun login Portal Customer. Hanya untuk akun owner.
require_once __DIR__ . '/report-lib.php';
require_once __DIR__ . '/portal-lib.php';
rp_boot(false);
if (!ali_is_owner()) { header('Location: admin.php'); exit; }
cp_ensure_table();
pt_ensure_table();
courier_columns_ready();

if (empty($_SESSION['ca_csrf'])) { $_SESSION['ca_csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['ca_csrf'];
$msg = ''; $err = ''; $shown = null;   // $shown = [username, password sementara]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sameOriginOk() || !hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $err = 'Permintaan ditolak. Muat ulang halaman lalu coba lagi.';
    } else {
        $act = (string)($_POST['do'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        if ($act === 'create') {
            $cid = (int)($_POST['customer_id'] ?? 0);
            $un = strtolower(trim((string)($_POST['username'] ?? '')));
            $name = cp_clean($_POST['full_name'] ?? '', 120);
            $mail = cp_clean($_POST['email'] ?? '', 190);
            $cu = $cid > 0 ? dbFetchAssoc(dbQuery("SELECT id, company_name FROM customer_profile WHERE id = ?", [$cid])) : null;
            if (!$cu) { $err = 'Pilih perusahaan customer dulu.'; }
            elseif (!pt_valid_username($un)) { $err = 'Username 3–40 karakter: huruf kecil, angka, titik, strip, atau garis bawah.'; }
            elseif (dbFetchAssoc(dbQuery("SELECT id FROM customer_users WHERE username = ?", [$un]))) { $err = 'Username "' . $un . '" sudah dipakai.'; }
            elseif ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) { $err = 'Format email tidak valid.'; }
            else {
                $tmp = pt_temp_password();
                dbQuery("INSERT INTO customer_users (customer_id, username, full_name, email, pwd, active, must_change, created_at, created_by) VALUES (?, ?, ?, ?, ?, 1, 1, ?, ?)",
                    [$cid, $un, $name, $mail, password_hash($tmp, PASSWORD_BCRYPT), date('Y-m-d H:i:s'), (string)$_SESSION['user_name']]);
                $shown = [$un, $tmp];
                $msg = 'Akun untuk ' . $cu['company_name'] . ' sudah dibuat.';
            }
        } elseif ($act === 'reset' && $id > 0) {
            $r = dbFetchAssoc(dbQuery("SELECT username FROM customer_users WHERE id = ?", [$id]));
            if ($r) {
                $tmp = pt_temp_password();
                dbQuery("UPDATE customer_users SET pwd = ?, must_change = 1 WHERE id = ?", [password_hash($tmp, PASSWORD_BCRYPT), $id]);
                $shown = [$r['username'], $tmp];
                $msg = 'Password direset. Login lama otomatis keluar.';
            }
        } elseif (($act === 'off' || $act === 'on') && $id > 0) {
            dbQuery("UPDATE customer_users SET active = ? WHERE id = ?", [$act === 'on' ? 1 : 0, $id]);
            $msg = $act === 'on' ? 'Akun diaktifkan lagi.' : 'Akun dimatikan. Customer langsung tidak bisa masuk.';
        } elseif ($act === 'delete' && $id > 0) {
            dbQuery("DELETE FROM customer_users WHERE id = ?", [$id]);
            $msg = 'Akun dihapus.';
        }
    }
}

$customers = []; $st = dbQuery("SELECT c.id, c.company_name, c.code, (SELECT COUNT(*) FROM courier k WHERE k.customer_id = c.id) AS n FROM customer_profile c ORDER BY c.company_name");
while ($r = dbFetchAssoc($st)) { $customers[] = $r; }
$users = []; $st = dbQuery("SELECT u.*, c.company_name, (SELECT COUNT(*) FROM courier k WHERE k.customer_id = u.customer_id) AS n FROM customer_users u LEFT JOIN customer_profile c ON c.id = u.customer_id ORDER BY c.company_name, u.username");
while ($r = dbFetchAssoc($st)) { $users[] = $r; }
$portal = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'www.anekalintasindcargo.com') . '/portal/';

rp_start('Akun Portal Customer', 'user-circle', 'Khusus owner');
?>
<style>
.ca-card { background:#fff; border:1px solid #e6eaee; border-radius:12px; padding:18px 20px; margin-bottom:16px; }
.ca-card h4 { margin:0 0 12px; font-size:17px; font-weight:800; }
.ca-row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
.ca-row label { display:block; font-size:13px; font-weight:700; color:#4a5560; margin-bottom:4px; }
.ca-row select, .ca-row input[type=text], .ca-row input[type=email] { border:1px solid #d5dbe1; border-radius:8px; padding:9px 11px; font-size:14px; min-width:200px; }
.ca-btn { border:0; background:#e11d27; color:#fff; font-weight:700; border-radius:8px; padding:9px 14px; cursor:pointer; font-size:13px; }
.ca-btn.gray { background:#eef1f4; color:#222; } .ca-btn.red { background:#fde8e9; color:#c8151f; }
.ca-ok { background:#e5f6ec; border:1px solid #b9e3cb; border-radius:8px; padding:10px 12px; margin-bottom:14px; }
.ca-err { background:#fde8e9; border:1px solid #f1c5c8; border-radius:8px; padding:10px 12px; margin-bottom:14px; }
.ca-cred { background:#0f1720; color:#e6edf3; font-family:monospace; font-size:15px; padding:12px 14px; border-radius:8px; margin:10px 0; line-height:1.7; white-space:pre-wrap; }
.ca-warn { background:#fff6dd; border:1px solid #f1d48a; border-radius:8px; padding:10px 12px; font-size:13px; }
.ca-table { width:100%; border-collapse:collapse; font-size:14px; }
.ca-table th, .ca-table td { text-align:left; padding:9px 8px; border-bottom:1px solid #eef1f4; vertical-align:middle; }
.ca-pill { font-size:12px; font-weight:700; padding:2px 9px; border-radius:10px; }
.ca-pill.on { background:#dff3e9; color:#17794f; } .ca-pill.off { background:#eef1f4; color:#6b7681; } .ca-pill.wait { background:#fdf2dc; color:#a86a00; }
</style>

<?php if ($msg) { ?><div class="ca-ok"><?php echo rp_e($msg); ?></div><?php } ?>
<?php if ($err) { ?><div class="ca-err"><?php echo rp_e($err); ?></div><?php } ?>

<?php if ($shown) { $txt = "Portal Customer ALI Cargo\n" . $portal . "\nUsername: " . $shown[0] . "\nPassword sementara: " . $shown[1]; ?>
<div class="ca-card">
    <h4>Data login untuk dikirim ke customer</h4>
    <div class="ca-cred" id="cred"><?php echo rp_e($txt); ?></div>
    <button class="ca-btn gray" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cred').innerText);this.innerText='Tersalin'">Salin</button>
    <p class="ca-warn" style="margin-top:12px"><b>Password sementara ini hanya tampil sekali.</b> Customer wajib menggantinya saat login pertama. Kalau hilang, klik "Reset password" untuk membuat yang baru.</p>
</div>
<?php } ?>

<div class="ca-card">
    <h4>Buat akun customer</h4>
    <form method="post" class="ca-row">
        <input type="hidden" name="csrf" value="<?php echo rp_e($csrf); ?>"><input type="hidden" name="do" value="create">
        <div><label>Perusahaan</label>
            <select name="customer_id" required>
                <option value="">— pilih perusahaan —</option>
                <?php foreach ($customers as $c) { ?><option value="<?php echo (int)$c['id']; ?>"><?php echo rp_e($c['company_name'] . ($c['code'] !== '' ? ' (' . $c['code'] . ')' : '') . ' · ' . (int)$c['n'] . ' pengiriman'); ?></option><?php } ?>
            </select></div>
        <div><label>Username</label><input type="text" name="username" maxlength="40" pattern="[a-z0-9._\-]{3,40}" placeholder="contoh: gramedia.gudang" required></div>
        <div><label>Nama pengguna</label><input type="text" name="full_name" maxlength="120" placeholder="Nama staf (opsional)"></div>
        <div><label>Email</label><input type="email" name="email" maxlength="190" placeholder="opsional"></div>
        <button class="ca-btn" type="submit">Buat akun</button>
    </form>
    <p style="font-size:13px;color:#6b7681;margin:10px 0 0">Satu perusahaan boleh punya beberapa akun. Semua akun melihat data perusahaan yang sama: pengiriman yang tertaut ke perusahaan itu di menu Customer. Alamat portal: <b><?php echo rp_e($portal); ?></b></p>
</div>

<div class="ca-card">
    <h4>Akun yang ada</h4>
    <?php if (!$users) { ?><p style="color:#6b7681">Belum ada akun customer.</p><?php } else { ?>
    <div style="overflow-x:auto"><table class="ca-table">
        <thead><tr><th>Perusahaan</th><th>Username</th><th>Nama</th><th>Pengiriman terlihat</th><th>Login terakhir</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u) { ?>
        <tr>
            <td><b><?php echo rp_e($u['company_name'] ?? '(perusahaan dihapus)'); ?></b></td>
            <td><code><?php echo rp_e($u['username']); ?></code></td>
            <td><?php echo rp_e($u['full_name']); ?><?php echo $u['email'] !== '' ? '<br><span style="color:#6b7681;font-size:12px">' . rp_e($u['email']) . '</span>' : ''; ?></td>
            <td><?php echo (int)$u['n']; ?></td>
            <td><?php echo $u['last_login'] ? rp_e($u['last_login']) : '<span style="color:#9aa4ae">belum pernah</span>'; ?></td>
            <td><?php if (!$u['active']) { ?><span class="ca-pill off">Mati</span><?php } elseif ($u['must_change']) { ?><span class="ca-pill wait">Menunggu ganti password</span><?php } else { ?><span class="ca-pill on">Aktif</span><?php } ?></td>
            <td style="white-space:nowrap">
                <?php foreach ([['reset', 'Reset password', 'gray', 'Buat password sementara baru? Login lama akan keluar.'], [$u['active'] ? 'off' : 'on', $u['active'] ? 'Matikan' : 'Aktifkan', 'gray', $u['active'] ? 'Matikan akun ini?' : ''], ['delete', 'Hapus', 'red', 'Hapus akun ini permanen?']] as $b) { ?>
                <form method="post" style="display:inline"<?php echo $b[3] !== '' ? ' onsubmit="return confirm(\'' . rp_e($b[3]) . '\')"' : ''; ?>>
                    <input type="hidden" name="csrf" value="<?php echo rp_e($csrf); ?>"><input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>"><input type="hidden" name="do" value="<?php echo $b[0]; ?>">
                    <button class="ca-btn <?php echo $b[2]; ?>" type="submit"><?php echo rp_e($b[1]); ?></button>
                </form>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table></div>
    <?php } ?>
</div>
<?php rp_end(''); ?>
