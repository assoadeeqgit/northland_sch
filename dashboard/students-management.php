<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// session_start();

require_once '../config/logger.php';

require_once 'auth-check.php';

// For admin dashboard:
checkAuth('admin');

// Initialize variables
$totalStudents = $totalTeachers = $totalClasses = 0;
$studentsData = [];
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));


// Database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Case conversion functions
function formatName($name)
{
    if (empty($name))
        return $name;

    // Convert to lowercase first, then proper case to handle mixed case
    $name = trim($name);
    $name = ucwords(strtolower($name));

    // Handle names with apostrophes (O'Neil, D'Angelo, etc.)
    $name = preg_replace_callback('/\'(.)/', function ($matches) {
        return "'" . strtoupper($matches[1]);
    }, $name);

    // Handle names with hyphens (Jean-Claude, Mary-Jane, etc.)
    $name = preg_replace_callback('/-(.)/', function ($matches) {
        return "-" . strtoupper($matches[1]);
    }, $name);

    // Handle common prefixes (Mc, Mac, Van, De, Di, Le, La, etc.)
    $prefixes = ['Mc', 'Mac', 'Van', 'De', 'Di', 'Le', 'La'];
    foreach ($prefixes as $prefix) {
        $name = preg_replace_callback("/\b{$prefix}(\w)/i", function ($matches) use ($prefix) {
            return $prefix . strtoupper($matches[1]);
        }, $name);
    }

    return $name;
}


function formatLocation($location)
{
    if (empty($location))
        return $location;

    $location = trim($location);

    // Special cases for common locations
    $specialCases = [
        'lga' => 'LGA',
        'usa' => 'USA',
        'uk' => 'UK',
        'uae' => 'UAE'
    ];

    $lowerLocation = strtolower($location);
    if (isset($specialCases[$lowerLocation])) {
        return $specialCases[$lowerLocation];
    }

    // Convert to proper case
    $location = ucwords(strtolower($location));

    return $location;
}

function formatReligion($religion)
{
    if (empty($religion))
        return $religion;

    $religion = trim($religion);

    // Standardize common religions
    $religions = [
        'islam' => 'Islam',
        'christianity' => 'Christianity',
        'christian' => 'Christianity',
        'catholic' => 'Catholicism',
        'catholicism' => 'Catholicism',
        'protestant' => 'Protestant',
        'traditional' => 'Traditional',
        'other' => 'Other'
    ];

    $lowerReligion = strtolower($religion);
    if (isset($religions[$lowerReligion])) {
        return $religions[$lowerReligion];
    }

    return ucwords(strtolower($religion));
}

function formatNationality($nationality)
{
    if (empty($nationality))
        return $nationality;

    $nationality = trim($nationality);

    // Standardize common nationalities
    $nationalities = [
        'nigerian' => 'Nigerian',
        'nigeria' => 'Nigerian',
        'ghanaian' => 'Ghanaian',
        'ghana' => 'Ghanaian',
        'british' => 'British',
        'american' => 'American',
        'canadian' => 'Canadian'
    ];

    $lowerNationality = strtolower($nationality);
    if (isset($nationalities[$lowerNationality])) {
        return $nationalities[$lowerNationality];
    }

    return ucwords(strtolower($nationality));
}

function formatMedicalCondition($condition)
{
    if (empty($condition))
        return $condition;

    $condition = trim($condition);

    // Common medical conditions that should be properly capitalized
    $commonConditions = [
        'asthma' => 'Asthma',
        'diabetes' => 'Diabetes',
        'hypertension' => 'Hypertension',
        'allergy' => 'Allergy',
        'epilepsy' => 'Epilepsy',
        'anemia' => 'Anemia'
    ];

    $lowerCondition = strtolower($condition);
    if (isset($commonConditions[$lowerCondition])) {
        return $commonConditions[$lowerCondition];
    }

    // For longer descriptions, only capitalize first letter of each sentence
    if (strlen($condition) > 20) {
        return ucfirst(strtolower($condition));
    }

    return ucwords(strtolower($condition));
}

// Get filter parameters - MOVED BEFORE POST HANDLING
// Get filter parameters - MOVED BEFORE POST HANDLING
$searchQuery = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
$classFilter = isset($_REQUEST['class_filter']) ? intval($_REQUEST['class_filter']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        addStudent($db);
    }
    // Handle hide form request
    if (isset($_POST['hide_add_form'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Handle CSV Export with Filters
    if (isset($_POST['export_csv'])) {
        try {
            // Clear any previous output to prevent file corruption
            if (ob_get_level())
                ob_end_clean();

            // Set headers for CSV download
            $filename = "students_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Create file pointer connected to output stream
            $output = fopen('php://output', 'w');

            // Add BOM for proper UTF-8 encoding in Excel
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV headers
            $headers = [
                'Student ID',
                'Admission Number',
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Date of Birth',
                'Gender',
                'Class',
                'Class Code',
                'Admission Date',
                'Religion',
                'Nationality',
                'State of Origin',
                'LGA',
                'Medical Conditions',
                'Emergency Contact Name',
                'Emergency Contact Phone'
            ];

            fputcsv($output, $headers);

            // Build the same WHERE clause as the main query to respect filters
            $whereConditions = ["u.user_type = 'student'", "u.is_active = 1", "s.status = 'active'"];
            $params = [];

            // Apply search filter if present
            if (!empty($searchQuery)) {
                $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR s.student_id LIKE ? OR s.admission_number LIKE ?)";
                $searchParam = "%{$searchQuery}%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
            }

            // Apply class filter if present
            if (!empty($classFilter)) {
                $whereConditions[] = "s.class_id = ?";
                $params[] = $classFilter;
            }

            $whereClause = implode(" AND ", $whereConditions);

            // Get filtered students data
            $exportSql = "
            SELECT 
                s.student_id,
                s.admission_number,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.date_of_birth,
                u.gender,
                c.class_name,
                c.class_code,
                s.admission_date,
                s.religion,
                s.nationality,
                s.state_of_origin,
                s.lga,
                s.medical_conditions,
                s.emergency_contact_name,
                s.emergency_contact_phone
            FROM users u
            INNER JOIN students s ON u.id = s.user_id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE {$whereClause}
            ORDER BY s.class_id, u.first_name
        ";

            $stmt = $db->prepare($exportSql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add data rows
            foreach ($students as $student) {
                $row = [
                    $student['student_id'],
                    $student['admission_number'],
                    $student['first_name'],
                    $student['last_name'],
                    $student['email'],
                    $student['phone'] ?? '',
                    $student['date_of_birth'],
                    $student['gender'],
                    $student['class_name'] ?? 'Not Assigned',
                    $student['class_code'] ?? '',
                    $student['admission_date'],
                    $student['religion'] ?? '',
                    $student['nationality'] ?? '',
                    $student['state_of_origin'] ?? '',
                    $student['lga'] ?? '',
                    $student['medical_conditions'] ?? '',
                    $student['emergency_contact_name'] ?? '',
                    $student['emergency_contact_phone'] ?? ''
                ];
                fputcsv($output, $row);
            }

            fclose($output);

            // Log export activity
            $admin_name = $_SESSION['user_name'] ?? 'Admin';
            $filterInfo = "";
            if (!empty($searchQuery))
                $filterInfo .= " Search: " . $searchQuery;
            if (!empty($classFilter))
                $filterInfo .= " Class ID: " . $classFilter;

            logActivity(
                $db,
                $admin_name,
                "Export Students",
                "Exported " . count($students) . " students to CSV" . $filterInfo,
                "fas fa-file-export",
                "bg-purple-600"
            );

            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Export failed: " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Handle CSV Import - IMPROVED VERSION (KEEP ONLY THIS ONE)
    if (isset($_POST['import_students'])) {
        try {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please select a valid CSV file.");
            }

            $file = $_FILES['csv_file'];

            // Validate file type
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileType !== 'csv') {
                throw new Exception("Only CSV files are allowed.");
            }

            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size must be less than 5MB.");
            }

            // Process CSV file
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                throw new Exception("Unable to open the uploaded file.");
            }

            try {
                // Read header row
                $headers = fgetcsv($handle);
                if (!$headers) {
                    throw new Exception("Empty CSV file or invalid format.");
                }

                // Clean headers - remove BOM and trim whitespace
                $headers = array_map(function ($header) {
                    // Remove UTF-8 BOM if present
                    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
                    return trim($header);
                }, $headers);

                // Debug: Log headers for troubleshooting
                error_log("CSV Headers found: " . implode(', ', $headers));

                // Create a flexible header mapping system
                $headerMap = [];
                $requiredFields = ['first_name', 'last_name', 'email'];

                // Map all possible field names
                $fieldMappings = [
                    'first_name' => ['first_name', 'firstname', 'fname', 'first name'],
                    'last_name' => ['last_name', 'lastname', 'lname', 'last name', 'surname'],
                    'email' => ['email', 'email_address', 'email address'],
                    'phone' => ['phone', 'phone_number', 'telephone', 'mobile', 'phone number'],
                    'date_of_birth' => ['date_of_birth', 'dob', 'birthdate', 'birth_date', 'date of birth'],
                    'gender' => ['gender', 'sex'],
                    'class_id' => ['class_id', 'class', 'classid', 'grade', 'class id'],
                    'religion' => ['religion', 'faith'],
                    'nationality' => ['nationality', 'country'],
                    'state_of_origin' => ['state_of_origin', 'state', 'state origin', 'state of origin'],
                    'lga' => ['lga', 'local_government', 'local government', 'local government area'],
                    'medical_conditions' => ['medical_conditions', 'medical', 'health_conditions', 'medical conditions'],
                    'emergency_contact_name' => ['emergency_contact_name', 'emergency_contact', 'emergency contact', 'emergency name'],
                    'emergency_contact_phone' => ['emergency_contact_phone', 'emergency_phone', 'emergency contact phone']
                ];

                // Map headers to field names
                foreach ($headers as $index => $header) {
                    $cleanHeader = strtolower(trim($header));

                    foreach ($fieldMappings as $field => $possibleNames) {
                        if (in_array($cleanHeader, $possibleNames)) {
                            $headerMap[$field] = $index;
                            break;
                        }
                    }
                }

                // Check for required fields
                $missingHeaders = [];
                foreach ($requiredFields as $required) {
                    if (!isset($headerMap[$required])) {
                        $missingHeaders[] = $required;
                    }
                }

                if (!empty($missingHeaders)) {
                    throw new Exception("Missing required columns: " . implode(', ', $missingHeaders) .
                        ". Found columns: " . implode(', ', $headers));
                }

                // Start transaction
                $db->beginTransaction();

                $importCount = 0;
                $errorRows = [];
                $rowNumber = 1; // Start from 1 for header row
                $skippedRows = 0;

                // Process data rows
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $rowNumber++;

                    // Skip empty rows or rows with mostly empty data
                    if (empty($data) || count(array_filter($data)) < 2) {
                        $skippedRows++;
                        continue;
                    }

                    // Skip comment/instruction rows
                    $rowContent = implode('', $data);
                    if (
                        stripos($rowContent, 'required') !== false ||
                        stripos($rowContent, 'optional') !== false ||
                        stripos($rowContent, '===') !== false ||
                        stripos($rowContent, 'first_name') !== false
                    ) {
                        $skippedRows++;
                        continue;
                    }

                    try {
                        // Map data using header map
                        $rowData = [];
                        foreach ($headerMap as $field => $index) {
                            $rowData[$field] = isset($data[$index]) ? trim($data[$index]) : '';
                        }

                        // === APPLY CASE FORMATTING HERE ===
                        // Apply case formatting to relevant fields
                        if (!empty($rowData['first_name'])) {
                            $rowData['first_name'] = formatName($rowData['first_name']);
                        }
                        if (!empty($rowData['last_name'])) {
                            $rowData['last_name'] = formatName($rowData['last_name']);
                        }
                        if (!empty($rowData['state_of_origin'])) {
                            $rowData['state_of_origin'] = formatLocation($rowData['state_of_origin']);
                        }
                        if (!empty($rowData['lga'])) {
                            $rowData['lga'] = formatLocation($rowData['lga']);
                        }
                        if (!empty($rowData['religion'])) {
                            $rowData['religion'] = formatReligion($rowData['religion']);
                        }
                        if (!empty($rowData['nationality'])) {
                            $rowData['nationality'] = formatNationality($rowData['nationality']);
                        }
                        if (!empty($rowData['medical_conditions'])) {
                            $rowData['medical_conditions'] = formatMedicalCondition($rowData['medical_conditions']);
                        }
                        if (!empty($rowData['emergency_contact_name'])) {
                            $rowData['emergency_contact_name'] = formatName($rowData['emergency_contact_name']);
                        }
                        // === END CASE FORMATTING ===

                        // Debug: Log row data for troubleshooting
                        error_log("Row $rowNumber data: " . json_encode($rowData));

                        // Validate required fields with better error messages
                        if (empty($rowData['first_name'])) {
                            throw new Exception("First name is required");
                        }
                        if (empty($rowData['last_name'])) {
                            throw new Exception("Last name is required");
                        }
                        if (empty($rowData['email'])) {
                            throw new Exception("Email is required");
                        }

                        // Validate email format
                        if (!filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Invalid email format: " . $rowData['email']);
                        }

                        // Handle date_of_birth - be more flexible with formats
                        if (!empty($rowData['date_of_birth'])) {
                            $dateStr = $rowData['date_of_birth'];
                            // Try different date formats
                            $dateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
                            $validDate = false;

                            foreach ($dateFormats as $format) {
                                $dateObj = DateTime::createFromFormat($format, $dateStr);
                                if ($dateObj && $dateObj->format($format) === $dateStr) {
                                    $rowData['date_of_birth'] = $dateObj->format('Y-m-d');
                                    $validDate = true;
                                    break;
                                }
                            }

                            if (!$validDate) {
                                throw new Exception("Invalid date format for date_of_birth: " . $dateStr . ". Use YYYY-MM-DD format.");
                            }
                        } else {
                            $rowData['date_of_birth'] = '2000-01-01'; // Default date if not provided
                        }

                        // Handle gender - be more flexible
                        if (!empty($rowData['gender'])) {
                            $gender = strtolower(trim($rowData['gender']));
                            if (in_array($gender, ['male', 'm', 'boy'])) {
                                $rowData['gender'] = 'Male';
                            } elseif (in_array($gender, ['female', 'f', 'girl'])) {
                                $rowData['gender'] = 'Female';
                            } else {
                                $rowData['gender'] = 'Male'; // Default if not recognized
                            }
                        } else {
                            $rowData['gender'] = 'Male'; // Default if not provided
                        }

                        // Check if email already exists
                        $emailCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                        $emailCheck->execute([$rowData['email']]);
                        if ($emailCheck->fetchColumn() > 0) {
                            throw new Exception("Email already exists: " . $rowData['email']);
                        }

                        // Generate unique student IDs
                        $studentId = 'STU' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                        $admissionNumber = 'ADM' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

                        // Ensure unique student_id
                        $maxAttempts = 10;
                        $attempts = 0;
                        do {
                            $checkStmt = $db->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
                            $checkStmt->execute([$studentId]);
                            $attempts++;
                            if ($checkStmt->fetchColumn() > 0 && $attempts < $maxAttempts) {
                                $studentId = 'STU' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                            } else if ($attempts >= $maxAttempts) {
                                throw new Exception("Unable to generate unique student ID");
                            }
                        } while ($checkStmt->fetchColumn() > 0 && $attempts < $maxAttempts);

                        // Generate username
                        $baseUsername = strtolower($rowData['first_name']) . '.' . strtolower($rowData['last_name']);
                        $username = $baseUsername;
                        $usernameCheckStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                        $usernameCheckStmt->execute([$username]);
                        $usernameCount = $usernameCheckStmt->fetchColumn();

                        if ($usernameCount > 0) {
                            $username = $baseUsername . rand(1, 999);
                        }

                        // Insert into users table
                        $userSql = "INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone, date_of_birth, gender, is_active) 
            VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, 1)";
                        $userStmt = $db->prepare($userSql);

                        $password_hash = password_hash('password123', PASSWORD_DEFAULT);

                        $userStmt->execute([
                            $username,
                            $rowData['email'],
                            $password_hash,
                            $rowData['first_name'],
                            $rowData['last_name'],
                            !empty($rowData['phone']) ? $rowData['phone'] : null,
                            $rowData['date_of_birth'],
                            $rowData['gender']
                        ]);

                        $userId = $db->lastInsertId();

                        // Insert into students table
                        $studentSql = "INSERT INTO students (user_id, student_id, admission_number, class_id, admission_date, religion, nationality, state_of_origin, lga, medical_conditions, emergency_contact_name, emergency_contact_phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $studentStmt = $db->prepare($studentSql);
                        $studentStmt->execute([
                            $userId,
                            $studentId,
                            $admissionNumber,
                            !empty($rowData['class_id']) ? intval($rowData['class_id']) : 1,
                            date('Y-m-d'),
                            !empty($rowData['religion']) ? $rowData['religion'] : 'Islam',
                            !empty($rowData['nationality']) ? $rowData['nationality'] : 'Nigerian',
                            !empty($rowData['state_of_origin']) ? $rowData['state_of_origin'] : '',
                            !empty($rowData['lga']) ? $rowData['lga'] : '',
                            !empty($rowData['medical_conditions']) ? $rowData['medical_conditions'] : '',
                            !empty($rowData['emergency_contact_name']) ? $rowData['emergency_contact_name'] : '',
                            !empty($rowData['emergency_contact_phone']) ? $rowData['emergency_contact_phone'] : ''
                        ]);

                        $importCount++;
                        error_log("Successfully imported student: " . $rowData['first_name'] . " " . $rowData['last_name']);
                    } catch (Exception $e) {
                        $errorRows[] = "Row $rowNumber: " . $e->getMessage();
                        error_log("Import error row $rowNumber: " . $e->getMessage());
                        // Continue processing other rows instead of stopping
                    }
                }

                fclose($handle);

                // Commit or rollback based on results
                if ($importCount > 0) {
                    $db->commit();

                    // Log import activity
                    $admin_name = $_SESSION['user_name'] ?? 'Admin';
                    logActivity(
                        $db,
                        $admin_name,
                        "Bulk Import",
                        "Imported $importCount students via CSV",
                        "fas fa-file-import",
                        "bg-nskgreen"
                    );

                    $message = "Successfully imported $importCount student(s).";
                    if (!empty($errorRows)) {
                        $message .= " " . count($errorRows) . " row(s) had errors.";
                        $_SESSION['import_errors'] = $errorRows;
                    }
                    if ($skippedRows > 0) {
                        $message .= " $skippedRows row(s) were skipped.";
                    }
                    $_SESSION['success'] = $message;
                } else {
                    $db->rollBack();
                    if (!empty($errorRows)) {
                        $_SESSION['import_errors'] = $errorRows;
                        throw new Exception("No students were imported. All $rowNumber row(s) had errors. Check the error details below.");
                    } else {
                        throw new Exception("No valid student data found in the CSV file. Processed $rowNumber rows, but no valid student records found.");
                    }
                }
            } catch (Exception $e) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                throw $e;
            }
        } catch (Exception $e) {
            // Only rollback if transaction is active
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error'] = "Import failed: " . $e->getMessage();
            error_log("Import failed: " . $e->getMessage());
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Handle Template Download - FIXED VERSION
    if (isset($_POST['download_template'])) {
        try {
            // Set headers for CSV download
            $filename = "student_import_template_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Create file pointer connected to output stream
            $output = fopen('php://output', 'w');

            // Add BOM for proper UTF-8 encoding in Excel
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Add CSV headers - Clean version without merged fields
            $headers = [
                'first_name',           // REQUIRED
                'last_name',            // REQUIRED
                'email',                // REQUIRED
                'phone',                // OPTIONAL
                'date_of_birth',        // REQUIRED (format: YYYY-MM-DD)
                'gender',               // REQUIRED (Male/Female)
                'class_id',             // OPTIONAL (1, 2, 3, etc.)
                'religion',             // OPTIONAL (Islam/Christianity/Other)
                'nationality',          // OPTIONAL (Default: Nigerian)
                'state_of_origin',      // OPTIONAL
                'lga',                  // OPTIONAL
                'medical_conditions',   // OPTIONAL (Leave empty for no conditions)
                'emergency_contact_name',  // OPTIONAL
                'emergency_contact_phone'  // OPTIONAL
            ];

            fputcsv($output, $headers);

            // Add sample data rows - Use separate columns
            $sampleData1 = [
                'John',                 // first_name
                'Doe',                  // last_name
                'john.doe@example.com', // email
                '08012345678',          // phone
                '2010-05-15',          // date_of_birth
                'Male',                 // gender
                '1',                    // class_id
                'Islam',                // religion
                'Nigerian',             // nationality
                'Kano',                 // state_of_origin
                'Nassarawa',            // lga
                '',                     // medical_conditions - EMPTY for no conditions
                'Parent Name',          // emergency_contact_name
                '08098765432'           // emergency_contact_phone
            ];
            fputcsv($output, $sampleData1);

            $sampleData2 = [
                'Mary',
                'Smith',
                'mary.smith@example.com',
                '08011223344',
                '2011-08-20',
                'Female',
                '2',
                'Christianity',
                'Nigerian',
                'Lagos',
                'Ikeja',
                'Asthma - bring inhaler', // medical_conditions - Only fill if student has conditions
                'Guardian Name',
                '08087654321'
            ];
            fputcsv($output, $sampleData2);

            fclose($output);
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Template download failed: " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
} // End of POST handling

function addStudent($db)
{
    try {
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
            throw new Exception("First name, last name, and email are required.");
        }

        $db->beginTransaction();

        // Apply case formatting to relevant fields
        $firstName = formatName($_POST['first_name']);
        $lastName = formatName($_POST['last_name']);
        $stateOfOrigin = formatLocation($_POST['state_of_origin'] ?? '');
        $lga = formatLocation($_POST['lga'] ?? '');
        $religion = formatReligion($_POST['religion'] ?? 'Islam');
        $nationality = formatNationality($_POST['nationality'] ?? 'Nigerian');
        $medicalConditions = formatMedicalCondition($_POST['medical_conditions'] ?? '');
        $emergencyContactName = formatName($_POST['emergency_contact_name'] ?? '');

        $studentId = 'STU' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $admissionNumber = 'ADM' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        $checkStmt = $db->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
        $checkStmt->execute([$studentId]);
        if ($checkStmt->fetchColumn() > 0) {
            $studentId = 'STU' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        }

        $username = strtolower($firstName) . '.' . strtolower($lastName);

        $usernameCheckStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $usernameCheckStmt->execute([$username]);
        $usernameCount = $usernameCheckStmt->fetchColumn();

        if ($usernameCount > 0) {
            $username = $username . rand(1, 999);
        }

        $userSql = "INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone, date_of_birth, gender, is_active) 
                      VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, 1)";
        $userStmt = $db->prepare($userSql);

        $password_hash = password_hash('password123', PASSWORD_DEFAULT);

        $userStmt->execute([
            $username,
            $_POST['email'],
            $password_hash,
            $firstName,
            $lastName,
            $_POST['phone'] ?? null,
            $_POST['date_of_birth'] ?? null,
            $_POST['gender'] ?? 'Male'
        ]);

        $userId = $db->lastInsertId();

        $studentSql = "INSERT INTO students (user_id, student_id, admission_number, class_id, admission_date, religion, nationality, state_of_origin, lga, medical_conditions, emergency_contact_name, emergency_contact_phone) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $studentStmt = $db->prepare($studentSql);
        $studentStmt->execute([
            $userId,
            $studentId,
            $admissionNumber,
            $_POST['class_id'] ?? 1,
            $_POST['admission_date'] ?? date('Y-m-d'),
            $religion,
            $nationality,
            $stateOfOrigin,
            $lga,
            $medicalConditions,
            $emergencyContactName,
            $_POST['emergency_contact_phone'] ?? ''
        ]);

        $db->commit();
        $_SESSION['success'] = "Student added successfully! Student ID: " . $studentId . ", Admission Number: " . $admissionNumber;

        // --- LOG ACTIVITY (This is the correct placement) ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $student_name = $firstName . ' ' . $lastName;
        logActivity(
            $db,
            $admin_name,
            "New Student",
            "Added student: $student_name ($studentId)",
            "fas fa-user-plus",
            "bg-nsklightblue"
        );
        // --- END LOG ---

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Error adding student: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
// Function to get single student data
function getStudentData($db, $student_id)
{
    try {
        $sql = "
            SELECT 
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.date_of_birth,
                u.gender,
                u.address,
                s.id as student_db_id,
                s.student_id,
                s.admission_number,
                s.class_id,
                c.class_name,
                c.class_code,
                s.admission_date,
                s.religion,
                s.nationality,
                s.state_of_origin,
                s.lga,
                s.medical_conditions,
                s.emergency_contact_name,
                s.emergency_contact_phone
            FROM users u
            INNER JOIN students s ON u.id = s.user_id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.student_id = ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function updateStudent($db)
{
    try {
        if (empty($_POST['student_id']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
            throw new Exception("Required fields are missing.");
        }

        $db->beginTransaction();

        // Apply case formatting to relevant fields
        $firstName = formatName($_POST['first_name']);
        $lastName = formatName($_POST['last_name']);
        $stateOfOrigin = formatLocation($_POST['state_of_origin'] ?? '');
        $lga = formatLocation($_POST['lga'] ?? '');
        $religion = formatReligion($_POST['religion'] ?? 'Islam');
        $nationality = formatNationality($_POST['nationality'] ?? 'Nigerian');
        $medicalConditions = formatMedicalCondition($_POST['medical_conditions'] ?? '');
        $emergencyContactName = formatName($_POST['emergency_contact_name'] ?? '');

        // Get student data before update for logging
        $oldStudentData = getStudentData($db, $_POST['student_id']);

        if (!$oldStudentData) {
            throw new Exception("Student not found.");
        }

        // Update users table
        $userSql = "UPDATE users SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        date_of_birth = ?, 
                        gender = ?
                        WHERE id = (SELECT user_id FROM students WHERE student_id = ?)";

        $userStmt = $db->prepare($userSql);
        $userStmt->execute([
            $firstName,
            $lastName,
            $_POST['email'],
            $_POST['phone'] ?? null,
            $_POST['date_of_birth'] ?? null,
            $_POST['gender'] ?? 'Male',
            $_POST['student_id']
        ]);

        // Update students table
        $studentSql = "UPDATE students SET 
                           class_id = ?, 
                           religion = ?, 
                           nationality = ?, 
                           state_of_origin = ?, 
                           lga = ?, 
                           medical_conditions = ?, 
                           emergency_contact_name = ?, 
                           emergency_contact_phone = ?
                           WHERE student_id = ?";

        $studentStmt = $db->prepare($studentSql);
        $studentStmt->execute([
            $_POST['class_id'] ?? 1,
            $religion,
            $nationality,
            $stateOfOrigin,
            $lga,
            $medicalConditions,
            $emergencyContactName,
            $_POST['emergency_contact_phone'] ?? '',
            $_POST['student_id']
        ]);

        $db->commit();
        $_SESSION['success'] = "Student updated successfully!";

        // --- LOG ACTIVITY FOR UPDATE ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $student_name = $firstName . ' ' . $lastName;
        $student_id = $_POST['student_id'];

        // Log what changed (you can make this more detailed)
        $changes = [];
        if ($oldStudentData['first_name'] != $firstName || $oldStudentData['last_name'] != $lastName) {
            $changes[] = "name";
        }
        if ($oldStudentData['email'] != $_POST['email']) {
            $changes[] = "email";
        }
        if ($oldStudentData['class_id'] != $_POST['class_id']) {
            $changes[] = "class";
        }

        $changeDescription = !empty($changes) ? " (Updated: " . implode(", ", $changes) . ")" : "";

        logActivity(
            $db,
            $admin_name,
            "Update Student",
            "Updated student: $student_name ($student_id)$changeDescription",
            "fas fa-user-edit",
            "bg-nskgold"
        );
        // --- END LOG ---

        return true;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Error updating student: " . $e->getMessage();
        return false;
    }
}

// Function to delete student
function deleteStudent($db, $student_id)
{
    try {
        // Get student data before deletion for logging
        $studentData = getStudentData($db, $student_id);
        if (!$studentData) {
            throw new Exception("Student not found.");
        }

        // Get user_id first from student_id
        $stmt = $db->prepare("SELECT user_id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $user_id = $stmt->fetchColumn();

        if (!$user_id) {
            throw new Exception("Student not found.");
        }

        // Begin transaction
        $db->beginTransaction();

        // Soft delete: Update the is_active flag in the users table to 0
        $softDeleteSql = "UPDATE users SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($softDeleteSql);
        $stmt->execute([$user_id]);

        // Commit the transaction
        $db->commit();

        // Update the success message to be more accurate
        $_SESSION['success'] = "Student deactivated successfully!";

        // --- LOG ACTIVITY FOR DELETE ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $student_name = $studentData['first_name'] . ' ' . $studentData['last_name'];

        logActivity(
            $db,
            $admin_name,
            "Delete Student",
            "Deactivated student: $student_name ($student_id)",
            "fas fa-user-times",
            "bg-nskred"
        );
        // --- END LOG ---

        return true;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        // Update the error message
        $_SESSION['error'] = "Error deactivating student: " . $e->getMessage();
        return false;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle update student
    if (isset($_POST['update_student'])) {
        updateStudent($db);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Handle delete student
    if (isset($_POST['delete_student']) && isset($_POST['student_id'])) {
        deleteStudent($db, $_POST['student_id']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle GET requests for viewing/editing
$viewStudent = null;
$editStudent = null;

if (isset($_GET['view']) && !empty($_GET['view'])) {
    $viewStudent = getStudentData($db, $_GET['view']);
}

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editStudent = getStudentData($db, $_GET['edit']);
}

// Get data for display with filters
try {
    if ($db) {
        // Build the WHERE clause based on filters
        $whereConditions = ["u.user_type = 'student'", "u.is_active = 1", "s.status = 'active'"];
        $params = [];

        // Add search filter
        if (!empty($searchQuery)) {
            $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR s.student_id LIKE ? OR s.admission_number LIKE ?)";
            $searchParam = "%{$searchQuery}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }

        // Add class filter
        if (!empty($classFilter)) {
            $whereConditions[] = "s.class_id = ?";
            $params[] = $classFilter;
        }

        $whereClause = implode(" AND ", $whereConditions);

        // Total Students (with filters applied)
        $countSql = "SELECT COUNT(*) as total FROM users u 
                       INNER JOIN students s ON u.id = s.user_id 
                       WHERE {$whereClause}";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $totalStudents = $stmt->fetch()['total'] ?? 0;

        // Total Teachers (no filter)
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'teacher' AND is_active = 1");
        $stmt->execute();
        $totalTeachers = $stmt->fetch()['total'] ?? 0;

        // Total Classes (no filter)
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM classes");
        $stmt->execute();
        $totalClasses = $stmt->fetch()['total'] ?? 0;

        // Get students data with class information (with filters)
        $studentsSql = "
            SELECT 
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.date_of_birth,
                u.gender,
                s.student_id,
                s.admission_number,
                s.class_id,
                c.class_name,
                c.class_code,
                s.admission_date,
                s.religion,
                s.nationality,
                s.state_of_origin,
                s.lga,
                s.medical_conditions,
                s.emergency_contact_name,
                s.emergency_contact_phone
            FROM users u
            INNER JOIN students s ON u.id = s.user_id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE {$whereClause}
            ORDER BY s.class_id, u.first_name
        ";
        $stmt = $db->prepare($studentsSql);
        $stmt->execute($params);
        $studentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $totalStudents = 0;
    $totalTeachers = 0;
    $totalClasses = 0;
    $studentsData = [];
}

// Get classes for dropdown
$classes = [];
try {
    $classStmt = $db->prepare("SELECT id, class_name FROM classes ORDER BY id");
    $classStmt->execute();
    $classes = $classStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $classes = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Management - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="sidebar.css" />

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
                        nskred: '#ef4444',
                    },
                },
            },
        };
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }

        /* Fixed Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.active {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            margin: 20px;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Rest of your existing styles */
        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }

        .student-card {
            transition: all 0.3s ease;
        }

        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .nav-item {
            position: relative;
        }

        .nav-item::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #f59e0b;
            transition: width 0.3s ease;
        }

        .nav-item:hover::after {
            width: 100%;
        }

        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background-color: #ef4444;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .student-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .student-table th {
            background-color: #f8fafc;
        }

        .student-table tr:last-child td {
            border-bottom: 0;
        }

        .student-table tbody tr {
            transition: all 0.3s ease;
        }

        .student-table tbody tr:hover {
            background-color: #f8fafc;
            transform: scale(1.01);
        }

        .grade-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            animation: fadeInUp 0.5s ease;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .tab-button {
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background-color: #1e40af;
            color: white;
        }

        .form-section {
            animation: slideInUp 0.5s ease;
        }

        .progress-bar {
            transition: width 0.5s ease;
        }

        .card-animate {
            animation: slideInUp 0.6s ease;
        }

        .button-hover {
            transition: all 0.3s ease;
        }

        .button-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .file-drop-zone {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }

        .file-drop-zone.dragover {
            border-color: #1e40af;
            background-color: #f0f9ff;
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* .id-card {
        background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    } */

        .medical-alert {
            animation: pulse 2s infinite;
        }

        .document-item {
            transition: all 0.3s ease;
        }

        .document-item:hover {
            background-color: #f8fafc;
            transform: translateX(5px);
        }

        /* Loading spinner for buttons */
        .btn-spinner {
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .btn-loading {
            cursor: not-allowed;
        }
    </style>
</head>

<body class="flex">
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <?php
        $pageTitle = 'Student Management';
        require_once 'header.php';
        ?>

        <div class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="student-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nsklightblue p-4 rounded-full mr-4">
                        <i class="fas fa-user-graduate text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Students</p>
                        <p class="text-2xl font-bold text-nsknavy" id="totalStudents">
                            <?= number_format($totalStudents) ?>
                        </p>
                        <p class="text-xs text-nskgreen"><i class="fas fa-arrow-up"></i> Current enrollment</p>
                    </div>
                </div>

                <div class="student-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgreen p-4 rounded-full mr-4">
                        <i class="fas fa-chalkboard text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Classes</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= number_format($totalClasses) ?></p>
                        <p class="text-xs text-gray-600">All levels</p>
                    </div>
                </div>

                <div class="student-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgold p-4 rounded-full mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Teaching Staff</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= number_format($totalTeachers) ?></p>
                        <p class="text-xs text-gray-600">Active teachers</p>
                    </div>
                </div>

                <div class="student-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskred p-4 rounded-full mr-4">
                        <i class="fas fa-bullhorn text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Notices</p>
                        <p class="text-2xl font-bold text-nsknavy">3</p>
                        <p class="text-xs text-nskred"><i class="fas fa-exclamation-circle"></i> New alerts</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <form method="GET" action="" class="space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-nsknavy">All Students</h2>

                        <div class="flex flex-wrap gap-4">
                            <div class="relative">
                                <div
                                    class="flex items-center space-x-2 bg-nsklight rounded-lg py-2 px-4 border border-gray-200">
                                    <i class="fas fa-search text-gray-500"></i>
                                    <input type="text" name="search" id="searchInput" placeholder="Search students..."
                                        value="<?= htmlspecialchars($searchQuery) ?>"
                                        class="bg-transparent outline-none w-32 md:w-64" />
                                </div>
                            </div>

                            <select name="class_filter" id="classFilter"
                                class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= $classFilter == $class['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit"
                                class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>

                            <?php if (!empty($searchQuery) || !empty($classFilter)): ?>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>"
                                    class="bg-gray-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-600 transition flex items-center">
                                    <i class="fas fa-times mr-2"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($searchQuery) || !empty($classFilter)): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center justify-between">
                            <div class="flex items-center text-blue-700">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span class="text-sm">
                                    Showing <?= $totalStudents ?> student(s)
                                    <?php if (!empty($searchQuery)): ?>
                                        matching "<?= htmlspecialchars($searchQuery) ?>"
                                    <?php endif; ?>
                                    <?php if (!empty($classFilter)): ?>
                                        <?php
                                        $selectedClassName = '';
                                        foreach ($classes as $class) {
                                            if ($class['id'] == $classFilter) {
                                                $selectedClassName = $class['class_name'];
                                                break;
                                            }
                                        }
                                        ?>
                                        in <?= htmlspecialchars($selectedClassName) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>

                <div class="flex flex-wrap gap-4 mt-4">
                    <?php if (!isset($_POST['show_add_form'])): ?>
                        <form method="POST" action="" class="inline">
                            <button type="submit" name="show_add_form" value="true"
                                class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add Student
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Add these new buttons -->
                    <button type="button" onclick="downloadTemplate(this)"
                        class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center">
                        <i class="fas fa-download mr-2"></i> Download Template
                    </button>

                    <button type="button" onclick="showImportModal()"
                        class="bg-nskgold text-white px-4 py-2 rounded-lg font-semibold hover:bg-amber-600 transition flex items-center">
                        <i class="fas fa-upload mr-2"></i> Import from CSV
                    </button>

                    <form method="POST" action="" class="inline">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                        <input type="hidden" name="class_filter" value="<?= htmlspecialchars($classFilter) ?>">
                        <button type="submit" name="export_csv"
                            class="bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition flex items-center">
                            <i class="fas fa-file-export mr-2"></i> Export
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full student-table">
                        <thead>
                            <tr>
                                <th class="py-3 px-6 text-left text-nsknavy">Student</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Grade/Class</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Contact Information</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Admission Details</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Medical Status</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody" class="divide-y divide-gray-200">
                            <?php foreach ($studentsData as $student): ?>
                                <tr class="hover:bg-gray-50 transition-all duration-300">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <div
                                                class="w-10 h-10 rounded-full <?= getAvatarColor($student['first_name']) ?> flex items-center justify-center text-white font-bold mr-3">
                                                <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold">
                                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                </p>
                                                <p class="text-sm text-gray-600">ID: <?= $student['student_id'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <p class="font-medium">
                                            <?= htmlspecialchars($student['class_name'] ?? 'Not Assigned') ?>
                                        </p>
                                        <p class="text-sm text-gray-600"><?= $student['class_code'] ?? '' ?></p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <p class="text-sm"><?= htmlspecialchars($student['email']) ?></p>
                                        <p class="text-sm text-gray-600"><?= $student['phone'] ?? 'No phone' ?></p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <p class="text-sm">Admitted:
                                            <?= date('M j, Y', strtotime($student['admission_date'])) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">Adm No: <?= $student['admission_number'] ?></p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <?php if (!empty($student['medical_conditions'])): ?>
                                                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                                                <span class="text-red-600 text-sm">Has Conditions</span>
                                            <?php else: ?>
                                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                                <span class="text-green-600 text-sm">Normal</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex space-x-2">
                                            <button
                                                class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50 transition"
                                                onclick="viewStudent('<?= $student['student_id'] ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50 transition"
                                                onclick="editStudent('<?= $student['student_id'] ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition"
                                                onclick="deleteStudent('<?= $student['student_id'] ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($studentsData)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                        <i class="fas fa-user-graduate text-4xl mb-4"></i>
                                        <p>No students found in the database.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php
        // Show add form if button was clicked
        if (isset($_POST['show_add_form'])) {
            ?>
            <div class="modal active" id="addStudentModal">
                <div class="modal-content">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-nsknavy">Add New Student</h2>
                        <button type="button" onclick="closeAddStudentModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form method="POST" action="" id="addStudentForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" name="first_name" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                                    value="<?= $_POST['first_name'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" name="last_name" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                                    value="<?= $_POST['last_name'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email *</label>
                                <input type="email" name="email" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                                    value="<?= $_POST['email'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="tel" name="phone"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                                    value="<?= $_POST['phone'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date of Birth *</label>
                                <input type="date" name="date_of_birth" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                                    value="<?= $_POST['date_of_birth'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gender *</label>
                                <select name="gender" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    <option value="Male" <?= ($_POST['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male
                                    </option>
                                    <option value="Female" <?= ($_POST['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female
                                    </option>
                                    <option value="Other" <?= ($_POST['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Class *</label>
                                <select name="class_id" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>" <?= ($_POST['class_id'] ?? '') == $class['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Admission Date *</label>
                                <input type="date" name="admission_date" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                                    value="<?= $_POST['admission_date'] ?? date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Religion</label>
                                <select name="religion"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                    <option value="Islam" <?= ($_POST['religion'] ?? '') == 'Islam' ? 'selected' : '' ?>>Islam
                                    </option>
                                    <option value="Christianity" <?= ($_POST['religion'] ?? '') == 'Christianity' ? 'selected' : '' ?>>Christianity</option>
                                    <option value="Other" <?= ($_POST['religion'] ?? '') == 'Other' ? 'selected' : '' ?>>Other
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nationality</label>
                                <input type="text" name="nationality" value="<?= $_POST['nationality'] ?? 'Nigerian' ?>"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">State of Origin</label>
                                <input type="text" name="state_of_origin" value="<?= $_POST['state_of_origin'] ?? '' ?>"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">LGA</label>
                                <input type="text" name="lga" value="<?= $_POST['lga'] ?? '' ?>"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Medical Conditions</label>
                                <textarea name="medical_conditions"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"><?= $_POST['medical_conditions'] ?? '' ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name"
                                    value="<?= $_POST['emergency_contact_name'] ?? '' ?>"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Emergency Contact Phone</label>
                                <input type="tel" name="emergency_contact_phone"
                                    value="<?= $_POST['emergency_contact_phone'] ?? '' ?>"
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeAddStudentModal()"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition">
                                Cancel
                            </button>
                            <button type="submit" name="add_student"
                                class="bg-nskgreen text-white px-4 py-2 rounded-md hover:bg-green-600 transition">
                                Add Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }
        ?>

        <!-- Import Students Modal -->
        <div id="importModal" class="modal">
            <div class="modal-content" style="max-width: 600px;">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-nsknavy">Import Students</h3>
                    <button onclick="closeImportModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="importForm">
                    <div class="space-y-6">
                        <div class="file-drop-zone border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-all duration-300"
                            id="fileDropZone" ondrop="handleDrop(event)" ondragover="handleDragOver(event)"
                            ondragleave="handleDragLeave(event)">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                            <p class="text-lg font-medium text-gray-700 mb-2">Drop your CSV file here</p>
                            <p class="text-sm text-gray-500 mb-4">or</p>
                            <input type="file" id="csvFile" name="csv_file" accept=".csv" class="hidden"
                                onchange="handleFileSelect(this)">
                            <button type="button" onclick="document.getElementById('csvFile').click()"
                                class="bg-nskblue text-white px-6 py-2 rounded-lg font-semibold hover:bg-nsknavy transition">
                                <i class="fas fa-folder-open mr-2"></i> Choose File
                            </button>
                            <p class="text-xs text-gray-500 mt-4">Supported format: CSV (Max 5MB)</p>
                        </div>

                        <div id="fileInfo" class="hidden bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-file-csv text-green-600 text-xl mr-3"></i>
                                    <div>
                                        <p class="font-medium text-green-800" id="fileName"></p>
                                        <p class="text-sm text-green-600" id="fileSize"></p>
                                    </div>
                                </div>
                                <button type="button" onclick="removeFile()"
                                    class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i> Import Instructions
                            </h4>
                            <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                                <li>Download and use the provided template</li>
                                <li>Required fields: first_name, last_name, email, date_of_birth, gender</li>
                                <li>Optional fields: phone, class_id, religion, nationality, etc.</li>
                                <li>Keep the header row in your CSV file</li>
                                <li>Maximum 1000 records per import</li>
                            </ul>
                        </div>

                        <div class="flex items-center" style="display: none;">
                            <input type="checkbox" id="sendWelcomeEmail" name="send_welcome_email" class="mr-2">
                            <label for="sendWelcomeEmail" class="text-sm text-gray-700">
                                Send welcome email to new students (default password: password123)
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeImportModal()"
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" name="import_students"
                            class="bg-nskgreen text-white px-6 py-2 rounded-lg hover:bg-green-600 transition flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                            id="importSubmit" disabled>
                            <i class="fas fa-upload mr-2"></i> Upload and Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import Progress Modal -->
        <div id="importProgressModal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div class="text-center">
                    <div class="w-16 h-16 bg-nsklightblue rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-spinner loading-spinner text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-nsknavy mb-2">Importing Students</h3>
                    <p class="text-gray-600 mb-4" id="progressText">Processing your file...</p>

                    <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                        <div id="progressBar" class="bg-nskgreen h-2 rounded-full progress-bar" style="width: 0%"></div>
                    </div>

                    <div class="text-sm text-gray-500" id="progressDetails">
                        <span id="processedCount">0</span> of <span id="totalCount">0</span> records processed
                    </div>
                </div>
            </div>
        </div>

        <div id="viewStudentModal" class="modal">
            <div class="modal-content" style="max-width: 900px;">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h3 class="text-2xl font-bold text-nsknavy">Student Details</h3>
                    <button onclick="closeViewStudentModal()"
                        class="text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div id="viewStudentContent" class="space-y-6">
                </div>

                <div class="mt-8 flex justify-end space-x-3 border-t pt-4">
                    <button onclick="closeViewStudentModal()"
                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition flex items-center">
                        <i class="fas fa-times mr-2"></i> Close
                    </button>
                    <button onclick="editCurrentStudent()"
                        class="bg-nskblue text-white px-6 py-2 rounded-lg hover:bg-nsknavy transition flex items-center">
                        <i class="fas fa-edit mr-2"></i> Edit Student
                    </button>
                </div>
            </div>
        </div>

        <div id="editStudentModal" class="modal">
            <div class="modal-content" style="max-width: 900px;">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Edit Student</h3>
                    <button onclick="closeEditStudentModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form method="POST" action="" id="editStudentForm">
                    <input type="hidden" name="student_id" id="edit_student_id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input type="text" name="first_name" id="edit_first_name" required
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input type="text" name="last_name" id="edit_last_name" required
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" id="edit_email" required
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="tel" name="phone" id="edit_phone"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                            <input type="date" name="date_of_birth" id="edit_date_of_birth" required
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                            <select name="gender" id="edit_gender" required
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                            <select name="class_id" id="edit_class_id" required
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date *</label>
                            <input type="date" name="admission_date" id="edit_admission_date" required
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                            <select name="religion" id="edit_religion"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                                <option value="Islam">Islam</option>
                                <option value="Christianity">Christianity</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                            <input type="text" name="nationality" id="edit_nationality" value="Nigerian"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State of Origin</label>
                            <input type="text" name="state_of_origin" id="edit_state_of_origin"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">LGA</label>
                            <input type="text" name="lga" id="edit_lga"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="address" id="edit_address" rows="2"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medical Conditions</label>
                            <textarea name="medical_conditions" id="edit_medical_conditions" rows="2"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" id="edit_emergency_contact_name"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Phone</label>
                            <input type="tel" name="emergency_contact_phone" id="edit_emergency_contact_phone"
                                class="w-full px-3 py-2 border rounded-lg focus:border-nskblue focus:outline-none">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditStudentModal()"
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" name="update_student"
                            class="bg-nskgreen text-white px-6 py-2 rounded-lg hover:bg-green-600 transition">
                            <i class="fas fa-check mr-2"></i>Update Student
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        function getAvatarColor($name)
        {
            $colors = [
                'bg-nskblue',
                'bg-nskgreen',
                'bg-nskgold',
                'bg-nskred',
                'bg-purple-500',
            ];
            $index = ord($name[0]) % count($colors);
            return $colors[$index];
        }
        ?>

        <script>
            // View Student Function
            function viewStudent(studentId) {
                window.location.href = `?view=${studentId}`;
            }

            // Edit Student Function
            function editStudent(studentId) {
                window.location.href = `?edit=${studentId}`;
            }

            // Modal Functions
            function openViewStudentModal() {
                document.getElementById('viewStudentModal').classList.add('active');
            }

            function closeViewStudentModal() {
                document.getElementById('viewStudentModal').classList.remove('active');
                // Clean up the URL when closing
                if (window.location.search.includes('view=')) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }

            function openEditStudentModal() {
                document.getElementById('editStudentModal').classList.add('active');
            }

            function closeEditStudentModal() {
                document.getElementById('editStudentModal').classList.remove('active');
                // Clean up the URL when closing
                if (window.location.search.includes('edit=')) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }

            // Close Add Student Modal Function
            function closeAddStudentModal() {
                // Create a form and submit to hide the add form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const hideInput = document.createElement('input');
                hideInput.type = 'hidden';
                hideInput.name = 'hide_add_form';
                hideInput.value = 'true';

                form.appendChild(hideInput);
                document.body.appendChild(form);
                form.submit();
            }

            // Delete Student Function
            function deleteStudent(studentId) {
                if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    const studentIdInput = document.createElement('input');
                    studentIdInput.type = 'hidden';
                    studentIdInput.name = 'student_id';
                    studentIdInput.value = studentId;

                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_student';
                    deleteInput.value = '1';

                    form.appendChild(studentIdInput);
                    form.appendChild(deleteInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            }

            // Import Modal Functions
            function showImportModal() {
                document.getElementById('importModal').classList.add('active');
            }

            function closeImportModal() {
                document.getElementById('importModal').classList.remove('active');
                resetImportForm();
            }

            function resetImportForm() {
                document.getElementById('csvFile').value = '';
                document.getElementById('fileInfo').classList.add('hidden');
                document.getElementById('fileDropZone').classList.remove('dragover');
                document.getElementById('importSubmit').disabled = true;
                document.getElementById('sendWelcomeEmail').checked = false;
            }

            // File Drag & Drop Handling
            function handleDragOver(e) {
                e.preventDefault();
                e.stopPropagation();
                document.getElementById('fileDropZone').classList.add('dragover');
            }

            function handleDragLeave(e) {
                e.preventDefault();
                e.stopPropagation();
                document.getElementById('fileDropZone').classList.remove('dragover');
            }

            function handleDrop(e) {
                e.preventDefault();
                e.stopPropagation();
                document.getElementById('fileDropZone').classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFile(files[0]);
                }
            }

            function handleFileSelect(input) {
                if (input.files.length > 0) {
                    handleFile(input.files[0]);
                }
            }

            function handleFile(file) {
                if (file.type !== 'text/csv' && !file.name.toLowerCase().endsWith('.csv')) {
                    alert('Please select a CSV file.');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    return;
                }

                // Update file info display
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = formatFileSize(file.size);
                document.getElementById('fileInfo').classList.remove('hidden');

                // Enable submit button
                document.getElementById('importSubmit').disabled = false;

                // Create a new FileList and set it to the file input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('csvFile').files = dataTransfer.files;
            }

            function removeFile() {
                resetImportForm();
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Template Download Function
            function downloadTemplate(btn) {
                // Add loading state
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner btn-spinner"></i> Downloading...';
                btn.classList.add('btn-loading');
                btn.disabled = true;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const templateInput = document.createElement('input');
                templateInput.type = 'hidden';
                templateInput.name = 'download_template';
                templateInput.value = '1';

                form.appendChild(templateInput);
                document.body.appendChild(form);
                form.submit();

                // Reset button after a short delay (since download doesn't trigger a page reload)
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                    document.body.removeChild(form);
                }, 3000);
            }

            // Load student data for viewing (when page loads with view parameter)
            <?php if (isset($_GET['view']) && $viewStudent): ?>
                document.addEventListener('DOMContentLoaded', function () {
                    loadViewStudentData(<?= json_encode($viewStudent) ?>);
                });
            <?php endif; ?>

            // Load student data for editing (when page loads with edit parameter)
            <?php if (isset($_GET['edit']) && $editStudent): ?>
                document.addEventListener('DOMContentLoaded', function () {
                    loadEditStudentData(<?= json_encode($editStudent) ?>);
                });
            <?php endif; ?>

            // Function to load view student data
            function loadViewStudentData(student) {
                // Helper function for creating neat, responsive info rows
                const infoRow = (label, value) => {
                    const val = value || 'N/A';
                    return `
            <div class="flex flex-col sm:flex-row sm:justify-between py-3 border-b border-gray-100 last:border-b-0">
                <strong class="text-sm font-medium text-gray-500 w-full sm:w-1/3 flex-shrink-0">${label}</strong>
                <span class="text-sm text-gray-900 text-left sm:text-right w-full sm:w-2/3">${val}</span>
            </div>
        `;
                };

                // Helper for long-text blocks like medical conditions
                const infoBlock = (label, value) => {
                    const val = value || 'None specified.';
                    return `
            <div class="py-3">
                <strong class="text-sm font-medium text-gray-500">${label}</strong>
                <p class="text-sm text-gray-900 mt-1 whitespace-pre-wrap">${val}</p>
            </div>
         `;
                };

                const content = `
        <div class="bg-white rounded-lg p-0 sm:p-4">
            <div class="flex flex-col sm:flex-row items-center mb-6">
                <div class="w-20 h-20 rounded-full ${getAvatarColor(student.first_name)} flex items-center justify-center text-white font-bold text-3xl mr-0 sm:mr-6 mb-4 sm:mb-0 flex-shrink-0">
                    ${student.first_name.charAt(0)}${student.last_name.charAt(0)}
                </div>
                <div class="text-center sm:text-left">
                    <h2 class="text-3xl font-bold text-nsknavy">${student.first_name} ${student.last_name}</h2>
                    <div class="flex flex-col sm:flex-row sm:space-x-4 text-gray-600 mt-1">
                        <span>Student ID: <strong>${student.student_id}</strong></span>
                        <span>Admission No: <strong>${student.admission_number}</strong></span>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
                <div class="space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold text-nsknavy border-b border-gray-200 pb-2">Personal Information</h3>
                    <div class="flow-root">
                        ${infoRow('Email', student.email)}
                        ${infoRow('Phone', student.phone)}
                        ${infoRow('Date of Birth', student.date_of_birth ? new Date(student.date_of_birth).toLocaleDateString() : 'N/A')}
                        ${infoRow('Gender', student.gender)}
                        ${infoRow('Nationality', student.nationality)}
                        ${infoRow('State of Origin', student.state_of_origin)}
                        ${infoRow('LGA', student.lga)}
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-nsknavy border-b border-gray-200 pb-2">Academic & Emergency</h3>
                        <div class="flow-root">
                            ${infoRow('Class', student.class_name)}
                            ${infoRow('Admission Date', new Date(student.admission_date).toLocaleDateString())}
                            ${infoRow('Religion', student.religion)}
                            ${infoRow('Emergency Contact', student.emergency_contact_name)}
                            ${infoRow('Emergency Phone', student.emergency_contact_phone)}
                        </div>
                    </div>
                    
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <h3 class="text-lg font-semibold text-red-700 border-b border-red-200 pb-2 flex items-center">
                            <i class="fas fa-notes-medical mr-2"></i> Medical Conditions
                        </h3>
                        <div class="flow-root">
                            ${infoBlock('Conditions', student.medical_conditions)}
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    `;

                document.getElementById('viewStudentContent').innerHTML = content;
                openViewStudentModal();
            }

            // Function to load edit student data
            function loadEditStudentData(student) {
                document.getElementById('edit_student_id').value = student.student_id;
                document.getElementById('edit_first_name').value = student.first_name || '';
                document.getElementById('edit_last_name').value = student.last_name || '';
                document.getElementById('edit_email').value = student.email || '';
                document.getElementById('edit_phone').value = student.phone || '';
                document.getElementById('edit_date_of_birth').value = student.date_of_birth || '';
                document.getElementById('edit_gender').value = student.gender || 'Male';
                document.getElementById('edit_class_id').value = student.class_id || '';
                document.getElementById('edit_admission_date').value = student.admission_date || '';
                document.getElementById('edit_religion').value = student.religion || 'Islam';
                document.getElementById('edit_nationality').value = student.nationality || 'Nigerian';
                document.getElementById('edit_state_of_origin').value = student.state_of_origin || '';
                document.getElementById('edit_lga').value = student.lga || '';
                document.getElementById('edit_address').value = student.address || '';
                document.getElementById('edit_medical_conditions').value = student.medical_conditions || '';
                document.getElementById('edit_emergency_contact_name').value = student.emergency_contact_name || '';
                document.getElementById('edit_emergency_contact_phone').value = student.emergency_contact_phone || '';

                openEditStudentModal();
            }

            // Helper function for avatar colors (matches PHP function)
            function getAvatarColor(name) {
                const colors = [
                    'bg-nskblue',
                    'bg-nskgreen',
                    'bg-nskgold',
                    'bg-nskred',
                    'bg-purple-500',
                ];
                const index = name.charCodeAt(0) % colors.length;
                return colors[index];
            }

            // Close modals when clicking outside
            window.onclick = function (event) {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (event.target === modal) {
                        modal.classList.remove('active');
                        // Remove URL parameters when closing modals
                        if (window.location.search.includes('view=') || window.location.search.includes('edit=')) {
                            window.history.replaceState({}, document.title, window.location.pathname);
                        }
                    }
                });
            }

            // Close modals with Escape key
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal');
                    modals.forEach(modal => modal.classList.remove('active'));
                    // Remove URL parameters when closing modals
                    if (window.location.search.includes('view=') || window.location.search.includes('edit=')) {
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                }
            });

            // Switch from View to Edit
            function editCurrentStudent() {
                const urlParams = new URLSearchParams(window.location.search);
                const studentId = urlParams.get('view');
                if (studentId) {
                    // Close the view modal
                    closeViewStudentModal();
                    // Open the edit modal
                    editStudent(studentId);
                }
            }

            // Show import errors if any
            <?php if (isset($_SESSION['import_errors'])): ?>
                document.addEventListener('DOMContentLoaded', function () {
                    const errors = <?= json_encode($_SESSION['import_errors']) ?>;
                    if (errors.length > 0) {
                        let errorMessage = "Some rows had errors:\n" + errors.join('\n');
                        alert(errorMessage);
                    }
                });
                <?php
                unset($_SESSION['import_errors']);
            endif;
            ?>
        </script>

        <script src="./footer.js"></script>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const classFilter = document.getElementById('classFilter');
            const form = searchInput ? searchInput.closest('form') : null;

            if (!form) return;

            let searchTimeout;

            // Auto-submit on search input (debounced)
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function () {
                        form.submit();
                    }, 800);
                });
            }

            // Auto-submit on class filter change
            if (classFilter) {
                classFilter.addEventListener('change', function () {
                    form.submit();
                });
            }
        });

        // Show import errors in a modal instead of alert
        function showImportErrors(errors) {
            let errorHtml = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <div class="flex items-center mb-2">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                <h4 class="font-semibold text-red-800">Import Errors</h4>
            </div>
            <div class="max-h-60 overflow-y-auto">
                <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
    `;

            errors.forEach(error => {
                errorHtml += `<li>${error}</li>`;
            });

            errorHtml += `
                </ul>
            </div>
        </div>
        <p class="text-sm text-gray-600 mt-2">
            Please correct these errors in your CSV file and try again.
        </p>
    `;

            // Create error modal
            const errorModal = document.createElement('div');
            errorModal.className = 'modal active';
            errorModal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-red-600">Import Completed with Errors</h3>
                <button onclick="this.closest('.modal').remove()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            ${errorHtml}
            <div class="mt-6 flex justify-end">
                <button onclick="this.closest('.modal').remove()" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                    Close
                </button>
            </div>
        </div>
    `;

            document.body.appendChild(errorModal);
        }

        // Update the import error handling
        <?php if (isset($_SESSION['import_errors'])): ?>
            document.addEventListener('DOMContentLoaded', function () {
                const errors = <?= json_encode($_SESSION['import_errors']) ?>;
                if (errors.length > 0) {
                    showImportErrors(errors);
                }
            });
            <?php
            unset($_SESSION['import_errors']);
        endif;
        ?>
    </script>
</body>

</html>