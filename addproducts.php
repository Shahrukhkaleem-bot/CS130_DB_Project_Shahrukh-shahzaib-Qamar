<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = '';
$error_message = '';

// Ensure uploads directory exists
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $image_option = $_POST['image_option'] ?? 'url'; // 'url' or 'upload'
    $image_url = $_POST['image_url'] ?? '';
    $image_path = '';

    // Validate category_id
    $category_check = $conn->prepare("SELECT id FROM product_category WHERE id = ?");
    $category_check->bind_param("i", $category_id);
    $category_check->execute();
    $category_check->store_result();
    $category_exists = $category_check->num_rows > 0;
    $category_check->close();

    if (!$category_exists) {
        $error_message = "Invalid category ID. Please select a valid category.";
    } elseif (empty($name)) {
        $error_message = "Product name is required.";
    } else {
        // Handle image
        if ($image_option === 'upload' && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            // Validate file type and size
            if (!in_array($file['type'], $allowed_types)) {
                $error_message = "Only JPEG, PNG, and GIF images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error_message = "Image size must be less than 5MB.";
            } else {
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_extension;
                $image_path = $upload_dir . $file_name;

                if (!move_uploaded_file($file['tmp_name'], $image_path)) {
                    $error_message = "Failed to upload image.";
                }
            }
        } elseif ($image_option === 'url' && !empty($image_url)) {
            $image_path = $image_url;
        } else {
            $image_path = ''; // Allow empty image if not required
        }

        // Insert product if no errors
        if (!$error_message) {
            $stmt = $conn->prepare("INSERT INTO product (name, description, image, category_id) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssi", $name, $description, $image_path, $category_id);
                if ($stmt->execute()) {
                    $success_message = "Product added successfully!";
                } else {
                    $error_message = "Error adding product: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error_message = "Failed to prepare statement: " . $conn->error;
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Click Shopping</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .error { color: #dc3545; }
        .success { color: #28a745; }
        .form-container { max-width: 500px; margin: 20px auto; }
        .hidden { display: none; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <header class="bg-blue-900 text-white p-6 shadow-lg">
        <h1 class="text-3xl font-bold">Click Shopping - Add Product</h1>
        <nav class="mt-4 flex space-x-6 text-lg">
            <a href="index.php" class="hover:text-blue-200 transition">Home</a>
            <a href="products.php" class="hover:text-blue-200 transition">Products</a>
            <a href="categories.php" class="hover:text-blue-200 transition">Categories</a>
            <a href="cart.php" class="hover:text-blue-200 transition">Cart</a>
        </nav>
    </header>

    <main class="form-container">
        <?php if ($success_message): ?>
            <p class="success text-center"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <p class="error text-center"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-bold mb-2">Product Name:</label>
                <input type="text" id="name" name="name" required class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700 font-bold mb-2">Description:</label>
                <textarea id="description" name="description" class="w-full p-2 border rounded" rows="3"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Image Option:</label>
                <div class="flex space-x-4">
                    <label><input type="radio" name="image_option" value="url" checked onclick="toggleImageInput()"> Use URL</label>
                    <label><input type="radio" name="image_option" value="upload" onclick="toggleImageInput()"> Upload File</label>
                </div>
            </div>
            <div id="image_url_field" class="mb-4">
                <label for="image_url" class="block text-gray-700 font-bold mb-2">Image URL:</label>
                <input type="text" id="image_url" name="image_url" class="w-full p-2 border rounded" placeholder="e.g., https://via.placeholder.com/150">
            </div>
            <div id="image_file_field" class="mb-4 hidden">
                <label for="image_file" class="block text-gray-700 font-bold mb-2">Upload Image:</label>
                <input type="file" id="image_file" name="image_file" class="w-full p-2 border rounded">
                <p class="text-sm text-gray-600 mt-1">Allowed types: JPEG, PNG, GIF. Max size: 5MB.</p>
            </div>
            <div class="mb-4">
                <label for="category_id" class="block text-gray-700 font-bold mb-2">Category ID:</label>
                <input type="number" id="category_id" name="category_id" required class="w-full p-2 border rounded" min="1">
                <p class="text-sm text-gray-600 mt-1">Check valid IDs in <a href="categories.php" class="text-blue-600 hover:underline">Categories</a>.</p>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700 transition">Add Product</button>
        </form>
    </main>

    <script>
        function toggleImageInput() {
            const urlField = document.getElementById('image_url_field');
            const fileField = document.getElementById('image_file_field');
            const imageOption = document.querySelector('input[name="image_option"]:checked').value;

            if (imageOption === 'url') {
                urlField.classList.remove('hidden');
                fileField.classList.add('hidden');
            } else {
                urlField.classList.add('hidden');
                fileField.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>