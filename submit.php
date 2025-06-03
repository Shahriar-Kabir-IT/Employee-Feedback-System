<?php
date_default_timezone_set('Asia/Dhaka');

// Database connection
$conn = new mysqli("localhost", "root", "1234", "feedback_db");

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

// Validate and sanitize input
$mood = isset($_POST['mood']) ? strtolower(trim($_POST['mood'])) : '';
$employee_id = isset($_POST['emp_id']) ? trim($_POST['emp_id']) : '';

if (!in_array($mood, ['happy', 'sad']) || empty($employee_id)) {
    http_response_code(400);
    echo "Invalid input.";
    exit;
}

// Prepare and execute insert
$stmt = $conn->prepare("INSERT INTO feedback (employee_id, mood, timestamp) VALUES (?, ?, NOW())");
$stmt->bind_param("ss", $employee_id, $mood);

if ($stmt->execute()) {
    http_response_code(200);
    echo "Success";
} else {
    http_response_code(500);
    echo "Error saving feedback.";
}

$stmt->close();
$conn->close();
?>
