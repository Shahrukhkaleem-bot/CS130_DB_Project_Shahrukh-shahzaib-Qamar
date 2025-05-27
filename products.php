<?php
session_start();

// PDO Database Connection
$host = 'localhost';
$dbname = 'ecommerce_store';
$username = 'root';
$password = ''; // Update with your MySQL password if set

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = '';

try {
    // Fetch all products with their items, including the image, using LEFT JOIN
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.description, p.image, pi.id AS item_id, pi.price, pi.SKU, pi.qty_in_stock 
                           FROM product p 
                           LEFT JOIN product_item pi ON p.id = pi.product_id");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        $message = "No products found in the database.";
    } else {
        foreach ($products as &$product) {
            if ($product['price'] !== null) {
                $product['original_price'] = $product['price'] * 1.1; // Simulate 10% higher original price
                $product['discount'] = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
            } else {
                $product['original_price'] = null;
                $product['discount'] = 0;
            }
            // Ensure image path is valid; fallback to placeholder if null
            $product['image'] = !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/150';
        }
        unset($product); // Unset reference to avoid issues
    }

    // Fetch categories (limit to 8) - optional, for navigation or filtering
    $stmt = $pdo->prepare("SELECT DISTINCT pc.category_name 
                           FROM product_category pc 
                           JOIN product p ON pc.id = p.category_id 
                           LIMIT 8");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $categories = array_column($categories, 'category_name');
} catch (PDOException $e) {
    $message = "Error fetching products: " . $e->getMessage();
    $products = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping - Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(to bottom, #F5F7FA, #E0E7FF);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        .gradient-header {
            background: linear-gradient(90deg, #4B6CB7, #182848);
        }
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            min-height: 300px;
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
        .footer-bg {
            background-color: #1f2937;
        }
    </style>
</head>
<body class="font-sans flex flex-col">
    <!-- Navbar -->
    <nav class="gradient-header text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold">Click Shopping</a>
            <button type="button" class="md:hidden text-white focus:outline-none" id="menu-toggle">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
            <div class="hidden md:flex space-x-6" id="navbarNav">
                <a href="products.php" class="hover:text-gray-200">Products</a>
                <a href="cart.php" class="hover:text-gray-200">Cart</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="hover:text-gray-200">Profile</a>
                    <a href="logout.php" class="hover:text-gray-200">Logout</a>
                <?php else: ?>
                    <a href="register.php" class="hover:text-gray-200">Register</a>
                    <a href="login.php" class="hover:text-gray-200">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-10 p-6 fade-in">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">All Products</h2>
        <?php if ($message): ?>
            <p class="text-center text-red-600"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <?php if (empty($products)): ?>
                <p class="text-center text-gray-600">No products available. <a href="addproducts.php" class="text-blue-600 hover:underline">Add a product</a>.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card bg-white p-4 rounded-lg shadow-md text-center border border-gray-200 flex flex-col justify-between">
                        <div>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover rounded-t-lg">
                            <h3 class="text-sm font-medium text-gray-800 h-12 overflow-hidden mt-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <?php if ($product['price'] !== null): ?>
                                <p class="text-xs text-gray-500 line-through">Rs. <?php echo number_format($product['original_price'], 2); ?></p>
                                <p class="text-sm font-bold text-gray-800">Rs. <?php echo number_format($product['price'], 2); ?></p>
                                <p class="text-xs text-purple-600"><?php echo $product['discount']; ?>% OFF</p>
                            <?php else: ?>
                                <p class="text-sm text-gray-600">Price not available</p>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['item_id'] !== null): ?>
                            <button onclick="addToCart(<?php echo $product['item_id']; ?>, this)" class="bg-purple-600 text-white px-3 py-1 rounded text-sm mt-4 w-full hover:bg-purple-700 transition">Add to Cart</button>
                        <?php else: ?>
                            <p class="text-xs text-gray-500 mt-4">Not available for purchase</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-bg text-white py-8 mt-auto">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">Click Shopping</h3>
                <p class="text-gray-400">Your one-stop shop for amazing deals and quality products.</p>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="products.php" class="text-gray-400 hover:text-white transition duration-300">Products</a></li>
                    <li><a href="cart.php" class="text-gray-400 hover:text-white transition duration-300">Cart</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php" class="text-gray-400 hover:text-white transition duration-300">Profile</a></li>
                        <li><a href="logout.php" class="text-gray-400 hover:text-white transition duration-300">Logout</a></li>
                    <?php else: ?>
                        <li><a href="register.php" class="text-gray-400 hover:text-white transition duration-300">Register</a></li>
                        <li><a href="login.php" class="text-gray-400 hover:text-white transition duration-300">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Contact Us</h3>
                <p class="text-gray-400">Email: support@clickshopping.com</p>
                <p class="text-gray-400">Phone: +1 (123) 456-7890</p>
                <p class="text-gray-400">Address: 123 Shopping Lane, Commerce City</p>
            </div>
        </div>
        <div class="container mx-auto mt-8 border-t border-gray-700 pt-4 text-center">
            <p class="text-gray-400">© <?php echo date('Y'); ?> Click Shopping. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            const navbar = document.getElementById('navbarNav');
            navbar.classList.toggle('hidden');
        });

        function addToCart(itemId, button) {
            fetch('cart.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_item_id: itemId, qty: 1 })
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                button.classList.add('pulse');
                const successMessage = document.createElement('div');
                successMessage.className = 'text-green-600 text-center mt-2 text-xs fade-in';
                successMessage.textContent = data.message || 'Item added to cart!';
                button.parentElement.appendChild(successMessage);
                setTimeout(() => {
                    successMessage.remove();
                    button.classList.remove('pulse');
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add to cart. Please try again.');
            });
        }
    </script>
</body>
</html>