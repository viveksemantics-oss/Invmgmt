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
        $stmt = $pdo->prepare("DELETE FROM paper_sizes WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Paper size deleted successfully!";
    } catch(Exception $e) {
        $error = "Cannot delete: " . $e->getMessage();
    }
}

// Handle Edit - Get data
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM paper_sizes WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $size_name = $_POST['size_name'];
    $width_cm = $_POST['width_cm'];
    $height_cm = $_POST['height_cm'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $id = $_POST['id'] ?? null;
    
    try {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE paper_sizes SET size_name = ?, width_cm = ?, height_cm = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$size_name, $width_cm, $height_cm, $is_active, $id]);
            $success = "Paper size updated successfully!";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO paper_sizes (size_name, width_cm, height_cm, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$size_name, $width_cm, $height_cm, $is_active]);
            $success = "Paper size added successfully!";
        }
        // Redirect to clear form
        header("Location: sizes.php");
        exit();
        } catch(Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all paper sizes
$sizes = $pdo->query("SELECT * FROM paper_sizes ORDER BY size_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paper Sizes - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        <?php include_once __DIR__ . '/../../assets/css/style.css'; ?>
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/header.php'; ?>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="col-md-10 p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Paper Size Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sizeModal">
                <i class="bi bi-plus-circle"></i> Add New Size
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
                                <th>Size Name</th>
                                <th>Width (cm)</th>
                                <th>Height (cm)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($sizes->rowCount() > 0): ?>
                                <?php while($row = $sizes->fetch()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><strong><?php echo $row['size_name']; ?></strong></td>
                                        <td><?php echo $row['width_cm']; ?></td>
                                        <td><?php echo $row['height_cm']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#sizeModal">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No paper sizes found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="sizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_data ? 'Edit' : 'Add'; ?> Paper Size</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo getCsrfInput(); ?>
                    <div class="modal-body">
                        <?php if($edit_data): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label>Size Name</label>
                            <input type="text" name="size_name" class="form-control" required 
                                   value="<?php echo $edit_data['size_name'] ?? ''; ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Width (cm)</label>
                                    <input type="number" step="0.01" name="width_cm" class="form-control" 
                                           value="<?php echo $edit_data['width_cm'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Height (cm)</label>
                                    <input type="number" step="0.01" name="height_cm" class="form-control" 
                                           value="<?php echo $edit_data['height_cm'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                       <?php echo ($edit_data && $edit_data['is_active']) ? 'checked' : 'checked'; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo $edit_data ? 'Update' : 'Save'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>