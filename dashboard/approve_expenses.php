<?php
require_once 'auth-check.php';
// Ensure only relevant admin roles can access
checkAuth(['admin', 'administrator', 'super_admin', 'principal']);

require_once '../config/database.php';
require_once '../config/logger.php';

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['expense_id'])) {
    $expense_id = (int)$_POST['expense_id'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'] ?? 0;
    $user_name = $_SESSION['user_name'] ?? 'System Admin';

    if ($action === 'approve') {
        $new_status = 'approved';
        $log_action = 'Approved Expense';
        $icon = 'fas fa-check-double';
        $color = 'bg-green-600';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
        $log_action = 'Rejected Expense';
        $icon = 'fas fa-ban';
        $color = 'bg-red-600';
    } else {
        $new_status = '';
    }

    if ($new_status && $expense_id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE expenses SET status = ?, approved_by = ? WHERE id = ?");
            $stmt->execute([$new_status, $admin_id, $expense_id]);

            // Fix: Correct arguments for logActivity
            // logActivity($db, $user_name, $action_type, $description, $icon, $color)
            logActivity($conn, $user_name, $log_action, "Expense #$expense_id marked as $new_status", $icon, $color);
            
            $message = "Expense #$expense_id has been " . ucfirst($new_status) . ".";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error updating expense: " . $e->getMessage();
            $messageType = "danger";
        } catch (ArgumentCountError $e) {
             $message = "System Error: " . $e->getMessage();
             $messageType = "danger";
        }
    }
}

// Fetch Pending Expenses
$stmt = $conn->query("SELECT e.*, u.first_name, u.last_name 
                      FROM expenses e 
                      LEFT JOIN users u ON e.created_by = u.id 
                      WHERE e.status = 'pending' 
                      ORDER BY e.expense_date DESC, e.created_at DESC");
$pending_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Expenses - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nskblue: '#1e40af',
                        nsklightblue: '#3b82f6',
                        nsknavy: '#1e3a8a',
                        nskgold: '#f59e0b',
                        nsklight: '#f0f9ff',
                        nskgreen: '#10b981',
                        nskred: '#ef4444'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body { font-family: 'Montserrat', sans-serif; background: #f8fafc; }
        .content-card { background: white; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 1.5rem; }
    </style>
</head>
<body class="bg-gray-50">
    <?php require_once 'sidebar.php'; ?>
    
    <main class="main-content">
        <?php 
        $pageTitle = 'Expense Approvals';
        $pageSubtitle = 'Review high-value expenses needing authorization';
        require_once 'header.php'; 
        ?>

        <div class="p-6">
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400' ?> border">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-nsknavy">Pending Expenses (> ₦50,000)</h2>
                    <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                        <?= count($pending_expenses) ?> Pending
                    </span>
                </div>

                <?php if (empty($pending_expenses)): ?>
                    <div class="text-center py-10 text-gray-500">
                        <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                        <p>All clear! No pending expenses found.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3">Category</th>
                                    <th class="px-6 py-3">Amount</th>
                                    <th class="px-6 py-3">Requested By</th>
                                    <th class="px-6 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_expenses as $expense): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <?= date('M d, Y', strtotime($expense['expense_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($expense['description']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                                <?= ucfirst($expense['category']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-gray-900">
                                            ₦<?= number_format($expense['amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($expense['first_name'] . ' ' . $expense['last_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <button onclick="confirmAction(<?= $expense['id'] ?>, 'approve')" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-xs px-3 py-2 text-center transition">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button onclick="confirmAction(<?= $expense['id'] ?>, 'reject')" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-xs px-3 py-2 text-center transition">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hidden Form for Actions -->
        <form id="actionForm" method="POST" style="display: none;">
            <input type="hidden" name="expense_id" id="form_expense_id">
            <input type="hidden" name="action" id="form_action">
        </form>

        <?php require_once 'footer.php'; ?>
    </main>

    <script>
        function confirmAction(id, action) {
            const isApprove = action === 'approve';
            const title = isApprove ? 'Approve Expense?' : 'Reject Expense?';
            const text = isApprove 
                ? 'This will authorize the payment.' 
                : 'This request will be marked as rejected.';
            const confirmBtnColor = isApprove ? '#16a34a' : '#dc2626';

            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#6b7280',
                confirmButtonText: isApprove ? 'Yes, Approve' : 'Yes, Reject'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form_expense_id').value = id;
                    document.getElementById('form_action').value = action;
                    document.getElementById('actionForm').submit();
                }
            });
        }
    </script>
</body>
</html>
