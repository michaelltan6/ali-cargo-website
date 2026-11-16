<?php
// Fungsi bersama untuk halaman layanan, rute, dan FAQ.
// Data tujuan ada di layanan-data.php (tanpa harga).
if (!defined('ALI_LAYANAN_LIB')) {
define('ALI_LAYANAN_LIB', 1);

define('ALI_SITE', 'https://www.anekalintasindcargo.com/');
define('ALI_WA', '6281376927815');

function ls_data() { static $d = null; if ($d === null) { $d = include __DIR__ . '/layanan-data.php'; } return $d; }
function ls_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ls_wa($text, $number = ALI_WA) { return 'https://wa.me/' . $number . '?text=' . rawurlencode($text); }
function ls_jenis($j) { return $j === 'darat' ? 'Cargo Darat' : 'Cargo Laut (kapal reguler)'; }
function ls_page($regKey) { $d = ls_data(); return 'ekspedisi-jakarta-' . $d['slug'][$regKey] . '.php'; }

// Rentang estimasi hari terkecil dan terbesar dari daftar tujuan
function ls_range($rows) {
    $min = 999; $max = 0;
    foreach ($rows as $r) {
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $r['hari'], $m)) { $min = min($min, (int)$m[1]); $max = max($max, (int)$m[2]); }
    }
    return [$min, $max];
}

function ls_head($title, $desc, $path, $jsonld = '') {
    $url = ALI_SITE . $path;
    ?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title><?php echo ls_h($title); ?></title>
  <meta name="description" content="<?php echo ls_h($desc); ?>" />
  <meta name="author" content="Aneka Lintas Indonesia Cargo" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="canonical" href="<?php echo ls_h($url); ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="Aneka Lintas Indonesia Cargo" />
  <meta property="og:locale" content="id_ID" />
  <meta property="og:title" content="<?php echo ls_h($title); ?>" />
  <meta property="og:description" content="<?php echo ls_h($desc); ?>" />
  <meta property="og:url" content="<?php echo ls_h($url); ?>" />
  <meta property="og:image" content="<?php echo ALI_SITE; ?>deprixa_components/images/slider/img1.png" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="icon" type="image/png" href="logoAli.png" />
  <link href="deprixa_components/content/cssefe4.css" rel="stylesheet" />
<?php if ($jsonld) { echo '  <script type="application/ld+json">' . json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n"; } ?>
</head>
<body>
<?php include_once __DIR__ . '/menu.php'; ?>
<main class="ls-main">
<?php
}

function ls_foot() {
    ?>
</main>
<?php include_once __DIR__ . '/footer.php'; ?>
</body>
</html>
<?php
}

function ls_cta($text, $label = 'Minta penawaran lewat WhatsApp') {
    ?><div class="ls-cta">
  <div>
    <strong>Mau tahu ongkos untuk barang kamu?</strong>
    <span>Kirim kota tujuan, jenis barang, dan perkiraan berat atau ukurannya. Tim kami balas dengan penawaran.</span>
  </div>
  <a class="ls-btn" href="<?php echo ls_h(ls_wa($text)); ?>" target="_blank" rel="noopener"><?php echo ls_h($label); ?></a>
</div>
<?php
}

function ls_table($rows) {
    ?><div class="ls-table-wrap"><table class="ls-table">
<thead><tr><th>Kota tujuan</th><th>Layanan</th><th>Estimasi</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr data-q="<?php echo ls_h(strtolower($r['kota'])); ?>">
  <td data-label="Kota tujuan"><?php echo ls_h($r['kota']); ?><?php if (!empty($r['penerus'])) echo ' <span class="ls-star" title="Dilayani lewat penerus">*</span>'; ?></td>
  <td data-label="Layanan"><span class="ls-tag ls-tag-<?php echo ls_h($r['jenis']); ?>"><?php echo ls_h(ls_jenis($r['jenis'])); ?></span></td>
  <td data-label="Estimasi"><?php echo ls_h($r['hari']); ?> hari</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php
}

function ls_note_penerus() {
    ?><p class="ls-note"><span class="ls-star">*</span> Kota bertanda bintang dilayani lewat mitra penerus di daerah tujuan, sehingga ada biaya penerus tambahan. Tim kami akan menjelaskannya saat memberi penawaran.</p>
<?php
}

function ls_region_links($except = '') {
    $d = ls_data();
    echo '<ul class="ls-chips">';
    foreach ($d['order'] as $k) {
        if ($k === $except) continue;
        echo '<li><a href="' . ls_h(ls_page($k)) . '">' . ls_h($d['name'][$k]) . '</a></li>';
    }
    echo '</ul>';
}

}
