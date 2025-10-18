<?php
session_start(); // Start the session
require_once 'db_connect.php'; // Include your database connection

header('Content-Type: application/json'); // Set header to return JSON response

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Basic authentication check: Only logged-in admin can perform actions
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $response['message'] = 'Unauthorized access. Please log in as an administrator.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? null;
    
    // --- CREATE ACTION for Walk-ins ---
    if ($action === 'create') {
        $res_name = htmlspecialchars(trim($_POST['res_name'] ?? ''));
        $res_email = filter_var(trim($_POST['res_email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $res_phone = htmlspecialchars(trim($_POST['res_phone'] ?? ''));
        $res_date = htmlspecialchars(trim($_POST['res_date'] ?? ''));
        $res_time = htmlspecialchars(trim($_POST['res_time'] ?? ''));
        $num_guests = filter_var(trim($_POST['num_guests'] ?? ''), FILTER_SANITIZE_NUMBER_INT);
        $status = "Confirmed"; // Walk-ins are typically confirmed immediately
        $source = "Walk-in"; // Set the source

        if (empty($res_name) || empty($res_email) || empty($res_phone) || empty($res_date) || empty($res_time) || empty($num_guests)) {
            $response['message'] = 'Please fill in all required fields for the new reservation.';
            echo json_encode($response);
            exit;
        }

        $sql = "INSERT INTO reservations (res_name, res_email, res_phone, res_date, res_time, num_guests, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssiss", $res_name, $res_email, $res_phone, $res_date, $res_time, $num_guests, $status, $source);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Walk-in reservation added successfully.';
            } else {
                $response['message'] = 'Database error: Could not add reservation.';
                error_log("Create reservation error: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error: Could not prepare statement for creation.';
            error_log("Prepare create statement error: " . mysqli_error($link));
        }
    }
    // --- UPDATE ACTION ---
    elseif ($action === 'update') {
        $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_SANITIZE_NUMBER_INT);
        if(empty($reservation_id)) {
             $response['message'] = 'Missing reservation ID for update.';
             echo json_encode($response);
             exit;
        }

        $res_name = htmlspecialchars(trim($_POST['res_name'] ?? ''));
        $res_email = filter_var(trim($_POST['res_email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $res_phone = htmlspecialchars(trim($_POST['res_phone'] ?? ''));
        $res_date = htmlspecialchars(trim($_POST['res_date'] ?? ''));
        $res_time = htmlspecialchars(trim($_POST['res_time'] ?? ''));
        $num_guests = filter_var(trim($_POST['num_guests'] ?? ''), FILTER_SANITIZE_NUMBER_INT);
        $status = htmlspecialchars(trim($_POST['status'] ?? ''));

        if (empty($res_name) || empty($res_email) || empty($res_date) || empty($res_time) || empty($num_guests) || empty($status)) {
            $response['message'] = 'Missing required fields for update.';
            echo json_encode($response);
            exit;
        }

        $sql = "UPDATE reservations SET res_name = ?, res_email = ?, res_phone = ?, res_date = ?, res_time = ?, num_guests = ?, status = ? WHERE reservation_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssisi", $res_name, $res_email, $res_phone, $res_date, $res_time, $num_guests, $status, $reservation_id);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Reservation updated successfully.';
            } else {
                $response['message'] = 'Database error: Could not update reservation.';
                error_log("Update reservation error: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error: Could not prepare statement for update.';
            error_log("Prepare update statement error: " . mysqli_error($link));
        }

    // --- DELETE (SOFT-DELETE) ACTION ---
    } elseif ($action === 'delete') {
        $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_SANITIZE_NUMBER_INT);
         if(empty($reservation_id)) {
             $response['message'] = 'Missing reservation ID for deletion.';
             echo json_encode($response);
             exit;
        }

        // First, retrieve the full reservation data to be logged before deleting
        $sql_select = "SELECT * FROM reservations WHERE reservation_id = ?";
        $stmt_select = mysqli_prepare($link, $sql_select);
        mysqli_stmt_bind_param($stmt_select, "i", $reservation_id);
        mysqli_stmt_execute($stmt_select);
        $result = mysqli_stmt_get_result($stmt_select);
        $reservation_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_select);

        if ($reservation_data) {
            $item_data_json = json_encode($reservation_data);

            // Use a transaction to ensure both operations succeed or fail together
            mysqli_begin_transaction($link);

            try {
                // 1. Perform the soft-delete by setting the 'deleted_at' timestamp
                $sql_soft_delete = "UPDATE reservations SET deleted_at = NOW() WHERE reservation_id = ?";
                $stmt_soft_delete = mysqli_prepare($link, $sql_soft_delete);
                mysqli_stmt_bind_param($stmt_soft_delete, "i", $reservation_id);
                mysqli_stmt_execute($stmt_soft_delete);
                mysqli_stmt_close($stmt_soft_delete);

                // 2. Log the deleted item into the 'deletion_history' table
                // The purge date is automatically set for 30 days in the future
                $sql_log = "INSERT INTO deletion_history (item_type, item_id, item_data, purge_date) VALUES ('reservation', ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
                $stmt_log = mysqli_prepare($link, $sql_log);
                mysqli_stmt_bind_param($stmt_log, "is", $reservation_id, $item_data_json);
                mysqli_stmt_execute($stmt_log);
                mysqli_stmt_close($stmt_log);

                // If everything is successful, commit the changes
                mysqli_commit($link);
                $response['success'] = true;
                $response['message'] = 'Reservation moved to deletion history successfully.';

            } catch (mysqli_sql_exception $exception) {
                // If any step fails, roll back the changes
                mysqli_rollback($link);
                $response['message'] = 'Database error during deletion process.';
                error_log("Reservation deletion transaction failed: " . $exception->getMessage());
            }
        } else {
            $response['message'] = 'No reservation found with the given ID to delete.';
        }
    } else {
        $response['message'] = 'Invalid action specified.';
    }

    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>