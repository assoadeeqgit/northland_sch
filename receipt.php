<?php
require_once 'auth-check.php';
// checkAuth('admin'); // Optional: restrict access

require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$payment_id = $_GET['id'] ?? 0;
$payment = null;

if ($payment_id > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.*,
                s.admission_number,
                u.first_name, u.last_name,
                c.class_name,
                fs.fee_type,
                asess.session_name,
                t.term_name
            FROM payments p
            JOIN students s ON p.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN fee_structure fs ON p.fee_structure_id = fs.id
            LEFT JOIN academic_sessions asess ON p.academic_session_id = asess.id
            LEFT JOIN terms t ON p.term_id = t.id
            WHERE p.id = ?
        ");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching receipt: " . $e->getMessage());
    }
}

if (!$payment) {
    die("Receipt not found or invalid ID.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #REC-<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #eee;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            width: 76mm; /* slightly less than 80mm to prevent clipping */
            margin: 0 auto;
            background: white;
            padding: 3mm; /* Reduced padding */
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            box-sizing: border-box; /* Ensure padding is included in width */
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #1a237e; /* Navy */
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .logo {
            max-width: 60px;
            margin-bottom: 5px;
        }
        .school-info h1 {
            color: #1a237e; /* Navy */
            margin: 5px 0 0;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .school-info p {
            margin: 2px 0;
            font-size: 9px;
            color: #555;
        }
        .receipt-details {
            text-align: center;
            margin-top: 10px;
        }
        .receipt-details h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #f59e0b; /* Orange */
            text-transform: uppercase;
            border: 1px solid #f59e0b;
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .info-grid {
            display: block;
            margin-bottom: 15px;
        }
        .info-group {
            margin-bottom: 8px;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 5px;
        }
        .info-group label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2px;
            color: #1a237e;
        }
        .info-group div {
            font-weight: 600;
            font-size: 12px;
            word-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        th {
            background: #f0f0f0;
            padding: 5px 0;
            text-align: left;
            border-bottom: 1px solid #000;
            font-weight: 700;
            color: #1a237e;
        }
        td {
            padding: 5px 0;
            border-bottom: 1px dotted #000;
            vertical-align: top;
        }
        .amount-col {
            text-align: right;
        }
        .total-section {
            margin-top: 10px;
            border-top: 2px solid #1a237e;
            padding-top: 5px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 12px;
        }
        .grand-total {
            font-weight: 800;
            font-size: 16px;
            margin-top: 5px;
            border-top: 1px dashed #000;
            padding-top: 5px;
            color: #1a237e;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #444;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #1a237e;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            font-size: 12px;
        }
        .btn-print:hover {
            background: #000051;
        }
        
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body { 
                background: white; 
                padding: 0; 
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .receipt-container { 
                width: 100%;
                box-shadow: none; 
                padding: 2mm;
                margin: 0;
                border: none;
            }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="school-info">
                <img src="assets/images/logo.jpeg" alt="Logo" class="logo">
                <h1>Northland Schools Kano</h1>
                <p>123 Education Lane, Kano State</p>
                <p>Tel: +234 800 123 4567</p>
            </div>
            <div class="receipt-details">
                <h2>PAYMENT RECEIPT</h2>
                <p style="font-weight: 500; font-size: 11px;">Date: <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></p>
                <p style="font-size: 10px; color: #6b7280;">Transaction ID: #<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-group">
                <label>Received From</label>
                <div style="font-size: 18px; color: #111;"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                <div style="font-weight: 500; font-size: 14px; margin-top: 4px; color: #4b5563;">
                    Admission No: <?php echo htmlspecialchars($payment['admission_number']); ?>
                </div>
                <div style="font-weight: 500; font-size: 14px; margin-top: 2px; color: #4b5563;">
                    Class: <?php echo htmlspecialchars($payment['class_name'] ?? 'N/A'); ?>
                </div>
            </div>
            <div class="info-group">
                <label>Payment Method</label>
                <div style="text-transform: capitalize;"><?php echo htmlspecialchars($payment['payment_method']); ?></div>
                <?php if (!empty($payment['remarks'])): ?>
                    <div style="font-weight: 400; font-size: 13px; margin-top: 5px; color: #6b7280;">
                        Ref/Note: <?php echo htmlspecialchars($payment['remarks']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Term / Session</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($payment['fee_type'] ?? 'School Fee'); ?></td>
                    <td><?php echo htmlspecialchars($payment['term_name']); ?> - <?php echo htmlspecialchars($payment['session_name']); ?></td>
                    <td class="amount-col">₦<?php echo number_format($payment['amount_paid'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₦<?php echo number_format($payment['amount_paid'], 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total Paid:</span>
                    <span>₦<?php echo number_format($payment['amount_paid'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business.</p>
            <p>Generated by School Financial Management System on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>

    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button onclick="window.print()" class="btn-print" style="flex: 1; background: #333;">Print</button>
        <button onclick="downloadPDF()" class="btn-print" style="flex: 1; background: #1a237e;">Download PDF</button>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.receipt-container');
            const opt = {
                margin:       0,
                filename:     'Receipt_#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: [80, 200], orientation: 'portrait' } // Auto-height not perfectly supported in strict pages, setting 200 as estimation or letting it auto-page
            };

            // Enhanced settings for long receipts if needed
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
