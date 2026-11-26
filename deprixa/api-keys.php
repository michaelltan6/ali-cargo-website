<?php
// Kelola API key customer. Hanya untuk akun owner.
require_once __DIR__ . '/report-lib.php';
require_once __DIR__ . '/api-lib.php';
require_once __DIR__ . '/customer-lib.php';
rp_boot(false);
if (!ali_is_owner()) { header('Location: admin.php'); exit; }
api_ensure_tables();

if (empty($_SESSION['api_csrf'])) { $_SESSION['api_csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['api_csrf'];
$newKey = ''; $msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sameOriginOk() || !hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $err = 'Permintaan ditolak. Muat ulang halaman lalu coba lagi.';
    } else {
        $act = (string)($_POST['do'] ?? '');
        if ($act === 'create') {
            $cid = (int)($_POST['customer_id'] ?? 0);
            $label = trim(mb_substr((string)($_POST['label'] ?? ''), 0, 80));
            $cu = $cid > 0 ? dbFetchAssoc(dbQuery("SELECT id, company_name FROM customer_profile WHERE id = ?", [$cid])) : null;
            if (!$cu) { $err = 'Pilih customer dulu.'; }
            else {
                $newKey = api_new_key();
                dbQuery("INSERT INTO api_keys (customer_id, label, key_prefix, key_hash, active, created_at, created_by) VALUES (?, ?, ?, ?, 1, ?, ?)",
                    [$cid, $label, substr($newKey, 0, 12), api_hash($newKey), date('Y-m-d H:i:s'), (string)$_SESSION['user_name']]);
                $msg = 'Kunci untuk ' . $cu['company_name'] . ' sudah dibuat.';
            }
        } elseif ($act === 'revoke' || $act === 'enable') {
            dbQuery("UPDATE api_keys SET active = ? WHERE id = ?", [$act === 'enable' ? 1 : 0, (int)($_POST['id'] ?? 0)]);
            $msg = $act === 'enable' ? 'Kunci diaktifkan lagi.' : 'Kunci dimatikan. Customer tidak bisa memakainya lagi.';
        } elseif ($act === 'delete') {
            $kid = (int)($_POST['id'] ?? 0);
            dbQuery("DELETE FROM api_keys WHERE id = ?", [$kid]);
            dbQuery("DELETE FROM api_hits WHERE key_id = ?", [$kid]);
            $msg = 'Kunci dihapus.';
        }
    }
}

$customers = []; $st = dbQuery("SELECT id, company_name, code FROM customer_profile ORDER BY company_name");
while ($r = dbFetchAssoc($st)) { $customers[] = $r; }
$keys = []; $st = dbQuery("SELECT k.*, c.company_name FROM api_keys k LEFT JOIN customer_profile c ON c.id = k.customer_id ORDER BY k.id DESC");
while ($r = dbFetchAssoc($st)) { $keys[] = $r; }
$base = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'www.anekalintasindcargo.com');

rp_start('API Customer', 'plug', 'Khusus owner');
?>
<style>
.ak-card { background:#fff; border:1px solid #e6eaee; border-radius:12px; padding:18px 20px; margin-bottom:16px; }
.ak-card h4 { margin:0 0 12px; font-size:17px; font-weight:800; }
.ak-row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
.ak-row label { display:block; font-size:13px; font-weight:700; color:#4a5560; margin-bottom:4px; }
.ak-row select, .ak-row input[type=text] { border:1px solid #d5dbe1; border-radius:8px; padding:9px 11px; font-size:14px; min-width:240px; }
.ak-btn { border:0; background:#e11d27; color:#fff; font-weight:700; border-radius:8px; padding:10px 16px; cursor:pointer; }
.ak-btn.gray { background:#eef1f4; color:#222; }
.ak-btn.red { background:#fde8e9; color:#c8151f; }
.ak-key { background:#0f1720; color:#7ee787; font-family:monospace; font-size:15px; padding:12px 14px; border-radius:8px; word-break:break-all; margin:10px 0; }
.ak-warn { background:#fff6dd; border:1px solid #f1d48a; border-radius:8px; padding:10px 12px; font-size:13px; }
.ak-ok { background:#e5f6ec; border:1px solid #b9e3cb; border-radius:8px; padding:10px 12px; margin-bottom:14px; }
.ak-err { background:#fde8e9; border:1px solid #f1c5c8; border-radius:8px; padding:10px 12px; margin-bottom:14px; }
.ak-table { width:100%; border-collapse:collapse; font-size:14px; }
.ak-table th, .ak-table td { text-align:left; padding:9px 8px; border-bottom:1px solid #eef1f4; vertical-align:middle; }
.ak-pill { font-size:12px; font-weight:700; padding:2px 9px; border-radius:10px; }
.ak-pill.on { background:#dff3e9; color:#17794f; } .ak-pill.off { background:#eef1f4; color:#6b7681; }
.ak-doc pre { background:#0f1720; color:#e6edf3; padding:12px 14px; border-radius:8px; overflow-x:auto; font-size:13px; }
.ak-doc code { background:#eef1f4; padding:1px 6px; border-radius:5px; font-size:13px; }
</style>

<?php if ($msg) { ?><div class="ak-ok"><?php echo rp_e($msg); ?></div><?php } ?>
<?php if ($err) { ?><div class="ak-err"><?php echo rp_e($err); ?></div><?php } ?>

<?php if ($newKey) { ?>
<div class="ak-card">
    <h4>Kunci baru</h4>
    <div class="ak-key" id="newkey"><?php echo rp_e($newKey); ?></div>
    <button class="ak-btn gray" type="button" onclick="navigator.clipboard.writeText(document.getElementById('newkey').innerText);this.innerText='Tersalin'">Salin kunci</button>
    <p class="ak-warn" style="margin-top:12px"><b>Kunci ini hanya tampil sekali.</b> Setelah halaman ditutup, kunci asli tidak bisa dilihat lagi (yang tersimpan hanya sidik jarinya). Kirim ke customer lewat jalur yang aman. Kalau hilang, buat kunci baru dan matikan yang lama.</p>
</div>
<?php } ?>

<div class="ak-card">
    <h4>Buat kunci untuk customer</h4>
    <form method="post" class="ak-row">
        <input type="hidden" name="csrf" value="<?php echo rp_e($csrf); ?>">
        <input type="hidden" name="do" value="create">
        <div><label>Customer</label>
            <select name="customer_id" required>
                <option value="">— pilih customer —</option>
                <?php foreach ($customers as $c) { ?><option value="<?php echo (int)$c['id']; ?>"><?php echo rp_e($c['company_name'] . ($c['code'] !== '' ? ' (' . $c['code'] . ')' : '')); ?></option><?php } ?>
            </select></div>
        <div><label>Catatan (opsional)</label><input type="text" name="label" maxlength="80" placeholder="Contoh: dashboard gudang"></div>
        <button class="ak-btn" type="submit">Buat kunci</button>
    </form>
    <p style="font-size:13px;color:#6b7681;margin:10px 0 0">Customer hanya bisa melihat pengiriman yang tertaut ke profil mereka di menu Customer.</p>
</div>

<div class="ak-card">
    <h4>Kunci yang ada</h4>
    <?php if (!$keys) { ?><p style="color:#6b7681">Belum ada kunci.</p><?php } else { ?>
    <div style="overflow-x:auto"><table class="ak-table">
        <thead><tr><th>Customer</th><th>Catatan</th><th>Awal kunci</th><th>Dibuat</th><th>Terakhir dipakai</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($keys as $k) { ?>
        <tr>
            <td><b><?php echo rp_e($k['company_name'] ?? '(customer dihapus)'); ?></b></td>
            <td><?php echo rp_e($k['label']); ?></td>
            <td><code><?php echo rp_e($k['key_prefix']); ?>…</code></td>
            <td><?php echo rp_e(substr((string)$k['created_at'], 0, 10)); ?></td>
            <td><?php echo $k['last_used_at'] ? rp_e($k['last_used_at']) : '<span style="color:#9aa4ae">belum pernah</span>'; ?></td>
            <td><span class="ak-pill <?php echo $k['active'] ? 'on' : 'off'; ?>"><?php echo $k['active'] ? 'Aktif' : 'Mati'; ?></span></td>
            <td style="white-space:nowrap">
                <form method="post" style="display:inline" <?php if ($k['active']) { ?>onsubmit="return confirm('Matikan kunci ini? Customer langsung tidak bisa memakainya.')"<?php } ?>>
                    <input type="hidden" name="csrf" value="<?php echo rp_e($csrf); ?>"><input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>">
                    <input type="hidden" name="do" value="<?php echo $k['active'] ? 'revoke' : 'enable'; ?>">
                    <button class="ak-btn gray" type="submit"><?php echo $k['active'] ? 'Matikan' : 'Aktifkan'; ?></button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Hapus kunci ini permanen?')">
                    <input type="hidden" name="csrf" value="<?php echo rp_e($csrf); ?>"><input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>"><input type="hidden" name="do" value="delete">
                    <button class="ak-btn red" type="submit">Hapus</button>
                </form>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table></div>
    <?php } ?>
</div>

<div class="ak-card ak-doc">
    <h4>Panduan untuk customer</h4>
    <p>API hanya membaca data (tidak bisa mengubah apa pun), dan hanya menampilkan pengiriman milik customer pemilik kunci. Batas <b><?php echo API_RATE_PER_MIN; ?> permintaan per menit</b> per kunci. Panggil dari server mereka, jangan dari browser pengunjung, supaya kunci tidak bocor.</p>
    <p><b>Autentikasi:</b> kirim header <code>Authorization: Bearer KUNCI_ANDA</code></p>
    <p><b>1. Daftar pengiriman</b> — <code>GET <?php echo rp_e($base); ?>/api/v1/shipments</code><br>
       Opsi: <code>status=active</code> atau <code>delivered</code>, <code>from=2026-11-01</code>, <code>to=2026-11-30</code> (tanggal barang diterima), <code>page</code>, <code>limit</code> (maks 100).</p>
    <p><b>2. Detail satu resi + riwayat</b> — <code>GET <?php echo rp_e($base); ?>/api/v1/shipments/ALI-7507</code><br>
       Nomor resi boleh ditulis <code>ALI-7507</code>, <code>ALI%207507</code>, atau <code>ALI7507</code>.</p>
<pre>curl -H "Authorization: Bearer KUNCI_ANDA" \
  "<?php echo rp_e($base); ?>/api/v1/shipments/ALI-7507"</pre>
    <p><b>Isi balasan:</b> tracking, status dan status_label, origin, destination, last_location, shipper, receiver, service, received_date, etd, eta, delivered_date, total_quantity, total_weight_kg, total_volume_m3, last_updated. Detail resi ditambah items dan history (waktu, status, lokasi, catatan).</p>
    <p><b>Kode kesalahan:</b> 401 kunci salah atau sudah dimatikan, 404 resi bukan milik kunci ini, 429 melewati batas, 400 parameter salah.</p>
</div>
<?php rp_end(''); ?>
