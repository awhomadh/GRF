<?php
// Start session to handle status messages
session_start();

// Include database configuration
require_once 'db_config.php';

// Fetch all requisitions from the database
// Order by most recent date, then by ID for consistent sorting
$result = $mysqli->query("SELECT id, grf_number, office_name, requisition_date, requested_by FROM requisitions ORDER BY requisition_date DESC, id DESC");

// Check for query errors
if (!$result) {
    // In a real application, log this error and show a user-friendly message
    die("Error fetching requisitions: " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisitions List</title>
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
        }
        .container {
            margin-top: 30px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .table th {
            font-weight: 600;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .action-icons a {
            margin: 0 5px;
            font-size: 1.2rem;
            text-decoration: none;
        }
        .action-icons .bi-eye { color: #0d6efd; }
        .action-icons .bi-pencil-square { color: #198754; }
        .action-icons .bi-trash { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Requisitions</h4>
                <a href="create_grf.php" class="btn btn-light">
                    <i class="bi bi-plus-circle me-2"></i>Create New Requisition
                </a>
            </div>
            <div class="card-body">
                <?php
                // Display status message if it exists
                if (isset($_SESSION['status_message'])) {
                    $alert_type = $_SESSION['status_type'] === 'success' ? 'alert-success' : 'alert-danger';
                    echo "<div class='alert {$alert_type} alert-dismissible fade show' role='alert'>";
                    echo htmlspecialchars($_SESSION['status_message']);
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo "</div>";

                    // Unset session variables
                    unset($_SESSION['status_message']);
                    unset($_SESSION['status_type']);
                }
                ?>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th scope="col">GRF Number</th>
                                <th scope="col">Office Name</th>
                                <th scope="col">Requisition Date</th>
                                <th scope="col">Requested By</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['grf_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['office_name']); ?></td>
                                        <td><?php echo date("F j, Y", strtotime($row['requisition_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['requested_by']); ?></td>
                                        <td class="text-center action-icons">
                                            <a href="view_requisition.php?id=<?php echo $row['id']; ?>" title="View Details"><i class="bi bi-eye"></i></a>
                                            <a href="edit_requisition.php?id=<?php echo $row['id']; ?>" title="Edit Requisition"><i class="bi bi-pencil-square"></i></a>
                                            <a href="delete_requisition.php?id=<?php echo $row['id']; ?>" title="Delete Requisition" onclick="return confirm('Are you sure you want to delete this requisition? This action cannot be undone.');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No requisitions found. You can create one using the button above.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close the database connection
$result->free();
$mysqli->close();
?>