<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // In production, use password_hash
    $phone = $conn->real_escape_string($_POST['phone']);
    $result = $conn->query("INSERT INTO site_user (email_address, password, phone_number) VALUES ('$email', '$password', '$phone')");
    if ($result) {
        $_SESSION['user_id'] = $conn->insert_id;
        header('Location: profile.php');
        exit;
    } else {
        $message = 'Registration failed';
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        <h2 class="text-xl font-semibold mb-4">Register</h2>
        <form method="POST" class="bg-white p-4 rounded shadow">
            <?php if ($message): ?>
                <p class="text-red-500"><?php echo $message; ?></p>
            <?php endif; ?>
            <label class="block mb-2">Email</label>
            <input name="email" type="email" class="w-full p-2 border rounded" required>
            <label class="block mb-2 mt-4">Password</label>
            <input name="password" type="password" class="w-full p-2 border rounded" required>
            <label class="block mb-2 mt-4">Phone Number</label>
            <input name="phone" type="tel" class="w-full p-2 border rounded">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded mt-4">Register</button>
            <p class="mt-2">Already have an account? <a href="login.php" class="text-blue-500">Login</a></p>
        </form>
    </main>
</body>
</html>