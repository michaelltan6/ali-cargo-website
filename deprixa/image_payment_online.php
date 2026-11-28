<?php
// Menampilkan gambar dari database. Parameter divalidasi dan query memakai parameter terikat.
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode', '1'); session_start(); }
require_once('database.php');
require_once('library.php');
// Bukti transfer hanya boleh dilihat admin yang login
if (!isUser()) { http_response_code(403); exit; }
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
if ($cid > 0) {
    try {
        $stmt = dbQuery("SELECT imagen, tipo FROM upload_image_bank WHERE cid = ?", [$cid]);
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
