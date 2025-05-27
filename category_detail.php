<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT p.id, p.name, p.description, p.image, pi.id AS item_id, pi.price 
                        FROM product p 
                        JOIN product_item pi ON p.id = pi.product_id 
                        WHERE p.category_id = $category_id");
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Fetch category name for display
$category_name = "Unknown Category";
if ($category_id > 0) {
    $cat_result = $conn->query("SELECT category_name FROM product_category WHERE id = $category_id");
    if ($cat_result->num_rows > 0) {
        $category_name = $cat_result->fetch_assoc()['category_name'];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping - <?php echo htmlspecialchars($category_name); ?> Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-header {
            background: linear-gradient(to right, #1E3A8A, #7C3AED);
        }
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .pulse {
            animation: pulse 0.5s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <header class="gradient-header text-white p-6 shadow-lg">
        <h1 class="text-3xl font-bold">Click Shopping</h1>
        <nav class="mt-4 flex space-x-6 text-lg">
            <a href="index.php" class="hover:text-blue-200 transition">Home</a>
            <a href="products.php" class="hover:text-blue-200 transition">Products</a>
            <a href="categories.php" class="hover:text-blue-200 transition">Categories</a>
            <a href="cart.php" class="hover:text-blue-200 transition">Cart</a>
            <a href="profile.php" class="hover:text-blue-200 transition">Profile</a>
            <a href="order_history.php" class="hover:text-blue-200 transition">Orders</a>
            <a href="contact.php" class="hover:text-blue-200 transition">Contact</a>
        </nav>
    </header>

    <main class="container mx-auto p-6 max-w-4xl">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo htmlspecialchars($category_name); ?> Products</h2>
        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 fade-in">
            <?php if (empty($products)): ?>
                <p class="text-center text-gray-500 text-lg">No products in this category. <a href="categories.php" class="text-blue-600 hover:underline">Browse other categories</a></p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
                        <img src="<?php echo htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/150'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-40 object-cover rounded mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-purple-600 hover:text-purple-800 transition"><?php echo htmlspecialchars($product['name']); ?></a>
                        </h3>
                        <p class="text-gray-600 text-sm mb-2 line-clamp-2"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="text-lg font-bold text-gray-800 mb-3">$<?php echo number_format($product['price'], 2); ?></p>
                        <button onclick="addToCart(<?php echo $product['item_id']; ?>, this)" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded w-full transition duration-300">Add to Cart</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function addToCart(itemId, button) {
            fetch('cart.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_item_id: itemId, qty: 1 })
            })
            .then(response => response.json())
            .then(data => {
                button.classList.add('pulse');
                const successMessage = document.createElement('div');
                successMessage.className = 'text-green-600 text-center mt-2 fade-in';
                successMessage.textContent = data.message;
                button.parentElement.appendChild(successMessage);
                setTimeout(() => successMessage.remove(), 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add to cart. Please try again.');
            });
        }

        // Apply fade-in animation on page load
        document.addEventListener('DOMContentLoaded', () => {
            const productGrid = document.getElementById('productGrid');
            productGrid.classList.add('fade-in');
        });
    </script>
</body>
</html>