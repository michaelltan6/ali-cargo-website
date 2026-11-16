<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); ini_set('session.use_strict_mode','1'); session_start(); }
require_once('database.php');
require_once('library.php');
requireAdmin();

header('Content-Type: application/json');

try {
    $cid = $_GET['cid'] ?? 0;
    if ($cid <= 0) {
        throw new Exception("Invalid courier ID.");
    }

    $sql = "DELETE FROM courier_online WHERE cid = :cid";
    $stmt = $dbConn->prepare($sql);
    $stmt->execute(['cid' => $cid]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("No online courier found with ID: $cid");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil dihapus.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Gagal menghapus data. Error: ' . $e->getMessage()
    ]);
}
?>