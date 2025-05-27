Project Summary Document
Group Members’ Names and IDs
•	Shahrukh Kaleem
ID:241828
•	Shahzaib Qamar

Project Description
The project is an E-commerce Store Database designed to support a web-based shopping application named "Click Shopping." The database, named ecommerce_store, manages essential e-commerce functionalities such as product catalog management, user accounts, shopping carts, and order processing. It consists of the following tables with their relationships enforced via foreign key constraints (default RESTRICT behavior):
•	Tables: 
o	site_user: Stores user information (e.g., email, password, phone number).
o	product_category: Manages product categories (e.g., Electronics, Food).
o	product: Contains product details (e.g., name, description, image, linked to a category).
o	product_item: Stores individual product items with stock and pricing (linked to a product).
o	shopping_cart: Represents a user’s cart (linked to a user).
o	shopping_cart_item: Manages items in a cart (linked to a cart and product item).
o	shipping_method: Stores shipping options (e.g., Standard, Express).
o	order_status: Defines order statuses (e.g., Pending, Shipped).
o	shop_order: Records orders (linked to shipping method and status).
o	order_line: Contains order items (linked to an order and product item).
•	Key Features: 
o	Add products with categories (addproducts.php).
o	Display products with pricing and discounts (products.php).
o	Manage shopping carts and orders.
o	Handle user authentication (implied login.php, register.php).
o	Support for image uploads for products (local uploads or URLs).
•	Purpose: The database supports a fully functional e-commerce website, allowing users to browse products, add them to a cart, and place orders, while administrators can manage products, categories, and orders. Foreign key constraints ensure data integrity, requiring careful deletion of dependent records due to the RESTRICT behavior.
Setup Instructions
Follow these steps to set up the database, restore the schema, populate it with sample data, and run queries to test the application.
1.	Prerequisites: 
o	Install XAMPP (or a similar LAMP/WAMP stack) on your system.
o	Ensure MySQL and Apache are running (C:\xampp\xampp-control.exe).
o	Place your project files in the web server directory: C:\xampp\htdocs\Ecommerce_store\.
2.	Create the Database: 
o	Open phpMyAdmin by navigating to http://localhost/phpmyadmin in your browser.
o	Create a new database named ecommerce_store: 

                     CREATE DATABASE ecommerce_store;
o	Select the ecommerce_store database from the left sidebar.
3.	Restore the Database Schema: 
o	Use the following SQL script to create all tables with foreign key constraints. Save it as create_ecommerce_store_tables.sql in C:\xampp\htdocs\Ecommerce_store\.

•	In phpMyAdmin, go to the "Import" tab, select 
•	create_ecommerce_store_tables.sql from repository
•	and click "Go" to create the tables.
•	Verify the tables are created:
•	 SHOW TABLES;.
4.	Populate the Database with Sample Data: 
o	Use the following script to insert sample data into the tables.
o	 Save it as
o	 populate_ecommerce_store.sql in C:\xampp\htdocs\Ecommerce_store\.
populate_ecommerce_store.sql is in repository
•	In phpMyAdmin, go to the "Import" tab, select populate_ecommerce_store.sql, and click "Go".
•	Verify the data: SELECT * FROM product; and SELECT * FROM product_item;.
5.	Set Up the Uploads Directory: 
o	Create an uploads/ directory in C:\xampp\htdocs\Ecommerce_store\ to store uploaded product images: 
mkdir C:\xampp\htdocs\Ecommerce_store\uploads
o	Ensure the directory is writable by the web server (Apache). On Windows, this is typically automatic, but you can set permissions if needed: 

icacls "C:\xampp\htdocs\Ecommerce_store\uploads" /grant Everyone:F
o	If using local image paths (e.g., uploads/pizza.jpg), place sample images in this directory or adjust paths in the data.
6.	Run Sample Queries: 
o	Use the following script, which includes sample queries for insertion, retrieval, updates, and deletions. Save it as ecommerce_store_queries.sql in C:\xampp\htdocs\Ecommerce_store\.
ecommerce_store_queries.sql


•	In phpMyAdmin, go to the "Import" tab, select ecommerce_store_queries.sql, and click "Go" to run the queries.
•	Alternatively, copy and execute specific sections (e.g., insertions) in the "SQL" tab.
7.	Test the Application: 
o	Ensure your PHP files (e.g., addproducts.php, products.php) are in C:\xampp\htdocs\Ecommerce_store\.
o	Access the application via the browser: 
	http://localhost/Ecommerce_store/addproducts.php to add new products.
	http://localhost/Ecommerce_store/products.php to view products.
o	Verify that products are displayed correctly and that you can add new products with valid category IDs.
8.	Troubleshooting: 
o	Foreign Key Errors: If deletions fail (e.g., DELETE FROM product_category WHERE id = 3), update or delete child records first: 

UPDATE product SET category_id = NULL WHERE category_id = 3;
DELETE FROM product_category WHERE id = 3;
o	Image Upload Issues: If uploading images fails in addproducts.php, check php.ini settings (file_uploads = On, upload_max_filesize = 5M) and restart Apache.
o	No Products Displayed: Ensure the product and product_item tables are populated (SELECT * FROM product;). If empty, rerun the population script.
