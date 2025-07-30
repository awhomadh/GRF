<?php
// Start session to handle status messages
session_start();

// Include database configuration
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
    // In a real application, log this error and show a user-friendly message
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
while ($item = $result_items->fetch_assoc()) {
    $items[] = $item;
}
$result_items->free();

/**
 * Helper function to display a signature.
 * It checks if the signature is a Base64 encoded image and displays it,
 * otherwise, it shows the text.
 *
 * @param string $signature_data The signature data from the database.
 * @return string HTML for the signature.
 */
function display_signature($signature_data) {
    if (strpos($signature_data, 'data:image/png;base64,') === 0) {
        return '<img src="' . htmlspecialchars($signature_data) . '" alt="Signature" class="signature-img">';
    }
    // If it's not a data URI, just display it as text
    return '<span class="signature-text">' . htmlspecialchars($signature_data) . '</span>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Requisition - <?php echo htmlspecialchars($requisition['grf_number']); ?></title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Faruma';
            src:url('fonts/Faruma.ttf') format('truetype');
        }
        .dhivehi {
                font-family: 'Faruma', Arial, sans-serif;
                font-size: 18px;
                text-align: right;
                margin-top: 10px;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
            max-width: 1140px;
        }
        .web-view .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1rem 1.5rem;
        }
        .card-body {
            padding: 2rem;
        }
        .info-section {
            margin-bottom: 2rem;
        }
        .info-section h5 {
            color: #007bff;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #212529;
        }
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .signature-block {
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 8px;
            background-color: #fdfdfd;
            height: 100%;
        }
        .signature-img {
            max-width: 200px;
            height: auto;
            border-bottom: 1px solid #ccc;
        }
        .signature-text {
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.1rem;
        }
        .print-view {
            display: none;
        }
        @media print {
            body {
                background-color: #fff;
                font-family: "Faruma", Arial, sans-serif;
                font-size: 16px;
                margin: 0;
            }
            .web-view {
                display: none;
            }
            .print-view {
                display: block;
                width: 210mm;
                height: 297mm;
                margin-left: auto;
                margin-right: auto;
            }
            .print-view .card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
            .no-print {
                display: none;
            }
            .print-view .center { text-align: center; }
            .print-view .right { text-align: right; }
            .print-view table {
                border-collapse: collapse;
                width: 100%;
                margin-top: 10px;
                font-size: 14px;
            }
            .print-view td, .print-view th {
                border: 1px solid #000;
                padding: 8px;
                vertical-align: top;
            }
            .print-view .no-border { border: none; }
            .print-view .header {
                text-align: center;
                margin-bottom: 20px;
            }
            .print-view .signature-section td { height: 40px; }
            .print-view p {
                text-align: center;
                font-size: 18px;
                margin-bottom: 0;
            }
            .print-view .dhivehi {
                font-family: 'Faruma', Arial, sans-serif;
                font-size: 18px;
                text-align: right;
                margin-top: 10px;
            }
            .print-view #address {
                text-align: right;
                font-size: 15px;
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container web-view">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Requisition Details</h4>
                <div class="no-print">
                    <a href="dashboard.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
                    <a href="edit_requisition.php?id=<?php echo $requisition_id; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square me-1"></i> Edit</a>
                    <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="bi bi-printer me-1"></i> Print</button>
                </div>
            </div>
            <div class="card-body">
                <!-- General Information -->
                <div class="info-section">
                    <h5>General Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <span class="info-label">GRF Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($requisition['grf_number']); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <span class="info-label">Requisition Date:</span>
                            <span class="info-value"><?php echo date("F j, Y", strtotime($requisition['requisition_date'])); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <span class="info-label">Office Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($requisition['office_name']); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <span class="info-label">Section/Unit:</span>
                            <span class="info-value"><?php echo htmlspecialchars($requisition['section_unit']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Item Details -->
                <div class="info-section">
                    <h5>Item Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Description</th>
                                    <th scope="col" class="text-center">Quantity</th>
                                    <th scope="col" class="text-center">Issued Qty</th>
                                    <th scope="col">Date Required</th>
                                    <th scope="col">Received By</th>
                                    <th scope="col">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($items) > 0): ?>
                                    <?php $item_count = 1; ?>
                                    <?php foreach($items as $item): ?>
                                        <tr>
                                            <td><?php echo $item_count++; ?></td>
                                            <td class="dhivehi"><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($item['issued_qty']); ?></td>
                                            <td><?php echo $item['request_date'] ? date("F j, Y", strtotime($item['request_date'])) : 'N/A'; ?></td>
                                            <td><?php echo $item['received_by'] ? htmlspecialchars($item['received_by']) : 'N/A'; ?></td>
                                            <td class="dhivehi"><?php echo $item['remarks'] ? nl2br(htmlspecialchars($item['remarks'])) : 'N/A'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No items found for this requisition.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Authorization Details -->
                <div class="info-section">
                    <h5>Authorization</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="signature-block">
                                <h6 class="mb-3">Requested By</h6>
                                <p class="mb-2"><span class="info-label">Name:</span> <?php echo htmlspecialchars($requisition['requested_by']); ?></p>
                                <p class="mb-2"><span class="info-label">Date:</span> <?php echo date("F j, Y", strtotime($requisition['requested_by_date'])); ?></p>
                                <div class="mt-3">
                                    <span class="info-label d-block mb-1">Signature:</span>
                                    <?php echo display_signature($requisition['requested_by_signature']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="signature-block">
                                <h6 class="mb-3">Authorised By</h6>
                                <p class="mb-2"><span class="info-label">Name:</span> <?php echo htmlspecialchars($requisition['authorised_by']); ?></p>
                                <p class="mb-2"><span class="info-label">Date:</span> <?php echo date("F j, Y", strtotime($requisition['authorised_by_date'])); ?></p>
                                <div class="mt-3">
                                    <span class="info-label d-block mb-1">Signature:</span>
                                    <?php echo display_signature($requisition['authorised_by_signature']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print-only View -->
    <div class="print-view">
        <p id="bismi"><br>
            <img src="images/emblem.png" alt="emblem" style="width: 45px; height: auto;">
        </p>
        <div id="address">
            ށ.އަތޮޅު ހޮސްޕިޓަލް<br>ފުނަދޫ، ދިވެހިރާއްޖެ
        </div>
        <table>
            <tr>
                <td class="no-border" colspan="3">
                    <strong>Office:</strong> <?php echo htmlspecialchars($requisition['office_name']); ?><br>
                    <strong>Section:</strong> <?php echo htmlspecialchars($requisition['section_unit']); ?><br>
                    <strong>Number:</strong> <?php echo htmlspecialchars($requisition['grf_number']); ?><br>
                    <strong>Date:</strong> <?php echo date("d/m/Y", strtotime($requisition['requisition_date'])); ?>
                </td>
            </tr>
        </table>

        <div class="header">
            <div style="font-size: 20px;">ޚިދުމަތާއި މުދާ ލިބިގަތުމަށް އެދޭފޯމް </div>
            <div><strong>REQUISITION FORM FOR GOODS/ SERVICES</strong></div>
        </div>

        <table>
            <thead>
                <tr class="center">
                    <th>އިތުރުބަޔާން</th>
                    <th>މުދަލާ ހަވާލުވިފަރާތް</th>
                    <th>ލިބެންވީ ތާރީޚް</th>
                    <th>ތަފްޞީލް</th>
                    <th colspan="2">
                        <span>ޢަދަދު</span>
                        <span>/ Quantity</span>
                    </th>
                </tr>
                <tr class="center">
                    <th>Remarks</th>
                    <th>Received by</th>
                    <th>Rqt. Date</th>
                    <th>Description of Goods / Services</th>
                    <th>
                        <span>ދޫކުރި /</span><br>
                        <span>Issued</span>
                    </th>
                    <th>
                        <span>އެދުނު /</span><br>
                        <span>Requested</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach($items as $item): ?>
                        <tr class="center">
                            <td data-label="Remarks"><?php echo htmlspecialchars($item['remarks']); ?></td>
                            <td data-label="Goods Recieved by"><?php echo htmlspecialchars($item['received_by']); ?></td>
                            <td data-label="Rqt.Date"><?php echo $item['request_date'] ? date("d/m/Y", strtotime($item['request_date'])) : ''; ?></td>
                            <td data-label="Description of Goods / Services"><?php echo htmlspecialchars($item['description']); ?></td>
                            <td data-label="Issued"></td>
                            <td data-label="Requested"><?php echo htmlspecialchars($item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="center"><td colspan="6">No items for this requisition.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <table class="signature-section">
            <thead>
                <tr class="center">
                    <th> ތާރީޚް <br> Date</th>
                    <th>ސޮއި <br> Signature</th>
                    <th>ނަން <br> Name</th>
                    <th>ކުރަންވީ ކަންތައް <br> Functions</th>
                </tr>
            </thead>
            <tbody>
                <tr class="center">
                    <td><?php echo date("d/m/Y", strtotime($requisition['requested_by_date'])); ?></td>
                    <td></td>
                    <td><?php echo htmlspecialchars($requisition['requested_by']); ?></td>
                    <td> / އެދުނު<br>Requested by</td>
                </tr>
                <tr class="center">
                    <td><?php echo date("d/m/Y", strtotime($requisition['authorised_by_date'])); ?></td>
                    <td></td>
                    <td><?php echo htmlspecialchars($requisition['authorised_by']); ?></td>
                    <td>/ ރިކުއެސްޓަށް ހުއްދަދެއްވި <br> Request Authorized by</td>
                </tr>
                <tr class="center">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>/ ދޫކުރި <br> Issued by</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
// Close the database connection
$mysqli->close();
?>
