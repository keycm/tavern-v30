<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'reservations' => [], 'message' => 'An error occurred.'];

// Security check: ensure user is a logged-in admin
if (!isset($_SESSION['loggedin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if (isset($_GET['date'])) {
    $date = $_GET['date'];

    // Prepare and execute the query to get reservations for the selected date
    $sql = "SELECT res_name, res_phone, res_time, num_guests, status 
            FROM reservations 
            WHERE res_date = ? AND deleted_at IS NULL 
            ORDER BY res_time ASC";
            
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $reservations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Format time for better readability
            $row['res_time_formatted'] = date('g:i A', strtotime($row['res_time']));
            $reservations[] = $row;
        }
        
        $response['success'] = true;
        $response['reservations'] = $reservations;
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database query failed.';
    }
} else {
    $response['message'] = 'No date provided.';
}

mysqli_close($link);
echo json_encode($response);
?>