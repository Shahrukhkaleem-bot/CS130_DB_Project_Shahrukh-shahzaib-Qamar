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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Function to log queries for debugging
function logQuery($query, $params = []) {
    $log = "Query: $query\nParams: " . json_encode($params) . "\n";
    file_put_contents('checkout_debug.log', $log, FILE_APPEND);
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate form inputs
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $shipping_method_id = (int)($_POST['shipping_method'] ?? 0);

        if (empty($shipping_address)) {
            throw new Exception('Shipping address is required.');
        }
        if ($shipping_method_id <= 0) {
            throw new Exception('Please select a valid shipping method.');
        }

        // Start a transaction
        $pdo->beginTransaction();

        // Fetch the cart ID for the user
        $stmt = $pdo->prepare("SELECT id FROM shopping_cart WHERE user_id = ?");
        logQuery("SELECT id FROM shopping_cart WHERE user_id = ?", [$user_id]);
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            throw new Exception('No cart found for this user.');
        }
        $cart_id = $cart['id'];

        // Calculate the total price of items in the cart
        $stmt = $pdo->prepare("
            SELECT SUM(pi.price * sci.qty) AS total
            FROM shopping_cart_item sci
            JOIN product_item pi ON sci.product_item_id = pi.id
            WHERE sci.cart_id = ?
        ");
        logQuery("SELECT SUM(pi.price * sci.qty) AS total FROM shopping_cart_item sci JOIN product_item pi ON sci.product_item_id = pi.id WHERE sci.cart_id = ?", [$cart_id]);
        $stmt->execute([$cart_id]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        if ($total <= 0) {
            throw new Exception('Cart is empty or total is invalid.');
        }

        // Create a new order
        $stmt = $pdo->prepare("
            INSERT INTO shop_order (user_id, order_date, order_total, shipping_address, shipping_method_id, order_status_id)
            VALUES (?, NOW(), ?, ?, ?, 1)
        ");
        logQuery("INSERT INTO shop_order (user_id, order_date, order_total, shipping_address, shipping_method_id, order_status_id) VALUES (?, NOW(), ?, ?, ?, 1)", [$user_id, $total, $shipping_address, $shipping_method_id]);
        $stmt->execute([$user_id, $total, $shipping_address, $shipping_method_id]);
        $order_id = $pdo->lastInsertId();

        // Copy cart items to order lines
        $stmt = $pdo->prepare("
            SELECT sci.product_item_id, sci.qty, pi.price
            FROM shopping_cart_item sci
            JOIN product_item pi ON sci.product_item_id = pi.id
            WHERE sci.cart_id = ?
        ");
        logQuery("SELECT sci.product_item_id, sci.qty, pi.price FROM shopping_cart_item sci JOIN product_item pi ON sci.product_item_id = pi.id WHERE sci.cart_id = ?", [$cart_id]);
        $stmt->execute([$cart_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO order_line (order_id, product_item_id, qty, price)
                VALUES (?, ?, ?, ?)
            ");
            logQuery("INSERT INTO order_line (order_id, product_item_id, qty, price) VALUES (?, ?, ?, ?)", [$order_id, $item['product_item_id'], $item['qty'], $item['price']]);
            $stmt->execute([$order_id, $item['product_item_id'], $item['qty'], $item['price']]);
        }

        // Clear the cart
        $stmt = $pdo->prepare("DELETE FROM shopping_cart_item WHERE cart_id = ?");
        logQuery("DELETE FROM shopping_cart_item WHERE cart_id = ?", [$cart_id]);
        $stmt->execute([$cart_id]);

        // Commit the transaction
        $pdo->commit();

        // Redirect to order confirmation
        header("Location: order_confirmation.php?id=$order_id");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Checkout failed: " . $e->getMessage();
        logQuery("Error: " . $e->getMessage());
    }
}

// Fetch available shipping methods
try {
    $stmt = $pdo->prepare("SELECT id, name, price FROM shipping_method");
    logQuery("SELECT id, name, price FROM shipping_method", []);
    $stmt->execute();
    $shipping_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching shipping methods: " . $e->getMessage();
    logQuery("Error fetching shipping methods: " . $e->getMessage());
    $shipping_methods = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping - Checkout</title>
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
        .checkout-card {
            transition: box-shadow 0.3s ease;
        }
        .checkout-card:hover {
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
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .loading {
            animation: spin 1s linear infinite;
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
                <a href="profile.php" class="hover:text-gray-200">Profile</a>
                <a href="order_history.php" class="hover:text-gray-200">Orders</a>
                <a href="contact.php" class="hover:text-gray-200">Contact</a>
                <a href="logout.php" class="hover:text-gray-200">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto p-6 max-w-2xl flex-grow">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6 fade-in">Checkout</h2>
        <?php if ($message): ?>
            <p class="text-center text-red-600 mb-4"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form method="POST" id="checkoutForm" class="checkout-card bg-white p-6 rounded-lg shadow-md border border-gray-200 fade-in">
            <div class="mb-4">
                <label for="shipping_address" class="block text-lg font-medium text-gray-700 mb-2">Shipping Address</label>
                <textarea name="shipping_address" id="shipping_address" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" rows="4" required placeholder="Enter your shipping address"></textarea>
            </div>
            <div class="mb-6">
                <label for="shipping_method" class="block text-lg font-medium text-gray-700 mb-2">Shipping Method</label>
                <select name="shipping_method" id="shipping_method" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <option value="">Select a shipping method</option>
                    <?php foreach ($shipping_methods as $method): ?>
                        <option value="<?php echo $method['id']; ?>">
                            <?php echo htmlspecialchars($method['name']) . ' (Rs. ' . number_format($method['price'], 2) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" id="submitButton" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg w-full transition duration-300 flex items-center justify-center">
                Place Order
            </button>
            <div id="loadingSpinner" class="hidden mt-2 text-center text-purple-600"></div>
            <div id="successMessage" class="hidden mt-2 text-center text-green-600"></div>
        </form>
    </main>

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
                    <li><a href="cart.php" class="hover:text-gray-200">Cart</a></li>
                    <li><a href="profile.php" class="text-gray-400 hover:text-white transition duration-300">Profile</a></li>
                    <li><a href="logout.php" class="text-gray-400 hover:text-white transition duration-300">Logout</a></li>
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
            const form = document.getElementById('checkoutForm');
            form.classList.add('fade-in');

            form.addEventListener('submit', (e) => {
                const submitButton = document.getElementById('submitButton');
                const loadingSpinner = document.getElementById('loadingSpinner');
                const successMessage = document.getElementById('successMessage');

                submitButton.disabled = true;
                loadingSpinner.innerHTML = 'Processing <span class="loading inline-block w-4 h-4 border-2 border-t-transparent border-white rounded-full"></span>';
                loadingSpinner.classList.remove('hidden');

                setTimeout(() => {
                    loadingSpinner.classList.add('hidden');
                    successMessage.textContent = 'Order placed successfully! Redirecting...';
                    successMessage.classList.remove('hidden');
                    successMessage.classList.add('fade-in');
                }, 1000);
            });
        });
    </script>
</body>
</html>
