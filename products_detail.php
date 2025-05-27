<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT p.id, p.name, p.description, pi.id AS item_id, pi.price, pi.SKU, pi.qty_in_stock, pc.category_name 
                        FROM product p 
                        JOIN product_item pi ON p.id = pi.product_id 
                        JOIN product_category pc ON p.category_id = pc.id 
                        WHERE p.id = $product_id");
$product = $result->fetch_assoc();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Detail</title>
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
        <div class="bg-white p-4 rounded shadow">
            <?php if ($product): ?>
                <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($product['name']); ?></h2>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <p class="font-bold">$<?php echo $product['price']; ?></p>
                <p>SKU: <?php echo htmlspecialchars($product['SKU']); ?></p>
                <p>Stock: <?php echo $product['qty_in_stock']; ?></p>
                <p>Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                <button onclick="addToCart(<?php echo $product['item_id']; ?>)" class="bg-blue-500 text-white px-4 py-2 rounded mt-2">Add to Cart</button>
            <?php else: ?>
                <p>Product not found.</p>
            <?php endif; ?>
        </div>
    </main>
    <script>
        function addToCart(itemId) {
            fetch('cart.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_item_id: itemId, qty: 1 })
            })
            .then(response => response.json())
            .then(data => alert(data.message));
        }
    </script>
</body>
</html>