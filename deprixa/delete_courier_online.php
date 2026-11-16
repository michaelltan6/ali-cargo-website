<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

session_start();
require_once('database.php');
require_once('library.php');
isUser();

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