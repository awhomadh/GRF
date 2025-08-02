<?php
session_start();

require_once 'db_config.php';

/**
 * Redirects the user to the dashboard with a status message.
 *
 * @param string $type The type of message ('success', 'danger', or 'warning').
 * @param string $message The message to display.
 */
function redirect_with_status($type, $message) {
    $_SESSION['status_type'] = $type;
    $_SESSION['status_message'] = $message;
    header("Location: dashboard.php");
    exit();
}

// --- Security & Validation ---
// For better security, destructive actions like deletion should be handled via POST requests
// to prevent Cross-Site Request Forgery (CSRF) attacks. This script handles GET for
// simplicity, matching the link implementation in dashboard.php.
if ($_SERVER["REQUEST_METHOD"] !== "GET" || !isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$requisition_id = (int)$_GET['id'];

if ($requisition_id <= 0) {
    redirect_with_status('danger', 'Invalid Requisition ID provided.');
}

// --- Database Deletion ---
// The 'requisition_items' table is set up with ON DELETE CASCADE in db.sql.
// This means that when a record in the 'requisitions' table is deleted,
// all corresponding records in 'requisition_items' will be deleted automatically.
$stmt = $mysqli->prepare("DELETE FROM requisitions WHERE id = ?");

if ($stmt === false) {
    // In a real application, you should log this error instead of displaying it.
    error_log("Failed to prepare delete statement: " . $mysqli->error);
    redirect_with_status('danger', 'A server error occurred. Please try again later.');
}

$stmt->bind_param("i", $requisition_id);

if ($stmt->execute()) {
    // Check if any row was actually deleted.
    if ($stmt->affected_rows > 0) {
        redirect_with_status('success', 'Requisition has been deleted successfully.');
    } else {
        redirect_with_status('warning', 'Requisition not found or already deleted.');
    }
} else {
    error_log("Error deleting requisition ID {$requisition_id}: " . $stmt->error);
    redirect_with_status('danger', 'An error occurred while trying to delete the requisition.');
}

$stmt->close();
$mysqli->close();

?>

