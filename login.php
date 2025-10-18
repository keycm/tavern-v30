<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'redirect' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $response['message'] = 'Please fill in both username/email and password.';
        echo json_encode($response);
        exit;
    }

    // MODIFIED SQL to select the new 'avatar' column
    $sql = "SELECT user_id, username, password_hash, is_admin, is_verified, avatar FROM users WHERE username = ? OR email = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                // MODIFIED bind_result to include the avatar
                mysqli_stmt_bind_result($stmt, $user_id, $db_username, $hashed_password, $is_admin, $is_verified, $avatar);
                mysqli_stmt_fetch($stmt);

                if (password_verify($password, $hashed_password)) {
                    if ($is_verified == 1) {
                        session_regenerate_id(true);
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $db_username;
                        $_SESSION['is_admin'] = boolval($is_admin);
                        $_SESSION['avatar'] = $avatar; // NEW: Store avatar in session
                        $_SESSION['show_rating_modal'] = true; // Trigger the rating modal on next page load

                        $response['success'] = true;
                        $response['message'] = 'Login successful!';
                        $response['redirect'] = $_SESSION['is_admin'] ? 'admin.php' : 'index.php';
                    } else {
                        $response['message'] = 'Please verify your email address before logging in.';
                    }
                } else {
                    $response['message'] = 'Invalid username/email or password.';
                }
            } else {
                $response['message'] = 'Invalid username/email or password.';
            }
        } else {
            $response['message'] = 'Oops! Something went wrong.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database error.';
    }
    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>