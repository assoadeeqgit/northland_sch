<?php 
require_once '../auth-check.php';
require_once '../config/database.php';
checkAuth('accountant');

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? null;
$item = null;
$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $item_code = $_POST['item_code'] ?? '';
    $unit_price = $_POST['unit_price'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $description = $_POST['description'] ?? '';

    if (empty($item_name) || empty($item_code)) {
        $error = "Product Name and SKU are required.";
    } else {
        try {
            if ($id) {
                // Update
                $query = "UPDATE inventory SET item_name=?, category=?, item_code=?, unit_price=?, quantity=?, description=? WHERE id=?";
                $stmt = $db->prepare($query);
                $stmt->execute([$item_name, $category, $item_code, $unit_price, $quantity, $description, $id]);
                $success = "Product updated successfully!";
            } else {
                // Insert
                $query = "INSERT INTO inventory (item_name, category, item_code, unit_price, quantity, description) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$item_name, $category, $item_code, $unit_price, $quantity, $description]);
                $success = "Product added successfully!";
                $id = $db->lastInsertId(); // To keep editing if needed
                
                // Redirect to list to avoid resubmission
                header("Location: inventory.php?msg=added");
                exit;
            }
        } catch (Exception $e) {
            $error = "Error saving product: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit
if ($id) {
    $stmt = $db->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
}

$page_title = $item ? "Edit Product" : "Add New Product";
$btn_text = $item ? "Update Product" : "Save Product";

include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 20px;">
        <a href="inventory.php" style="text-decoration: none; color: var(--text-light); font-size: 0.9rem; margin-bottom: 10px; display: inline-block;">&larr; Back to Inventory</a>
        <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
    </div>

    <div style="max-width: 700px; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        
        <?php if ($error): ?>
            <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Product Name</label>
                <input type="text" name="item_name" value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>" placeholder="e.g. JSS 1 Math Textbook" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Category</label>
                    <select name="category" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <option <?php echo ($item['category'] ?? '') == 'Uniforms' ? 'selected' : ''; ?>>Uniforms</option>
                        <option <?php echo ($item['category'] ?? '') == 'Textbooks' ? 'selected' : ''; ?>>Textbooks</option>
                        <option <?php echo ($item['category'] ?? '') == 'Stationery' ? 'selected' : ''; ?>>Stationery</option>
                        <option <?php echo ($item['category'] ?? '') == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">SKU / Item Code</label>
                    <input type="text" name="item_code" value="<?php echo htmlspecialchars($item['item_code'] ?? ''); ?>" placeholder="e.g. BK-MATH-01" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Selling Price (₦)</label>
                    <input type="number" step="0.01" name="unit_price" value="<?php echo htmlspecialchars($item['unit_price'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Quantity in Stock</label>
                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Description (Optional)</label>
                <textarea name="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                 <a href="inventory.php" class="btn" style="color: var(--text-light); background: #f3f4f6; text-decoration: none;">Cancel</a>
                <button type="submit" class="btn btn-primary"><?php echo $btn_text; ?></button>
            </div>

        </form>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
