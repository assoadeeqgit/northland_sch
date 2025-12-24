<?php 
require_once '../auth-check.php';
checkAuth('accountant');
include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 20px;">
        <a href="expenses.php" style="text-decoration: none; color: var(--text-light); font-size: 0.9rem; margin-bottom: 10px; display: inline-block;">&larr; Back to Expenses</a>
        <h1 class="page-title">Record Expense</h1>
    </div>

    <div style="max-width: 600px; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid var(--brand-orange);">
        
        <form>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Expense Title</label>
                <input type="text" placeholder="e.g. Office Supplies Restock" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Category</label>
                    <select style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <option>Salary & Wages</option>
                        <option>Utilities (Power/Water)</option>
                        <option>Maintenance & Repairs</option>
                        <option>Office Supplies</option>
                        <option>Transport</option>
                        <option>Miscellaneous</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Amount ($)</label>
                    <input type="number" step="0.01" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 700; color: var(--brand-navy);">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Date Incurred</label>
                <input type="date" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
            </div>

             <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Payment Method</label>
                <select style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                    <option>Cash</option>
                    <option>Bank Transfer</option>
                    <option>Cheque</option>
                    <option>Company Card</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Description / Notes</label>
                <textarea rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;"></textarea>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color);">Attach Receipt (Optional)</label>
                <input type="file" style="width: 100%; padding: 10px; border: 1px dashed var(--border-color); border-radius: 6px; background: #f9fafb;">
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                 <button type="button" class="btn" style="color: var(--text-light); background: #f3f4f6;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: var(--brand-orange); border:none;">Submit for Approval</button>
            </div>

        </form>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
