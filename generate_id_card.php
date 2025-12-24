<?php
session_start();
require_once './config/database.php';

// Get student ID from URL
$student_id = $_GET['student_id'] ?? '';

if (empty($student_id)) {
    die("No student ID provided");
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get student data
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
            s.admission_date,
            sch.name as school_name,
            sch.address as school_address,
            sch.phone as school_phone
        FROM users u
        INNER JOIN students s ON u.id = s.user_id
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN schools sch ON sch.id = 1
        WHERE s.student_id = ?
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        die("Student not found");
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

function getAvatarColor($name) {
    $colors = [
        '#1e40af', // nskblue
        '#10b981', // nskgreen
        '#f59e0b', // nskgold
        '#ef4444', // nskred
        '#8b5cf6', // purple
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
    <title>ID Card - <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></title>
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
            background: <?= getAvatarColor($student['first_name']) ?>;
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
                padding: 20px;
            }
            .no-print {
                display: none;
            }
            .id-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="text-center">
        <div class="id-card mx-auto">
            <!-- Header -->
            <div class="id-card-header">
                <h1 class="text-xl font-bold">NORTHLAND SCHOOLS KANO</h1>
                <p class="text-sm mt-1">STUDENT IDENTIFICATION CARD</p>
            </div>
            
            <!-- Student Photo -->
            <div class="student-photo">
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
        
        <!-- Print Button -->
        <div class="mt-6 no-print space-x-4">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-print mr-2"></i>Print ID Card
            </button>
            <button onclick="window.close()" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition">
                <i class="fas fa-times mr-2"></i>Close
            </button>
        </div>
    </div>
</body>
</html>