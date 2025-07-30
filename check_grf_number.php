<?php

require_once 'db_config.php';

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Ensure a GRF number is provided in the request
if (!isset($_GET['grf_number']) || empty(trim($_GET['grf_number']))) {
    // Silently exit if no number is provided; the client-side script handles empty input.
    echo json_encode(['exists' => false]);
    exit();
}

$grf_number = trim($_GET['grf_number']);

try {
    // Prepare a statement to check for the existence of the GRF number
    $stmt = $mysqli->prepare("SELECT id FROM requisitions WHERE grf_number = ?");
    if ($stmt === false) {
        throw new Exception('Statement preparation failed.');
    }

    $stmt->bind_param("s", $grf_number);
    $stmt->execute();
    $stmt->store_result();

    // Respond with whether the number exists
    echo json_encode(['exists' => $stmt->num_rows > 0]);

    $stmt->close();
    $mysqli->close();
} catch (Exception $e) {
    error_log('check_grf_number.php - Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['exists' => false, 'error' => 'A server error occurred.']);
}

