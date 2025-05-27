<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

// Fetch products for flash sale (limit to 4) with images
$result = $conn->query("SELECT p.id, p.name, p.description, p.image, pi.id AS item_id, pi.price, pi.SKU, pi.qty_in_stock 
                        FROM product p 
                        JOIN product_item pi ON p.id = pi.product_id 
                        LIMIT 4");
$products = [];
while ($row = $result->fetch_assoc()) {
    $row['original_price'] = $row['price'] * 1.1; // Simulate 10% higher original price
    $row['discount'] = round((($row['original_price'] - $row['price']) / $row['original_price']) * 100);
    $products[] = $row;
}

// Fetch categories (limit to 8)
$result = $conn->query("SELECT DISTINCT pc.category_name 
                        FROM product_category pc 
                        JOIN product p ON pc.id = p.category_id 
                        LIMIT 8");
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category_name'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping</title>
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
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 500px;
            position: relative;
        }
        .shop-btn {
            transition: transform 0.3s ease, background-color 0.3s ease;
        }
        .shop-btn:hover {
            transform: scale(1.05);
            background-color: #1d4ed8;
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
        @keyframes spin {
            from { transform: rotateY(0deg); }
            to { transform: rotateY(360deg); }
        }
        .cube {
            transform-style: preserve-3d;
            animation: spin 15s infinite linear;
        }
        .cube-face {
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(30, 58, 138, 0.8);
            border: 2px solid #3B82F6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        .cube-face:nth-child(1) { transform: rotateY(0deg) translateZ(100px); }
        .cube-face:nth-child(2) { transform: rotateY(90deg) translateZ(100px); }
        .cube-face:nth-child(3) { transform: rotateY(180deg) translateZ(100px); }
        .cube-face:nth-child(4) { transform: rotateY(270deg) translateZ(100px); }
        .cube-face:nth-child(5) { transform: rotateX(90deg) translateZ(100px); }
        .cube-face:nth-child(6) { transform: rotateX(-90deg) translateZ(100px); }
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
<body class="bg-gray-100 font-sans">
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

    <!-- Hero Section -->
    <section class="hero-section flex items-center justify-center text-center text-white fade-in">
        <div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to Click Shopping</h1>
            <p class="text-lg md:text-xl mb-6">Discover amazing deals and start shopping today!</p>
            <a href="categories.php" class="shop-btn bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-semibold">Shop Now</a>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mx-auto mt-10 p-6 fade-in">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Explore Our Flash Sale</h2>
        <p class="text-gray-600 mb-6">Check out the best deals on limited-time offers. Don’t miss out!</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <?php if (empty($products)): ?>
                <p class="text-center text-gray-600">No products available for flash sale.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card bg-white p-4 rounded-lg shadow-md text-center border border-gray-200">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-36 h-36 object-contain mx-auto mb-2">
                        <h3 class="text-sm font-medium text-gray-800 h-12 overflow-hidden"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="text-xs text-gray-500 line-through">Rs. <?php echo number_format($product['original_price'], 2); ?></p>
                        <p class="text-sm font-bold text-gray-800">Rs. <?php echo number_format($product['price'], 2); ?></p>
                        <p class="text-xs text-purple-600"><?php echo $product['discount']; ?>% OFF</p>
                        <button onclick="addToCart(<?php echo $product['item_id']; ?>, this)" class="bg-purple-600 text-white px-3 py-1 rounded text-sm mt-2 w-full hover:bg-purple-700 transition">Add to Cart</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="categories.php" class="mt-6 inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300">Browse All Categories</a>
    </div>

    <!-- Footer -->
    <footer class="footer-bg text-white py-8 mt-10">
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

        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('.fade-in');
            sections.forEach(section => section.classList.add('fade-in'));

            document.getElementById('searchInput').addEventListener('focus', () => {
                document.getElementById('searchInput').classList.add('ring-2', 'ring-purple-500');
            });
            document.getElementById('searchInput').addEventListener('blur', () => {
                document.getElementById('searchInput').classList.remove('ring-2', 'ring-purple-500');
            });
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

        function buyAll() {
            if (<?php echo count($products); ?> > 0) {
                let addedCount = 0;
                <?php foreach ($products as $product): ?>
                    addToCart(<?php echo $product['item_id']; ?>);
                    addedCount++;
                <?php endforeach; ?>
                alert(`${addedCount} products added to cart!`);
            } else {
                alert('No products available to add.');
            }
        }

        function searchProducts() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                window.location.href = `search.php?query=${encodeURIComponent(query)}`;
            } else {
                alert('Please enter a search term.');
            }
        }
    </script>
</body>
</html>
<?php
// Close the database connection

?>