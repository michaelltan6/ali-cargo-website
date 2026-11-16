<?php
header('Content-Type: application/json');
require_once('database.php');

try {
    error_log("update_duedate.php started, POST data: " . print_r($_POST, true));
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_duedate' && isset($_POST['cid'])) {
        $cid = filter_var($_POST['cid'], FILTER_SANITIZE_NUMBER_INT);
        $due_date = !empty($_POST['due_date']) ? filter_var($_POST['due_date'], FILTER_SANITIZE_STRING) : null;
        error_log("Processing update for cid=$cid, due_date=$due_date");

        $stmt = dbQuery("UPDATE courier SET due_date = :due_date WHERE cid = :cid", [
            ':due_date' => $due_date,
            ':cid' => $cid
        ]);

        if ($stmt->rowCount() > 0) {
            error_log("Successfully updated due_date for cid $cid to $due_date");
            echo json_encode(['success' => true, 'message' => 'Due date updated', 'due_date' => $due_date]);
        } else {
            error_log("No rows updated for cid $cid, due_date $due_date (record not found or no change)");
            // Check if record exists
            $checkStmt = dbQuery("SELECT cid FROM courier WHERE cid = :cid", [':cid' => $cid]);
            if (dbNumRows($checkStmt) > 0) {
                echo json_encode(['success' => false, 'message' => 'No changes made (due_date already set?)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
            }
        }
    } else {
        error_log("Invalid request to update_duedate.php: " . print_r($_POST, true));
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} catch (PDOException $e) {
    error_log("Database error in update_duedate.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in update_duedate.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>