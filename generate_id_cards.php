<?php
session_start();
require_once './config/database.php';

// Get parameters
$all = isset($_GET['all']) ? true : false;
$studentIds = isset($_GET['students']) ? explode(',', $_GET['students']) : [];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Build query based on parameters
    if ($all) {
        $sql = "
            SELECT 
                u.first_name,
                u.last_name,
                u.date_of_birth,
                u.gender,
                s.student_id,
                s.admission_number,
                c.class_name,
                c.class_code,
                s.admission_date
            FROM users u
            INNER JOIN students s ON u.id = s.user_id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE u.user_type = 'student' AND u.is_active = 1
            ORDER BY c.id, u.first_name
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
    } else {
        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        $sql = "
            SELECT 
                u.first_name,
                u.last_name,
                u.date_of_birth,
                u.gender,
                s.student_id,
                s.admission_number,
                c.class_name,
                c.class_code,
                s.admission_date
            FROM users u
            INNER JOIN students s ON u.id = s.user_id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.student_id IN ($placeholders)
            ORDER BY c.id, u.first_name
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($studentIds);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        die("No students found");
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

function getAvatarColor($name) {
    $colors = [
        '#1e40af', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
    ];
    $index = ord($name[0]) % count($colors);
    return $colors[$index];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Cards - Batch Print</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body {
            font-family: 'Montserrat', sans-serif;
        }
        
        .id-card {
            width: 350px;
            height: 550px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin: 20px;
            display: inline-block;
            vertical-align: top;
        }
        
        .id-card-header {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .student-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            margin: 20px auto;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .barcode {
            background: repeating-linear-gradient(
                90deg,
                #000 0px,
                #000 2px,
                #fff 2px,
                #fff 4px
            );
            height: 50px;
            margin: 10px 0;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .id-card {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
                page-break-after: always;
                margin: 10mm;
            }
            .id-card:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <!-- Print Button -->
    <div class="text-center mb-6 no-print">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">ID Cards - Ready to Print</h1>
        <p class="text-gray-600 mb-4">Total: <?= count($students) ?> student(s)</p>
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition mr-4">
            <i class="fas fa-print mr-2"></i>Print All Cards
        </button>
        <button onclick="window.close()" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition">
            <i class="fas fa-times mr-2"></i>Close
        </button>
    </div>
    
    <!-- ID Cards -->
    <div class="text-center">
        <?php foreach ($students as $student): ?>
        <div class="id-card">
            <!-- Header -->
            <div class="id-card-header">
                <h1 class="text-xl font-bold">NORTHLAND SCHOOLS KANO</h1>
                <p class="text-sm mt-1">STUDENT IDENTIFICATION CARD</p>
            </div>
            
            <!-- Student Photo -->
            <div class="student-photo" style="background: <?= getAvatarColor($student['first_name']) ?>">
                <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
            </div>
            
            <!-- Student Details -->
            <div class="px-6 py-4">
                <h2 class="text-xl font-bold text-center text-gray-800 mb-4">
                    <?= htmlspecialchars(strtoupper($student['first_name'] . ' ' . $student['last_name'])) ?>
                </h2>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Student ID:</span>
                        <span class="font-bold text-blue-900"><?= $student['student_id'] ?></span>
                    </div>
                    
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Admission No:</span>
                        <span class="font-bold text-blue-900"><?= $student['admission_number'] ?></span>
                    </div>
                    
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Class:</span>
                        <span class="font-bold text-blue-900"><?= htmlspecialchars($student['class_name']) ?></span>
                    </div>
                    
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Gender:</span>
                        <span class="font-bold text-blue-900"><?= $student['gender'] ?></span>
                    </div>
                    
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Date of Birth:</span>
                        <span class="font-bold text-blue-900"><?= date('d/m/Y', strtotime($student['date_of_birth'])) ?></span>
                    </div>
                </div>
                
                <!-- Barcode -->
                <div class="barcode mt-6"></div>
                <p class="text-center text-xs text-gray-600 mt-2"><?= $student['student_id'] ?></p>
            </div>
            
            <!-- Footer -->
            <div class="bg-blue-900 text-white text-center py-3 text-xs">
                <p class="font-semibold">Valid for Academic Session 2024/2025</p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>