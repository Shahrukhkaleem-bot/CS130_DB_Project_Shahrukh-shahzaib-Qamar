<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? 1;
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = $conn->real_escape_string($_POST['shipping_address']);
    $shipping_method_id = (int)$_POST['shipping_method'];

    $result = $conn->query("SELECT SUM(pi.price * sci.qty) AS total 
                            FROM shopping_cart sc 
                            JOIN shopping_cart_item sci ON sc.id = sci.cart_id 
                            JOIN product_item pi ON sci.product_item_id = pi.id 
                            WHERE sc.user_id = $user_id");
    $total = $result->fetch_assoc()['total'] ?? 0;

    $conn->query("INSERT INTO shop_order (user_id, order_date, order_total, shipping_address, shipping_method_id, order_status_id) 
                  VALUES ($user_id, NOW(), $total, '$shipping_address', $shipping_method_id, 1)");
    $order_id = $conn->insert_id;

    $result = $conn->query("SELECT sci.product_item_id, sci.qty, pi.price 
                            FROM shopping_cart sc 
                            JOIN shopping_cart_item sci ON sc.id = sci.cart_id 
                            JOIN product_item pi ON sci.product_item_id = pi.id 
                            WHERE sc.user_id = $user_id");
    while ($row = $result->fetch_assoc()) {
        $conn->query("INSERT INTO order_line (order_id, product_item_id, qty, price) 
                      VALUES ($order_id, {$row['product_item_id']}, {$row['qty']}, {$row['price']})");
    }

    $conn->query("DELETE FROM shopping_cart_item WHERE cart_id = (SELECT id FROM shopping_cart WHERE user_id = $user_id)");
    header("Location: order_confirmation.php?id=$order_id");
    exit;
}

$result = $conn->query("SELECT id, name, price FROM shipping_method");
$shipping_methods = [];
while ($row = $result->fetch_assoc()) {
    $shipping_methods[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping - Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-header {
            background: linear-gradient(to right, #1E3A8A, #7C3AED);
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

    <main class="container mx-auto p-6 max-w-2xl">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Checkout</h2>
        <form method="POST" id="checkoutForm" class="checkout-card bg-white p-6 rounded-lg shadow-md border border-gray-200 fade-in">
            <div class="mb-4">
                <label for="shipping_address" class="block text-lg font-medium text-gray-700 mb-2">Shipping Address</label>
                <textarea name="shipping_address" id="shipping_address" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" rows="4" required placeholder="Enter your shipping address"></textarea>
            </div>
            <div class="mb-6">
                <label for="shipping_method" class="block text-lg font-medium text-gray-700 mb-2">Shipping Method</label>
                <select name="shipping_method" id="shipping_method" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                    <?php foreach ($shipping_methods as $method): ?>
                        <option value="<?php echo $method['id']; ?>">
                            <?php echo htmlspecialchars($method['name']) . ' ($' . number_format($method['price'], 2) . ')'; ?>
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

    <script>
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

                // Simulate form submission delay (remove this in production)
                setTimeout(() => {
                    loadingSpinner.classList.add('hidden');
                    successMessage.textContent = 'Order placed successfully! Redirecting...';
                    successMessage.classList.remove('hidden');
                    successMessage.classList.add('fade-in');
                    setTimeout(() => window.location.href = form.action, 2000); // Redirect after 2 seconds
                }, 1000); // Simulated 1-second delay
            });
        });
    </script>
</body>
</html>