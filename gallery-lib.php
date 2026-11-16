<?php
/**
 * Fungsi bersama untuk Gallery website ALI Cargo.
 * Dipakai oleh gallery.php (halaman publik) dan deprixa/gallery-manager.php (admin).
 *
 * Cara kerjanya:
 *  - Setiap album = satu folder di gallery/ (folder1..folder8 yang lama tetap dipakai).
 *  - Daftar album + judulnya disimpan di gallery/albums.json (dibuat otomatis saat pertama kali disimpan).
 *  - Foto yang diunggah selalu dikecilkan dan disimpan ulang sebagai JPEG dengan nama acak,
 *    jadi isi file pasti gambar murni (bukan skrip) dan nama aslinya tidak dipakai.
 *
 * File ini tidak menghasilkan output apa pun kalau dibuka langsung.
 */
if (!defined('ALI_GALLERY_LIB')) {
    define('ALI_GALLERY_LIB', 1);

    define('ALI_GALLERY_MAX_EDGE', 1600);               // sisi terpanjang foto setelah dikecilkan (px)
    define('ALI_GALLERY_JPEG_Q', 82);                 // kualitas JPEG
    define('ALI_GALLERY_MAX_BYTES', 15 * 1024 * 1024);   // batas ukuran satu file sebelum diproses
    define('ALI_GALLERY_MAX_PIXELS', 60000000);           // batas piksel (60 MP) supaya memori aman
    define('ALI_GALLERY_MAX_ALBUMS', 40);

    function gallery_dir() { return __DIR__ . '/gallery'; }

    function gallery_exts() { return ['jpg', 'jpeg', 'png', 'gif', 'webp']; }

    function gallery_valid_id($id) {
        return is_string($id) && preg_match('/^[a-z0-9][a-z0-9-]{2,39}$/', $id) === 1;
    }

    function gallery_clean_title($t) {
        $t = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$t);
        $t = trim(preg_replace('/\s+/u', ' ', (string)$t));
        return function_exists('mb_substr') ? mb_substr($t, 0, 60, 'UTF-8') : substr($t, 0, 60);
    }

    /** Album bawaan (sebelum albums.json ada): folder1..folder8 tanpa judul. */
    function gallery_default_albums() {
        $out = [];
        for ($i = 1; $i <= 8; $i++) {
            if (is_dir(gallery_dir() . '/folder' . $i)) {
                $out[] = ['id' => 'folder' . $i, 'title' => ''];
            }
        }
        return $out;
    }

    function gallery_load_albums() {
        $file = gallery_dir() . '/albums.json';
        if (is_file($file)) {
            $data = json_decode((string)@file_get_contents($file), true);
            if (is_array($data) && isset($data['albums']) && is_array($data['albums'])) {
                $albums = [];
                $seen = [];
                foreach ($data['albums'] as $a) {
                    if (!is_array($a) || !isset($a['id']) || !gallery_valid_id($a['id'])) continue;
                    if (isset($seen[$a['id']]) || !is_dir(gallery_dir() . '/' . $a['id'])) continue;
                    $seen[$a['id']] = true;
                    $albums[] = ['id' => $a['id'], 'title' => gallery_clean_title($a['title'] ?? '')];
                }
                return $albums;
            }
        }
        return gallery_default_albums();
    }

    function gallery_save_albums(array $albums) {
        $dir = gallery_dir();
        if (!is_dir($dir) || !is_writable($dir)) return false;
        $clean = [];
        foreach ($albums as $a) {
            if (gallery_valid_id($a['id'] ?? null)) {
                $clean[] = ['id' => $a['id'], 'title' => gallery_clean_title($a['title'] ?? '')];
            }
        }
        $json = json_encode(['albums' => $clean], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $tmp  = $dir . '/albums.json.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
        @chmod($tmp, 0644);
        if (!@rename($tmp, $dir . '/albums.json')) {
            @unlink($tmp);
            return false;
        }
        return true;
    }

    function gallery_find_album($id) {
        if (!gallery_valid_id($id)) return null;
        foreach (gallery_load_albums() as $a) {
            if ($a['id'] === $id) return $a;
        }
        return null;
    }

    /** Daftar nama file foto di satu album, yang terbaru di depan. */
    function gallery_images($albumId) {
        if (!gallery_valid_id($albumId)) return [];
        $dir = gallery_dir() . '/' . $albumId;
        if (!is_dir($dir)) return [];
        $items = [];
        foreach ((array)@scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || $f[0] === '.') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, gallery_exts(), true)) continue;
            $p = $dir . '/' . $f;
            if (!is_file($p)) continue;
            $items[] = ['name' => $f, 'time' => (int)@filemtime($p)];
        }
        usort($items, function ($a, $b) {
            return $b['time'] <=> $a['time'] ?: strcmp($a['name'], $b['name']);
        });
        return array_column($items, 'name');
    }

    function gallery_add_album($title, &$err) {
        $albums = gallery_load_albums();
        if (count($albums) >= ALI_GALLERY_MAX_ALBUMS) { $err = 'Jumlah album sudah mencapai batas (' . ALI_GALLERY_MAX_ALBUMS . ').'; return null; }
        $dir = gallery_dir();
        if (!is_dir($dir) || !is_writable($dir)) { $err = 'Folder gallery tidak bisa ditulis oleh server. Atur izin folder "gallery" ke 755 atau 775 di File Manager.'; return null; }
        do { $id = 'album-' . bin2hex(random_bytes(3)); } while (file_exists($dir . '/' . $id));
        if (!@mkdir($dir . '/' . $id, 0755)) { $err = 'Gagal membuat folder album baru.'; return null; }
        $albums[] = ['id' => $id, 'title' => gallery_clean_title($title)];
        if (!gallery_save_albums($albums)) { @rmdir($dir . '/' . $id); $err = 'Gagal menyimpan daftar album.'; return null; }
        return $id;
    }

    function gallery_rename_album($id, $title, &$err) {
        $albums = gallery_load_albums();
        foreach ($albums as &$a) {
            if ($a['id'] === $id) {
                $a['title'] = gallery_clean_title($title);
                unset($a);
                if (!gallery_save_albums($albums)) { $err = 'Gagal menyimpan judul album.'; return false; }
                return true;
            }
        }
        unset($a);
        $err = 'Album tidak ditemukan.';
        return false;
    }

    function gallery_move_album($id, $dir, &$err) {
        $albums = gallery_load_albums();
        foreach ($albums as $i => $a) {
            if ($a['id'] !== $id) continue;
            $j = $dir === 'up' ? $i - 1 : $i + 1;
            if ($j < 0 || $j >= count($albums)) return true;
            $tmp = $albums[$i]; $albums[$i] = $albums[$j]; $albums[$j] = $tmp;
            if (!gallery_save_albums($albums)) { $err = 'Gagal menyimpan urutan album.'; return false; }
            return true;
        }
        $err = 'Album tidak ditemukan.';
        return false;
    }

    /** Hapus album — hanya boleh kalau sudah kosong, supaya foto tidak hilang tanpa sengaja. */
    function gallery_delete_album($id, &$err) {
        if (!gallery_find_album($id)) { $err = 'Album tidak ditemukan.'; return false; }
        if (count(gallery_images($id)) > 0) { $err = 'Album masih berisi foto. Hapus semua fotonya dulu.'; return false; }
        $albums = array_values(array_filter(gallery_load_albums(), function ($a) use ($id) { return $a['id'] !== $id; }));
        if (!gallery_save_albums($albums)) { $err = 'Gagal menyimpan daftar album.'; return false; }
        @rmdir(gallery_dir() . '/' . $id);   // hanya berhasil kalau folder benar-benar kosong
        return true;
    }

    function gallery_delete_photo($albumId, $name, &$err) {
        if (!gallery_find_album($albumId)) { $err = 'Album tidak ditemukan.'; return false; }
        $name = basename((string)$name);
        if (!in_array($name, gallery_images($albumId), true)) { $err = 'Foto tidak ditemukan.'; return false; }
        if (!@unlink(gallery_dir() . '/' . $albumId . '/' . $name)) { $err = 'Foto gagal dihapus (izin file di server).'; return false; }
        return true;
    }

    function gallery_memory_ok($w, $h) {
        @ini_set('memory_limit', '384M');
        $limit = trim((string)ini_get('memory_limit'));
        if ($limit === '-1') return true;
        $bytes = (int)$limit;
        switch (strtolower(substr($limit, -1))) {
            case 'g': $bytes *= 1073741824; break;
            case 'm': $bytes *= 1048576; break;
            case 'k': $bytes *= 1024; break;
        }
        $need = (int)($w * $h * 6) + 24 * 1048576;
        return ($need + memory_get_usage()) < $bytes;
    }

    /**
     * Proses satu file unggahan: cek isinya benar-benar gambar, putar sesuai EXIF,
     * kecilkan, simpan sebagai JPEG baru. Mengembalikan nama file baru, atau null + $err.
     */
    function gallery_save_upload($tmpPath, $albumId, &$err) {
        if (!gallery_find_album($albumId)) { $err = 'Album tidak ditemukan.'; return null; }
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            $err = 'Ekstensi GD belum aktif di PHP hosting, jadi foto belum bisa diproses.'; return null;
        }
        $destDir = gallery_dir() . '/' . $albumId;
        if (!is_dir($destDir) || !is_writable($destDir)) { $err = 'Folder album tidak bisa ditulis oleh server.'; return null; }
        $size = @filesize($tmpPath);
        if ($size === false || $size < 100) { $err = 'File kosong atau rusak.'; return null; }
        if ($size > ALI_GALLERY_MAX_BYTES) { $err = 'Ukuran file lebih dari ' . (ALI_GALLERY_MAX_BYTES / 1048576) . ' MB.'; return null; }

        $info = @getimagesize($tmpPath);
        if (!$info) { $err = 'Bukan file gambar yang valid (format HEIC dari iPhone belum didukung; ubah ke JPG dulu).'; return null; }
        $type = $info[2];
        $w = (int)$info[0]; $h = (int)$info[1];
        if ($w < 1 || $h < 1 || $w * $h > ALI_GALLERY_MAX_PIXELS) { $err = 'Resolusi foto terlalu besar.'; return null; }
        if (!gallery_memory_ok($w, $h)) { $err = 'Foto terlalu besar untuk diproses di server ini. Kecilkan dulu resolusinya.'; return null; }

        switch ($type) {
            case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($tmpPath); break;
            case IMAGETYPE_PNG:  $src = @imagecreatefrompng($tmpPath); break;
            case IMAGETYPE_GIF:  $src = @imagecreatefromgif($tmpPath); break;
            case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false; break;
            default: $src = false;
        }
        if (!$src) { $err = 'Format gambar tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.'; return null; }

        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data') && function_exists('imagerotate')) {
            $exif = @exif_read_data($tmpPath);
            $o = is_array($exif) ? (int)($exif['Orientation'] ?? 1) : 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($angle) {
                $rot = @imagerotate($src, $angle, 0);
                if ($rot) { imagedestroy($src); $src = $rot; }
            }
        }

        $sw = imagesx($src); $sh = imagesy($src);
        $scale = min(1, ALI_GALLERY_MAX_EDGE / max($sw, $sh));
        $nw = max(1, (int)round($sw * $scale));
        $nh = max(1, (int)round($sh * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // latar putih untuk PNG/GIF transparan
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
        imagedestroy($src);
        imageinterlace($dst, true);

        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.jpg';
        $path = $destDir . '/' . $name;
        $ok = @imagejpeg($dst, $path, ALI_GALLERY_JPEG_Q);
        imagedestroy($dst);
        if (!$ok || !is_file($path)) { $err = 'Gagal menyimpan foto ke server.'; return null; }
        @chmod($path, 0644);
        return $name;
    }
}
