<?php
// Start session to handle status messages
session_start();

// Include database configuration
require_once 'db_config.php';

// --- Pagination Configuration ---
$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// --- Build Query with Filters ---
$sql_base = "FROM requisitions";
$where_clauses = [];
$params = [];
$types = '';
$query_params = []; // For building URL query strings

// Search Term Filter (GRF Number, Office Name, Requested By)
if (!empty($_GET['search_term'])) {
    $search_term_get = trim($_GET['search_term']);
    $query_params['search_term'] = $search_term_get;
    $search_term_sql = '%' . $search_term_get . '%';
    $where_clauses[] = "(grf_number LIKE ? OR office_name LIKE ? OR requested_by LIKE ?)";
    $params[] = $search_term_sql;
    $params[] = $search_term_sql;
    $params[] = $search_term_sql;
    $types .= 'sss';
}

// Date Range Filter
if (!empty($_GET['start_date'])) {
    $query_params['start_date'] = trim($_GET['start_date']);
    $where_clauses[] = "requisition_date >= ?";
    $params[] = $query_params['start_date'];
    $types .= 's';
}
if (!empty($_GET['end_date'])) {
    $query_params['end_date'] = trim($_GET['end_date']);
    $where_clauses[] = "requisition_date <= ?";
    $params[] = $query_params['end_date'];
    $types .= 's';
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(' AND ', $where_clauses);
}

// --- Get Total Record Count for Pagination ---
$sql_count = "SELECT COUNT(*) as total " . $sql_base . $where_sql;
$stmt_count = $mysqli->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_records / $records_per_page);

// --- Fetch Paginated Requisitions ---
$sql = "SELECT id, grf_number, office_name, requisition_date, requested_by " . $sql_base . $where_sql;
// Order by most recent date, then by ID for consistent sorting
$sql .= " ORDER BY requisition_date DESC, id DESC LIMIT ? OFFSET ?";

// Add LIMIT and OFFSET to params
$params[] = $records_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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
                <div>
                    <a href="export_csv.php?<?php echo http_build_query($query_params); ?>" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export to CSV
                    </a>
                    <a href="create_grf.php" class="btn btn-light">
                        <i class="bi bi-plus-circle me-2"></i>Create New Requisition
                    </a>
                </div>
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

                <!-- Search and Filter Form -->
                <div class="mb-4 p-3 bg-light border rounded">
                    <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Requisitions</h5>
                    <form action="dashboard.php" method="get" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search_term" class="form-label">Search Term</label>
                            <input type="text" class="form-control form-control-sm" id="search_term" name="search_term" placeholder="GRF No, Office, Requestor..." value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">From Date</label>
                            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">To Date</label>
                            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                            <a href="dashboard.php" class="btn btn-secondary btn-sm w-100 mt-2">Reset</a>
                        </div>
                    </form>
                </div>

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

            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-secondary small">
                        <?php
                        $start_record = $offset + 1;
                        $end_record = $offset + $result->num_rows;
                        ?>
                        Showing <?php echo $start_record; ?> to <?php echo $end_record; ?> of <?php echo $total_records; ?> records
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination mb-0">
                            <!-- Previous Page Link -->
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $current_page - 1])); ?>">Previous</a>
                            </li>

                            <?php
                            $page_window = 2; // Number of pages to show around the current page
                            $start_page = max(1, $current_page - $page_window);
                            $end_page = min($total_pages, $current_page + $page_window);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($query_params, ['page' => 1])) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor;

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($query_params, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <!-- Next Page Link -->
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $current_page + 1])); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
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
$stmt->close();
$mysqli->close();
?>