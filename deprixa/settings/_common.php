<?php
// Pengendali bersama untuk menu pengaturan admin:
// Kantor, Jenis Pengiriman, Klien, User, dan Admin.
//
// Semua query memakai parameter terikat, hanya admin yang sudah login yang
// boleh memanggil, dan permintaan dari situs lain ditolak.
// Setiap file di folder menu (listar.php, agregar.php, dst.) tinggal memanggil
// st_run('<modul>', '<aksi>').

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../library.php';    // sesi + isUser() + database
require_once __DIR__ . '/../funciones.php';  // claves() untuk hash password

function st_json($data, $code = 200) {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    exit;
}

function st_is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Tolak permintaan POST yang berasal dari situs lain
function st_same_origin() {
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
    foreach (array('HTTP_ORIGIN', 'HTTP_REFERER') as $h) {
        if (!empty($_SERVER[$h])) {
            $src = strtolower((string)parse_url($_SERVER[$h], PHP_URL_HOST));
            $port = parse_url($_SERVER[$h], PHP_URL_PORT);
            if ($port) { $src .= ':' . $port; }
            return $src === $host;
        }
    }
    return true; // tanpa Origin/Referer (misalnya dari alat uji) diteruskan, login tetap wajib
}

function st_auth($emptyBody = false) {
    if (!function_exists('isUser') || !isUser() || (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '') !== 'Administrator') {
        st_json($emptyBody ? array() : array('msg' => 'sesi', 'details' => 'Silakan login lagi.'), 401);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !st_same_origin()) {
        st_json(array('msg' => 'error', 'details' => 'Permintaan ditolak.'), 403);
    }
}

function st_post($k) { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }
function st_estado() { return (isset($_POST['estado']) && (int)$_POST['estado'] === 1) ? 1 : 0; }

function st_modules() {
    return array(
        'offices' => array(
            'table' => 'offices', 'key' => 'id',
            'fields' => array('off_name' => 'nomvacio', 'address' => 'apevacio', 'city' => 'telvacio', 'ph_no' => 'emavacio', 'office_time' => 'usuvacio', 'contact_person' => 'pasvacio'),
        ),
        'typeshipments' => array(
            'table' => 'type_shipments', 'key' => 'id',
            'fields' => array('name' => 'nomvacio', 'packaging' => 'apevacio', 'dimensions' => 'telvacio'),
        ),
        'clients' => array(
            'table' => 'tbl_clients', 'key' => 'id',
            'fields' => array('name' => 'nomvacio', 'address' => 'telvacio', 'phone' => 'usuvacio', 'email' => 'emavacio'),
            'secret' => 'password', 'secret_code' => 'apevacio',
            'insert_extra' => array('company', 'country', 'state', 'zipcode'),
            'dup' => array('email', 'name'), 'dup_op' => 'AND',
            'date' => true, 'redirect' => '../../customer.php',
            'protect_self' => false,
        ),
        'users' => array(
            'table' => 'manager_user', 'key' => 'cid',
            'fields' => array('name' => 'nomvacio', 'email' => 'apevacio', 'phone' => 'telvacio', 'office' => 'emavacio', 'role' => 'usuvacio'),
            'secret' => 'pwd', 'secret_code' => 'pasvacio',
            'insert_extra' => array('name_parson'), 'insert_first' => array('name_parson' => 'nompavacio'),
            'with_type' => true,
            'dup' => array('email', 'name'), 'dup_op' => 'OR',
            'date' => true, 'redirect' => '../../add-new-users.php',
            'protect_self' => false,
        ),
        'admins' => array(
            'table' => 'manager_admin', 'key' => 'cid',
            'fields' => array('name' => 'nomvacio', 'email' => 'apevacio', 'phone' => 'telvacio', 'office' => 'emavacio', 'role' => 'usuvacio'),
            'secret' => 'pwd', 'secret_code' => 'pasvacio',
            'insert_extra' => array('name_parson'), 'insert_first' => array('name_parson' => 'nompavacio'),
            'with_type' => true,
            'dup' => array('email', 'name'), 'dup_op' => 'OR',
            'date' => true, 'redirect' => '../../add-new-users-admin.php',
            'protect_self' => true,
        ),
    );
}

function st_run($module, $action) {
    $mods = st_modules();
    if (!isset($mods[$module])) { st_json(array('msg' => 'error'), 400); }
    $m = $mods[$module];
    st_auth($action === 'listar');
    try {
        switch ($action) {
            case 'listar':    st_listar($m); break;
            case 'editar':    st_editar($m); break;
            case 'agregar':   st_agregar($m); break;
            case 'actualizar': st_actualizar($m); break;
            case 'eliminar':  st_eliminar($m); break;
            case 'estado':    st_estado_toggle($m); break;
        }
    } catch (Exception $e) {
        error_log('settings/' . $module . '/' . $action . ' gagal: ' . $e->getMessage());
        if ($action === 'listar') { st_json(array(), 500); }
        st_json(array('msg' => 'error', 'details' => 'Terjadi kesalahan di server.'), 500);
    }
}

// Sembunyikan password/hash dari data yang dikirim ke browser
function st_scrub($m, $row) {
    if (!empty($m['secret']) && array_key_exists($m['secret'], $row)) { $row[$m['secret']] = ''; }
    return $row;
}

function st_listar($m) {
    $stmt = dbQuery('SELECT * FROM ' . $m['table']);
    $out = array();
    while ($row = dbFetchAssoc($stmt)) {
        $row = st_scrub($m, $row);
        if (!empty($m['secret'])) { $row[$m['secret']] = '********'; }
        $out[] = $row;
    }
    st_json($out);
}

function st_editar($m) {
    $id = isset($_POST[$m['key']]) ? $_POST[$m['key']] : (isset($_POST['id']) ? $_POST['id'] : '');
    $stmt = dbQuery('SELECT * FROM ' . $m['table'] . ' WHERE ' . $m['key'] . ' = ?', array($id));
    $out = array();
    while ($row = dbFetchAssoc($stmt)) { $out[] = st_scrub($m, $row); }
    st_json($out);
}

function st_check_required($m, $isInsert) {
    if ($isInsert && !empty($m['insert_first']) && isset($_POST['name_parson'])) {
        foreach ($m['insert_first'] as $f => $code) {
            if (st_post($f) === '') { return $code; }
        }
    }
    foreach ($m['fields'] as $f => $code) {
        if (st_post($f) === '') { return $code; }
    }
    // password wajib hanya saat menambah data baru
    if ($isInsert && !empty($m['secret']) && st_post($m['secret']) === '') { return $m['secret_code']; }
    return '';
}

function st_agregar($m) {
    $ajax = st_is_ajax();
    $code = st_check_required($m, true);
    if ($code !== '') {
        if ($ajax) { st_json(array('msg' => $code)); }
        st_page_msg('Data belum lengkap. Isi semua kolom yang wajib.', $m);
    }
    // cek data ganda (untuk user, admin, klien)
    if (!empty($m['dup'])) {
        $conds = array(); $params = array();
        foreach ($m['dup'] as $f) { $conds[] = $f . ' = ?'; $params[] = st_post($f); }
        $stmt = dbQuery('SELECT 1 FROM ' . $m['table'] . ' WHERE ' . implode(' ' . $m['dup_op'] . ' ', $conds) . ' LIMIT 1', $params);
        if (dbFetchAssoc($stmt)) {
            if ($ajax) { st_json(array('msg' => 'error', 'details' => 'Email atau nama itu sudah terdaftar.')); }
            st_page_msg('Email atau nama itu sudah terdaftar. Gunakan data yang berbeda.', $m);
        }
    }
    $cols = array(); $vals = array();
    foreach (array_keys($m['fields']) as $f) { $cols[] = $f; $vals[] = st_post($f); }
    if (!empty($m['insert_extra'])) {
        foreach ($m['insert_extra'] as $f) { $cols[] = $f; $vals[] = st_post($f); }
    }
    if (!empty($m['secret'])) { $cols[] = $m['secret']; $vals[] = ali_make_hash(st_post($m['secret']), $m['table']); }
    $cols[] = 'estado'; $vals[] = st_estado();
    if (!empty($m['with_type'])) { $cols[] = 'type'; $vals[] = isset($_POST['type']) ? (int)$_POST['type'] : 0; }
    $sql = 'INSERT INTO ' . $m['table'] . ' (' . implode(',', $cols) . (!empty($m['date']) ? ',date' : '') . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . (!empty($m['date']) ? ',CURDATE()' : '') . ')';
    dbQuery($sql, $vals);
    if ($ajax) { st_json(array('msg' => 'ok')); }
    st_page_msg('Terima kasih, data berhasil disimpan.', $m);
}

function st_page_msg($text, $m) {
    header('Content-Type: text/html; charset=utf-8');
    $to = isset($m['redirect']) ? $m['redirect'] : '../../admin.php';
    echo '<script>alert(' . json_encode($text) . ');window.location=' . json_encode($to) . ';</script>';
    exit;
}

function st_actualizar($m) {
    $id = isset($_POST[$m['key']]) ? $_POST[$m['key']] : (isset($_POST['id']) ? $_POST['id'] : '');
    $code = st_check_required($m, false);
    if ($code !== '') { st_json(array('msg' => $code)); }
    $sets = array(); $vals = array();
    foreach (array_keys($m['fields']) as $f) { $sets[] = $f . ' = ?'; $vals[] = st_post($f); }
    // password dikosongkan = tidak diubah
    if (!empty($m['secret']) && st_post($m['secret']) !== '') { $sets[] = $m['secret'] . ' = ?'; $vals[] = ali_make_hash(st_post($m['secret']), $m['table']); }
    $sets[] = 'estado = ?'; $vals[] = st_estado();
    if (!empty($m['protect_self'])) {
        // jangan sampai admin menonaktifkan akunnya sendiri
        $stmt = dbQuery('SELECT name FROM ' . $m['table'] . ' WHERE ' . $m['key'] . ' = ?', array($id));
        $row = dbFetchAssoc($stmt);
        if ($row && $row['name'] === $_SESSION['user_name'] && st_estado() === 0) {
            st_json(array('msg' => 'error', 'details' => 'Akun yang sedang dipakai tidak boleh dinonaktifkan.'));
        }
    }
    $vals[] = $id;
    dbQuery('UPDATE ' . $m['table'] . ' SET ' . implode(', ', $sets) . ' WHERE ' . $m['key'] . ' = ?', $vals);
    // kalau admin mengganti password akunnya sendiri, sesi ikut diperbarui supaya tidak terlempar keluar
    if (!empty($m['protect_self']) && !empty($m['secret']) && st_post($m['secret']) !== '') {
        $stmt = dbQuery('SELECT name FROM ' . $m['table'] . ' WHERE ' . $m['key'] . ' = ?', array($id));
        $row = dbFetchAssoc($stmt);
        if ($row && $row['name'] === $_SESSION['user_name']) {
            $stmt = dbQuery('SELECT ' . $m['secret'] . ' AS p FROM ' . $m['table'] . ' WHERE ' . $m['key'] . ' = ?', array($id));
            $pr = dbFetchAssoc($stmt);
            if ($pr) { $_SESSION['pwd'] = ali_pwd_fp($pr['p']); }
        }
    }
    st_json(array('msg' => 'ok'));
}

function st_eliminar($m) {
    $id = isset($_POST[$m['key']]) ? $_POST[$m['key']] : (isset($_POST['id']) ? $_POST['id'] : '');
    if (!empty($m['protect_self'])) {
        $stmt = dbQuery('SELECT name FROM ' . $m['table'] . ' WHERE ' . $m['key'] . ' = ?', array($id));
        $row = dbFetchAssoc($stmt);
        if ($row && $row['name'] === $_SESSION['user_name']) {
            st_json(array('msg' => 'error', 'details' => 'Akun yang sedang dipakai tidak boleh dihapus.'));
        }
    }
    dbQuery('DELETE FROM ' . $m['table'] . ' WHERE ' . $m['key'] . ' = ?', array($id));
    st_json(array('msg' => 'ok'));
}

function st_estado_toggle($m) {
    $id = isset($_POST[$m['key']]) ? $_POST[$m['key']] : (isset($_POST['id']) ? $_POST['id'] : '');
    $stmt = dbQuery('SELECT * FROM ' . $m['table'] . ' WHERE ' . $m['key'] . ' = ?', array($id));
    $row = dbFetchAssoc($stmt);
    if (!$row) { st_json(array('msg' => 'error', 'details' => 'Data tidak ditemukan.')); }
    if (!empty($m['protect_self']) && $row['name'] === $_SESSION['user_name']) {
        st_json(array('msg' => 'error', 'details' => 'Akun yang sedang dipakai tidak boleh dinonaktifkan.'));
    }
    $new = ((string)$row['estado'] === '1') ? 0 : 1;
    dbQuery('UPDATE ' . $m['table'] . ' SET estado = ? WHERE ' . $m['key'] . ' = ?', array($new, $id));
    st_json(array('msg' => 'ok'));
}
