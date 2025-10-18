<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in AND is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

// --- BUG FIX: Dynamic Back Link Logic ---
$return_to_page = 'admin.php'; // Default fallback page
$return_title = 'Back to Dashboard';
if (isset($_GET['return_to'])) {
    if ($_GET['return_to'] === 'reservation') {
        $return_to_page = 'reservation.php';
        $return_title = 'Back to Reservations';
    }
}

// Get customer ID from the URL, ensure it's an integer
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id <= 0) {
    die("Error: Invalid Customer ID.");
}

// Fetch customer data from the database, including mobile and birthday
$customer = null;
$sql_user = "SELECT username, email, created_at, avatar, mobile, birthday FROM users WHERE user_id = ?";
if ($stmt_user = mysqli_prepare($link, $sql_user)) {
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    if (mysqli_stmt_execute($stmt_user)) {
        $result_user = mysqli_stmt_get_result($stmt_user);
        $customer = mysqli_fetch_assoc($result_user);
    }
    mysqli_stmt_close($stmt_user);
}

// If customer not found, stop the script
if (!$customer) {
    die("Error: Customer not found.");
}

// Fetch the customer's reservations
$reservations = [];
$sql_reservations = "SELECT res_date, res_time, num_guests, status, created_at FROM reservations WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC";
if ($stmt_reservations = mysqli_prepare($link, $sql_reservations)) {
    mysqli_stmt_bind_param($stmt_reservations, "i", $customer_id);
    if (mysqli_stmt_execute($stmt_reservations)) {
        $result_reservations = mysqli_stmt_get_result($stmt_reservations);
        while ($row = mysqli_fetch_assoc($result_reservations)) {
            $reservations[] = $row;
        }
    }
    mysqli_stmt_close($stmt_reservations);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile - <?= htmlspecialchars($customer['username']); ?></title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link rel="stylesheet" href="CSS/profile.css"> 
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .profile-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .close-profile-btn {
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: #888;
            transition: color 0.2s;
        }
        .close-profile-btn:hover {
            color: #333;
        }
        .toggle-history-btn {
            display: block;
            margin: 15px auto 0;
            background: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="Tavern.png" alt="Home Icon" class="home-icon">
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li class="menu-item"><a href="admin.php"><i class="material-icons">dashboard</i> Dashboard</a></li>
                    <li class="menu-item"><a href="update.php"><i class="material-icons">file_upload</i> Upload Management</a></li>
                    <li class="menu-item"><a href="reservation.php"><i class="material-icons">event_note</i> Reservation</a></li>
                </ul>
                <div class="user-management-title">User Management</div>
                <ul class="sidebar-menu user-management-menu">
                    <li class="menu-item"><a href="notification_control.php"><i class="material-icons">notifications</i> Notification Control</a></li>
                    <li class="menu-item"><a href="table_management.php"><i class="material-icons">table_chart</i> Table Management</a></li>
                    <li class="menu-item"><a href="customer_database.php"><i class="material-icons">people</i> Customer Database</a></li>
                    <li class="menu-item"><a href="reports.php"><i class="material-icons">analytics</i>Reservation Reports</a></li>
                    <li class="menu-item"><a href="deletion_history.php"><i class="material-icons">history</i> Deletion History</a></li>
                    <li class="menu-item"><a href="logout.php"><i class="material-icons">logout</i> Log out</a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin-content-area">
            <header class="main-header">
                <div class="header-content">
                     <div class="admin-header-right">
                        <div class="admin-user-info">
                            <span class="admin-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="admin-role">Admin</span>
                        </div>
                        <?php
                        $admin_avatar_path = isset($_SESSION['avatar']) && file_exists($_SESSION['avatar']) 
                                            ? htmlspecialchars($_SESSION['avatar']) 
                                            : 'images/default_avatar.png';
                        ?>
                        <img src="<?php echo $admin_avatar_path; ?>" alt="Admin Avatar" class="admin-avatar">
                    </div>
                </div>
            </header>

            <main class="dashboard-main-content">
                <div class="profile-header-container">
                    <h1 class="dashboard-heading" style="margin-bottom: 0;">Customer Profile</h1>
                    <a href="<?php echo htmlspecialchars($return_to_page); ?>" class="close-profile-btn" title="<?php echo htmlspecialchars($return_title); ?>">&times;</a>
                </div>

                <div class="profile-content-grid">
                    <div class="profile-details-card">
                        <div class="card-header" style="display: flex; align-items: center; gap: 15px;">
                            <?php $avatar_path = !empty($customer['avatar']) && file_exists($customer['avatar']) ? $customer['avatar'] : 'images/default_avatar.png'; ?>
                            <img src="<?= htmlspecialchars($avatar_path) ?>" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%;">
                            <h3><?= htmlspecialchars($customer['username']); ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?= htmlspecialchars($customer['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Mobile</span>
                                <span class="info-value"><?= htmlspecialchars($customer['mobile'] ?? 'Not Provided'); ?></span>
                            </div>
                             <div class="info-row">
                                <span class="info-label">Birthday</span>
                                <span class="info-value"><?= !empty($customer['birthday']) ? date('F j, Y', strtotime($customer['birthday'])) : 'Not Provided'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Member Since</span>
                                <span class="info-value"><?= date('F j, Y', strtotime($customer['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="reservation-history-card">
                        <div class="card-header">
                            <h3><i class="material-icons">calendar_today</i> Reservation History</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Guests</th>
                                            <th>Status</th>
                                            <th>Booked On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($reservations)): ?>
                                            <?php foreach ($reservations as $index => $res): ?>
                                                <tr class="reservation-row" style="<?= $index >= 3 ? 'display: none;' : '' ?>">
                                                    <td><?= htmlspecialchars($res['res_date']); ?></td>
                                                    <td><?= htmlspecialchars(date('g:i A', strtotime($res['res_time']))); ?></td>
                                                    <td><?= htmlspecialchars($res['num_guests']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?= strtolower(htmlspecialchars($res['status'])); ?>">
                                                            <?= htmlspecialchars($res['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($res['created_at']))); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="no-reservations">This customer has no reservation history.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <?php if (count($reservations) > 3): ?>
                                    <button id="toggleHistoryBtn" class="toggle-history-btn">Show More</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('toggleHistoryBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const rows = document.querySelectorAll('.reservation-row');
                    const isShowingAll = this.textContent === 'Show Less';

                    rows.forEach((row, index) => {
                        if (index >= 3) {
                            row.style.display = isShowingAll ? 'none' : 'table-row';
                        }
                    });

                    this.textContent = isShowingAll ? 'Show More' : 'Show Less';
                });
            }
        });
    </script>
</body>
</html>