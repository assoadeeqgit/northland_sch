<?php 
require_once 'auth-check.php'; // Allows both admin and accountant
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
                <tr>
                    <td>
                        <div style="font-weight: 600;">JSS 1 Boys Uniform (Set)</div>
                        <div style="font-size: 0.8rem; color: var(--text-light);">SKU: UNIF-J-B-S</div>
                    </td>
                    <td>Uniforms</td>
                    <td><span style="font-weight: 600;">$45.00</span></td>
                    <td>150 Units</td>
                    <td><span class="text-success" style="background: rgba(46, 125, 50, 0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">In Stock</span></td>
                    <td>
                        <button class="btn" style="padding: 6px; color: var(--brand-navy);"><i class="fas fa-edit"></i></button>
                        <button class="btn" style="padding: 6px; color: var(--text-light);"><i class="fas fa-history"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="font-weight: 600;">New General Mathematics (SS 1)</div>
                        <div style="font-size: 0.8rem; color: var(--text-light);">SKU: BK-BJ-MATH-1</div>
                    </td>
                    <td>Textbooks</td>
                    <td><span style="font-weight: 600;">$12.50</span></td>
                    <td>42 Units</td>
                    <td><span class="text-success" style="background: rgba(46, 125, 50, 0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">In Stock</span></td>
                    <td>
                        <button class="btn" style="padding: 6px; color: var(--brand-navy);"><i class="fas fa-edit"></i></button>
                        <button class="btn" style="padding: 6px; color: var(--text-light);"><i class="fas fa-history"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="font-weight: 600;">School Badge (Woven)</div>
                        <div style="font-size: 0.8rem; color: var(--text-light);">SKU: ACC-BADGE-01</div>
                    </td>
                    <td>Accessories</td>
                    <td><span style="font-weight: 600;">$5.00</span></td>
                    <td><span style="color: var(--brand-orange); font-weight: 700;">5 Units</span></td>
                    <td><span class="text-warning" style="background: rgba(245, 127, 23, 0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">Low Stock</span></td>
                    <td>
                        <button class="btn" style="padding: 6px; color: var(--brand-navy);"><i class="fas fa-edit"></i></button>
                        <button class="btn" style="padding: 6px; color: var(--success);" title="Restock"><i class="fas fa-plus-circle"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="font-weight: 600;">Integrated Science Workbook</div>
                        <div style="font-size: 0.8rem; color: var(--text-light);">SKU: BK-SCI-WB</div>
                    </td>
                    <td>Textbooks</td>
                    <td><span style="font-weight: 600;">$8.00</span></td>
                    <td>0 Units</td>
                    <td><span class="text-danger" style="background: rgba(211, 47, 47, 0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">Out of Stock</span></td>
                    <td>
                        <button class="btn" style="padding: 6px; color: var(--brand-navy);"><i class="fas fa-edit"></i></button>
                        <button class="btn" style="padding: 6px; color: var(--success);" title="Restock"><i class="fas fa-plus-circle"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
