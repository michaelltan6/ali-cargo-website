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
    // Fetch record by ID
    $stmt = dbQuery("SELECT * FROM mode_bookings WHERE id = ?", [$id]);
    $datos = [];
    while ($row = dbFetchAssoc($stmt)) {
        $datos[] = $row;
    }
    echo json_encode($datos);
} catch (PDOException $e) {
    error_log("editar.php query failed: " . $e->getMessage());
    echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
}
?>