<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? 1;
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT o.id, o.order_date, o.order_total, o.shipping_address, sm.name AS shipping_method, os.status 
                        FROM shop_order o 
                        JOIN shipping_method sm ON o.shipping_method_id = sm.id 
                        JOIN order_status os ON o.order_status_id = os.id 
                        WHERE o.id = $order_id AND o.user_id = $user_id");
$order = $result->fetch_assoc();

$items = [];
if ($order) {
    $result = $conn->query("SELECT p.name, ol.qty, ol.price 
                            FROM order_line ol 
                            JOIN product_item pi ON ol.product_item_id = pi.id 
                            JOIN product p ON pi.product_id = p.id 
                            WHERE ol.order_id = $order_id");
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Detail</title>
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
        <h2 class="text-xl font-semibold mb-4">Order Details</h2>
        <div class="bg-white p-4 rounded shadow">
            <?php if ($order): ?>
                <p>Order ID: <?php echo $order['id']; ?></p>
                <p>Date: <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                <p>Total: $<?php echo $order['order_total']; ?></p>
                <p>Shipping Address: <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                <p>Shipping Method: <?php echo htmlspecialchars($order['shipping_method']); ?></p>
                <p>Status: <?php echo htmlspecialchars($order['status']); ?></p>
                <h3 class="text-lg font-semibold mt-4">Items</h3>
                <?php foreach ($items as $item): ?>
                    <p><?php echo htmlspecialchars($item['name']); ?> - <?php echo $item['qty']; ?> x $<?php echo $item['price']; ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Order not found.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
