<?php
require_once '../auth-check.php';
checkAuth('accountant'); // Finance management is for accountants only

require_once '../config/config.php';
include '../includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// --- HANDLE FORM SUBMISSIONS ---
$message = '';
$messageType = '';
$selectedStudent = null;

// Process Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $student_id = $_POST['student_id'] ?? 0;
    $fee_structure_id = $_POST['fee_structure_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $academic_session_id = $_POST['academic_session_id'] ?? 0;
    $term_id = $_POST['term_id'] ?? 0;
    $remarks = $_POST['remarks'] ?? '';

    if ($student_id > 0 && $fee_structure_id > 0 && $amount > 0 && $academic_session_id > 0 && $term_id > 0) {
        try {
            // Insert payment record
            $stmt = $conn->prepare("INSERT INTO payments (student_id, fee_structure_id, amount_paid, payment_method, academic_session_id, term_id, remarks, payment_date, received_by) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)");
            $stmt->execute([$student_id, $fee_structure_id, $amount, $payment_method, $academic_session_id, $term_id, $remarks]);
            $payment_id = $conn->lastInsertId();
            
            $message = "Payment of ₦" . number_format($amount, 2) . " recorded successfully! <a href='receipt.php?id=" . $payment_id . "' target='_blank' style='margin-left:10px; text-decoration:underline; font-weight:bold;'>Print Receipt <i class='fas fa-print'></i></a>";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error processing payment: " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "Please fill in all required fields and select a valid student.";
        $messageType = "warning";
    }
}

// Student Search
$searchResults = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $conn->prepare("
        SELECT s.*, u.first_name, u.last_name, c.class_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE s.admission_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$search, $search, $search]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get selected student details
if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $stmt = $conn->prepare("
        SELECT s.*, u.first_name, u.last_name, c.class_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$_GET['student_id']]);
    $selectedStudent = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch academic sessions
$sessions = $conn->query("SELECT * FROM academic_sessions ORDER BY session_name DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch terms
$terms = $conn->query("SELECT * FROM terms ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Get current session and term
$currentSession = $conn->query("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$currentTerm = $conn->query("SELECT * FROM terms WHERE is_current = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Get fee structures for selected student
$feeStructures = [];
$totalDue = 0;
$totalPaid = 0;
$balance = 0;

if ($selectedStudent) {
    $stmt = $conn->prepare("
        SELECT fs.*, t.term_name, asess.session_name
        FROM fee_structure fs
        JOIN terms t ON fs.term_id = t.id
        JOIN academic_sessions asess ON fs.academic_session_id = asess.id
        WHERE fs.class_id = ?
    ");
    $stmt->execute([$selectedStudent['class_id']]);
    $feeStructures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    foreach ($feeStructures as $fee) {
        $totalDue += $fee['amount'];
    }
    
    // Get payments already made
    $stmt = $conn->prepare("SELECT SUM(amount_paid) as paid FROM payments WHERE student_id = ?");
    $stmt->execute([$selectedStudent['id']]);
    $paidResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPaid = $paidResult['paid'] ?? 0;
    
    $balance = $totalDue - $totalPaid;
}
?>

<div class="content-body" style="padding: 20px;">
    
    <div class="page-title-box">
        <h1 class="page-title">Process Payment</h1>
        <p style="color: #6b7280; margin-top: 5px;">Record a new fee payment transaction.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: <?php echo $messageType == 'success' ? '#d4edda' : ($messageType == 'warning' ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $messageType == 'success' ? '#155724' : ($messageType == 'warning' ? '#856404' : '#721c24'); ?>;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px;">
        
        <!-- Payment Form -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            
            <!-- Step 1: Student Selection -->
            <form method="GET" style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 500; margin-bottom: 8px;">Select Student</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Enter Admission No or Name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="flex: 1; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>

            <!-- Search Results -->
            <?php if (!empty($searchResults)): ?>
                <div style="margin-bottom: 25px; max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <?php foreach ($searchResults as $student): ?>
                        <a href="?student_id=<?php echo $student['id']; ?>" style="display: block; padding: 12px; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: inherit; transition: background 0.2s;">
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                            <div style="font-size: 13px; color: #6b7280;">
                                <?php echo htmlspecialchars($student['admission_number']); ?> | <?php echo htmlspecialchars($student['class_name'] ?? 'No Class'); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Selected Student Details -->
            <?php if ($selectedStudent): ?>
                <div style="background: #f0f9ff; padding: 15px; border-radius: 6px; border: 1px solid #38bdf8; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between;">
                        <div>
                            <h4 style="margin-bottom: 5px; color: #0369a1;"><?php echo htmlspecialchars($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']); ?></h4>
                            <p style="color: #0284c7; font-size: 14px; margin: 0;">
                                <?php echo htmlspecialchars($selectedStudent['admission_number']); ?> | <?php echo htmlspecialchars($selectedStudent['class_name'] ?? 'No Class'); ?>
                            </p>
                        </div>
                        <a href="?" style="color: #0369a1; text-decoration: none; font-size: 14px;">
                            <i class="fas fa-times-circle"></i> Change
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Payment Form -->
            <form method="POST">
                <input type="hidden" name="student_id" value="<?php echo $selectedStudent['id'] ?? ''; ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 8px;">Academic Year *</label>
                        <select name="academic_session_id" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <option value="">Select Session...</option>
                            <?php foreach ($sessions as $session): ?>
                                <option value="<?php echo $session['id']; ?>" <?php echo ($currentSession && $session['id'] == $currentSession['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($session['session_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 8px;">Term *</label>
                        <select name="term_id" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <option value="">Select Term...</option>
                            <?php foreach ($terms as $term): ?>
                                <option value="<?php echo $term['id']; ?>" <?php echo ($currentTerm && $term['id'] == $currentTerm['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($term['term_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Fee Type *</label>
                    <select name="fee_structure_id" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <option value="">Select Fee...</option>
                        <?php if (!empty($feeStructures)): ?>
                            <?php foreach ($feeStructures as $fee): ?>
                                <option value="<?php echo htmlspecialchars($fee['id']); ?>">
                                    <?php echo htmlspecialchars($fee['fee_type']); ?> - ₦<?php echo number_format($fee['amount'], 2); ?>
                                    (<?php echo htmlspecialchars($fee['term_name']); ?>, <?php echo htmlspecialchars($fee['session_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback or manual entry options if needed, though they won't work with foreign key constraint without ID -->
                            <option value="" disabled>No fee structures found for this class</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                     <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 8px;">Payment Method *</label>
                        <select name="payment_method" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <option value="Cash">Cash</option>
                            <option value="POS">Card / POS</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 8px;">Amount Paid (₦) *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Remarks / Transaction Ref</label>
                    <textarea name="remarks" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;" rows="2"></textarea>
                </div>

                <button type="submit" name="process_payment" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px;" <?php echo !$selectedStudent ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i> Complete Payment
                </button>
                
                <?php if (!$selectedStudent): ?>
                    <p style="text-align: center; color: #dc2626; margin-top: 10px; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> Please search and select a student first
                    </p>
                <?php endif; ?>
            </form>

        </div>

        <!-- Right Side: Summary / Recent -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Fee Summary Card -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">Fee Summary</h3>
                
                <?php if ($selectedStudent): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #6b7280;">Total Due:</span>
                        <span style="font-weight: 600;">₦<?php echo number_format($totalDue, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="color: #6b7280;">Already Paid:</span>
                        <span style="color: #10b981; font-weight: 600;">₦<?php echo number_format($totalPaid, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
                        <span style="font-weight: 600;">Balance:</span>
                        <span style="color: <?php echo $balance > 0 ? '#dc2626' : '#10b981'; ?>; font-weight: 700;">₦<?php echo number_format($balance, 2); ?></span>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #9ca3af; padding: 20px 0;">
                        <i class="fas fa-search" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                        Select a student to view fee summary
                    </p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">Need Help?</h4>
                <p style="font-size: 14px; margin-bottom: 15px; opacity: 0.9;">Verify transaction references before confirming bank transfers.</p>
                <a href="fees.php" class="btn" style="background: rgba(255,255,255,0.2); color: white; width: 100%; border: none; display: block; text-align: center; padding: 10px; text-decoration: none; border-radius: 6px;">
                    View Fee Structures
                </a>
            </div>

        </div>

    </div>

</div>

<style>
a[href*="student_id"]:hover {
    background-color: #f9fafb;
}
</style>

<?php include '../includes/footer.php'; ?>
