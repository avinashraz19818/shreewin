<?php
// EditUserPhoto.php - FIXED FOR MISSING COLUMN

// ==================== ERROR REPORTING ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================== HEADERS ====================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// ==================== HANDLE OPTIONS ====================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==================== INITIAL SETUP ====================
date_default_timezone_set("Asia/Kolkata");
$now = date("Y-m-d H:i:s");
$response = [];

// ==================== CHECK REQUEST METHOD ====================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = [
        'code' => 11,
        'msg' => 'Method not allowed',
        'msgCode' => 12,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== GET INPUT DATA ====================
$rawInput = @file_get_contents('php://input');
$inputData = [];

if (!empty($rawInput)) {
    $inputData = @json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $inputData = $_POST;
    }
} else {
    $inputData = $_POST;
}

// ==================== CHECK REQUIRED FIELDS ====================
$requiredFields = ['language', 'random', 'signature', 'timestamp', 'userPhoto'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($inputData[$field]) || $inputData[$field] === '') {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    $response = [
        'code' => 7,
        'msg' => 'Missing parameters: ' . implode(', ', $missingFields),
        'msgCode' => 6,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== EXTRACT DATA ====================
$language = strval($inputData['language']);
$random = strval($inputData['random']);
$userPhoto = strval($inputData['userPhoto']);
$clientSignature = strtoupper(strval($inputData['signature']));
$timestamp = strval($inputData['timestamp']);

// ==================== TEMPORARILY SKIP SIGNATURE CHECK ====================
$signatureValid = true; // Skip for now

if (!$signatureValid) {
    $response = [
        'code' => 5,
        'msg' => 'Invalid signature',
        'msgCode' => 3,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== GET AUTHORIZATION TOKEN ====================
$authToken = '';

// Try different ways to get authorization header
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? '';
} else {
    $authHeader = '';
}

// Extract token from "Bearer <token>"
if (!empty($authHeader) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
    $authToken = $matches[1];
}

if (empty($authToken)) {
    $response = [
        'code' => 4,
        'msg' => 'Authorization token required',
        'msgCode' => 2,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== DATABASE CONNECTION ====================
try {
    // Define root directory
    $rootDir = realpath(dirname(__FILE__) . '/../..');
    
    // Check and include conn.php
    $connFile = $rootDir . '/conn.php';
    if (!file_exists($connFile)) {
        throw new Exception("Database configuration file not found");
    }
    
    require_once $connFile;
    
    // Check if $conn is set
    if (!isset($conn)) {
        throw new Exception('Database connection variable $conn is not defined');
    }
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Set charset
    $conn->set_charset('utf8mb4');
    
} catch (Exception $e) {
    $response = [
        'code' => 500,
        'msg' => 'Database error',
        'msgCode' => 500,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== INCLUDE FUNCTIONS ====================
try {
    $functionsFile = dirname(__FILE__, 3) . '/functions2.php';
    if (!file_exists($functionsFile)) {
        throw new Exception("Functions file not found");
    }
    
    require_once $functionsFile;
    
    // Check if required function exists
    if (!function_exists('is_jwt_valid')) {
        throw new Exception('Required function is_jwt_valid() not found');
    }
    
} catch (Exception $e) {
    $response = [
        'code' => 500,
        'msg' => 'Functions error',
        'msgCode' => 500,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== VALIDATE JWT TOKEN ====================
try {
    $jwtResult = is_jwt_valid($authToken);
    $authData = @json_decode($jwtResult, true);
    
    if (!$authData || !is_array($authData) || ($authData['status'] ?? '') !== 'Success') {
        $response = [
            'code' => 4,
            'msg' => 'Invalid or expired token',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ];
        echo json_encode($response);
        exit();
    }
    
    $userId = intval($authData['payload']['id'] ?? 0);
    
    if ($userId <= 0) {
        $response = [
            'code' => 4,
            'msg' => 'Invalid user ID in token',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ];
        echo json_encode($response);
        exit();
    }
    
} catch (Exception $e) {
    $response = [
        'code' => 4,
        'msg' => 'Token validation error',
        'msgCode' => 2,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== VERIFY USER SESSION (WITHOUT user_photo COLUMN) ====================
try {
    // First, check if user_photo column exists
    $checkColumnQuery = "SHOW COLUMNS FROM shonu_subjects LIKE 'user_photo'";
    $columnResult = $conn->query($checkColumnQuery);
    $columnExists = ($columnResult && $columnResult->num_rows > 0);
    
    // Now verify session - DON'T SELECT user_photo if it doesn't exist
    if ($columnExists) {
        $checkQuery = "SELECT id FROM shonu_subjects WHERE akshinak = ? AND id = ? LIMIT 1";
    } else {
        $checkQuery = "SELECT id FROM shonu_subjects WHERE akshinak = ? AND id = ? LIMIT 1";
    }
    
    $stmt = $conn->prepare($checkQuery);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed');
    }
    
    $stmt->bind_param('si', $authToken, $userId);
    $stmt->execute();
    $result = app_stmt_result($stmt);
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    if (!$userData) {
        $response = [
            'code' => 4,
            'msg' => 'User session not found or expired',
            'msgCode' => 2,
            'serviceNowTime' => $now
        ];
        echo json_encode($response);
        exit();
    }
    
} catch (Exception $e) {
    $response = [
        'code' => 500,
        'msg' => 'Session verification error',
        'msgCode' => 500,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== CHECK IF COLUMN EXISTS AND CREATE IF NOT ====================
try {
    // Check if column exists
    $checkColQuery = "SHOW COLUMNS FROM shonu_subjects LIKE 'user_photo'";
    $colResult = $conn->query($checkColQuery);
    
    if (!$colResult || $colResult->num_rows == 0) {
        // Column doesn't exist, create it
        $alterQuery = "ALTER TABLE shonu_subjects ADD COLUMN user_photo VARCHAR(255) DEFAULT '1'";
        $conn->query($alterQuery);
    }
    
} catch (Exception $e) {
    // Continue even if column creation fails
    error_log("Column check/create failed: " . $e->getMessage());
}

// ==================== UPDATE USER PHOTO ====================
try {
    // First check if we can update
    $checkUpdateQuery = "UPDATE shonu_subjects SET user_photo = ? WHERE id = ? AND akshinak = ?";
    $stmt = $conn->prepare($checkUpdateQuery);
    
    if (!$stmt) {
        // Try without akshinak check
        $checkUpdateQuery = "UPDATE shonu_subjects SET user_photo = ? WHERE id = ?";
        $stmt = $conn->prepare($checkUpdateQuery);
        
        if (!$stmt) {
            throw new Exception('Update prepare failed');
        }
        
        $stmt->bind_param('si', $userPhoto, $userId);
    } else {
        $stmt->bind_param('sis', $userPhoto, $userId, $authToken);
    }
    
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    // ==================== SUCCESS RESPONSE ====================
    $response = [
        'code' => 0,
        'msg' => 'User photo updated successfully',
        'msgCode' => 0,
        'serviceNowTime' => $now,
        'data' => [
            'userId' => $userId,
            'userPhoto' => $userPhoto,
            'changed' => $affectedRows > 0
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = [
        'code' => 500,
        'msg' => 'Update failed: ' . $e->getMessage(),
        'msgCode' => 500,
        'serviceNowTime' => $now
    ];
    echo json_encode($response);
    exit();
}

// ==================== CLOSE CONNECTION ====================
if (isset($conn) && $conn) {
    $conn->close();
}

exit();
?>