<?php
require_once __DIR__ . '/layanan-lib.php';
// Album dan foto dikelola dari admin: Pengaturan > Gallery Website
require_once __DIR__ . '/gallery-lib.php';

$albumImages = [];
$albumsToShow = [];
foreach (gallery_load_albums() as $album) {
    $list = [];
    foreach (gallery_images($album['id']) as $fileName) {
        $list[] = 'gallery/' . $album['id'] . '/' . rawurlencode($fileName);
    }
    if ($list) {                      // album kosong tidak ditampilkan
        $albumImages[$album['id']] = $list;
        $albumsToShow[] = $album;
    }
}

ls_head('Gallery - Aneka Lintas Indonesia Cargo',
    'Dokumentasi kegiatan dan layanan pengiriman PT. Aneka Lintas Indonesia Cargo.',
    'gallery.php');
?>
<section class="ls-hero">
  <div class="ls-wrap">
    <nav class="ls-crumb" aria-label="Navigasi"><a href="index.php">Beranda</a> &rsaquo; Gallery</nav>
    <h1>Gallery</h1>
    <p class="ls-lead">Dokumentasi kegiatan dan layanan pengiriman kami. Klik sebuah album untuk melihat semua fotonya.</p>
  </div>
</section>

<section class="ls-wrap ls-section">
<?php if (!$albumsToShow): ?>
  <p class="ls-note">Foto akan segera ditambahkan.</p>
<?php else: ?>
  <div class="pg-gal">
  <?php foreach ($albumsToShow as $n => $album):
      $id = $album['id'];
      $first = $albumImages[$id][0];
      $count = count($albumImages[$id]);
      $title = $album['title'] !== '' ? $album['title'] : 'Album ' . ($n + 1);
      $alt = $album['title'] !== '' ? $album['title'] : 'Foto layanan cargo ' . ($n + 1); ?>
    <button type="button" class="pg-gal-item" data-album="<?php echo ls_h($id); ?>" data-title="<?php echo ls_h($title); ?>" aria-label="Buka album <?php echo ls_h($title); ?>">
      <img src="<?php echo ls_h($first); ?>" alt="<?php echo ls_h($alt); ?>" class="slide-in"<?php echo $n > 2 ? ' loading="lazy"' : ''; ?>>
      <?php if ($count > 1): ?><span class="pg-gal-count"><?php echo $count; ?> foto</span><?php endif; ?>
      <?php if ($album['title'] !== ''): ?><span class="pg-gal-cap"><?php echo ls_h($album['title']); ?></span><?php endif; ?>
    </button>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
  <?php ls_cta('Halo ALI Cargo, saya mau tanya soal layanan pengiriman.'); ?>
</section>

<div class="pg-lb" id="pg-lb" role="dialog" aria-modal="true" aria-label="Penampil foto">
  <button type="button" class="lb-x" aria-label="Tutup">&times;</button>
  <button type="button" class="lb-prev" aria-label="Foto sebelumnya">&lsaquo;</button>
  <img id="pg-lb-img" alt="">
  <button type="button" class="lb-next" aria-label="Foto berikutnya">&rsaquo;</button>
  <div class="lb-info" id="pg-lb-info"></div>
</div>

<script>
(function () {
  var albums = <?php echo json_encode($albumImages, JSON_UNESCAPED_SLASHES); ?>;

  // 1) Slideshow kecil di tiap album. Foto berikutnya baru diunduh saat akan tampil.
  Array.prototype.forEach.call(document.querySelectorAll('.pg-gal-item'), function (item) {
    var list = albums[item.getAttribute('data-album')] || [];
    if (list.length < 2) return;
    var idx = 0, busy = false, current = item.querySelector('img');
    function showNext() {
      if (busy || document.hidden) return;
      busy = true;
      var n = (idx + 1) % list.length, img = new Image();
      img.onload = function () {
        img.className = 'next'; img.alt = current.alt;
        item.insertBefore(img, item.querySelector('.pg-gal-count') || item.querySelector('.pg-gal-cap'));
        void img.offsetWidth;
        current.className = 'slide-out'; img.className = 'slide-in';
        setTimeout(function () { if (current.parentNode) current.parentNode.removeChild(current); current = img; idx = n; busy = false; }, 1500);
      };
      img.onerror = function () { idx = n; busy = false; };
      img.src = list[n];
    }
    setInterval(showNext, 4000 + Math.floor(Math.random() * 1500));
  });

  // 2) Penampil foto (lightbox)
  var lb = document.getElementById('pg-lb'), lbImg = document.getElementById('pg-lb-img'), lbInfo = document.getElementById('pg-lb-info');
  var cur = [], pos = 0, ttl = '', lastFocus = null;
  function show() {
    lbImg.src = cur[pos]; lbImg.alt = ttl;
    lbInfo.textContent = ttl + ' (' + (pos + 1) + ' dari ' + cur.length + ')';
  }
  function open(id, title) {
    cur = albums[id] || []; if (!cur.length) return;
    pos = 0; ttl = title; lastFocus = document.activeElement;
    lb.classList.add('open'); document.body.style.overflow = 'hidden'; show();
    lb.querySelector('.lb-x').focus();
  }
  function close() { lb.classList.remove('open'); document.body.style.overflow = ''; lbImg.removeAttribute('src'); if (lastFocus) lastFocus.focus(); }
  function step(d) { if (!cur.length) return; pos = (pos + d + cur.length) % cur.length; show(); }
  Array.prototype.forEach.call(document.querySelectorAll('.pg-gal-item'), function (item) {
    item.addEventListener('click', function () { open(item.getAttribute('data-album'), item.getAttribute('data-title')); });
  });
  lb.querySelector('.lb-x').addEventListener('click', close);
  lb.querySelector('.lb-prev').addEventListener('click', function () { step(-1); });
  lb.querySelector('.lb-next').addEventListener('click', function () { step(1); });
  lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
  document.addEventListener('keydown', function (e) {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') close(); else if (e.key === 'ArrowLeft') step(-1); else if (e.key === 'ArrowRight') step(1);
  });
  var tx = null;
  lb.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, { passive: true });
  lb.addEventListener('touchend', function (e) {
    if (tx === null) return; var dx = e.changedTouches[0].clientX - tx; tx = null;
    if (Math.abs(dx) > 50) step(dx < 0 ? 1 : -1);
  });
})();
</script>
<?php ls_foot();
