<?php
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
        $stmt = $pdo->prepare("DELETE FROM gsm_master WHERE id = ?");
        $stmt->execute([$id]);
        $success = "GSM deleted successfully!";
    } catch(Exception $e) {
        $error = "Cannot delete: " . $e->getMessage();
    }
}

// Handle Edit
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM gsm_master WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $gsm_value = $_POST['gsm_value'];
    $gsm_type = $_POST['gsm_type'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $id = $_POST['id'] ?? null;
    
    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE gsm_master SET gsm_value = ?, gsm_type = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$gsm_value, $gsm_type, $is_active, $id]);
            $success = "GSM updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO gsm_master (gsm_value, gsm_type, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$gsm_value, $gsm_type, $is_active]);
            $success = "GSM added successfully!";
        }
        header("Location: gsm.php");
        exit();
        } catch(Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$gsmList = $pdo->query("SELECT * FROM gsm_master ORDER BY gsm_value");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSM Management - <?php echo APP_NAME; ?></title>
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
            <h2>GSM Type Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#gsmModal">
                <i class="bi bi-plus-circle"></i> Add New GSM
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
                                <th>GSM Value</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($gsmList->rowCount() > 0): ?>
                                <?php while($row = $gsmList->fetch()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><strong><?php echo $row['gsm_value']; ?></strong></td>
                                        <td><?php echo $row['gsm_type']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#gsmModal">
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
                                    <td colspan="5" class="text-center">No GSM types found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal fade" id="gsmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_data ? 'Edit' : 'Add'; ?> GSM Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo getCsrfInput(); ?>
                    <div class="modal-body">
                        <?php if($edit_data): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label>GSM Value</label>
                            <input type="text" name="gsm_value" class="form-control" required 
                                   value="<?php echo $edit_data['gsm_value'] ?? ''; ?>" placeholder="e.g., 300ART, 250GSM">
                        </div>
                        <div class="mb-3">
                            <label>Type</label>
                            <select name="gsm_type" class="form-control">
                                <option value="art" <?php echo ($edit_data && $edit_data['gsm_type'] == 'art') ? 'selected' : ''; ?>>Art</option>
                                <option value="standard" <?php echo ($edit_data && $edit_data['gsm_type'] == 'standard') ? 'selected' : ''; ?>>Standard</option>
                                <option value="matte" <?php echo ($edit_data && $edit_data['gsm_type'] == 'matte') ? 'selected' : ''; ?>>Matte</option>
                                <option value="glossy" <?php echo ($edit_data && $edit_data['gsm_type'] == 'glossy') ? 'selected' : ''; ?>>Glossy</option>
                                <option value="other" <?php echo ($edit_data && $edit_data['gsm_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
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