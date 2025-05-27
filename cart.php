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
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Determine the action from raw input or fallback
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?: [];
$action = $inputData['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';

// Handle API request for adding to cart
if ($action === 'add') {
    // Set the content type to JSON for API response
    header('Content-Type: application/json');

    // Debug: Log the incoming request
    $requestData = [
        'GET' => $_GET,
        'POST' => $_POST,
        'RAW_INPUT' => $rawInput,
        'INPUT' => $inputData
    ];
    file_put_contents('cart_debug.log', json_encode($requestData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Please log in to add items to your cart.']);
        exit;
    }

    // Get the request data from JSON body
    $product_item_id = $inputData['product_item_id'] ?? 0;
    $product_id = $inputData['product_id'] ?? 0;
    $qty = $inputData['qty'] ?? 1;

    // Validate input
    if ($qty <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Quantity must be greater than 0.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get or create the user's cart
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id FROM shopping_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            $stmt = $pdo->prepare("INSERT INTO shopping_cart (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $cart_id = $pdo->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Handle case where product_item_id is 0 (create a new product_item)
        if ($product_item_id == 0 && $product_id > 0) {
            $stmt = $pdo->prepare("SELECT price FROM product WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product || !$product['price']) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found or no price set.']);
                $pdo->rollBack();
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO product_item (product_id, SKU, qty_in_stock, price) VALUES (?, ?, ?, ?)");
            $sku = 'AUTO-' . $product_id . '-' . time();
            $stmt->execute([$product_id, $sku, 100, $product['price']]);
            $product_item_id = $pdo->lastInsertId();
        }

        // Verify product_item exists
        $stmt = $pdo->prepare("SELECT qty_in_stock, price FROM product_item WHERE id = ?");
        $stmt->execute([$product_item_id]);
        $product_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product_item) {
            http_response_code(404);
            echo json_encode(['error' => 'Product item not found.']);
            $pdo->rollBack();
            exit;
        }

        if ($product_item['qty_in_stock'] < $qty) {
            http_response_code(400);
            echo json_encode(['error' => 'Insufficient stock available.']);
            $pdo->rollBack();
            exit;
        }

        // Check if the item is already in the cart
        $stmt = $pdo->prepare("SELECT id, qty FROM shopping_cart_item WHERE cart_id = ? AND product_item_id = ?");
        $stmt->execute([$cart_id, $product_item_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            $new_qty = $cart_item['qty'] + $qty;
            if ($new_qty > $product_item['qty_in_stock']) {
                http_response_code(400);
                echo json_encode(['error' => 'Total quantity exceeds available stock.']);
                $pdo->rollBack();
                exit;
            }
            $stmt = $pdo->prepare("UPDATE shopping_cart_item SET qty = ? WHERE id = ?");
            $stmt->execute([$new_qty, $cart_item['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO shopping_cart_item (cart_id, product_item_id, qty) VALUES (?, ?, ?)");
            $stmt->execute([$cart_id, $product_item_id, $qty]);
        }

        $pdo->commit();
        echo json_encode(['message' => 'Item added to cart successfully!']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add item to cart: ' . $e->getMessage()]);
        exit;
    }
} else {
    // Display the cart page (HTML mode)
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // Fetch cart items for the logged-in user
    $user_id = $_SESSION['user_id'];
    $cart_items = [];
    $total_price = 0;

    try {
        // Get the cart ID
        $stmt = $pdo->prepare("SELECT id FROM shopping_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            $cart_id = $cart['id'];
            // Fetch cart items with product details
            $stmt = $pdo->prepare("
                SELECT sci.id, sci.qty, pi.id AS product_item_id, pi.price, p.name, p.image
                FROM shopping_cart sc
                JOIN shopping_cart_item sci ON sc.id = sci.cart_id
                JOIN product_item pi ON sci.product_item_id = pi.id
                JOIN product p ON pi.product_id = p.id
                WHERE sc.id = ?
            ");
            $stmt->execute([$cart_id]);
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate total price
            foreach ($cart_items as $item) {
                $total_price += $item['price'] * $item['qty'];
            }
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching cart items: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping - Cart</title>
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
        .cart-item {
            transition: transform 0.3s ease;
        }
        .cart-item:hover {
            transform: translateY(-2px);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
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
                <a href="logout.php" class="hover:text-gray-200">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-10 p-6 fade-in flex-grow">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Your Cart</h2>
        <?php if (isset($error_message)): ?>
            <p class="text-center text-red-600"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (empty($cart_items)): ?>
            <p class="text-center text-gray-600">Your cart is empty. <a href="products.php" class="text-blue-600 hover:underline">Add some products</a>.</p>
        <?php else: ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item flex items-center border-b pb-4">
                            <img src="<?php echo htmlspecialchars($item['image'] ?: 'https://via.placeholder.com/80'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-20 h-20 object-cover rounded mr-4">
                            <div class="flex-grow">
                                <h3 class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-sm text-gray-600">Price: Rs. <?php echo number_format($item['price'], 2); ?></p>
                                <p class="text-sm text-gray-600">Quantity: <?php echo $item['qty']; ?></p>
                                <p class="text-sm font-bold text-gray-800">Total: Rs. <?php echo number_format($item['price'] * $item['qty'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-6 text-right">
                    <p class="text-lg font-bold text-gray-800">Total Price: Rs. <?php echo number_format($total_price, 2); ?></p>
                    <a href="checkout.php" class="inline-block bg-purple-600 text-white px-6 py-2 rounded mt-4 hover:bg-purple-700 transition">Proceed to Checkout</a>
                </div>
            </div>
        <?php endif; ?>
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
    </script>
</body>
</html>
<?php
}
?>
