<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'hungry_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $area_name = trim($_POST['area_name'] ?? '');

        if (empty($area_name)) {
            $_SESSION['message'] = 'Area name is required!';
            $_SESSION['message_type'] = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO areas (area_name) VALUES (?)");
                $stmt->execute([$area_name]);
                $_SESSION['message'] = "Area '{$area_name}' added successfully!";
                $_SESSION['message_type'] = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    $_SESSION['message'] = "Area '{$area_name}' already exists!";
                    $_SESSION['message_type'] = 'error';
                } else {
                    $_SESSION['message'] = 'Error adding area: ' . $e->getMessage();
                    $_SESSION['message_type'] = 'error';
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $area_name = trim($_POST['area_name'] ?? '');

        if (empty($area_name)) {
            $_SESSION['message'] = 'Area name is required!';
            $_SESSION['message_type'] = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE areas SET area_name = ? WHERE id = ?");
                $stmt->execute([$area_name, $id]);
                $_SESSION['message'] = "Area updated successfully!";
                $_SESSION['message_type'] = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    $_SESSION['message'] = "Area '{$area_name}' already exists!";
                    $_SESSION['message_type'] = 'error';
                } else {
                    $_SESSION['message'] = 'Error updating area: ' . $e->getMessage();
                    $_SESSION['message_type'] = 'error';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;

        try {
            // First, get the area name for the confirmation message
            $stmt = $pdo->prepare("SELECT area_name FROM areas WHERE id = ?");
            $stmt->execute([$id]);
            $area = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($area) {
                // Delete the area
                $stmt = $pdo->prepare("DELETE FROM areas WHERE id = ?");
                $stmt->execute([$id]);

                $_SESSION['message'] = "Area '{$area['area_name']}' deleted successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Area not found!';
                $_SESSION['message_type'] = 'error';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error deleting area: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }

    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get messages from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear messages from session after displaying
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Get areas from database
try {
    $stmt = $pdo->query("SELECT * FROM areas ORDER BY area_name ASC");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $areas = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Areas - HungryHub Admin</title>
    <link rel="stylesheet" href="../cssAdmin/manage-areas.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header">
        <h1>Manage Delivery Areas</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-food.php">Food</a>
            <a href="manage-orders.php">Orders</a>
            <a href="manage-users.php">Users</a>
            <a href="manage-areas.php" class="active">Areas</a>
            <a href="manage-coupons.php">Coupons</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Delivery Areas Management</h2>
            <div class="header-buttons">
                <button class="add-area-btn" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Add New Area
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Areas List -->
        <div class="areas-section">
            <?php if (empty($areas)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>No Areas Found</h3>
                    <p>Start by adding your first delivery area to manage your service coverage.</p>
                    <button class="btn btn-primary" onclick="openModal('add')">
                        <i class="fas fa-plus"></i> Add First Area
                    </button>
                </div>
            <?php else: ?>
                <div class="areas-grid">
                    <?php foreach ($areas as $area): ?>
                        <div class="area-card" data-id="<?php echo $area['id']; ?>">
                            <div class="area-header">
                                <div class="area-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="area-info">
                                    <h3 class="area-name"><?php echo htmlspecialchars($area['area_name']); ?></h3>
                                    <p class="area-id">ID: <?php echo $area['id']; ?></p>
                                </div>
                            </div>
                            <div class="area-actions">
                                <button class="btn btn-edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($area)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-delete" onclick="deleteArea(<?php echo $area['id']; ?>, '<?php echo htmlspecialchars($area['area_name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div id="areaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Area</h3>
                <button class="close-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="areaForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">

                <div class="form-content">
                    <div class="form-group">
                        <label for="area_name">Area Name *</label>
                        <input type="text" id="area_name" name="area_name" required
                            placeholder="Enter area name (e.g., Dhanmondi, Gulshan)"
                            maxlength="100">
                        <small class="form-help">Enter the name of the delivery area</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Area
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Area</h3>
                <button class="close-btn" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="delete-warning">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="warning-content">
                    <h4>⚠️ Confirm Deletion</h4>
                    <p>Are you sure you want to delete the area <strong id="areaToDelete"></strong>?</p>
                    <p class="warning-text">This action cannot be undone and may affect delivery options for customers.</p>
                </div>
            </div>

            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId" value="">

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Area
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../scriptAdmin/manage-areas.js?v=<?php echo time(); ?>"></script>
</body>

</html>