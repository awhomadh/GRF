<?php
session_start();

require_once 'db_config.php';

// Check if an ID is provided in the URL, otherwise redirect to the dashboard
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$requisition_id = (int)$_GET['id'];

// --- Fetch Main Requisition Data ---
$stmt_main = $mysqli->prepare("SELECT * FROM requisitions WHERE id = ?");
if ($stmt_main === false) {
    die("Error preparing main statement: " . $mysqli->error);
}
$stmt_main->bind_param("i", $requisition_id);
$stmt_main->execute();
$result_main = $stmt_main->get_result();
$requisition = $result_main->fetch_assoc();
$stmt_main->close();

// If no requisition is found, redirect to the dashboard with an error message
if (!$requisition) {
    $_SESSION['status_type'] = 'danger';
    $_SESSION['status_message'] = 'Requisition not found.';
    header("Location: dashboard.php");
    exit();
}

// --- Fetch Requisition Items ---
$stmt_items = $mysqli->prepare("SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY id ASC");
if ($stmt_items === false) {
    die("Error preparing items statement: " . $mysqli->error);
}
$stmt_items->bind_param("i", $requisition_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$items = [];
while ($row = $result_items->fetch_assoc()) {
    $items[] = $row;
}
$stmt_items->close();
$mysqli->close();

/**
 * Helper function to check if an option in a <select> should be marked as selected.
 * @param string $value The current option's value.
 * @param string $selected_value The value from the database.
 * @return string 'selected' if they match, otherwise an empty string.
 */
function is_selected($value, $selected_value) {
    return $value === $selected_value ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Requisition - <?php echo htmlspecialchars($requisition['grf_number']); ?></title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-section {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            background-color: #fdfdfd;
        }
        .form-section h5 {
            margin-bottom: 20px;
            color: #007bff;
            font-weight: 600;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4 text-primary">Edit Goods and Services Requisition</h2>

        <?php
        // Display status message if it exists
        if (isset($_SESSION['status_message'])) {
            $alert_type = $_SESSION['status_type'] === 'success' ? 'alert-success' : 'alert-danger';
            echo "<div class='alert {$alert_type} alert-dismissible fade show' role='alert'>";
            echo htmlspecialchars($_SESSION['status_message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo "</div>";
            unset($_SESSION['status_message']);
            unset($_SESSION['status_type']);
        }
        ?>

        <form action="update_requisition.php" method="post">
            <input type="hidden" name="requisition_id" value="<?php echo $requisition_id; ?>">

            <!-- Section 1: Basic Information -->
            <div class="form-section">
                <h5><i class="bi bi-info-circle me-2"></i>General Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="officeName" class="form-label">Office Name</label>
                        <input type="text" class="form-control" id="officeName" name="office_name" value="<?php echo htmlspecialchars($requisition['office_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="sectionUnit" class="form-label">Section/Unit</label>
                        <select class="form-select" id="sectionUnit" name="section_unit" required>
                            <option disabled value="">Choose...</option>
                            <option value="Administration" <?php echo is_selected('Administration', $requisition['section_unit']); ?>>Administration</option>
                            <option value="Finance" <?php echo is_selected('Finance', $requisition['section_unit']); ?>>Finance</option>
                            <option value="Human Resources" <?php echo is_selected('Human Resources', $requisition['section_unit']); ?>>Human Resources</option>
                            <option value="IT Department" <?php echo is_selected('IT Department', $requisition['section_unit']); ?>>IT Department</option>
                            <option value="Procurement" <?php echo is_selected('Procurement', $requisition['section_unit']); ?>>Procurement</option>
                            <option value="Support Service Unit" <?php echo is_selected('Support Service Unit', $requisition['section_unit']); ?>>Support Service Unit</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="grfNumber" class="form-label">GRF Number</label>
                        <input type="text" class="form-control" id="grfNumber" name="grf_number" value="<?php echo htmlspecialchars($requisition['grf_number']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="requisitionDate" class="form-label">Requisition Date</label>
                        <input type="date" class="form-control" id="requisitionDate" name="requisition_date" value="<?php echo htmlspecialchars($requisition['requisition_date']); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Section 2: Item Details -->
            <div class="form-section">
                <h5><i class="bi bi-list-check me-2"></i>Item Details</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle" id="itemDetailsTable">
                        <thead>
                            <tr>
                                <th scope="col">Quantity</th>
                                <th scope="col">Issued Qty</th>
                                <th scope="col">Description</th>
                                <th scope="col">Request Date</th>
                                <th scope="col">Received By</th>
                                <th scope="col">Remarks</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr class="item-row">
                                <td>
                                    <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                    <input type="number" class="form-control" name="quantity[]" min="1" value="<?php echo htmlspecialchars($item['quantity']); ?>" required>
                                </td>
                                <td><input type="number" class="form-control" name="issued_qty[]" min="0" value="<?php echo htmlspecialchars($item['issued_qty']); ?>" required></td>
                                <td><textarea class="form-control" name="description[]" rows="2" required><?php echo htmlspecialchars($item['description']); ?></textarea></td>
                                <td><input type="date" class="form-control" name="request_date[]" value="<?php echo htmlspecialchars($item['request_date']); ?>"></td>
                                <td><input type="text" class="form-control" name="received_by[]" value="<?php echo htmlspecialchars($item['received_by']); ?>"></td>
                                <td><textarea class="form-control" name="remarks[]" rows="3"><?php echo htmlspecialchars($item['remarks']); ?></textarea></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-item-row" title="Remove Item"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-success" id="addItemRowBtn"><i class="bi bi-plus-circle me-2"></i>Add Item</button>
                </div>
            </div>

            <!-- Template for new item rows -->
            <template id="itemRowTemplate">
                <tr class="item-row">
                    <td>
                        <input type="hidden" name="item_id[]" value="">
                        <input type="number" class="form-control" name="quantity[]" min="1" value="1" required>
                    </td>
                    <td><input type="number" class="form-control" name="issued_qty[]" min="0" value="0" required></td>
                    <td><textarea class="form-control" name="description[]" rows="2" placeholder="Detailed description of goods/services" required></textarea></td>
                    <td><input type="date" class="form-control" name="request_date[]"></td>
                    <td><input type="text" class="form-control" name="received_by[]" placeholder="Name of recipient"></td>
                    <td><textarea class="form-control" name="remarks[]" rows="3" placeholder="Any additional remarks"></textarea></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-item-row" title="Remove Item"><i class="bi bi-trash"></i></button></td>
                </tr>
            </template>

            <!-- Section 3: Requestor and Authorizer Information -->
            <div class="form-section">
                <h5><i class="bi bi-person-fill me-2"></i>Requestor and Authorizer Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="requestedBy" class="form-label">Requested By</label>
                        <select class="form-select" id="requestedBy" name="requested_by" required>
                            <option disabled value="">Choose...</option>
                            <option value="John Doe" <?php echo is_selected('John Doe', $requisition['requested_by']); ?>>John Doe (Admin)</option>
                            <option value="Jane Smith" <?php echo is_selected('Jane Smith', $requisition['requested_by']); ?>>Jane Smith (Finance)</option>
                            <option value="Peter Jones" <?php echo is_selected('Peter Jones', $requisition['requested_by']); ?>>Peter Jones (IT)</option>
                            <option value="Alice Brown" <?php echo is_selected('Alice Brown', $requisition['requested_by']); ?>>Alice Brown (HR)</option>
                            <option value="Michael Green" <?php echo is_selected('Michael Green', $requisition['requested_by']); ?>>Michael Green (Operations)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="requestedBySignature" class="form-label">Signature</label>
                        <input type="text" class="form-control" id="requestedBySignature" name="requested_by_signature" value="<?php echo htmlspecialchars($requisition['requested_by_signature']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="requestedByDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="requestedByDate" name="requested_by_date" value="<?php echo htmlspecialchars($requisition['requested_by_date']); ?>" required>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="authorisedBy" class="form-label">Authorised By</label>
                        <select class="form-select" id="authorisedBy" name="authorised_by" required>
                            <option disabled value="">Choose...</option>
                            <option value="Director A" <?php echo is_selected('Director A', $requisition['authorised_by']); ?>>Director A</option>
                            <option value="Manager B" <?php echo is_selected('Manager B', $requisition['authorised_by']); ?>>Manager B</option>
                            <option value="Head of Department C" <?php echo is_selected('Head of Department C', $requisition['authorised_by']); ?>>Head of Department C</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="authorisedBySignature" class="form-label">Signature</label>
                        <input type="text" class="form-control" id="authorisedBySignature" name="authorised_by_signature" value="<?php echo htmlspecialchars($requisition['authorised_by_signature']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="authorisedByDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="authorisedByDate" name="authorised_by_date" value="<?php echo htmlspecialchars($requisition['authorised_by_date']); ?>" required>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="view_requisition.php?id=<?php echo $requisition_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Requisition</button>
            </div>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addItemRowBtn = document.getElementById('addItemRowBtn');
            const tableBody = document.querySelector('#itemDetailsTable tbody');
            const itemRowTemplate = document.getElementById('itemRowTemplate');

            // Function to add a new row
            function addNewItemRow() {
                const newRow = itemRowTemplate.content.cloneNode(true);
                tableBody.appendChild(newRow);
            }

            // Add event listener to the "Add Item" button
            addItemRowBtn.addEventListener('click', addNewItemRow);

            // Use event delegation to handle remove button clicks for all rows
            tableBody.addEventListener('click', function(event) {
                if (event.target && event.target.closest('.remove-item-row')) {
                    if (tableBody.querySelectorAll('.item-row').length > 1) {
                        event.target.closest('.item-row').remove();
                    } else {
                        alert("Cannot remove the last item row.");
                    }
                }
            });
        });
    </script>
</body>
</html>
