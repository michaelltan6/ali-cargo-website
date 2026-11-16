<?php
// Template halaman rute per wilayah. Dipakai oleh ekspedisi-jakarta-*.php
// Sebelum di-include, halaman pembungkus mengisi $regKey (contoh: 'PAPUA').
require_once __DIR__ . '/layanan-lib.php';
$d = ls_data();
if (!isset($regKey) || !isset($d['data'][$regKey])) { http_response_code(404); exit; }
$rows = $d['data'][$regKey];
$name = $d['name'][$regKey];
list($min, $max) = ls_range($rows);
$n = count($rows);

$kinds = array_unique(array_column($rows, 'jenis'));
if (count($kinds) === 1 && $kinds[0] === 'darat') {
    $jenisText = 'Semua tujuan di wilayah ini dikirim lewat jalur darat memakai truk.';
    $jenisShort = 'Cargo Darat';
} elseif (count($kinds) === 1) {
    $jenisText = 'Semua tujuan di wilayah ini dikirim lewat laut dengan kapal reguler.';
    $jenisShort = 'Cargo Laut';
} else {
    $jenisText = 'Sebagian kota dikirim lewat jalur darat dengan truk, sebagian lewat laut dengan kapal reguler. Jenisnya tertera di tabel di bawah.';
    $jenisShort = 'Cargo Darat dan Laut';
}

// tujuan tercepat dan terlama (berdasarkan angka awal estimasi)
$first = function ($r) { return preg_match('/(\d+)/', $r['hari'], $m) ? (int)$m[1] : 999; };
$sorted = $rows; usort($sorted, function ($a, $b) use ($first) { return $first($a) <=> $first($b); });
$fast = $sorted[0]; $slow = $sorted[count($sorted) - 1];

$hasPenerus = (bool)array_filter($rows, function ($r) { return !empty($r['penerus']); });
$titleH1 = 'Ekspedisi Jakarta ke ' . $name;
$title = $titleH1 . ' - ' . $jenisShort . ' | ALI Cargo';
$desc = 'Jasa ekspedisi dari Jakarta ke ' . $n . ' kota di ' . $name . '. Estimasi ' . $min . '-' . $max . ' hari. Minta penawaran lewat WhatsApp, Aneka Lintas Indonesia Cargo.';
$path = ls_page($regKey);

ls_head($title, $desc, $path, [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => $titleH1,
    'serviceType' => 'Jasa ekspedisi cargo',
    'provider' => ['@type' => 'Organization', 'name' => 'PT. Aneka Lintas Indonesia Cargo', 'url' => ALI_SITE],
    'areaServed' => $name,
    'description' => $desc,
]);
?>
<section class="ls-hero">
  <div class="ls-wrap">
    <nav class="ls-crumb" aria-label="Navigasi"><a href="index.php">Beranda</a> &rsaquo; <a href="layanan.php">Layanan</a> &rsaquo; <a href="rute-pengiriman.php">Rute</a> &rsaquo; <?php echo ls_h($name); ?></nav>
    <h1><?php echo ls_h($titleH1); ?></h1>
    <p class="ls-lead">Aneka Lintas Indonesia Cargo melayani pengiriman barang dari Jakarta ke <?php echo $n; ?> kota tujuan di <?php echo ls_h($name); ?>. <?php echo ls_h($jenisText); ?> Estimasi waktu tempuh <?php echo $min; ?> sampai <?php echo $max; ?> hari, tergantung kota tujuan.</p>
  </div>
</section>

<section class="ls-wrap ls-section">
  <h2>Daftar tujuan di <?php echo ls_h($name); ?></h2>
  <?php if ($fast['hari'] === $slow['hari']): ?>
  <p>Estimasi untuk semua kota di <?php echo ls_h($name); ?> sama, yaitu <strong><?php echo ls_h($fast['hari']); ?> hari</strong> dari Jakarta. Waktunya bisa bergeser sedikit.</p>
  <?php else: ?>
  <p>Yang tercepat adalah <strong><?php echo ls_h($fast['kota']); ?></strong> (<?php echo ls_h($fast['hari']); ?> hari), yang paling lama <strong><?php echo ls_h($slow['kota']); ?></strong> (<?php echo ls_h($slow['hari']); ?> hari). Estimasi dihitung dari Jakarta dan bisa bergeser sedikit.</p>
  <?php endif; ?>
  <?php ls_table($rows); ?>
  <?php if ($hasPenerus) ls_note_penerus(); ?>
  <?php ls_cta('Halo ALI Cargo, saya mau tanya pengiriman dari Jakarta ke ' . $name . '.'); ?>
</section>

<section class="ls-wrap ls-section">
  <h2>Yang perlu kamu tahu</h2>
  <ul class="ls-list">
    <li>Ongkos dihitung per kg atau per m&sup3; (kubikasi), sesuai tujuan dan barang kamu. <a href="faq.php#ongkos">Lihat cara hitungnya</a>.</li>
    <li>Harga berlaku untuk pengantaran di dalam kota tujuan, bukan ke luar kota.</li>
    <li>Biaya bongkar, pajak, dan biaya lain di luar pengiriman tidak termasuk.</li>
    <li>Kalau ada kekurangan, kerusakan, atau kehilangan barang karena kelalaian kami, kami yang bertanggung jawab.</li>
  </ul>
</section>

<section class="ls-wrap ls-section">
  <h2>Wilayah lain</h2>
  <?php ls_region_links($regKey); ?>
  <p><a href="rute-pengiriman.php">Lihat semua rute</a> &middot; <a href="faq.php">Pertanyaan yang sering diajukan</a></p>
</section>
<?php ls_foot();
