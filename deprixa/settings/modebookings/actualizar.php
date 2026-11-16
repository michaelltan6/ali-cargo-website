<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

require_once('../../database.php');
require_once __DIR__ . '/../_common.php';
st_auth(false);

$id = $_POST['id'] ?? '';
$name = $_POST['name'] ?? '';
$services = $_POST['services'] ?? '';
$deliverytime = $_POST['deliverytime'] ?? '';
$observations = $_POST['observations'] ?? '';
$estado = isset($_POST['estado']) ? 1 : 0;

// Basic validation
if (empty($name)) {
    echo json_encode(['msg' => 'nomvacio']);
    exit;
}
if (empty($services)) {
    echo json_encode(['msg' => 'apevacio']);
    exit;
}
if (empty($deliverytime)) {
    echo json_encode(['msg' => 'telvacio']);
    exit;
}
if (empty($observations)) {
    echo json_encode(['msg' => 'emavacio']);
    exit;
}

try {
    // Update mode_bookings (password field removed as it’s not in schema)
    $stmt = dbQuery(
        "UPDATE mode_bookings SET name = ?, services = ?, deliverytime = ?, observations = ?, estado = ? WHERE id = ?",
        [$name, $services, $deliverytime, $observations, $estado, $id]
    );
    echo json_encode(['msg' => 'ok']);
} catch (PDOException $e) {
    error_log("actualizar.php update failed: " . $e->getMessage());
    echo json_encode(['msg' => 'error', 'details' => htmlspecialchars($e->getMessage())]);
}
?>