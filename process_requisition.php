<?php
// Start the session to pass status messages.
session_start();

// Include database configuration
require_once 'db_config.php';

// Function to redirect back to the form with a status message
function redirect_with_status($type, $message) {
    $_SESSION['status_type'] = $type;
    $_SESSION['status_message'] = $message;
    header("Location: dashboard.php");
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // If not a POST request, redirect to the form
    header("Location: dashboard.php");
    exit();
}

// Basic Server-Side Validation
$required_fields = ["office_name", "section_unit", "grf_number", "requisition_date", "requested_by", "requested_by_signature", "requested_by_date", "authorised_by", "authorised_by_signature", "authorised_by_date"];
foreach ($required_fields as $field) {
    if (empty(trim($_POST[$field]))) {
        redirect_with_status('danger', "Validation failed: '{$field}' is a required field.");
    }
}

// Validate that at least one item is submitted
if (empty($_POST['quantity']) || empty($_POST['description'])) {
    redirect_with_status('danger', 'You must add at least one item to the requisition.');
}


// Initialize variables for form data
$office_name = trim($_POST["office_name"]);
$section_unit = trim($_POST["section_unit"]);
$grf_number = trim($_POST["grf_number"]);
$requisition_date = trim($_POST["requisition_date"]);

// --- Server-Side Duplicate GRF Number Check ---
// This is a crucial fallback in case client-side validation is bypassed.
$stmt_check = $mysqli->prepare("SELECT id FROM requisitions WHERE grf_number = ?");
if ($stmt_check) {
    $stmt_check->bind_param("s", $grf_number);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        // The GRF number already exists. Redirect with an error.
        redirect_with_status('danger', "Submission failed: The GRF Number '{$grf_number}' already exists.");
    }
    $stmt_check->close();
}

$requested_by = trim($_POST["requested_by"]);
$requested_by_signature = trim($_POST["requested_by_signature"]);
$requested_by_date = trim($_POST["requested_by_date"]);

$authorised_by = trim($_POST["authorised_by"]);
$authorised_by_signature = trim($_POST["authorised_by_signature"]);
$authorised_by_date = trim($_POST["authorised_by_date"]);

// Handle multiple item rows
$quantities = $_POST['quantity'] ?? [];
$issued_qtys = $_POST['issued_qty'] ?? [];
$descriptions = $_POST['description'] ?? [];
$request_dates = $_POST['request_date'] ?? [];
$received_bys = $_POST['received_by'] ?? [];
$remarks_array = $_POST['remarks'] ?? [];

// Start transaction for atomicity
$mysqli->begin_transaction();

try {
    // Insert into main requisitions table
    $sql_main = "INSERT INTO requisitions (office_name, section_unit, grf_number, requisition_date, requested_by, requested_by_signature, requested_by_date, authorised_by, authorised_by_signature, authorised_by_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_main = $mysqli->prepare($sql_main);
    if ($stmt_main === false) {
        throw new Exception("Error preparing main statement: " . $mysqli->error);
    }

    $stmt_main->bind_param("ssssssssss", $office_name, $section_unit, $grf_number, $requisition_date, $requested_by, $requested_by_signature, $requested_by_date, $authorised_by, $authorised_by_signature, $authorised_by_date);
    
    if (!$stmt_main->execute()) {
        throw new Exception("Error inserting main requisition: " . $stmt_main->error);
    }

    $requisition_id = $mysqli->insert_id;
    $stmt_main->close();

    // Insert into a separate table for requisition items
    $sql_items = "INSERT INTO requisition_items (requisition_id, quantity, issued_qty, description, request_date, received_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_items = $mysqli->prepare($sql_items);
    if ($stmt_items === false) {
        throw new Exception("Error preparing items statement: " . $mysqli->error);
    }

    for ($i = 0; $i < count($quantities); $i++) {
        // Trim data before binding
        $qty = trim($quantities[$i]);
        $iss_qty = trim($issued_qtys[$i]);
        $desc = trim($descriptions[$i]);
        $req_date = trim($request_dates[$i]);
        $rec_by = trim($received_bys[$i]);
        $rem = trim($remarks_array[$i]);

        // Bind parameters for each item
        $stmt_items->bind_param("iiissss", $requisition_id, $qty, $iss_qty, $desc, $req_date, $rec_by, $rem);
        if (!$stmt_items->execute()) {
            throw new Exception("Error inserting item " . ($i + 1) . ": " . $stmt_items->error);
        }
    }
    $stmt_items->close();

    // If all queries were successful, commit the transaction
    $mysqli->commit();
    redirect_with_status('success', 'Requisition submitted successfully!');

} catch (Exception $e) {
    // If any query fails, roll back the transaction
    $mysqli->rollback();
    // Log the detailed error for developers, but show a generic message to the user.
    error_log($e->getMessage());
    redirect_with_status('danger', 'Requisition submission failed due to a server error. Please try again.');
} finally {
    // Close connection
    $mysqli->close();
}