<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['image'];
$category = $_POST['category'] ?? '';

// Validate category
if (empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Category is required']);
    exit();
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = $file['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']);
    exit();
}

// Validate file size (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
    exit();
}

// Create category directory if it doesn't exist
$upload_dir = "../../assets/" . strtolower($category);
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create directory']);
        exit();
    }
}

// Generate unique filename
$original_name = pathinfo($file['name'], PATHINFO_FILENAME);
$extension = 'jpg'; // Convert all to JPG
$filename = $original_name . '_' . time() . '.' . $extension;

// Ensure filename is unique
$counter = 1;
$original_filename = $filename;
while (file_exists($upload_dir . '/' . $filename)) {
    $filename = pathinfo($original_filename, PATHINFO_FILENAME) . '_' . $counter . '.jpg';
    $counter++;
}

$upload_path = $upload_dir . '/' . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Convert to JPG if needed
    if ($file_type !== 'image/jpeg') {
        $image = null;
        switch ($file_type) {
            case 'image/png':
                $image = imagecreatefrompng($upload_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($upload_path);
                break;
        }

        if ($image) {
            // Create white background
            $jpg_image = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($jpg_image, 255, 255, 255);
            imagefill($jpg_image, 0, 0, $white);

            // Copy original image onto white background
            imagecopy($jpg_image, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

            // Save as JPG
            imagejpeg($jpg_image, $upload_path, 90);

            // Clean up
            imagedestroy($image);
            imagedestroy($jpg_image);
        }
    }

    // Return success with relative path
    $relative_path = "assets/" . strtolower($category) . "/" . $filename;
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file_path' => $relative_path
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}
