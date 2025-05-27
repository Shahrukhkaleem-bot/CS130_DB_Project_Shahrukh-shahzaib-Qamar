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
    $total = $result->fetch_assoc()['total'];

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
    <title>Checkout</title>
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
        <h2 class="text-xl font-semibold mb-4">Checkout</h2>
        <form method="POST" class="bg-white p-4 rounded shadow">
            <label class="block mb-2">Shipping Address</label>
            <textarea name="shipping_address" class="w-full p-2 border rounded" rows="4" required></textarea>
            <label class="block mb-2 mt-4">Shipping Method</label>
            <select name="shipping_method" class="w-full p-2 border rounded" required>
                <?php foreach ($shipping_methods as $method): ?>
                    <option value="<?php echo $method['id']; ?>">
                        <?php echo htmlspecialchars($method['name']) . ' ($' . $method['price'] . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded mt-4">Place Order</button>
        </form>
    </main>
</body>
</html>