<?php 
require_once '../auth-check.php';
checkAuth('accountant');
include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 20px;">
    
    <div class="page-title-box" style="margin-bottom: 20px;">
        <h1 class="page-title">Financial Reports</h1>
        <p style="color: #6b7280;">Generate and view financial statements.</p>
    </div>

    <!-- Controls -->
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 20px; align-items: flex-end;">
        <div style="flex: 1;">
             <label style="display: block; font-weight: 500; margin-bottom: 8px;">Date Range</label>
             <div style="display: flex; gap: 10px;">
                 <input type="date" value="2024-01-01" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                 <span style="align-self: center;">to</span>
                 <input type="date" value="2024-12-31" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
             </div>
        </div>
        <button class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
        <button class="btn" style="border: 1px solid #e5e7eb;"><i class="fas fa-download"></i> Export All</button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <!-- Report Card -->
        <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6;">
                <div style="width: 40px; height: 40px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color); margin-bottom: 15px;">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 18px;"></i>
                </div>
                <h3 style="margin-bottom: 5px;">Fee Collection Report</h3>
                <p style="font-size: 14px; color: #6b7280;">Detailed breakdown of fees collected by class, type, and date.</p>
            </div>
            <div style="padding: 15px 20px; background: #f9fafb; display: flex; justify-content: space-between;">
                <a href="#" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">View Report</a>
                <span style="font-size: 12px; color: #9ca3af;">Updated Today</span>
            </div>
        </div>

        <!-- Report Card -->
        <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6;">
                <div style="width: 40px; height: 40px; background: #dcfce7; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--success); margin-bottom: 15px;">
                    <i class="fas fa-chart-bar" style="font-size: 18px;"></i>
                </div>
                <h3 style="margin-bottom: 5px;">Income Statement</h3>
                <p style="font-size: 14px; color: #6b7280;">Profit and loss statement for the selected period.</p>
            </div>
            <div style="padding: 15px 20px; background: #f9fafb; display: flex; justify-content: space-between;">
                <a href="#" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">View Report</a>
                <span style="font-size: 12px; color: #9ca3af;">Monthly</span>
            </div>
        </div>

        <!-- Report Card -->
        <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
            <div style="padding: 20px; border-bottom: 1px solid #f3f4f6;">
                <div style="width: 40px; height: 40px; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--danger); margin-bottom: 15px;">
                    <i class="fas fa-user-times" style="font-size: 18px;"></i>
                </div>
                <h3 style="margin-bottom: 5px;">Defaulters List</h3>
                <p style="font-size: 14px; color: #6b7280;">List of students with outstanding balances.</p>
            </div>
            <div style="padding: 15px 20px; background: #f9fafb; display: flex; justify-content: space-between;">
                <a href="#" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">View Report</a>
                <span style="font-size: 12px; color: #9ca3af;">Critical</span>
            </div>
        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>
