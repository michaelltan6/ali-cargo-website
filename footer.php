<?php
// Footer halaman depan ALI Cargo.
// Tampilannya diatur di deprixa_components/styles/ali-site.css
?>
<footer class="footer-slide">
  <section class="footer-content">
    <div class="footer-block logo-block">
      <div class="mpd-logo-text">
        <img src="logoAli.png" alt="Logo Aneka Lintas Indonesia Cargo" />
        <p>Consult your Shipping with Us!</p>
      </div>
    </div>

    <div class="footer-block menu-block about-block">
      <header>Sekilas Tentang Kami</header>
      <ul>
        <li><a href="about-us">Tentang Kami</a></li>
        <li><a href="layanan">Layanan</a></li>
        <li><a href="rute-pengiriman">Rute Pengiriman</a></li>
        <li><a href="faq">FAQ</a></li>
        <li><a href="contact-us">Hubungi Kami</a></li>
      </ul>
    </div>

    <div class="footer-block menu-block tools-block">
      <header>Tools</header>
      <ul>
        <li><a href="tracking">Track A Shipment</a></li>
        <li><a href="contact-us">Dapatkan Quote</a></li>
      </ul>
    </div>

    <div class="mpd-social">
      <a href="https://wa.me/6281376927815" class="whatsapp-btn" target="_blank" rel="noopener" title="Chat dengan kami di WhatsApp">
        <img src="imagen/wa.png" alt="WhatsApp" />
      </a>
    </div>
  </section>

  <div class="footer-dark">
    <section class="footer-baseline">
      <p class="footer-text">&copy; Copyright PT. ANEKA LINTAS INDONESIA CARGO <?php echo date('Y'); ?></p>
      <ul class="footer-links">
        <li><a href="privacy-policy">Privacy Policy</a></li>
        <li><a href="cookie-policy">Cookie Policy</a></li>
        <li><a href="terms-and-conditions">Terms And Conditions</a></li>
      </ul>
    </section>
    <a href="#" id="back-top" aria-label="Kembali ke atas"></a>
  </div>

  <!-- Menu HP + tombol kembali ke atas. Tidak bergantung pada jQuery. -->
  <script>
  (function () {
    "use strict";

    // Tombol menu untuk layar HP
    var header = document.querySelector('.header-wrap');
    var nav = header ? header.querySelector('.menu-nav') : null;
    if (header && nav && !header.querySelector('.nav-toggle')) {
      if (!nav.id) { nav.id = 'ali-menu'; }
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'nav-toggle';
      btn.setAttribute('aria-controls', nav.id);
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Buka menu');
      btn.innerHTML = '<span class="nav-toggle-bars" aria-hidden="true"></span>';
      nav.parentNode.insertBefore(btn, nav);
      header.classList.add('js-nav');

      var setOpen = function (open) {
        header.classList.toggle('nav-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
      };
      btn.addEventListener('click', function () {
        setOpen(!header.classList.contains('nav-open'));
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && header.classList.contains('nav-open')) { setOpen(false); btn.focus(); }
      });

      // Tandai menu halaman yang sedang dibuka
      var here = (location.pathname.split('/').pop() || 'index.php').toLowerCase();
      var links = nav.querySelectorAll('a');
      for (var i = 0; i < links.length; i++) {
        var target = (links[i].getAttribute('href') || '').split('?')[0].toLowerCase();
        if (target === here) { links[i].setAttribute('aria-current', 'page'); }
      }
    }

    // Tombol kembali ke atas
    var top = document.getElementById('back-top');
    if (top) {
      var onScroll = function () {
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        top.classList.toggle('is-visible', y > 300);
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
      top.addEventListener('click', function (e) {
        e.preventDefault();
        try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (err) { window.scrollTo(0, 0); }
      });
    }
  })();
  </script>
</footer>
