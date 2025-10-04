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
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $image = $_POST['image'] ?? '';

        try {
            $stmt = $pdo->prepare("INSERT INTO food_items (category, name, price, description, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category, $name, $price, $description, $image]);
            $_SESSION['message'] = 'Food item added successfully!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error adding food item: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $image = $_POST['image'] ?? '';

        try {
            // Fetch existing item to compare previous image
            $prevStmt = $pdo->prepare("SELECT image, category FROM food_items WHERE id = ?");
            $prevStmt->execute([$id]);
            $existing = $prevStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $old_image = $existing['image'] ?? '';
            $old_category = $existing['category'] ?? '';

            // Update record
            $stmt = $pdo->prepare("UPDATE food_items SET category = ?, name = ?, price = ?, description = ?, image = ? WHERE id = ?");
            $stmt->execute([$category, $name, $price, $description, $image, $id]);

            // If image changed and old image was local, delete the old file
            if (!empty($old_image) && $old_image !== $image && strpos($old_image, 'http') !== 0) {
                $deleted_any = false;
                $primary_path = '../../' . $old_image;
                if (file_exists($primary_path)) {
                    if (@unlink($primary_path)) {
                        $deleted_any = true;
                        error_log("Deleted old image (primary): $primary_path");
                    }
                }
                if (!$deleted_any) {
                    $image_filename = basename($old_image);
                    $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', ($old_category ?: $category)));
                    $alt_cat_path = "../../assets/{$category_slug}/{$image_filename}";
                    if (file_exists($alt_cat_path) && @unlink($alt_cat_path)) {
                        $deleted_any = true;
                        error_log("Deleted old image (category folder): $alt_cat_path");
                    }
                }
                if (!$deleted_any) {
                    $alt_main_path = "../../assets/{$image_filename}";
                    if (isset($image_filename) && file_exists($alt_main_path) && @unlink($alt_main_path)) {
                        $deleted_any = true;
                        error_log("Deleted old image (main assets): $alt_main_path");
                    }
                }
                if (!$deleted_any) {
                    error_log("Old image not found for deletion: $old_image");
                }
            }

            $_SESSION['message'] = 'Food item updated successfully!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error updating food item: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;

        try {
            // First, get the food item details to find the image path
            $stmt = $pdo->prepare("SELECT image, category FROM food_items WHERE id = ?");
            $stmt->execute([$id]);
            $food_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($food_item) {
                // Delete the image file if it exists
                $image_path = '../../' . $food_item['image'];
                $image_deleted = false;

                if (file_exists($image_path)) {
                    if (unlink($image_path)) {
                        error_log("Deleted food item image: $image_path");
                        $image_deleted = true;
                    } else {
                        error_log("Failed to delete food item image: $image_path");
                    }
                } else {
                    // Image might be in a different location, try to find it
                    $image_filename = basename($food_item['image']);
                    $category = $food_item['category'];
                    $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category));

                    // Try category subfolder
                    $category_image_path = "../../assets/{$category_slug}/{$image_filename}";
                    if (file_exists($category_image_path)) {
                        if (unlink($category_image_path)) {
                            error_log("Deleted food item image from category folder: $category_image_path");
                            $image_deleted = true;
                        }
                    }

                    // Try main assets folder
                    $main_image_path = "../../assets/{$image_filename}";
                    if (file_exists($main_image_path)) {
                        if (unlink($main_image_path)) {
                            error_log("Deleted food item image from main assets: $main_image_path");
                            $image_deleted = true;
                        }
                    }
                }

                // Delete the food item from database
                $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
                $stmt->execute([$id]);

                if ($image_deleted) {
                    $_SESSION['message'] = 'Food item and its image deleted successfully!';
                } else {
                    $_SESSION['message'] = 'Food item deleted successfully! (Image file not found)';
                }
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Food item not found!';
                $_SESSION['message_type'] = 'error';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error deleting food item: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    } elseif ($action === 'add_category') {
        $category_name = $_POST['category_name'] ?? '';
        $category_description = $_POST['category_description'] ?? '';

        // Validate category name (remove spaces, special chars)
        $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category_name));

        // Log debug information for troubleshooting (not shown to user)
        error_log("Category creation: $category_name ($category_slug)");
        if (isset($_FILES['category_picture'])) {
            error_log("Picture upload: error=" . $_FILES['category_picture']['error'] . ", size=" . $_FILES['category_picture']['size']);
        }
        if (isset($_FILES['category_icon'])) {
            error_log("Icon upload: error=" . $_FILES['category_icon']['error'] . ", size=" . $_FILES['category_icon']['size']);
        }

        if (empty($category_slug)) {
            $_SESSION['message'] = 'Invalid category name. Please use only letters and numbers.';
            $_SESSION['message_type'] = 'error';
        } else {
            try {
                // First, insert into food_menu table
                $stmt = $pdo->prepare("INSERT INTO food_menu (category, description) VALUES (?, ?)");
                $stmt->execute([$category_name, $category_description]);

                // Create main assets folder if it doesn't exist
                $main_assets_folder = '../../assets';
                if (!file_exists($main_assets_folder)) {
                    if (!mkdir($main_assets_folder, 0755, true)) {
                        throw new Exception("Failed to create main assets folder: $main_assets_folder");
                    }
                }

                // Create category subfolder in assets
                $category_assets_folder = '../../assets/' . $category_slug;
                if (!file_exists($category_assets_folder)) {
                    if (!mkdir($category_assets_folder, 0755, true)) {
                        throw new Exception("Failed to create category assets folder: $category_assets_folder");
                    }
                }

                // Log folder creation
                error_log("Created main assets folder: $main_assets_folder");
                error_log("Created empty category folder: $category_assets_folder");

                // Handle file uploads
                $category_picture = '';
                $category_icon = '';

                // Upload category picture (convert to PNG)
                if (isset($_FILES['category_picture']) && $_FILES['category_picture']['error'] === 0) {
                    $picture_name = $category_slug . '_pic.png';
                    $main_picture_path = $main_assets_folder . '/' . $picture_name;

                    // Log picture upload details
                    error_log("Picture temp file: " . $_FILES['category_picture']['tmp_name']);
                    error_log("Picture destination: $main_picture_path");

                    // Ensure main assets folder is writable
                    if (!is_writable($main_assets_folder)) {
                        throw new Exception("Main assets folder is not writable: $main_assets_folder");
                    }

                    // Convert and save to main assets folder
                    if (convertImageToPNG($_FILES['category_picture']['tmp_name'], $main_picture_path)) {
                        $category_picture = 'assets/' . $picture_name;
                        error_log("Picture uploaded successfully: $category_picture");
                    } else {
                        throw new Exception("Failed to convert and save category picture");
                    }
                } else {
                    $error_msg = isset($_FILES['category_picture']) ?
                        "Upload error: " . $_FILES['category_picture']['error'] :
                        "No picture file uploaded";
                    throw new Exception($error_msg);
                }

                // Upload category icon (convert to PNG)
                if (isset($_FILES['category_icon']) && $_FILES['category_icon']['error'] === 0) {
                    $icon_name = $category_slug . '_icon.png';
                    $main_icon_path = $main_assets_folder . '/' . $icon_name;

                    // Log icon upload details
                    error_log("Icon temp file: " . $_FILES['category_icon']['tmp_name']);
                    error_log("Icon destination: $main_icon_path");

                    // Convert and save to main assets folder
                    if (convertImageToPNG($_FILES['category_icon']['tmp_name'], $main_icon_path)) {
                        $category_icon = 'assets/' . $icon_name;
                        error_log("Icon uploaded successfully: $category_icon");
                    } else {
                        throw new Exception("Failed to convert and save category icon");
                    }
                } else {
                    $error_msg = isset($_FILES['category_icon']) ?
                        "Upload error: " . $_FILES['category_icon']['error'] :
                        "No icon file uploaded";
                    throw new Exception($error_msg);
                }

                // Create category page
                createCategoryPage($category_slug, $category_name, $category_description, $category_picture, $category_icon);

                $_SESSION['message'] = "Food menu '{$category_name}' created successfully! The category is ready for you to add food items.";
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $_SESSION['message'] = 'Error creating food menu: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
            }
        }
    } elseif ($action === 'edit_category') {
        $category_name = $_POST['category_name'] ?? '';
        $category_description = $_POST['category_description'] ?? '';
        $old_category_name = $_POST['old_category_name'] ?? '';

        // Validate category name (remove spaces, special chars)
        $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category_name));
        $old_category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $old_category_name));

        if (empty($category_slug)) {
            $_SESSION['message'] = 'Invalid category name. Please use only letters and numbers.';
            $_SESSION['message_type'] = 'error';
        } else {
            try {
                // Get existing category data to find old files
                $stmt = $pdo->prepare("SELECT category, description FROM food_menu WHERE category = ?");
                $stmt->execute([$old_category_name]);
                $existing_category = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$existing_category) {
                    throw new Exception("Category not found: $old_category_name");
                }

                // Initialize file paths
                $old_picture_file = '../../assets/' . $old_category_slug . '_pic.png';
                $old_icon_file = '../../assets/' . $old_category_slug . '_icon.png';

                // Handle new file uploads
                $category_picture = '';
                $category_icon = '';

                // Upload new category picture (convert to PNG)
                if (isset($_FILES['category_picture']) && $_FILES['category_picture']['error'] === 0) {
                    // Delete old picture file if it exists
                    if (file_exists($old_picture_file)) {
                        unlink($old_picture_file);
                        error_log("Deleted old category picture: $old_picture_file");
                    }
                    
                    $picture_name = $category_slug . '_pic.png';
                    $main_picture_path = '../../assets/' . $picture_name;

                    if (convertImageToPNG($_FILES['category_picture']['tmp_name'], $main_picture_path)) {
                        $category_picture = 'assets/' . $picture_name;
                        error_log("New category picture uploaded: $category_picture");
                    } else {
                        throw new Exception("Failed to convert and save category picture");
                    }
                } else {
                    // Keep existing picture if no new one uploaded, but rename it if category name changed
                    if ($old_category_slug !== $category_slug) {
                        $old_picture_path = '../../assets/' . $old_category_slug . '_pic.png';
                        $new_picture_path = '../../assets/' . $category_slug . '_pic.png';
                        if (file_exists($old_picture_path)) {
                            rename($old_picture_path, $new_picture_path);
                        }
                    }
                    $category_picture = 'assets/' . $category_slug . '_pic.png';
                }

                // Upload new category icon (convert to PNG)
                if (isset($_FILES['category_icon']) && $_FILES['category_icon']['error'] === 0) {
                    // Delete old icon file if it exists
                    if (file_exists($old_icon_file)) {
                        unlink($old_icon_file);
                        error_log("Deleted old category icon: $old_icon_file");
                    }
                    
                    $icon_name = $category_slug . '_icon.png';
                    $main_icon_path = '../../assets/' . $icon_name;

                    if (convertImageToPNG($_FILES['category_icon']['tmp_name'], $main_icon_path)) {
                        $category_icon = 'assets/' . $icon_name;
                        error_log("New category icon uploaded: $category_icon");
                    } else {
                        throw new Exception("Failed to convert and save category icon");
                    }
                } else {
                    // Keep existing icon if no new one uploaded, but rename it if category name changed
                    if ($old_category_slug !== $category_slug) {
                        $old_icon_path = '../../assets/' . $old_category_slug . '_icon.png';
                        $new_icon_path = '../../assets/' . $category_slug . '_icon.png';
                        if (file_exists($old_icon_path)) {
                            rename($old_icon_path, $new_icon_path);
                        }
                    }
                    $category_icon = 'assets/' . $category_slug . '_icon.png';
                }

                // Update category in food_menu table
                $stmt = $pdo->prepare("UPDATE food_menu SET category = ?, description = ? WHERE category = ?");
                $stmt->execute([$category_name, $category_description, $old_category_name]);

                // Update all food items in this category
                $stmt = $pdo->prepare("UPDATE food_items SET category = ? WHERE category = ?");
                $stmt->execute([$category_name, $old_category_name]);

                // If category name changed, rename the PHP file
                if ($old_category_slug !== $category_slug) {
                    $old_page_path = '../../pages/' . $old_category_slug . '.php';
                    $new_page_path = '../../pages/' . $category_slug . '.php';
                    
                    if (file_exists($old_page_path)) {
                        rename($old_page_path, $new_page_path);
                    }
                }
                
                // Always update the category page content with correct file paths
                createCategoryPage($category_slug, $category_name, $category_description, $category_picture, $category_icon);

                $_SESSION['message'] = "Category '{$category_name}' updated successfully!";
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $_SESSION['message'] = 'Error updating category: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
            }
        }
    } elseif ($action === 'delete_category') {
        $category_name = $_POST['category_name'] ?? '';
        $confirm_text = $_POST['confirm_text'] ?? '';

        if (strtolower($confirm_text) !== 'confirm') {
            $_SESSION['message'] = 'Deletion cancelled. You must type "confirm" to delete the category.';
            $_SESSION['message_type'] = 'error';
        } else {
            try {
                // Get category slug
                $category_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $category_name));

                // Delete all food items in this category
                $stmt = $pdo->prepare("DELETE FROM food_items WHERE category = ?");
                $stmt->execute([$category_name]);
                $deleted_items = $stmt->rowCount();

                // Delete from food_menu table
                $stmt = $pdo->prepare("DELETE FROM food_menu WHERE category = ?");
                $stmt->execute([$category_name]);
                $deleted_menu_entries = $stmt->rowCount();

                // Delete category PHP file
                $page_path = '../../pages/' . $category_slug . '.php';
                if (file_exists($page_path)) {
                    unlink($page_path);
                }

                // Delete category assets files from main assets folder
                $picture_file = '../../assets/' . $category_slug . '_pic.png';
                $icon_file = '../../assets/' . $category_slug . '_icon.png';

                if (file_exists($picture_file)) {
                    unlink($picture_file);
                }
                if (file_exists($icon_file)) {
                    unlink($icon_file);
                }

                // Delete category subfolder and its contents
                $category_assets_folder = '../../assets/' . $category_slug;
                if (file_exists($category_assets_folder)) {
                    // Recursively delete folder and contents
                    function deleteDirectory($dir)
                    {
                        if (!file_exists($dir)) return true;
                        if (!is_dir($dir)) return unlink($dir);
                        foreach (scandir($dir) as $item) {
                            if ($item == '.' || $item == '..') continue;
                            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
                        }
                        return rmdir($dir);
                    }
                    deleteDirectory($category_assets_folder);
                }

                $_SESSION['message'] = "Category '{$category_name}' and all its contents have been permanently deleted. ({$deleted_items} food items removed, {$deleted_menu_entries} menu entries removed)";
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $_SESSION['message'] = 'Error deleting category: ' . $e->getMessage();
                $_SESSION['message_type'] = 'error';
            }
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

// Get food items from database
try {
    $stmt = $pdo->query("SELECT * FROM food_items ORDER BY category, name");
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $food_items = [];
}

// Group items by category
$categorized_items = [];
foreach ($food_items as $item) {
    $categorized_items[$item['category']][] = $item;
}

// Get categories from food_menu table
try {
    $stmt = $pdo->query("SELECT category, description FROM food_menu ORDER BY category");
    $menu_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $menu_categories = [];
}

// Get categories from food_items table (for items that exist)
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM food_items ORDER BY category");
    $db_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $db_categories = [];
}

// Extract category names from food_menu table
$menu_category_names = array_column($menu_categories, 'category');

// Only show categories that exist in the food_menu table
$filter_categories = $menu_category_names;
sort($filter_categories);

// Render sections only for categories from food_menu
$display_categories = $menu_category_names;
sort($display_categories);

// Function to convert any image format to PNG
function convertImageToPNG($source_path, $destination_path)
{
    // Check if source file exists
    if (!file_exists($source_path)) {
        error_log("convertImageToPNG: Source file does not exist: $source_path");
        return false;
    }

    // Check if destination directory exists and is writable
    $dest_dir = dirname($destination_path);
    if (!is_dir($dest_dir)) {
        error_log("convertImageToPNG: Destination directory does not exist: $dest_dir");
        return false;
    }

    if (!is_writable($dest_dir)) {
        error_log("convertImageToPNG: Destination directory is not writable: $dest_dir");
        return false;
    }

    // Check if GD extension is available
    if (!extension_loaded('gd')) {
        error_log("convertImageToPNG: GD extension not available, using fallback");
        // If GD is not available, just copy the file and rename it to .png
        // This is a fallback solution

        // Get file extension from the original filename, not the temp file
        $file_extension = '';
        if (isset($_FILES['category_picture']['name'])) {
            $file_extension = strtolower(pathinfo($_FILES['category_picture']['name'], PATHINFO_EXTENSION));
        } elseif (isset($_FILES['category_icon']['name'])) {
            $file_extension = strtolower(pathinfo($_FILES['category_icon']['name'], PATHINFO_EXTENSION));
        } else {
            // Fallback: try to detect from MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $source_path);
            finfo_close($finfo);

            switch ($mime_type) {
                case 'image/jpeg':
                    $file_extension = 'jpg';
                    break;
                case 'image/png':
                    $file_extension = 'png';
                    break;
                case 'image/gif':
                    $file_extension = 'gif';
                    break;
                case 'image/webp':
                    $file_extension = 'webp';
                    break;
                case 'image/bmp':
                    $file_extension = 'bmp';
                    break;
                default:
                    $file_extension = 'jpg'; // Default fallback
            }
        }

        error_log("convertImageToPNG: Detected file extension: $file_extension");

        // Try to copy the file regardless of extension
        // The browser will handle the image display based on content, not extension
        $result = copy($source_path, $destination_path);
        error_log("convertImageToPNG: Fallback copy result: " . ($result ? 'Success' : 'Failed'));
        if ($result) {
            error_log("convertImageToPNG: File saved to: $destination_path");
        } else {
            error_log("convertImageToPNG: Copy failed - source: $source_path, dest: $destination_path");
        }
        return $result;
    }

    // Get image info
    $image_info = getimagesize($source_path);
    if (!$image_info) {
        error_log("convertImageToPNG: getimagesize failed for: $source_path");
        return false;
    }

    $mime_type = $image_info['mime'];
    error_log("convertImageToPNG: Processing image with MIME type: $mime_type");

    // Create image resource based on MIME type
    $image = null;
    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($source_path);
            } else {
                error_log("convertImageToPNG: WebP support not available");
                return false;
            }
            break;
        case 'image/bmp':
            if (function_exists('imagecreatefrombmp')) {
                $image = imagecreatefrombmp($source_path);
            } else {
                error_log("convertImageToPNG: BMP support not available");
                return false;
            }
            break;
        default:
            error_log("convertImageToPNG: Unsupported MIME type: $mime_type");
            return false;
    }

    if (!$image) {
        error_log("convertImageToPNG: Failed to create image resource from: $source_path");
        return false;
    }

    // Convert to PNG
    $result = imagepng($image, $destination_path, 9); // 9 = highest compression

    // Clean up memory
    imagedestroy($image);

    if ($result) {
        error_log("convertImageToPNG: Successfully converted and saved to: $destination_path");
    } else {
        error_log("convertImageToPNG: Failed to save PNG to: $destination_path");
    }

    return $result;
}

// Function to create category page
function createCategoryPage($category_slug, $category_name, $category_description, $category_picture, $category_icon)
{
    $page_content = '<?php
// Page configuration
$page_title = \'' . addslashes($category_name) . ' Menu\';
$category_name = \'' . addslashes($category_name) . '\';
$category_icon = \'' . addslashes($category_icon) . '\';
$page_description = \'' . addslashes($category_description) . '\';
$food_category = \'' . addslashes($category_name) . '\';

// Include the food page template
include \'../components/food_page_template.php\';
?>';

    // Write the page to pages directory as PHP file
    $page_path = '../../pages/' . $category_slug . '.php';
    file_put_contents($page_path, $page_content);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Food - HungryHub Admin</title>
    <link rel="stylesheet" href="../cssAdmin/manage-food.css?v=<?php echo time(); ?>&force=1">
</head>

<body>
    <div class="header">
        <h1>Manage Food Items</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage-food.php" class="active">Food</a>
            <a href="manage-orders.php">Orders</a>
            <a href="manage-users.php">Users</a>
            <a href="manage-areas.php">Areas</a>
            <a href="manage-coupons.php">Coupons</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Food Menu Management</h2>
            <div class="header-buttons">
                <a href="#" class="add-food-btn" onclick="openModal('add')">+ Add New Food Item</a>
                <a href="#" class="add-menu-btn" onclick="openCategoryModal()">+ Add New Food Menu</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Filter System -->
        <div class="filter-section">
            <div class="filter-header">
                <h3>Filter & Search</h3>
                <button class="btn btn-secondary" onclick="clearFilters()">Clear Filter</button>
            </div>
            <div class="filter-controls">
                <div class="filter-group search-group">
                    <label for="searchInput">Search by Name:</label>
                    <input type="text" id="searchInput" placeholder="Enter food item name..." onkeyup="filterFoodItems()">
                    <div id="resultsCount" class="results-count">Showing 69 food item(s)</div>
                </div>

                <div class="filter-group">
                    <label for="categoryFilter">Filter by Category:</label>
                    <select id="categoryFilter" onchange="filterFoodItems()">
                        <option value="">All Categories</option>
                        <?php foreach ($filter_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="priceRange">Price Range (৳):</label>
                    <div class="price-range">
                        <input type="number" id="minPrice" placeholder="Min" onchange="filterFoodItems()">
                        <span>to</span>
                        <input type="number" id="maxPrice" placeholder="Max" onchange="filterFoodItems()">
                    </div>
                </div>

                <div class="filter-group">
                    <label for="sortBy">Sort by:</label>
                    <select id="sortBy" onchange="filterFoodItems()">
                        <option value="name-asc">Name (A-Z)</option>
                        <option value="name-desc">Name (Z-A)</option>
                        <option value="price-asc">Price (Low to High)</option>
                        <option value="price-desc">Price (High to Low)</option>
                        <option value="category-asc">Category (A-Z)</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions" style="display: flex; justify-content: flex-end; gap: .5rem; margin-top: .75rem;">
                <button class="btn btn-primary" onclick="filterFoodItems()">Apply Filter</button>
            </div>
        </div>

        <?php if (empty($display_categories)): ?>
            <div class="message error">
                No food categories found. Please create a new food menu category.
            </div>
        <?php else: ?>
            <?php
            // Create a lookup array for menu category descriptions
            $menu_descriptions = [];
            foreach ($menu_categories as $menu_cat) {
                $menu_descriptions[$menu_cat['category']] = $menu_cat['description'];
            }

            foreach ($display_categories as $category):
                $category_description = $menu_descriptions[$category] ?? '';
            ?>
                <div class="category-section" data-category="<?php echo htmlspecialchars($category); ?>">
                    <div class="category-header">
                        <div class="category-title-section">
                            <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
                            <?php if ($category_description): ?>
                                <p class="category-description"><?php echo htmlspecialchars($category_description); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="category-actions">
                            <a href="#" class="add-category-btn" onclick="openModal('add', '<?php echo $category; ?>')">+ Add to <?php echo $category; ?></a>
                            <a href="#" class="edit-category-btn" onclick="openEditCategoryModal('<?php echo $category; ?>', '<?php echo htmlspecialchars($category_description); ?>')">Edit Category</a>
                            <a href="#" class="delete-category-btn" onclick="openDeleteCategoryModal('<?php echo $category; ?>')">Delete Category</a>
                        </div>
                    </div>
                    <div class="food-grid">
                        <?php if (isset($categorized_items[$category]) && !empty($categorized_items[$category])): ?>
                            <?php foreach ($categorized_items[$category] as $item): ?>
                                <div class="food-item" data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>" data-category="<?php echo htmlspecialchars($item['category']); ?>" data-price="<?php echo $item['price']; ?>">
                                    <img src="<?php echo (strpos($item['image'], 'http') === 0) ? htmlspecialchars($item['image']) : '../../' . htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="food-image">
                                    <div class="food-details">
                                        <div class="food-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="food-price"><span class="currency-symbol">৳</span><?php echo number_format($item['price']); ?></div>
                                        <div class="food-description"><?php echo htmlspecialchars($item['description']); ?></div>
                                        <div class="food-actions">
                                            <a href="#" class="btn btn-edit" onclick="openModal('edit', null, <?php echo htmlspecialchars(json_encode($item)); ?>)">Edit</a>
                                            <a href="#" class="btn btn-delete" onclick="deleteItem(<?php echo $item['id']; ?>)">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-category">
                                <div class="empty-category-content">
                                    <i class="fas fa-utensils empty-icon"></i>
                                    <h4>No items in this category</h4>
                                    <p>This category is empty. Click "Add to <?php echo htmlspecialchars($category); ?>" to add food items.</p>
                                    <button class="btn btn-primary" onclick="openModal('add', '<?php echo $category; ?>')">
                                        <i class="fas fa-plus me-2"></i>Add First Item
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal for Add/Edit -->
    <div id="foodModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add Food Item</h3>
            <form id="foodForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">

                <div class="form-content">
                    <div class="form-group">
                        <label for="name">Food Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($filter_categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (৳):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Image:</label>
                        <div class="image-upload-container">
                            <div class="upload-options">
                                <label class="upload-option">
                                    <input type="radio" name="image_type" value="upload" id="image_upload_radio">
                                    <span>Upload Image</span>
                                </label>
                                <label class="upload-option">
                                    <input type="radio" name="image_type" value="url" id="image_url_radio" checked>
                                    <span>Use URL</span>
                                </label>
                            </div>

                            <div id="upload_section" class="upload-section" style="display: none;">
                                <input type="file" id="image_file" name="image_file" accept="image/*" class="file-input">
                                <label for="image_file" class="file-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose Image File</span>
                                </label>
                                <button type="button" class="btn btn-primary" onclick="uploadImage()" style="margin-top: 1rem; width: 100%;">
                                    <i class="fas fa-upload"></i> Upload Image
                                </button>
                                <div id="upload_progress" class="upload-progress" style="display: none;">
                                    <div class="progress-bar"></div>
                                    <span class="progress-text">Uploading...</span>
                                </div>
                            </div>

                            <div id="url_section" class="url-section">
                                <input type="text" id="image" name="image" placeholder="assets/cake/chocolate_fudge.jpg or https://example.com/image.jpg">
                                <small class="form-help">Use relative path (e.g., assets/cake/image.jpg) or full URL</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Add New Food Menu/Category -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <h3>Add New Food Menu</h3>
            <form id="categoryForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_category">

                <div class="form-content">
                    <div class="form-group">
                        <label for="category_name">Category Name:</label>
                        <input type="text" id="category_name" name="category_name" required placeholder="e.g., Pasta, Pizza, Burgers">
                        <small class="form-help">This will be the name of your food category page</small>
                    </div>

                    <div class="form-group">
                        <label for="category_description">Category Description:</label>
                        <textarea id="category_description" name="category_description" rows="3" required placeholder="Describe this food category..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category_picture">Category Picture:</label>
                        <input type="file" id="category_picture" name="category_picture" accept="image/*" required>
                        <small class="form-help">Main image for the category page (recommended: 800x600px) - Will be converted to PNG format</small>
                    </div>

                    <div class="form-group">
                        <label for="category_icon">Category Icon:</label>
                        <input type="file" id="category_icon" name="category_icon" accept="image/*" required>
                        <small class="form-help">Small icon for navigation (recommended: 64x64px) - Will be converted to PNG format</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Food Menu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Edit Category -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <h3>Edit Food Menu</h3>
            <form id="editCategoryForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="old_category_name" id="editOldCategoryName" value="">

                <div class="form-content">
                    <div class="form-group">
                        <label for="edit_category_name">Category Name:</label>
                        <input type="text" id="edit_category_name" name="category_name" required placeholder="e.g., Pasta, Pizza, Burgers">
                        <small class="form-help">This will be the name of your food category page</small>
                    </div>

                    <div class="form-group">
                        <label for="edit_category_description">Category Description:</label>
                        <textarea id="edit_category_description" name="category_description" rows="3" required placeholder="Describe this food category..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_category_picture">Category Picture:</label>
                        <input type="file" id="edit_category_picture" name="category_picture" accept="image/*">
                        <small class="form-help">Main image for the category page (recommended: 800x600px) - Will be converted to PNG format. Leave empty to keep current picture.</small>
                    </div>

                    <div class="form-group">
                        <label for="edit_category_icon">Category Icon:</label>
                        <input type="file" id="edit_category_icon" name="category_icon" accept="image/*">
                        <small class="form-help">Small icon for navigation (recommended: 64x64px) - Will be converted to PNG format. Leave empty to keep current icon.</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Food Menu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Delete Category Confirmation -->
    <div id="deleteCategoryModal" class="modal">
        <div class="modal-content">
            <h3>Delete Category</h3>
            <div class="delete-warning">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="warning-content">
                    <h4>⚠️ Warning: This action cannot be undone!</h4>
                    <p>You are about to permanently delete the category <strong id="categoryToDelete"></strong> and all its contents:</p>
                    <ul>
                        <li>All food items in this category</li>
                        <li>Category page (<span id="categoryPageName"></span>.php)</li>
                        <li>Category assets folder and all images</li>
                        <li>All associated data</li>
                    </ul>
                </div>
            </div>

            <form id="deleteCategoryForm" method="POST">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_name" id="deleteCategoryName" value="">

                <div class="form-group">
                    <label for="confirmText">To confirm deletion, type <strong>"confirm"</strong> in the box below:</label>
                    <input type="text" id="confirmText" name="confirm_text" placeholder="Type 'confirm' here" required>
                    <small class="form-help">This helps prevent accidental deletions</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>Delete Category Permanently</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, category = null, item = null) {
            const modal = document.getElementById('foodModal');
            const form = document.getElementById('foodForm');
            const title = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const formId = document.getElementById('formId');

            if (action === 'add') {
                title.textContent = 'Add Food Item';
                formAction.value = 'add';
                formId.value = '';
                form.reset();
                if (category) {
                    document.getElementById('category').value = category;
                }
                // Reset upload interface
                document.getElementById('image_url_radio').checked = true;
                toggleImageInput();
            } else if (action === 'edit' && item) {
                title.textContent = 'Edit Food Item';
                formAction.value = 'edit';
                formId.value = item.id;
                document.getElementById('name').value = item.name;
                document.getElementById('category').value = item.category;
                document.getElementById('price').value = item.price;
                document.getElementById('description').value = item.description;
                document.getElementById('image').value = item.image;
                // Set URL mode for editing
                document.getElementById('image_url_radio').checked = true;
                toggleImageInput();
            }

            modal.style.display = 'block';
            // Ensure modal is visible and scrollable
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';
        }

        function toggleImageInput() {
            const uploadRadio = document.getElementById('image_upload_radio');
            const urlRadio = document.getElementById('image_url_radio');
            const uploadSection = document.getElementById('upload_section');
            const urlSection = document.getElementById('url_section');

            if (uploadRadio.checked) {
                uploadSection.style.display = 'block';
                urlSection.style.display = 'none';
            } else {
                uploadSection.style.display = 'none';
                urlSection.style.display = 'block';
            }
        }

        function uploadImage() {
            const fileInput = document.getElementById('image_file');
            const category = document.getElementById('category').value;
            const progressDiv = document.getElementById('upload_progress');
            const imageUrlInput = document.getElementById('image');

            if (!fileInput.files[0]) {
                showNotification('Please select a file to upload', 'warning');
                return;
            }

            if (!category) {
                showNotification('Please select a category first', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('image', fileInput.files[0]);
            formData.append('category', category);

            // Show progress
            progressDiv.style.display = 'block';

            fetch('upload_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    progressDiv.style.display = 'none';

                    if (data.success) {
                        // Set the uploaded file path to the URL input
                        imageUrlInput.value = data.file_path;
                        // Switch to URL mode
                        document.getElementById('image_url_radio').checked = true;
                        toggleImageInput();
                        showNotification('Image uploaded successfully!', 'success');
                    } else {
                        showNotification('Upload failed: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    progressDiv.style.display = 'none';
                    showNotification('Upload failed: ' + error.message, 'error');
                });
        }

        function closeModal() {
            document.getElementById('foodModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openCategoryModal() {
            const modal = document.getElementById('categoryModal');
            const form = document.getElementById('categoryForm');

            form.reset();
            modal.style.display = 'block';
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openEditCategoryModal(categoryName, categoryDescription) {
            const modal = document.getElementById('editCategoryModal');
            const form = document.getElementById('editCategoryForm');
            const oldCategoryName = document.getElementById('editOldCategoryName');
            const categoryNameInput = document.getElementById('edit_category_name');
            const categoryDescriptionInput = document.getElementById('edit_category_description');

            // Set form values
            oldCategoryName.value = categoryName;
            categoryNameInput.value = categoryName;
            categoryDescriptionInput.value = categoryDescription || '';

            // Reset file inputs
            document.getElementById('edit_category_picture').value = '';
            document.getElementById('edit_category_icon').value = '';

            modal.style.display = 'block';
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';
        }

        function closeEditCategoryModal() {
            document.getElementById('editCategoryModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openDeleteCategoryModal(categoryName) {
            const modal = document.getElementById('deleteCategoryModal');
            const categoryToDelete = document.getElementById('categoryToDelete');
            const categoryPageName = document.getElementById('categoryPageName');
            const deleteCategoryName = document.getElementById('deleteCategoryName');
            const confirmText = document.getElementById('confirmText');
            const confirmBtn = document.getElementById('confirmDeleteBtn');

            // Set category name
            categoryToDelete.textContent = categoryName;
            categoryPageName.textContent = categoryName.toLowerCase().replace(/[^a-zA-Z0-9]/g, '');
            deleteCategoryName.value = categoryName;

            // Reset form
            confirmText.value = '';
            confirmText.classList.remove('valid', 'invalid');
            confirmBtn.disabled = true;

            // Show modal
            modal.style.display = 'block';
            modal.scrollTop = 0;
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteCategoryModal() {
            document.getElementById('deleteCategoryModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function deleteItem(id) {
            // Create a custom confirmation modal instead of browser alert
            const confirmModal = document.createElement('div');
            confirmModal.className = 'modal';
            confirmModal.style.display = 'block';
            confirmModal.innerHTML = `
                <div class="modal-content" style="max-width: 400px;">
                    <h3>Delete Food Item</h3>
                    <div class="delete-warning" style="margin-bottom: 1.5rem;">
                        <div class="warning-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="warning-content">
                            <h4>Confirm Deletion</h4>
                            <p>Are you sure you want to delete this food item? This action cannot be undone.</p>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeDeleteConfirm()">Cancel</button>
                        <button type="button" class="btn btn-danger" onclick="confirmDeleteItem(${id})">Delete Item</button>
                    </div>
                </div>
            `;
            document.body.appendChild(confirmModal);
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteConfirm() {
            const modal = document.querySelector('.modal:last-of-type');
            if (modal) {
                modal.remove();
                document.body.style.overflow = 'auto';
            }
        }

        function confirmDeleteItem(id) {
            closeDeleteConfirm();
            showNotification('Deleting food item...', 'info', 2000);

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const foodModal = document.getElementById('foodModal');
            const categoryModal = document.getElementById('categoryModal');
            const editCategoryModal = document.getElementById('editCategoryModal');
            const deleteCategoryModal = document.getElementById('deleteCategoryModal');
            if (event.target === foodModal) {
                closeModal();
            } else if (event.target === categoryModal) {
                closeCategoryModal();
            } else if (event.target === editCategoryModal) {
                closeEditCategoryModal();
            } else if (event.target === deleteCategoryModal) {
                closeDeleteCategoryModal();
            }
        }

        // Add event listeners for radio buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('image_upload_radio').addEventListener('change', toggleImageInput);
            document.getElementById('image_url_radio').addEventListener('change', toggleImageInput);

            // File input change handler
            document.getElementById('image_file').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const label = document.querySelector('.file-label span');
                    label.textContent = file.name;
                }
            });

            // Delete confirmation text handler
            document.getElementById('confirmText').addEventListener('input', function(e) {
                const confirmBtn = document.getElementById('confirmDeleteBtn');
                const input = e.target;
                const value = input.value.toLowerCase().trim();

                // Remove existing classes
                input.classList.remove('valid', 'invalid');

                if (value === 'confirm') {
                    confirmBtn.disabled = false;
                    input.classList.add('valid');
                } else if (value.length > 0) {
                    confirmBtn.disabled = true;
                    input.classList.add('invalid');
                } else {
                    confirmBtn.disabled = true;
                }
            });

            // AJAX form submission
            document.getElementById('foodForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitFoodForm();
            });

            // On load, clear any previously saved filter state so refresh shows defaults
            try {
                localStorage.removeItem('foodFilterState');
            } catch (e) {}
        });

        // Filter System Functions
        function filterFoodItems() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
            const sortBy = document.getElementById('sortBy').value;

            const foodItems = document.querySelectorAll('.food-item');
            const categorySections = document.querySelectorAll('.category-section');
            let visibleItems = [];

            // Filter and collect visible items
            foodItems.forEach(item => {
                const name = item.getAttribute('data-name');
                const category = item.getAttribute('data-category');
                const price = parseFloat(item.getAttribute('data-price'));

                let showItem = true;

                // Search filter
                if (searchTerm && !name.includes(searchTerm)) {
                    showItem = false;
                }

                // Category filter
                if (categoryFilter && category !== categoryFilter) {
                    showItem = false;
                }

                // Price range filter
                if (price < minPrice || price > maxPrice) {
                    showItem = false;
                }

                if (showItem) {
                    item.style.display = 'block';
                    visibleItems.push({
                        element: item,
                        name: item.querySelector('.food-name').textContent,
                        category: category,
                        price: price
                    });
                } else {
                    item.style.display = 'none';
                }
            });

            // Sort visible items
            visibleItems.sort((a, b) => {
                switch (sortBy) {
                    case 'name-asc':
                        return a.name.localeCompare(b.name);
                    case 'name-desc':
                        return b.name.localeCompare(a.name);
                    case 'price-asc':
                        return a.price - b.price;
                    case 'price-desc':
                        return b.price - a.price;
                    case 'category-asc':
                        return a.category.localeCompare(b.category);
                    default:
                        return 0;
                }
            });

            // Reorder items in DOM
            visibleItems.forEach((item, index) => {
                const categorySection = item.element.closest('.category-section');
                const foodGrid = categorySection.querySelector('.food-grid');
                foodGrid.appendChild(item.element);
            });

            // Show only the selected category section (if any)
            if (categoryFilter) {
                categorySections.forEach(section => {
                    const sectionCategory = section.getAttribute('data-category');
                    section.style.display = sectionCategory === categoryFilter ? 'block' : 'none';
                });
            } else {
                // No category selected: show all
                categorySections.forEach(section => {
                    section.style.display = 'block';
                });
            }

            // Update results count
            updateResultsCount(visibleItems.length);
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            document.getElementById('sortBy').value = 'name-asc';

            // Show all items
            const foodItems = document.querySelectorAll('.food-item');
            const categorySections = document.querySelectorAll('.category-section');

            foodItems.forEach(item => {
                item.style.display = 'block';
            });

            categorySections.forEach(section => {
                section.style.display = 'block';
            });

            updateResultsCount(foodItems.length);
            // Ensure any persisted state is removed; no need to click Apply
            try {
                localStorage.removeItem('foodFilterState');
            } catch (e) {}
        }

        function updateResultsCount(count) {
            let resultsDiv = document.getElementById('resultsCount');
            if (resultsDiv) {
                resultsDiv.textContent = `Showing ${count} food item(s)`;
            }
        }

        // AJAX Form Submission
        function submitFoodForm() {
            const form = document.getElementById('foodForm');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Parse the response to check for success/error
                    if (data.includes('successfully')) {
                        showNotification('Food item saved successfully!', 'success');
                        closeModal();
                        // Reload the page content without full page reload
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification('Error saving food item. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error saving food item. Please try again.', 'error');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        }

        // Professional Notification System
        function showNotification(message, type = 'info', duration = 4000) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;

            // Get appropriate icon and colors
            let icon, bgColor, textColor, borderColor;
            switch (type) {
                case 'success':
                    icon = 'fa-check-circle';
                    bgColor = '#f0fff4';
                    textColor = '#22543d';
                    borderColor = '#48bb78';
                    break;
                case 'error':
                    icon = 'fa-exclamation-circle';
                    bgColor = '#fff5f5';
                    textColor = '#742a2a';
                    borderColor = '#f56565';
                    break;
                case 'warning':
                    icon = 'fa-exclamation-triangle';
                    bgColor = '#fffbf0';
                    textColor = '#744210';
                    borderColor = '#ed8936';
                    break;
                case 'info':
                default:
                    icon = 'fa-info-circle';
                    bgColor = '#ebf8ff';
                    textColor = '#2a4365';
                    borderColor = '#3182ce';
                    break;
            }

            notification.innerHTML = `
                <div class="notification-content">
                    <div class="notification-icon">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="notification-message">
                        <div class="notification-title">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
                        <div class="notification-text">${message}</div>
                    </div>
                    <button class="notification-close" onclick="closeNotification(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="notification-progress"></div>
            `;

            // Apply dynamic colors
            notification.style.setProperty('--bg-color', bgColor);
            notification.style.setProperty('--text-color', textColor);
            notification.style.setProperty('--border-color', borderColor);

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.add('notification-show');
            }, 10);

            // Auto remove after specified duration
            setTimeout(() => {
                closeNotification(notification.querySelector('.notification-close'));
            }, duration);
        }

        function closeNotification(closeBtn) {
            const notification = closeBtn.closest('.notification');
            if (notification) {
                notification.classList.add('notification-hide');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }

        // Preserve filter state
        function saveFilterState() {
            const filterState = {
                search: document.getElementById('searchInput').value,
                category: document.getElementById('categoryFilter').value,
                minPrice: document.getElementById('minPrice').value,
                maxPrice: document.getElementById('maxPrice').value,
                sortBy: document.getElementById('sortBy').value
            };
            // No-op: do not persist filters across reloads
        }

        function loadFilterState() {
            /* disabled: do not reload saved filters */ }

        // Save filter state on change
        function setupFilterStateSaving() {
            const filterInputs = ['searchInput', 'categoryFilter', 'minPrice', 'maxPrice', 'sortBy'];
            filterInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    // No persistence; optionally still update UI immediately on Enter
                    input.addEventListener('keyup', function(e) {
                        if (e.key === 'Enter') filterFoodItems();
                    });
                }
            });
        }
    </script>
</body>

</html>