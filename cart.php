<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? 1; // Fallback for demo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $product_item_id = (int)$data['product_item_id'];
    $qty = (int)$data['qty'];

    $result = $conn->query("SELECT id FROM shopping_cart WHERE user_id = $user_id");
    if ($result->num_rows === 0) {
        $conn->query("INSERT INTO shopping_cart (user_id) VALUES ($user_id)");
        $cart_id = $conn->insert_id;
    } else {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['id'];
    }

    $conn->query("INSERT INTO shopping_cart_item (cart_id, product_item_id, qty) 
                  VALUES ($cart_id, $product_item_id, $qty)
                  ON DUPLICATE KEY UPDATE qty = qty + $qty");
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Item added to cart', 'qty' => $qty, 'product_item_id' => $product_item_id]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $product_item_id = (int)$data['product_item_id'];
    $qty = (int)$data['qty'];
    $result = $conn->query("SELECT id FROM shopping_cart WHERE user_id = $user_id");
    $cart_id = $result->fetch_assoc()['id'];
    if ($qty > 0) {
        $conn->query("UPDATE shopping_cart_item SET qty = $qty WHERE cart_id = $cart_id AND product_item_id = $product_item_id");
    } else {
        $conn->query("DELETE FROM shopping_cart_item WHERE cart_id = $cart_id AND product_item_id = $product_item_id");
    }
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Cart updated', 'qty' => $qty, 'product_item_id' => $product_item_id]);
    exit;
}

$result = $conn->query("SELECT p.name AS product_name, sci.qty, pi.price, pi.id AS product_item_id 
                        FROM shopping_cart sc 
                        JOIN shopping_cart_item sci ON sc.id = sci.cart_id 
                        JOIN product_item pi ON sci.product_item_id = pi.id 
                        JOIN product p ON pi.product_id = p.id 
                        WHERE sc.user_id = $user_id");
$cart_items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $row['total'] = $row['price'] * $row['qty'];
    $cart_items[] = $row;
    $total += $row['total'];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping - Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-header {
            background: linear-gradient(to right, #1E3A8A, #7C3AED);
        }
        .cart-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .cart-card:hover {
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
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Your Shopping Cart</h2>
        <div id="cartItems" class="space-y-6">
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-card bg-white p-5 rounded-lg shadow-md flex items-center justify-between border border-gray-200">
                    <div class="flex items-center space-x-4">
                        <img src="https://via.placeholder.com/80" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="w-20 h-20 object-cover rounded">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            <p class="text-gray-600">Price: $<?php echo number_format($item['price'], 2); ?></p>
                            <div class="mt-2">
                                <input type="number" value="<?php echo $item['qty']; ?>" min="0" class="w-20 p-1 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="updateQuantity(<?php echo $item['product_item_id']; ?>, this.value)">
                                <button onclick="removeItem(<?php echo $item['product_item_id']; ?>)" class="ml-2 text-red-500 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                    </div>
                    <p class="text-xl font-bold text-gray-800" id="total-<?php echo $item['product_item_id']; ?>">
                        $<?php echo number_format($item['total'], 2); ?>
                    </p>
                </div>
            <?php endforeach; ?>
            <?php if (empty($cart_items)): ?>
                <p class="text-center text-gray-500 text-lg">Your cart is empty. <a href="index.php" class="text-blue-600 hover:underline">Continue shopping</a></p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 mt-6 sticky bottom-0">
        <div class="container mx-auto max-w-4xl flex justify-between items-center">
            <p class="text-lg">Total: $<span id="totalPrice"><?php echo number_format($total, 2); ?></span></p>
            <a href="checkout.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg transition duration-300">Proceed to Checkout</a>
        </div>
    </footer>

    <script>
        function updateQuantity(itemId, qty) {
            qty = parseInt(qty);
            if (qty < 0) qty = 0;
            fetch('cart.php?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_item_id: itemId, qty: qty })
            })
            .then(response => response.json())
            .then(data => {
                const totalElement = document.getElementById(`total-${itemId}`);
                const price = <?php echo json_encode(array_column($cart_items, 'price')); ?>[<?php echo json_encode(array_column($cart_items, 'product_item_id')); ?>.indexOf(itemId)];
                totalElement.textContent = `$${Number((price * qty).toFixed(2))}`;
                updateTotalPrice();
                alert(data.message);
                document.getElementById('cartItems').classList.add('fade-in');
                setTimeout(() => document.getElementById('cartItems').classList.remove('fade-in'), 500);
            })
            .catch(error => console.error('Error:', error));
        }

        function removeItem(itemId) {
            if (confirm('Are you sure you want to remove this item?')) {
                fetch('cart.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_item_id: itemId, qty: 0 })
                })
                .then(response => response.json())
                .then(data => {
                    const item = document.querySelector(`[onchange="updateQuantity(${itemId}, this.value)"]`).closest('.cart-card');
                    item.style.opacity = '0';
                    setTimeout(() => item.remove(), 300);
                    updateTotalPrice();
                    alert(data.message);
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function updateTotalPrice() {
            let total = 0;
            document.querySelectorAll('[id^="total-"]').forEach(element => {
                total += Number(element.textContent.replace('$', ''));
            });
            document.getElementById('totalPrice').textContent = total.toFixed(2);
        }

        // Initialize total price update on page load
        window.onload = updateTotalPrice;
    </script>
</body>
</html>