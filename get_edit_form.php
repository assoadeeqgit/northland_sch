<?php
session_start();
require_once './config/database.php';

if (isset($_GET['student_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get student data
    $stmt = $db->prepare("
        SELECT 
            u.first_name, u.last_name, u.email, u.phone, u.date_of_birth, u.gender,
            s.student_id, s.admission_number, s.class_id, s.admission_date, s.religion, 
            s.nationality, s.state_of_origin, s.lga, s.medical_conditions, 
            s.emergency_contact_name, s.emergency_contact_phone
        FROM users u
        INNER JOIN students s ON u.id = s.user_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$_GET['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get classes
    $classStmt = $db->prepare("SELECT id, class_name FROM classes ORDER BY id");
    $classStmt->execute();
    $classes = $classStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo '
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">First Name</label>
                <input type="text" name="first_name" value="' . $student['first_name'] . '" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                <input type="text" name="last_name" value="' . $student['last_name'] . '" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="' . $student['email'] . '" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="tel" name="phone" value="' . $student['phone'] . '" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                <input type="date" name="date_of_birth" value="' . $student['date_of_birth'] . '" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Gender</label>
                <select name="gender" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="Male" ' . ($student['gender'] == 'Male' ? 'selected' : '') . '>Male</option>
                    <option value="Female" ' . ($student['gender'] == 'Female' ? 'selected' : '') . '>Female</option>
                    <option value="Other" ' . ($student['gender'] == 'Other' ? 'selected' : '') . '>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Class</label>
                <select name="class_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">';
                
                foreach ($classes as $class) {
                    $selected = $class['id'] == $student['class_id'] ? 'selected' : '';
                    echo '<option value="' . $class['id'] . '" ' . $selected . '>' . $class['class_name'] . '</option>';
                }
                
                echo '</select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Admission Date</label>
                <input type="date" name="admission_date" value="' . $student['admission_date'] . '" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Religion</label>
                <select name="religion" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="Islam" ' . ($student['religion'] == 'Islam' ? 'selected' : '') . '>Islam</option>
                    <option value="Christianity" ' . ($student['religion'] == 'Christianity' ? 'selected' : '') . '>Christianity</option>
                    <option value="Other" ' . ($student['religion'] == 'Other' ? 'selected' : '') . '>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nationality</label>
                <input type="text" name="nationality" value="' . $student['nationality'] . '" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">State of Origin</label>
                <input type="text" name="state_of_origin" value="' . $student['state_of_origin'] . '" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">LGA</label>
                <input type="text" name="lga" value="' . $student['lga'] . '" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Medical Conditions</label>
                <textarea name="medical_conditions" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">' . $student['medical_conditions'] . '</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" value="' . $student['emergency_contact_name'] . '" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Emergency Contact Phone</label>
                <input type="tel" name="emergency_contact_phone" value="' . $student['emergency_contact_phone'] . '" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
        </div>';
    } else {
        echo '<p class="text-red-600">Student not found.</p>';
    }
}
?>