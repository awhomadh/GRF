<?php
session_start();

require_once 'db_config.php';

/**
 * Redirects the user to a specified page with a status message.
 *
 * @param string $type The type of message ('success' or 'danger').
 * @param string $message The message to display.
 * @param int|null $id The ID of the requisition to redirect to.
 */
function redirect_with_status($type, $message, $id = null) {
    $_SESSION['status_type'] = $type;
    $_SESSION['status_message'] = $message;
    $location = $id ? "view_requisition.php?id={$id}" : "dashboard.php";
    header("Location: " . $location);
    exit();
}

// --- Validation ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_POST['requisition_id']) || empty($_POST['requisition_id'])) {
    redirect_with_status('danger', 'Invalid requisition ID.');
}

$requisition_id = (int)$_POST['requisition_id'];

// Basic Server-Side Validation for main fields
$required_fields = ["office_name", "section_unit", "grf_number", "requisition_date", "requested_by", "requested_by_signature", "requested_by_date", "authorised_by", "authorised_by_signature", "authorised_by_date"];
foreach ($required_fields as $field) {
    if (empty(trim($_POST[$field]))) {
        redirect_with_status('danger', "Validation failed: '{$field}' is a required field.", $requisition_id);
    }
}

// Validate that at least one item is submitted
if (empty($_POST['quantity']) || empty($_POST['description'])) {
    redirect_with_status('danger', 'You must have at least one item in the requisition.', $requisition_id);
}

// --- Get Form Data ---
$office_name = trim($_POST["office_name"]);
$section_unit = trim($_POST["section_unit"]);
$grf_number = trim($_POST["grf_number"]);
$requisition_date = trim($_POST["requisition_date"]);
$requested_by = trim($_POST["requested_by"]);
$requested_by_signature = trim($_POST["requested_by_signature"]);
$requested_by_date = trim($_POST["requested_by_date"]);
$authorised_by = trim($_POST["authorised_by"]);
$authorised_by_signature = trim($_POST["authorised_by_signature"]);
$authorised_by_date = trim($_POST["authorised_by_date"]);

// Item data arrays
$item_ids = $_POST['item_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$issued_qtys = $_POST['issued_qty'] ?? [];
$descriptions = $_POST['description'] ?? [];
$request_dates = $_POST['request_date'] ?? [];
$received_bys = $_POST['received_by'] ?? [];
$remarks_array = $_POST['remarks'] ?? [];

// --- Database Operations ---
$mysqli->begin_transaction();

try {
    // 1. Update the main requisition table
    $sql_main = "UPDATE requisitions SET office_name=?, section_unit=?, grf_number=?, requisition_date=?, requested_by=?, requested_by_signature=?, requested_by_date=?, authorised_by=?, authorised_by_signature=?, authorised_by_date=? WHERE id=?";
    $stmt_main = $mysqli->prepare($sql_main);
    if ($stmt_main === false) {
        throw new Exception("Error preparing main update statement: " . $mysqli->error);
    }
    $stmt_main->bind_param("ssssssssssi", $office_name, $section_unit, $grf_number, $requisition_date, $requested_by, $requested_by_signature, $requested_by_date, $authorised_by, $authorised_by_signature, $authorised_by_date, $requisition_id);
    if (!$stmt_main->execute()) {
        // Check for unique constraint violation on grf_number
        if ($mysqli->errno === 1062) {
            throw new Exception("The GRF Number '{$grf_number}' already exists.");
        }
        throw new Exception("Error updating main requisition: " . $stmt_main->error);
    }
    $stmt_main->close();

    // 2. Handle item deletions
    // Get all existing item IDs for this requisition
    $result_existing_ids = $mysqli->query("SELECT id FROM requisition_items WHERE requisition_id = {$requisition_id}");
    $existing_item_ids = [];
    while ($row = $result_existing_ids->fetch_assoc()) {
        $existing_item_ids[] = $row['id'];
    }
    
    // Get submitted item IDs (filter out empty ones for new rows)
    $submitted_item_ids = array_filter($item_ids);
    
    // Find which items to delete
    $items_to_delete = array_diff($existing_item_ids, $submitted_item_ids);
    if (!empty($items_to_delete)) {
        $delete_placeholders = implode(',', array_fill(0, count($items_to_delete), '?'));
        $stmt_delete = $mysqli->prepare("DELETE FROM requisition_items WHERE id IN ({$delete_placeholders})");
        $stmt_delete->bind_param(str_repeat('i', count($items_to_delete)), ...$items_to_delete);
        if (!$stmt_delete->execute()) {
            throw new Exception("Error deleting items: " . $stmt_delete->error);
        }
        $stmt_delete->close();
    }

    // 3. Update existing items and insert new ones
    $stmt_update_item = $mysqli->prepare("UPDATE requisition_items SET quantity=?, issued_qty=?, description=?, request_date=?, received_by=?, remarks=? WHERE id=?");
    $stmt_insert_item = $mysqli->prepare("INSERT INTO requisition_items (requisition_id, quantity, issued_qty, description, request_date, received_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt_update_item === false || $stmt_insert_item === false) {
        throw new Exception("Error preparing item statements: " . $mysqli->error);
    }

    for ($i = 0; $i < count($quantities); $i++) {
        $item_id = (int)$item_ids[$i];
        $qty = (int)$quantities[$i];
        $iss_qty = (int)$issued_qtys[$i];
        $desc = trim($descriptions[$i]);
        // Handle potentially empty optional fields
        $req_date = !empty(trim($request_dates[$i])) ? trim($request_dates[$i]) : null;
        $rec_by = !empty(trim($received_bys[$i])) ? trim($received_bys[$i]) : null;
        $rem = !empty(trim($remarks_array[$i])) ? trim($remarks_array[$i]) : null;

        if ($item_id > 0) { // Existing item -> UPDATE
            $stmt_update_item->bind_param("iissssi", $qty, $iss_qty, $desc, $req_date, $rec_by, $rem, $item_id);
            if (!$stmt_update_item->execute()) {
                throw new Exception("Error updating item ID {$item_id}: " . $stmt_update_item->error);
            }
        } else { // New item -> INSERT
            $stmt_insert_item->bind_param("iiissss", $requisition_id, $qty, $iss_qty, $desc, $req_date, $rec_by, $rem);
            if (!$stmt_insert_item->execute()) {
                throw new Exception("Error inserting new item: " . $stmt_insert_item->error);
            }
        }
    }

    $stmt_update_item->close();
    $stmt_insert_item->close();

    // If all queries were successful, commit the transaction
    $mysqli->commit();
    redirect_with_status('success', 'Requisition updated successfully!', $requisition_id);

} catch (Exception $e) {
    // If any query fails, roll back the transaction
    $mysqli->rollback();
    
    // Log the detailed error for developers, but show a user-friendly message.
    error_log("Requisition update failed: " . $e->getMessage());
    redirect_with_status('danger', 'Update failed: ' . $e->getMessage(), $requisition_id);

} finally {
    // Close connection
    $mysqli->close();
}

