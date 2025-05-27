<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? 1;
$result = $conn->query("SELECT o.id, o.order_date, o.order_total, os.status 
                        FROM shop_order o 
                        JOIN order_status os ON o.order_status_id = os.id 
                        WHERE o.user_id = $user_id");
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
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
        <h2 class="text-xl font-semibold mb-4">Order History</h2>
        <div class="space-y-4">
            <?php if (empty($orders)): ?>
                <p>No orders found.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white p-4 rounded shadow">
                        <p>Order ID: <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="text-blue-500"><?php echo $order['id']; ?></a></p>
                        <p>Date: <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                        <p>Total: $<?php echo $order['order_total']; ?></p>
                        <p>Status: <?php echo htmlspecialchars($order['status']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>