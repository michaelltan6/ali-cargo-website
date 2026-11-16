<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

require_once('../../database.php');
require_once __DIR__ . '/../_common.php';
st_auth(false);

$id = $_POST['id'] ?? '';

try {
    // Delete record by ID
    $stmt = dbQuery("DELETE FROM mode_bookings WHERE id = ?", [$id]);
    echo json_encode(['msg' => 'ok']);
} catch (PDOException $e) {
    error_log("eliminar.php delete failed: " . $e->getMessage());
    echo json_encode(['msg' => 'error', 'details' => htmlspecialchars($e->getMessage())]);
}
?>