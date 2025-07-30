<?php
// Include database configuration
require_once 'db_config.php';

// --- Build Query with Filters (similar to dashboard.php, but without pagination) ---
// We join both tables to get a comprehensive export. Each row in the CSV will be an item.
$sql = "SELECT 
            r.id, 
            r.grf_number, 
            r.office_name, 
            r.section_unit, 
            r.requisition_date, 
            r.requested_by, 
            r.requested_by_date, 
            r.authorised_by, 
            r.authorised_by_date, 
            ri.quantity, 
            ri.issued_qty, 
            ri.description, 
            ri.request_date, 
            ri.received_by, 
            ri.remarks 
        FROM requisitions r
        LEFT JOIN requisition_items ri ON r.id = ri.requisition_id";

$where_clauses = [];
$params = [];
$types = '';

// Search Term Filter (checks against main requisition fields)
if (!empty($_GET['search_term'])) {
    $search_term_sql = '%' . trim($_GET['search_term']) . '%';
    $where_clauses[] = "(r.grf_number LIKE ? OR r.office_name LIKE ? OR r.requested_by LIKE ?)";
    $params[] = $search_term_sql;
    $params[] = $search_term_sql;
    $params[] = $search_term_sql;
    $types .= 'sss';
}

// Date Range Filter
if (!empty($_GET['start_date'])) {
    $where_clauses[] = "r.requisition_date >= ?";
    $params[] = trim($_GET['start_date']);
    $types .= 's';
}
if (!empty($_GET['end_date'])) {
    $where_clauses[] = "r.requisition_date <= ?";
    $params[] = trim($_GET['end_date']);
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Order by main requisition date, then by item ID for a structured export
$sql .= " ORDER BY r.requisition_date DESC, r.id DESC, ri.id ASC";

$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    // In a production environment, log this error.
    die("Error preparing statement: " . $mysqli->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- Generate and Output CSV ---
$filename = "requisitions_export_" . date('Y-m-d') . ".csv";

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the CSV column headings
fputcsv($output, [
    'Requisition ID',
    'GRF Number',
    'Office Name',
    'Section/Unit',
    'Requisition Date',
    'Requested By',
    'Requested By Date',
    'Authorised By',
    'Authorised By Date',
    'Item Quantity',
    'Item Issued Qty',
    'Item Description',
    'Item Request Date',
    'Item Received By',
    'Item Remarks'
]);

// Loop through the rows and output them to the CSV file
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

// Close resources and exit
$stmt->close();
$mysqli->close();
fclose($output);
exit();