<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods and Services Requisition Form</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (optional, for the icons used in headers) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
                @font-face {
            font-family: 'Faruma';
            src:url('fonts/Faruma.ttf') format('truetype');
        }
        .dhivehi {
                font-family: 'Faruma', Arial, sans-serif;
                font-size: 18px;
                /**text-align: right;**/
                margin-top: 10px;
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
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        textarea.form-control {
            min-height: 80px; /* Slightly larger height for textareas */
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 500;
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.25);
        }
        .table th, .table td {
            vertical-align: middle;
        }
        /* Classes to replace inline styles */
        .th-qty, .th-issued-qty { width: 10%; }
        .th-desc { width: 30%; }
        .th-req-date, .th-received, .th-remarks { width: 15%; }
        .th-action { width: 5%; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4 text-primary">Goods and Services Requisition Form</h2>
        <p class="text-center text-muted mb-4">Please fill out the form accurately for your requisition.</p>

        <?php
        // Get current date to pre-fill date fields
        $current_date = date('Y-m-d');
        ?>

        <?php
        // Start session to receive status messages from the processing script.
        session_start();

        // Display status message if it exists
        if (isset($_SESSION['status_message'])) {
            $alert_type = $_SESSION['status_type'] === 'success' ? 'alert-success' : 'alert-danger';
            echo "<div class='alert {$alert_type} alert-dismissible fade show' role='alert'>";
            echo htmlspecialchars($_SESSION['status_message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo "</div>";

            // Unset session variables so the message doesn't reappear on refresh
            unset($_SESSION['status_message']);
            unset($_SESSION['status_type']);
        }
        ?>

        <form action="process_requisition.php" method="post">
            <!-- Section 1: Basic Information -->
            <div class="form-section">
                <h5><i class="bi bi-info-circle me-2"></i>General Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="officeName" class="form-label">Office Name</label>
                        <input type="text" class="form-control" id="officeName" name="office_name" placeholder="Enter office name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="sectionUnit" class="form-label">Section/Unit</label>
                        <select class="form-select" id="sectionUnit" name="section_unit" required>
                            <option selected disabled value="">Choose...</option>
                            <option value="Administration">Administration</option>
                            <option value="Finance">Finance</option>
                            <option value="Human Resources">Human Resources</option>
                            <option value="IT Department">IT Department</option>
                            <option value="Procurement">Procurement</option>
                            <option value="Support Service Unit">Support Service Unit</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="grfNumber" class="form-label">GRF Number</label>
                        <input type="text" class="form-control" id="grfNumber" name="grf_number" placeholder="e.g., (FRM)SH-FAH-A/2025/001" required>
                    </div>
                    <div class="col-md-6">
                        <label for="requisitionDate" class="form-label">Requisition Date</label>
                        <input type="date" class="form-control" id="requisitionDate" name="requisition_date" value="<?php echo $current_date; ?>" required>
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
                                <th scope="col" class="th-qty">Quantity</th>
                                <th scope="col" class="th-issued-qty">Issued Qty</th>
                                <th scope="col" class="th-desc">Description</th>
                                <th scope="col" class="th-req-date">Request Date</th>
                                <th scope="col" class="th-received">Received By</th>
                                <th scope="col" class="th-remarks">Remarks</th>
                                <th scope="col" class="th-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="item-row">
                                <td><input type="number" class="form-control" name="quantity[]" min="1" value="1" required></td>
                                <td><input type="number" class="form-control" name="issued_qty[]" min="0" value="0" required></td>
                                <td><textarea class="form-control" name="description[]" rows="2" placeholder="Detailed description of goods/services" required></textarea></td>
                                <td><input type="date" class="form-control" name="request_date[]" required></td>
                                <td><input type="text" class="form-control" name="received_by[]" placeholder="Name of recipient"></td>
                                <td><textarea class="form-control" name="remarks[]" rows="3" placeholder="Any additional remarks"></textarea></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-item-row" title="Remove Item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-success" id="addItemRowBtn">
                        <i class="bi bi-plus-circle me-2"></i>Add Item
                    </button>
                </div>
            </div>

            <!-- Template for new item rows -->
            <template id="itemRowTemplate">
                <tr class="item-row">
                    <td><input type="number" class="form-control" name="quantity[]" min="1" value="1" required></td>
                    <td><input type="number" class="form-control" name="issued_qty[]" min="0" value="0" required></td>
                    <td><textarea class="form-control" name="description[]" rows="2" placeholder="Detailed description of goods/services" required></textarea></td>
                    <td><input type="date" class="form-control" name="request_date[]" required></td>
                    <td><input type="text" class="form-control" name="received_by[]" placeholder="Name of recipient"></td>
                    <td><textarea class="form-control" name="remarks[]" rows="3" placeholder="Any additional remarks"></textarea></td>
                    <td class="text-center">
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-danger btn-sm remove-item-row" title="Remove Item">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>

            <!-- Section 3: Requestor and Authorizer Information -->
            <div class="form-section">
                <h5><i class="bi bi-person-fill me-2"></i>Requestor and Authorizer Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="requestedBy" class="form-label">Requested By</label>
                        <select class="form-select" id="requestedBy" name="requested_by" required>
                            <option selected disabled value="">Choose...</option>
                            <option class="dhivehi" value="މުޙައްމަދު ކާމިލް / އ.މެއިންޓެނަންސް އޮފިސަރ">މުޙައްމަދު ކާމިލް / އ.މެއިންޓެނަންސް އޮފިސަރ</option>
                            <option class="dhivehi" value="މުޙައްމަދު ޝަހިމް / ކޮމްޕިއުޓަރ ޓެކްނިޝަން">މުޙައްމަދު ޝަހިމް / ކޮމްޕިއުޓަރ ޓެކްނިޝަން</option>
                            <option class="dhivehi" value="އަޙްމަދު ޢިރުފާން / އ. އެލެކްޓްރީޝަން">އަޙްމަދު ޢިރުފާން / އ. އެލެކްޓްރީޝަން</option>
                            <option class="dhivehi" value="އިބްރާހީމް އީސާ / މެކޭނިކް"> އިބްރާހީމް އީސާ / މެކޭނިކް</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="requestedBySignature" class="form-label">Signature</label>
                        <input type="text" class="form-control" id="requestedBySignature" name="requested_by_signature" placeholder="Signature/Name" required>
                    </div>
                    <div class="col-md-4">
                        <label for="requestedByDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="requestedByDate" name="requested_by_date" value="<?php echo $current_date; ?>" required>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="authorisedBy" class="form-label">Authorised By</label>
                        <select class="form-select" id="authorisedBy" name="authorised_by" required>
                            <option selected disabled value="">Choose...</option>
                            <option class="dhivehi" value="ސަނިއްޔާ މުޙައްމަދު / ޑިރެކްޓަރ">ސަނިއްޔާ މުޙައްމަދު / ޑިރެކްޓަރ</option>
                            <option class="dhivehi" value="ޙަސަން އިސްމާޢީލް / އ.ޑިރެކްޓަރ">ޙަސަން އިސްމާޢީލް / އ.ޑިރެކްޓަރ</option>
                            <option class="dhivehi" value="އިބްރާހީމް ނާޒިމް / އ.ޑިރެކްޓަރ">އިބްރާހީމް ނާޒިމް / އ.ޑިރެކްޓަރ</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="authorisedBySignature" class="form-label">Signature</label>
                        <input type="text" class="form-control" id="authorisedBySignature" name="authorised_by_signature" placeholder="Signature/Name" required>
                    </div>
                    <div class="col-md-4">
                        <label for="authorisedByDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="authorisedByDate" name="authorised_by_date" value="<?php echo $current_date; ?>" required>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="dashboard.php" class="btn btn-secondary btn-lg">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">Submit Requisition</button>
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
                // Clone the content from the template
                const newRow = itemRowTemplate.content.cloneNode(true);
                tableBody.appendChild(newRow);
            }

            // Add event listener to the "Add Item" button
            addItemRowBtn.addEventListener('click', addNewItemRow);

            // Use event delegation to handle remove button clicks for all rows
            tableBody.addEventListener('click', function(event) {
                // Check if a remove button or its icon was clicked
                if (event.target.closest('.remove-item-row')) {
                    // Prevent removing the last row
                    if (tableBody.querySelectorAll('.item-row').length > 1) {
                        event.target.closest('.item-row').remove();
                    } else {
                        // Optionally, provide feedback if they try to remove the last row
                        alert("Cannot remove the last item row.");
                    }
                }
            });
        });
    </script>
</body>
</html>
