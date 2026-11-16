<?php
// Endpoint JSON untuk halaman Customer Profile dan pencarian di form Tambah Pengiriman.
require_once __DIR__ . '/library.php';
require_once __DIR__ . '/customer-lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
requireAdmin(true);

function cp_out($ok, $data = []) {
    echo json_encode(array_merge(['success' => $ok], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'search';
        if ($action === 'search') {
            cp_out(true, ['items' => cp_search($_GET['q'] ?? '', (int)($_GET['limit'] ?? 10))]);
        }
        if ($action === 'get') {
            $row = cp_get($_GET['id'] ?? 0);
            $row ? cp_out(true, ['item' => $row]) : cp_out(false, ['message' => 'Customer tidak ditemukan.']);
        }
        cp_out(false, ['message' => 'Aksi tidak dikenal.']);
    }

    // Semua yang mengubah data: harus dari halaman website sendiri
    if (!sameOriginOk()) { http_response_code(403); cp_out(false, ['message' => 'Permintaan ditolak.']); }

    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $f = cp_fields($_POST);
        $err = cp_validate($f);
        if ($err !== '') { cp_out(false, ['message' => $err]); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            if (!cp_get($id)) { cp_out(false, ['message' => 'Customer tidak ditemukan.']); }
            cp_update($id, $f);
            cp_out(true, ['message' => 'Customer diperbarui.', 'item' => cp_get($id)]);
        }
        $id = cp_create($f);
        cp_out(true, ['message' => 'Customer ditambahkan.', 'item' => cp_get($id)]);
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !cp_get($id)) { cp_out(false, ['message' => 'Customer tidak ditemukan.']); }
        cp_delete($id);
        cp_out(true, ['message' => 'Customer dihapus.']);
    }
    cp_out(false, ['message' => 'Aksi tidak dikenal.']);
} catch (Exception $e) {
    error_log('customer-profile-api: ' . $e->getMessage());
    http_response_code(500);
    cp_out(false, ['message' => 'Terjadi kesalahan di server.']);
}
