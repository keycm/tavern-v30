<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$events = [];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['is_admin'] === true) {
    // --- UPDATED: Fetch individual reservations with specific times ---
    $sql_reservations = "SELECT res_name, res_date, res_time FROM reservations WHERE deleted_at IS NULL";
    if ($result = mysqli_query($link, $sql_reservations)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'title' => $row['res_name'], // The event title will now be the customer's name
                'start' => $row['res_date'] . 'T' . $row['res_time'], // Combines date and time to place it in the correct slot
                'backgroundColor' => '#28a745', // Green for reservations
                'borderColor' => '#28a745'
            ];
        }
    }

    // --- UNCHANGED: Fetch all-day blocked dates ---
    $sql_blocked = "SELECT block_date FROM blocked_dates";
    if ($result = mysqli_query($link, $sql_blocked)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'title' => 'Blocked',
                'start' => $row['block_date'],
                'backgroundColor' => '#dc3545', // Red for blocked dates
                'borderColor' => '#dc3545'
                // 'allDay' property is true by default when no time is specified
            ];
        }
    }
}

echo json_encode($events);
?>