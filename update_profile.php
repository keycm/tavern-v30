<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['loggedin']) || !$_SESSION['user_id']) {
    $response['message'] = 'You must be logged in to update your profile.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $username = trim($_POST['username'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $retype_password = $_POST['retype_password'] ?? '';

    if (empty($username)) {
        $response['message'] = 'Username cannot be empty.';
        echo json_encode($response);
        exit;
    }

    $sql_parts = [];
    $params = [];
    $types = "";

    $sql_parts[] = "username = ?";
    $params[] = $username;
    $types .= "s";
    
    $sql_parts[] = "birthday = ?";
    $params[] = $birthday;
    $types .= "s";

    $sql_parts[] = "mobile = ?";
    $params[] = $mobile;
    $types .= "s";

    if (!empty($new_password)) {
        if ($new_password !== $retype_password) {
            $response['message'] = 'Passwords do not match.';
            echo json_encode($response);
            exit;
        }
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_parts[] = "password_hash = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['username'] = $username;
            $response['success'] = true;
            $response['message'] = 'Your account settings have been saved.';
            $response['newUsername'] = $username;
        } else {
            $response['message'] = 'Error updating profile. The username might already be taken.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database error. Please try again.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>