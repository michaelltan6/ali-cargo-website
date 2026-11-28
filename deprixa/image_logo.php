<?php
require_once('database.php');
try {
    $stmt = dbQuery("SELECT imagen, tipo FROM subir_imagen WHERE id = ?", [1]);
    $row = dbFetchAssoc($stmt);
    if ($row) {
        $tipo = strtolower(trim((string)$row['tipo']));
        if (!preg_match('#^image/(png|jpe?g|gif|webp)$#', $tipo)) { $tipo = 'application/octet-stream'; }
        header('X-Content-Type-Options: nosniff');
        header("Content-Type: " . $tipo);
        echo $row['imagen'];
    } else {
        header("Content-Type: image/png");
        readfile("assets/images/default_logo.png");
    }
} catch (PDOException $e) {
    error_log("Error fetching logo: " . $e->getMessage());
    header("Content-Type: image/png");
    readfile("assets/images/default_logo.png");
}
?>