<?php 
require_once '../auth-check.php';
require_once '../config/database.php';
checkAuth('accountant');
include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 class="page-title">Inventory & Stock</h1>
            <p style="color: var(--text-light); margin-top: 5px;">Manage uniforms, textbooks, and stationeries.</p>
        </div>
        <a href="inventory_form.php" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> Add New Item</a>
    </div>

    <!-- Inventory Stats -->
    <div class="stats-grid" style="padding: 0 0 30px 0; grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card blue">
            <span class="label">Total SKUs</span>
            <span class="value">84</span>
        </div>
        <div class="stat-card green">
            <span class="label">Stock Value</span>
            <span class="value">$12,450</span>
            <span class="trend text-success"><i class="fas fa-arrow-up"></i> +5% this month</span>
        </div>
        <div class="stat-card orange">
            <span class="label">Low Stock Items</span>
            <span class="value">8</span>
            <span class="trend text-warning">Restock Needed</span>
        </div>
        <div class="stat-card blue">
            <span class="label">Total Sales (Today)</span>
            <span class="value">$350.00</span>
        </div>
    </div>

    <!-- Controls -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid var(--border-color);">
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 250px;">
                <input type="text" placeholder="Search products..." style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <select style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color);">
                    <option value="">All Categories</option>
                    <option value="uniform">Uniforms</option>
                    <option value="book">Textbooks</option>
                    <option value="stationery">Stationery</option>
                </select>
            </div>
            <button class="btn btn-primary">Filter</button>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="table-container" style="margin: 0;">
        <div class="table-header">
            <h3>Product List</h3>
            <button class="btn" style="background: #eef2ff; color: var(--brand-navy); border: none;"><i class="fas fa-download"></i> Export Inventory</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $database = new Database();
                    $db = $database->getConnection();
                    // Fetch inventory
                    $query = "SELECT * FROM inventory ORDER BY created_at DESC";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($items) > 0) {
                        foreach ($items as $item) {
                            $stock_status_class = 'text-success';
                            $stock_status_bg = 'rgba(46, 125, 50, 0.1)';
                            $stock_status_text = 'In Stock';
                            $min_qty = $item['min_quantity'] ?? 5;

                            if ($item['quantity'] == 0) {
                                $stock_status_class = 'text-danger';
                                $stock_status_bg = 'rgba(211, 47, 47, 0.1)';
                                $stock_status_text = 'Out of Stock';
                            } elseif ($item['quantity'] <= $min_qty) {
                                $stock_status_class = 'text-warning';
                                $stock_status_bg = 'rgba(245, 127, 23, 0.1)';
                                $stock_status_text = 'Low Stock';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-light);">SKU: <?php echo htmlspecialchars($item['item_code']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><span style="font-weight: 600;">$<?php echo number_format($item['unit_price'], 2); ?></span></td>
                                <td>
                                    <?php if ($item['quantity'] <= $min_qty): ?>
                                        <span style="color: var(--brand-orange); font-weight: 700;"><?php echo $item['quantity']; ?> Units</span>
                                    <?php else: ?>
                                        <?php echo $item['quantity']; ?> Units
                                    <?php endif; ?>
                                </td>
                                <td><span class="<?php echo $stock_status_class; ?>" style="background: <?php echo $stock_status_bg; ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;"><?php echo $stock_status_text; ?></span></td>
                                <td>
                                    <a href="inventory_form.php?id=<?php echo $item['id']; ?>" class="btn" style="padding: 6px; color: var(--brand-navy);" title="Edit"><i class="fas fa-edit"></i></a>
                                    <!-- Optional: Add delete or history here -->
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align:center; padding: 20px;">No inventory items found. Add one!</td></tr>';
                    }
                } catch(Exception $e) {
                    echo '<tr><td colspan="6" style="text-align:center; color:red;">Error loading inventory.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
