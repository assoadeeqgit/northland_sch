<?php include 'includes/header.php'; ?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 20px;">
        <a href="inventory.php" style="text-decoration: none; color: var(--text-light); font-size: 0.9rem; margin-bottom: 10px; display: inline-block;">&larr; Back to Inventory</a>
        <h1 class="page-title">Add New Product</h1>
    </div>

    <div style="max-width: 700px; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        
        <form>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Product Name</label>
                <input type="text" placeholder="e.g. JSS 1 Math Textbook" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Category</label>
                    <select style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <option>Uniforms</option>
                        <option>Textbooks</option>
                        <option>Stationery</option>
                        <option>Accessories</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">SKU / Item Code</label>
                    <input type="text" placeholder="e.g. BK-MATH-01" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Selling Price ($)</label>
                    <input type="number" step="0.01" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Cost Price ($)</label>
                    <input type="number" step="0.01" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Initial Stock Quantity</label>
                <input type="number" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Description (Optional)</label>
                <textarea rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;"></textarea>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                 <button type="button" class="btn" style="color: var(--text-light); background: #f3f4f6;">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>

        </form>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
