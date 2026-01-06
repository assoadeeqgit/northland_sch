<?php
require_once '../auth-check.php';
checkAuth('accountant'); // Finance management is for accountants only

require_once '../config/DatabaseManager.php';
$dbManager = DatabaseManager::getInstance();
$conn = $dbManager->getConnection();

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

// Receipt formatting logic
$year = date('y', strtotime($payment['payment_date']));
$paddedId = str_pad($payment['id'], 6, '0', STR_PAD_LEFT);
$suffix = strtoupper(substr(md5($payment['id'] . 'nsk'), 0, 2));
$fullReceiptId = "NSK/PAY/$year/$paddedId/ $suffix";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $fullReceiptId; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #525659; /* Darker background for contrast */
            color: #000;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .main-wrapper {
            width: 80mm; /* Standard thermal paper width */
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .receipt-container {
            width: 100%;
            background: white;
            padding: 4mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            box-sizing: border-box;
            border-radius: 4px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #1a237e;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .logo {
            max-width: 60px;
            margin-bottom: 8px;
        }
        .school-info h1 {
            color: #1a237e;
            margin: 5px 0 0;
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .school-info p {
            margin: 2px 0;
            font-size: 10px;
            color: #555;
        }
        .receipt-details {
            text-align: center;
            margin-top: 15px;
        }
        .receipt-label {
            margin: 5px 0;
            font-size: 14px;
            font-weight: 700;
            color: #1a237e; /* Changed from orange to navy for better print contrast */
            text-transform: uppercase;
            border: 2px solid #1a237e; /* Changed to navy */
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        /* Override for screen to keep orange if desired, but navy is safer for print */
        /* .receipt-label { color: #f59e0b; border-color: #f59e0b; } */
        
        .info-grid {
            display: block;
            margin-bottom: 20px;
        }
        .info-group {
            margin-bottom: 12px;
            border-bottom: 1px dotted #e5e7eb;
            padding-bottom: 8px;
        }
        .info-group:last-child {
            border-bottom: none;
        }
        .info-group label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 3px;
            color: #6b7280;
            letter-spacing: 0.5px;
        }
        .info-group div {
            font-weight: 600;
            font-size: 13px;
            color: #111827;
            word-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        th {
            background: #f3f4f6;
            padding: 8px 4px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 700;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 8px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            color: #1f2937;
        }
        .amount-col {
            text-align: right;
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }
        .total-section {
            margin-top: 15px;
            border-top: 2px solid #1a237e;
            padding-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 13px;
            color: #374151;
        }
        .grand-total {
            font-weight: 800;
            font-size: 18px;
            margin-top: 8px;
            padding-top: 8px;
            color: #1a237e;
            border-top: 1px dashed #d1d5db;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        /* Action Buttons Container */
        .actions-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            width: 100%; /* Matches main-wrapper width */
        }
        
        .btn-action {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            border: none;
            transition: opacity 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-action:hover {
            opacity: 0.9;
        }
        .btn-print { background: #1a237e; }
        .btn-download { background: #059669; }

        @media print {
            @page {
                margin: 0;
            }
            html, body {
                height: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                background: white;
                display: block;
            }
            .main-wrapper {
                width: 100%;
                max-width: 80mm; /* Ensure it stays receipt-sized even on A4 */
                margin: 0 auto; /* Center on the page */
                padding: 0;
            }
            .receipt-container { 
                width: 100%;
                box-shadow: none; 
                padding: 2mm; 
                margin: 0;
                border: none;
                border-radius: 0;
            }
            .actions-container { display: none !important; }
            
            /* Force colors for print */
            .school-info h1, .receipt-label, .grand-total, .header {
                color: #000 !important;
                border-color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            th {
                background-color: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="receipt-container" id="receipt">
            <div class="header">
                <div class="school-info">
                    <img src="../assets/images/logo.jpeg" alt="Logo" class="logo">
                    <h1>Northland Schools Kano</h1>
                    <p>123 Education Lane, Kano State</p>
                    <p>Tel: +234 800 123 4567</p>
                </div>
                <div class="receipt-details">
                    <div class="receipt-label">PAYMENT RECEIPT</div>
                    <p style="font-weight: 500; font-size: 11px; margin: 8px 0 2px;">Date: <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></p>
                    <p style="font-size: 10px; color: #6b7280; margin: 0;">Transaction ID: <?php echo $fullReceiptId; ?></p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-group">
                    <label>Received From</label>
                    <div style="font-size: 16px;"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                    <div style="font-size: 13px; margin-top: 2px; color: #4b5563;">
                        <?php echo htmlspecialchars($payment['admission_number']); ?> | <?php echo htmlspecialchars($payment['class_name'] ?? 'N/A'); ?>
                    </div>
                </div>
                <div class="info-group">
                    <label>Payment Details</label>
                    <div style="text-transform: capitalize;"><?php echo htmlspecialchars($payment['payment_method']); ?> Payment</div>
                    <?php if (!empty($payment['remarks'])): ?>
                        <div style="font-weight: 400; font-size: 12px; margin-top: 2px; color: #6b7280; font-style: italic;">
                            "<?php echo htmlspecialchars($payment['remarks']); ?>"
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount-col">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding-right: 10px;">
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($payment['fee_type'] ?? 'School Fee'); ?></div>
                            <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">
                                <?php echo htmlspecialchars($payment['term_name']); ?> (<?php echo htmlspecialchars($payment['session_name']); ?>)
                            </div>
                        </td>
                        <td class="amount-col" style="vertical-align: middle;">₦<?php echo number_format($payment['amount_paid'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>₦<?php echo number_format($payment['amount_paid'], 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total Paid</span>
                    <span>₦<?php echo number_format($payment['amount_paid'], 2); ?></span>
                </div>
            </div>

            <div class="footer">
                <p>Thank you for your payment!</p>
                <p>Generated on <?php echo date('d/m/Y H:i A'); ?></p>
            </div>
        </div>

        <div class="actions-container">
            <button onclick="window.print()" class="btn-action btn-print">Print Receipt</button>
            <button onclick="downloadPDF()" class="btn-action btn-download">Download PDF</button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('receipt');
            const opt = {
                margin:       0,
                filename:     'Receipt_<?php echo $payment['admission_number']; ?>_<?php echo $year . $paddedId . $suffix; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 3, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: [80, 200], orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
