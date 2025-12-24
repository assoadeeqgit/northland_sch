<?php include 'includes/header.php'; ?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 class="page-title">Student Fee Status</h1>
            <p style="color: var(--text-light); margin-top: 5px;">Track payment status by student and class.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn" style="background: white; border: 1px solid var(--border-color); color: var(--text-color);"><i class="fas fa-file-export" style="margin-right:8px;"></i> Export List</button>
            <a href="student_form.php" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> Add New Student</a>
        </div>
    </div>

    <!-- Stats Overview for Fees -->
    <div class="stats-grid" style="padding: 0 0 30px 0; grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card blue">
            <span class="label">Total Students</span>
            <span class="value">0</span>
        </div>
        <div class="stat-card green">
            <span class="label">Fully Paid</span>
            <span class="value">0</span>
            <span class="trend text-success">0% of Students</span>
        </div>
        <div class="stat-card orange">
            <span class="label">Unpaid / Defaulters</span>
            <span class="value">0</span>
            <span class="trend text-danger">No data yet</span>
        </div>
    </div>

    <!-- Search/Filter Controls -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid var(--border-color);">
        <h4 style="margin-bottom: 15px; color: var(--brand-navy);">Filter Students</h4>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 250px;">
                <input type="text" placeholder="Search by name or admission number..." style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; outline: none;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <select style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color);">
                    <option value="">All Classes</option>
                    <option value="1">JSS 1</option>
                    <option value="2">JSS 2</option>
                    <option value="3">JSS 3</option>
                    <option value="4">SS 1</option>
                    <option value="5">SS 2</option>
                    <option value="6">SS 3</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <select style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color);">
                    <option value="">All Payment Statuses</option>
                    <option value="paid">Fully Paid</option>
                    <option value="partial">Partially Paid</option>
                    <option value="unpaid">Not Paid</option>
                </select>
            </div>
            <button class="btn btn-primary" style="padding-left: 25px; padding-right: 25px;">Search</button>
        </div>
    </div>

    <!-- Student Table -->
    <div class="table-container" style="margin: 0;">
        <div class="table-header">
            <h3>Student List</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Student Info</th>
                    <th>Class</th>
                    <th>Fee Balance</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--text-light);">
                        <i class="fas fa-users" style="font-size: 3rem; color: #e0e0e0; margin-bottom: 15px; display: block;"></i>
                        <p style="font-size: 1.1rem; font-weight: 600; color: var(--text-color); margin-bottom: 8px;">No students found</p>
                        <p style="font-size: 0.9rem;">Students will appear here when payments are recorded in the system.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Pagination Placeholder -->
        <div style="padding: 20px 25px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; color: var(--text-light); font-size: 0.9rem;">
            <span>Showing 0 of 0 students</span>
            <div style="display: flex; gap: 8px;">
                <button class="btn" style="border: 1px solid var(--border-color); background: white; color: var(--text-color);">Previous</button>
                <button class="btn" style="border: 1px solid var(--border-color); background: white; color: var(--text-color);">Next</button>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
