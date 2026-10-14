<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    redirect('../../login.php');
}

$error = '';
$success = '';
$edit_id = null;
$edit_data = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM parties WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Party deleted successfully!";
    } catch(Exception $e) {
        $error = "Cannot delete party: " . $e->getMessage();
    }
}

// Handle Edit - Get data
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM parties WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $party_name = trim($_POST['party_name'] ?? '');
        $party_type = $_POST['party_type'] ?? 'customer';
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $gst_no = trim($_POST['gst_no'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $id = $_POST['id'] ?? null;
        
        if (empty($party_name)) {
            $error = "Party Name is required.";
        } else {
            try {
                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE parties SET party_name = ?, party_type = ?, phone = ?, email = ?, address = ?, gst_no = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$party_name, $party_type, $phone, $email, $address, $gst_no, $is_active, $id]);
                    $success = "Party updated successfully!";
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO parties (party_name, party_type, phone, email, address, gst_no, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$party_name, $party_type, $phone, $email, $address, $gst_no, $is_active]);
                    $success = "Party added successfully!";
                }
                // Redirect to clear URL and form parameters
                header("Location: parties.php");
                exit();
            } catch(Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get all parties
$parties = $pdo->query("SELECT * FROM parties ORDER BY party_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parties Management - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center">
        <h2>Customer & Supplier (Parties) Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#partyModal">
            <i class="bi bi-plus-circle"></i> Add New Party
        </button>
    </div>
    <hr>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Contact Details</th>
                            <th>GST No</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($parties->rowCount() > 0): ?>
                            <?php while($row = $parties->fetch()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['party_name']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $row['party_type'] == 'customer' ? 'primary' : 
                                                ($row['party_type'] == 'supplier' ? 'secondary' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($row['party_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <strong>Phone:</strong> <?php echo htmlspecialchars($row['phone']); ?><br>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?><br>
                                            <strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['gst_no']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this party?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No parties found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="partyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_data ? 'Edit' : 'Add'; ?> Party</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="window.location.href='parties.php'"></button>
                </div>
                <form method="POST">
                    <?php echo getCsrfInput(); ?>
                    <div class="modal-body">
                        <?php if($edit_data): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Party Name <span class="text-danger">*</span></label>
                            <input type="text" name="party_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($edit_data['party_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Party Type</label>
                            <select name="party_type" class="form-select">
                                <option value="customer" <?php echo ($edit_data && $edit_data['party_type'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                <option value="supplier" <?php echo ($edit_data && $edit_data['party_type'] == 'supplier') ? 'selected' : ''; ?>>Supplier</option>
                                <option value="both" <?php echo ($edit_data && $edit_data['party_type'] == 'both') ? 'selected' : ''; ?>>Both</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_data['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_data['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($edit_data['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">GST No</label>
                            <input type="text" name="gst_no" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_data['gst_no'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                       <?php echo (!$edit_data || $edit_data['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.href='parties.php'">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo $edit_data ? 'Update' : 'Save'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
    
    <?php if($edit_data): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var myModal = new bootstrap.Modal(document.getElementById('partyModal'));
            myModal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>
