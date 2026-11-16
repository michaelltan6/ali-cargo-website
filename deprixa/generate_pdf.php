<?php
require_once('deprixa/database/database.php');
require_once('library.php');
requireAdmin();

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$mode = $_GET['mode'] ?? '';

try {
    $sql = "SELECT * FROM courier WHERE ";
    $params = [];
    if ($mode === 'Delivered') {
        $sql .= "status = 'Delivered'";
    } elseif ($mode === 'ToPay') {
        $sql .= "book_mode = 'ToPay'";
    } else {
        throw new Exception("Invalid mode.");
    }
    if ($start && $end) {
        $sql .= " AND pick_date BETWEEN :start AND :end";
        $params = ['start' => $start, 'end' => $end];
    }
    $sql .= " ORDER BY cid";
    error_log("PDF query: $sql, params: " . print_r($params, true));
    $stmt = dbQuery($sql, $params);
    $rowCount = dbNumRows($stmt);
    error_log("PDF query returned $rowCount rows");

    // Example PDF output (replace with actual PDF library like TCPDF or mPDF)
    header('Content-Type: application/pdf');
    echo "<h1>Shipments ($mode)</h1>";
    echo "<table border='1'>";
    echo "<tr><th># Tracking</th><th>Nama Pengirim</th><th>Destinasi</th><th>Pick Date</th><th>Karyawan</th>";
    if ($mode === 'ToPay') {
        echo "<th>Due Date</th>";
    }
    echo "</tr>";
    while ($row = dbFetchAssoc($stmt)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['cons_no']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ship_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['rev_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['pick_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user']) . "</td>";
        if ($mode === 'ToPay') {
            echo "<td>" . htmlspecialchars($row['due_date'] ?? 'Not set') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    error_log("PDF generation error: " . $e->getMessage());
    http_response_code(500);
    echo "Error generating PDF: " . htmlspecialchars($e->getMessage());
}
?>