<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_orderingsystem";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Add new menu item
        if ($_POST['action'] == 'add') {
            $name = $conn->real_escape_string($_POST['name']);
            $description = $conn->real_escape_string($_POST['description']);
            $price = floatval($_POST['price']);
            
            // Handle image upload
            $image = "";
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $image = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $filename);
                    $upload_path = "uploads/" . $image;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $sql = "INSERT INTO menu (name, description, price, image) VALUES ('$name', '$description', $price, '$image')";
                        if ($conn->query($sql)) {
                            $message = "Menu item added successfully!";
                            $messageType = "success";
                        } else {
                            $message = "Error: " . $conn->error;
                            $messageType = "error";
                        }
                    } else {
                        $message = "Error uploading image!";
                        $messageType = "error";
                    }
                } else {
                    $message = "Invalid file type! Allowed: jpg, jpeg, png, gif, webp";
                    $messageType = "error";
                }
            } else {
                // Insert without image
                $sql = "INSERT INTO menu (name, description, price) VALUES ('$name', '$description', $price)";
                if ($conn->query($sql)) {
                    $message = "Menu item added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $messageType = "error";
                }
            }
        }
        
        // Update menu item
        elseif ($_POST['action'] == 'update') {
            $id = intval($_POST['id']);
            $name = $conn->real_escape_string($_POST['name']);
            $description = $conn->real_escape_string($_POST['description']);
            $price = floatval($_POST['price']);
            
            $sql = "UPDATE menu SET name='$name', description='$description', price=$price WHERE id=$id";
            
            if ($conn->query($sql)) {
                $message = "Menu item updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "error";
            }
        }
        
        // Delete menu item
        elseif ($_POST['action'] == 'delete') {
            $id = intval($_POST['id']);
            
            // Get image filename to delete
            $result = $conn->query("SELECT image FROM menu WHERE id=$id");
            if ($row = $result->fetch_assoc()) {
                if ($row['image'] && file_exists("uploads/" . $row['image'])) {
                    unlink("uploads/" . $row['image']);
                }
            }
            
            $sql = "DELETE FROM menu WHERE id=$id";
            if ($conn->query($sql)) {
                $message = "Menu item deleted successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "error";
            }
        }
    }
}

// Get menu items
$menu_items = [];
$result = $conn->query("SELECT * FROM menu ORDER BY id DESC");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM menu WHERE id=$id");
    if ($result->num_rows > 0) {
        $edit_item = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System - Menu Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        /* Form Styles */
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .form-container h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input[type="file"] {
            padding: 10px;
            border: 1px dashed #ddd;
            background: #f9f9f9;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        /* Message Styles */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .menu-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .menu-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .menu-card .no-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .menu-card .content {
            padding: 20px;
        }
        
        .menu-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        
        .menu-card .description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .menu-card .price {
            font-size: 1.5em;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .menu-card .actions {
            display: flex;
            gap: 10px;
        }
        
        .menu-card .actions button,
        .menu-card .actions a {
            flex: 1;
            text-align: center;
            padding: 10px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .stats {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stats h3 {
            color: #333;
            font-size: 1.5em;
        }
        
        .stats .count {
            font-size: 2.5em;
            color: #667eea;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ Food Ordering System</h1>
            <p>Manage your restaurant menu efficiently</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <div class="form-container">
            <h2><?php echo $edit_item ? '✏️ Edit Menu Item' : '➕ Add New Menu Item'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Food Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo $edit_item ? htmlspecialchars($edit_item['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo $edit_item ? htmlspecialchars($edit_item['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" required 
                           value="<?php echo $edit_item ? $edit_item['price'] : ''; ?>">
                </div>
                
                <?php if (!$edit_item): ?>
                    <div class="form-group">
                        <label for="image">Food Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small style="color: #666;">Allowed: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)</small>
                    </div>
                <?php endif; ?>
                
                <?php if ($edit_item && $edit_item['image']): ?>
                    <div class="form-group">
                        <label>Current Image</label>
                        <div>
                            <img src="uploads/<?php echo $edit_item['image']; ?>" 
                                 alt="<?php echo $edit_item['name']; ?>" 
                                 style="max-width: 100px; border-radius: 8px;">
                        </div>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_item ? 'Update Menu Item' : 'Add to Menu'; ?>
                </button>
                
                <?php if ($edit_item): ?>
                    <a href="form.php" class="btn btn-secondary" style="margin-left: 10px; text-decoration: none; display: inline-block;">
                        Cancel Edit
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Statistics -->
        <div class="stats">
            <h3>📊 Menu Statistics</h3>
            <div class="count"><?php echo count($menu_items); ?></div>
            <p>Total Menu Items</p>
        </div>
        
        <!-- Menu List -->
        <h2 style="color: white; margin-bottom: 20px;">📋 Current Menu</h2>
        <div class="menu-grid">
            <?php if (count($menu_items) > 0): ?>
                <?php foreach ($menu_items as $item): ?>
                    <div class="menu-card">
                        <?php if ($item['image'] && file_exists("uploads/" . $item['image'])): ?>
                            <img src="uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        <?php else: ?>
                            <div class="no-image">
                                🍽️
                            </div>
                        <?php endif; ?>
                        <div class="content">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($item['description'] ?: 'No description available'); ?></p>
                            <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                            <div class="actions">
                                <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-warning" style="text-decoration: none;">Edit</a>
                                <form method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="width: 100%;">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; color: white; padding: 40px;">
                    <p style="font-size: 1.2em;">No menu items yet. Add your first item above!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>