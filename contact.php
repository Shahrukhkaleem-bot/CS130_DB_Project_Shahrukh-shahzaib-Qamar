<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ecommerce_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $msg = $conn->real_escape_string($_POST['message']);
    // In production, save to database or send email
    $conn->query("INSERT INTO contact_messages (name, email, message, submitted_at) 
                  VALUES ('$name', '$email', '$msg', NOW())");
    $message = 'Message received! We will get back to you soon.';
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Shopping - Contact Us</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-header {
            background: linear-gradient(to right, #1E3A8A, #7C3AED);
        }
        .contact-card {
            transition: box-shadow 0.3s ease;
        }
        .contact-card:hover {
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
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Contact Us</h2>
        <form method="POST" id="contactForm" class="contact-card bg-white p-6 rounded-lg shadow-md border border-gray-200 fade-in">
            <?php if ($message): ?>
                <div id="successMessage" class="mb-4 text-green-600 text-center fade-in"><?php echo $message; ?></div>
            <?php endif; ?>
            <div class="mb-4">
                <label for="name" class="block text-lg font-medium text-gray-700 mb-2">Name</label>
                <input name="name" type="text" id="name" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required placeholder="Your Name">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-lg font-medium text-gray-700 mb-2">Email</label>
                <input name="email" type="email" id="email" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" required placeholder="Your Email">
            </div>
            <div class="mb-6">
                <label for="message" class="block text-lg font-medium text-gray-700 mb-2">Message</label>
                <textarea name="message" id="message" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" rows="4" required placeholder="Your Message"></textarea>
            </div>
            <button type="submit" id="submitButton" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg w-full transition duration-300 flex items-center justify-center">
                Send Message
            </button>
            <div id="loadingSpinner" class="hidden mt-2 text-center text-purple-600"></div>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('contactForm');
            form.classList.add('fade-in');

            form.addEventListener('submit', (e) => {
                e.preventDefault(); // Prevent default form submission for demo
                const submitButton = document.getElementById('submitButton');
                const loadingSpinner = document.getElementById('loadingSpinner');
                const successMessage = document.getElementById('successMessage') || document.createElement('div');

                submitButton.disabled = true;
                loadingSpinner.innerHTML = 'Sending <span class="loading inline-block w-4 h-4 border-2 border-t-transparent border-white rounded-full"></span>';
                loadingSpinner.classList.remove('hidden');

                // Simulate form submission
                setTimeout(() => {
                    loadingSpinner.classList.add('hidden');
                    if (!successMessage.id) {
                        successMessage.id = 'successMessage';
                        successMessage.className = 'mb-4 text-green-600 text-center fade-in';
                        successMessage.textContent = 'Message received! We will get back to you soon.';
                        form.prepend(successMessage);
                    }
                    submitButton.disabled = false;
                    form.reset();
                }, 1000); // Simulated 1-second delay
            });
        });
    </script>
</body>
</html>