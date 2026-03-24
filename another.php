<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_orderingsystemgi";

$conn = new mysqli($servername, $username, $password, $dbname); 

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch menu items from database
$sql = "SELECT * FROM menu ORDER BY id DESC";
$result = $conn->query($sql);
$menuItems = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }
}

// Handle cart operations
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $item_id = intval($_POST['item_id']);
    $found = false;
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $item_id) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        // Get item details from database
        $stmt = $conn->prepare("SELECT id, name, price FROM menu WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        if ($item) {
            $_SESSION['cart'][] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => floatval($item['price']),
                'quantity' => 1
            ];
        }
        $stmt->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $item_id = intval($_POST['item_id']);
    $new_quantity = intval($_POST['quantity']);
    
    if ($new_quantity <= 0) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $item_id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    } else {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $item_id) {
                $item['quantity'] = $new_quantity;
                break;
            }
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle remove from cart
if (isset($_POST['remove_from_cart'])) {
    $item_id = intval($_POST['remove_from_cart']);
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle checkout
if (isset($_POST['checkout'])) {
    $_SESSION['order_placed'] = true;
    $order_items = $_SESSION['cart'];
    $_SESSION['cart'] = [];
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle new order (clear order confirmation)
if (isset($_GET['new_order'])) {
    unset($_SESSION['order_placed']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Calculate cart totals
$cart = $_SESSION['cart'];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.10;
$delivery_fee = 2.99;
$total = $subtotal + $tax + $delivery_fee;
$cart_count = array_sum(array_column($cart, 'quantity'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EatNGo - Fast Food Ordering</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary-yellow: #FFD700;
            --dark-yellow: #FFC400;
            --light-yellow: #FFF9C4;
            --accent-red: #FF5252;
            --dark-gray: #333333;
            --light-gray: #F5F5F5;
            --accent-blue: #2196F3;
            --dark-blue: #1976D2;
        }
        
        body {
            background-color: #fff;
            color: var(--dark-gray);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background-color: var(--primary-yellow);
            padding: 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: var(--dark-gray);
        }
        
        .logo h1 {
            font-size: 2.2rem;
            color: var(--dark-gray);
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .cart-icon {
            position: relative;
            font-size: 1.8rem;
            color: var(--dark-gray);
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--accent-red);
            color: white;
            font-size: 0.8rem;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .admin-btn {
            background-color: var(--accent-blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .admin-btn:hover {
            background-color: var(--dark-blue);
        }
        
        /* Main Content */
        .main-content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        
        /* Menu Section */
        .menu-section {
            flex: 3;
            min-width: 300px;
        }
        
        .section-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-yellow);
            color: var(--dark-gray);
        }
        
        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .menu-item {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #eee;
            position: relative;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .item-image {
            height: 180px;
            background-color: var(--light-yellow);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark-gray);
            font-size: 4rem;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            padding: 20px;
        }
        
        .item-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .item-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark-gray);
        }
        
        .add-to-cart {
            background-color: var(--primary-yellow);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .add-to-cart:hover {
            background-color: var(--dark-yellow);
        }
        
        /* Cart Section */
        .cart-section {
            flex: 2;
            min-width: 300px;
            background-color: var(--light-gray);
            border-radius: 15px;
            padding: 25px;
            height: fit-content;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .cart-items {
            min-height: 200px;
            margin-bottom: 25px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-info h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: white;
            border: 1px solid #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            color: var(--dark-gray);
        }
        
        .quantity-btn:hover {
            background-color: var(--primary-yellow);
        }
        
        .cart-item-quantity {
            font-weight: 600;
            min-width: 25px;
            text-align: center;
        }
        
        .remove-item {
            color: var(--accent-red);
            cursor: pointer;
            margin-left: 10px;
            font-size: 1.2rem;
            background: none;
            border: none;
        }
        
        .cart-empty {
            text-align: center;
            padding: 40px 0;
            color: #888;
        }
        
        .cart-empty i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .cart-summary {
            border-top: 2px solid #ddd;
            padding-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            font-size: 1.5rem;
            font-weight: 800;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px dashed #ddd;
        }
        
        .checkout-btn {
            width: 100%;
            background-color: var(--accent-red);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        .checkout-btn:hover {
            background-color: #e53935;
        }
        
        /* Order Modal */
        .order-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }
        
        .order-modal.show {
            display: flex;
        }
        
        .order-confirm {
            background-color: white;
            border-radius: 15px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .order-success {
            padding: 30px;
            text-align: center;
        }
        
        .order-success i {
            font-size: 4rem;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .order-success h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--dark-gray);
        }
        
        .order-success p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        
        .order-details {
            background-color: var(--light-gray);
            border-radius: 10px;
            padding: 20px;
            text-align: left;
            margin-top: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .order-total {
            font-weight: 800;
            font-size: 1.3rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background-color: var(--primary-yellow);
            color: var(--dark-gray);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1001;
            font-weight: 600;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Footer */
        footer {
            background-color: var(--dark-gray);
            color: white;
            padding: 30px 0;
            text-align: center;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary-yellow);
        }
        
        .footer-info p {
            margin-bottom: 5px;
            color: #ccc;
        }
        
        .footer-social {
            display: flex;
            gap: 15px;
            font-size: 1.5rem;
        }
        
        .footer-social a {
            color: white;
            transition: color 0.3s;
        }
        
        .footer-social a:hover {
            color: var(--primary-yellow);
        }
        
        /* Search Bar */
        .search-bar {
            margin-bottom: 25px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-yellow);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 10px;
                align-items: flex-end;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-hamburger"></i>
                <h1>EatNGo</h1>
            </div>
            <div class="header-actions">
                <a href="form.php" class="admin-btn">
                    <i class="fas fa-cog"></i>
                    Admin Panel
                </a>
                <div class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Menu Section -->
        <section class="menu-section">
            <h2 class="section-title">Our Delicious Menu</h2>
            
            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search for your favorite food...">
            </div>
            
            <!-- Menu Items -->
            <div class="menu-items" id="menuItems">
                <?php if (count($menuItems) > 0): ?>
                    <?php foreach ($menuItems as $item): ?>
                        <div class="menu-item" data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>">
                            <div class="item-image">
                                <?php if (!empty($item['image']) && file_exists("uploads/" . $item['image'])): ?>
                                    <img src="uploads/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-utensils"></i>
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="item-description"><?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?></p>
                                <div class="item-footer">
                                    <div class="item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart">
                                            <i class="fas fa-plus"></i>
                                            Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        <p>No menu items available. Please add items in the admin panel!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Cart Section -->
        <section class="cart-section">
            <h2 class="section-title">Your Order</h2>
            
            <!-- Cart Items -->
            <div class="cart-items" id="cartItems">
                <?php if (empty($cart)): ?>
                    <div class="cart-empty">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Your cart is empty</p>
                        <p>Add delicious items from the menu!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <div class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            <div class="cart-item-controls">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                    <button type="submit" name="update_quantity" class="quantity-btn">-</button>
                                </form>
                                <div class="cart-item-quantity"><?php echo $item['quantity']; ?></div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                    <button type="submit" name="update_quantity" class="quantity-btn">+</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="remove_from_cart" value="<?php echo $item['id']; ?>" class="remove-item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Cart Summary -->
            <?php if (!empty($cart)): ?>
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (10%)</span>
                        <span>₱<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span>₱<?php echo number_format($delivery_fee, 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <form method="POST">
                        <button type="submit" name="checkout" class="checkout-btn">
                            <i class="fas fa-credit-card"></i>
                            Checkout Now
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </div>
    
    <!-- Order Modal -->
    <div class="order-modal <?php echo isset($_SESSION['order_placed']) ? 'show' : ''; ?>" id="orderModal">
        <div class="order-confirm">
            <div class="modal-header" style="padding: 20px 30px; border-bottom: 2px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.5rem;">Order Confirmation</h2>
                <a href="?new_order=1" style="font-size: 2rem; color: #777; text-decoration: none;">&times;</a>
            </div>
            <div class="order-success">
                <i class="fas fa-check-circle"></i>
                <h3>Order Placed Successfully!</h3>
                <p>Your food is being prepared and will be ready for pickup in 15-20 minutes.</p>
                
                <div class="order-details">
                    <?php if (isset($_SESSION['order_placed']) && isset($order_items)): ?>
                        <?php 
                        $order_subtotal = 0;
                        foreach ($order_items as $item) {
                            $order_subtotal += $item['price'] * $item['quantity'];
                        }
                        $order_tax = $order_subtotal * 0.10;
                        $order_total = $order_subtotal + $order_tax + $delivery_fee;
                        ?>
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="order-item">
                            <span>Subtotal</span>
                            <span>₱<?php echo number_format($order_subtotal, 2); ?></span>
                        </div>
                        <div class="order-item">
                            <span>Tax (10%)</span>
                            <span>₱<?php echo number_format($order_tax, 2); ?></span>
                        </div>
                        <div class="order-item">
                            <span>Delivery Fee</span>
                            <span>₱<?php echo number_format($delivery_fee, 2); ?></span>
                        </div>
                        <div class="order-item order-total">
                            <span>Total</span>
                            <span>₱<?php echo number_format($order_total, 2); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <a href="?new_order=1" class="checkout-btn" style="margin-top: 25px; display: inline-block; text-decoration: none;">
                    <i class="fas fa-utensils"></i>
                    Place New Order
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="footer-logo">EatNGo</div>
            <div class="footer-info">
                <p><i class="fas fa-clock"></i> Open: 10:00 AM - 11:00 PM</p>
                <p><i class="fas fa-phone"></i> Call: (123) 456-7890</p>
                <p><i class="fas fa-map-marker-alt"></i> 123 Fast Food Street, Yellow City</p>
            </div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </footer>

    <!-- Search and Filter Script -->
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const menuItems = document.querySelectorAll('.menu-item');
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            menuItems.forEach(item => {
                const itemName = item.getAttribute('data-name');
                if (itemName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Show notification if added to cart
        <?php if (isset($_POST['add_to_cart'])): ?>
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 10px;"></i> Item added to cart!';
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
        <?php endif; ?>
    </script>
</body>
</html>

<?php
$conn->close();
?>