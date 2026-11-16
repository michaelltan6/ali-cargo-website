<?php
// Menampilkan gambar dari database. Parameter divalidasi dan query memakai parameter terikat.
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
require_once('deprixa/database.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    try {
        $stmt = dbQuery("SELECT imagen, tipo FROM subir_imagen WHERE id = ?", [$id]);
        $row = dbFetchAssoc($stmt);
    } catch (Exception $e) {
        $row = false;
    }
    if ($row) {
        $tipo = strtolower(trim($row['tipo']));
        if (!preg_match('#^image/(png|jpe?g|gif|webp)$#', $tipo)) {
            $tipo = 'application/octet-stream';
        }
        header('Content-Type: ' . $tipo);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        echo $row['imagen'];
        exit;
    }
}
http_response_code(404);
