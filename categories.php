<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT id, category_name FROM product_category");
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <h1 class="text-2xl font-bold">E-Commerce Store</h1>
        <nav class="mt-2">
            <a href="index.php" class="mr-4">Home</a>
            <a href="products.php" class="mr-4">Products</a>
            <a href="categories.php" class="mr-4">Categories</a>
            <a href="cart.php" class="mr-4">Cart</a>
            <a href="profile.php" class="mr-4">Profile</a>
            <a href="order_history.php" class="mr-4">Orders</a>
            <a href="contact.php">Contact</a>
        </nav>
    </header>
    <main class="container mx-auto p-4">
        <h2 class="text-xl font-semibold mb-4">Product Categories</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($categories as $category): ?>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="text-lg font-semibold">
                        <a href="category_detail.php?id=<?php echo $category['id']; ?>" class="text-blue-500"><?php echo htmlspecialchars($category['category_name']); ?></a>
                    </h3>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>