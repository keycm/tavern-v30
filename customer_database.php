<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in AND is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

// NEW: Automatically delete unverified users whose OTP has expired.
// This ensures the list is clean every time an admin views this page.
$cleanup_sql = "DELETE FROM users WHERE is_verified = 0 AND otp_expiry < NOW()";
if (!mysqli_query($link, $cleanup_sql)) {
    error_log("Failed to cleanup expired users on customer_database.php: " . mysqli_error($link));
}
// END NEW LOGIC

// Fetch all non-admin users from the database
$users = [];
$sql = "SELECT user_id, username, email, created_at, is_verified FROM users WHERE is_admin = 0 AND deleted_at IS NULL ORDER BY created_at DESC";

if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
} else {
    error_log("Customer Database page error: " . mysqli_error($link));
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Customer Database</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
                    <li class="menu-item"><a href="table_management.php"><i class="material-icons">table_chart</i> Calendar Management</a></li>
                    <li class="menu-item active"><a href="customer_database.php"><i class="material-icons">people</i> Customer Database</a></li>
                    <li class="menu-item"><a href="reports.php"><i class="material-icons">analytics</i>Reservation Reports</a></li>
                    <li class="menu-item"><a href="deletion_history.php"><i class="material-icons">history</i> Archive</a></li>
                    <li class="menu-item"><a href="logout.php"><i class="material-icons">logout</i> Log out</a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin-content-area">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="header-page-title">Customer Database</h1>
                    <div class="admin-header-right">

                        <?php $admin_avatar_path = isset($_SESSION['avatar']) && file_exists($_SESSION['avatar']) ? htmlspecialchars($_SESSION['avatar']) : 'images/default_avatar.png'; ?>
                        <img src="<?php echo $admin_avatar_path; ?>" alt="Admin Avatar" class="admin-avatar">
                        <div class="admin-user-info">
                            <span class="admin-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="admin-role"></span>
                        </div>
                        
                        <div class="admin-notification-area">
                            <div class="admin-notification-item">
                                <button class="admin-notification-button" id="adminMessageBtn" title="Messages">
                                    <i class="material-icons">email</i>
                                    <span class="admin-notification-badge" id="adminMessageCount" style="display: none;">0</span>
                                </button>
                                <div class="admin-notification-dropdown" id="adminMessageDropdown"></div>
                            </div>
                            <div class="admin-notification-item">
                                <button class="admin-notification-button" id="adminReservationBtn" title="Reservations">
                                    <i class="material-icons">event_note</i>
                                    <span class="admin-notification-badge" id="adminReservationCount" style="display: none;">0</span>
                                </button>
                                <div class="admin-notification-dropdown" id="adminReservationDropdown"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </header>

            <main class="dashboard-main-content">
                <div class="reservation-page-header">
                    <input type="text" id="userSearch" class="search-input" placeholder="Search customers...">
                    <button id="addNewUserBtn" class="btn btn-primary" style="background-color: #28a745;">Add New Customer</button>
                </div>

                <section class="all-reservations-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>USER ID</th>
                                    <th>USERNAME</th>
                                    <th>EMAIL</th>
                                    <th>DATE JOINED</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="6" style="text-align: center;">No customers found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr data-user-id="<?= $user['user_id']; ?>"
                                            data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES); ?>"
                                            data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES); ?>">
                                            <td><?= sprintf('%04d', $user['user_id']); ?></td>
                                            <td><?= htmlspecialchars($user['username']); ?></td>
                                            <td><?= htmlspecialchars($user['email']); ?></td>
                                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                                            <td>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="status-badge confirmed">Verified</span>
                                                <?php else: ?>
                                                    <span class="status-badge pending">Not Verified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <?php if (!$user['is_verified']): ?>
                                                    <button class="btn btn-small verify-btn" style="background-color: #17a2b8;">Verify</button>
                                                <?php endif; ?>
                                                <button class="btn btn-small view-edit-btn">Edit</button>
                                                <button class="btn btn-small delete-btn">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <div id="userModal" class="modal">
                <div class="modal-content">
                    <span class="close-button">&times;</span>
                    <h2 id="modalTitle">Add New Customer</h2>
                    <form id="userForm">
                        <input type="hidden" id="userId" name="user_id">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password">
                            <small id="passwordHelp">Leave blank to keep the current password.</small>
                        </div>
                        <div class="form-group">
                            <label for="retype_password">Retype Password</label>
                            <input type="password" id="retype_password" name="retype_password">
                        </div>
                        <div class="modal-actions">
                            <button type="submit" class="btn modal-save-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    
    <div id="alertModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <span class="close-button">&times;</span>
            <h2 id="alertModalTitle" style="margin-top: 0;"></h2>
            <p id="alertModalMessage"></p>
            <div id="alertModalActions" class="modal-actions" style="justify-content: center;">
                </div>
        </div>
    </div>


    <script src="JS/customer_database.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const messageBtn = document.getElementById('adminMessageBtn');
        const reservationBtn = document.getElementById('adminReservationBtn');
        const messageDropdown = document.getElementById('adminMessageDropdown');
        const reservationDropdown = document.getElementById('adminReservationDropdown');
        
        const messageCountBadge = document.getElementById('adminMessageCount');
        const reservationCountBadge = document.getElementById('adminReservationCount');

        async function fetchAdminNotifications() {
            try {
                const response = await fetch('get_admin_notifications.php');
                const data = await response.json();

                if (data.success) {
                    // Update Message Count and Dropdown
                    if (data.new_messages > 0) {
                        messageCountBadge.textContent = data.new_messages;
                        messageCountBadge.style.display = 'block';
                    } else {
                        messageCountBadge.style.display = 'none';
                    }
                    messageDropdown.innerHTML = data.messages_html;

                    // Update Reservation Count and Dropdown
                    if (data.pending_reservations > 0) {
                        reservationCountBadge.textContent = data.pending_reservations;
                        reservationCountBadge.style.display = 'block';
                    } else {
                        reservationCountBadge.style.display = 'none';
                    }
                    reservationDropdown.innerHTML = data.reservations_html;
                }
            } catch (error) {
                console.error('Error fetching admin notifications:', error);
            }
        }

        // Toggle dropdowns
        messageBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            reservationDropdown.classList.remove('show');
            messageDropdown.classList.toggle('show');
        });

        reservationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            messageDropdown.classList.remove('show');
            reservationDropdown.classList.toggle('show');
        });

        // Close dropdowns when clicking outside
        window.addEventListener('click', () => {
            messageDropdown.classList.remove('show');
            reservationDropdown.classList.remove('show');
        });
        
        // Prevent dropdown from closing when clicking inside link area
        [messageDropdown, reservationDropdown].forEach(dropdown => {
            dropdown.addEventListener('click', (e) => {
                // Only stop propagation if it's NOT the dismiss button
                if (!e.target.classList.contains('admin-notification-dismiss')) {
                    e.stopPropagation();
                }
            });
        });

        // --- Handle Dismiss Click ---
        async function handleDismiss(e) {
            if (!e.target.classList.contains('admin-notification-dismiss')) return;

            e.preventDefault(); // Prevent default button action
            e.stopPropagation(); // Stop event from bubbling up and closing dropdown

            const button = e.target;
            const id = button.dataset.id;
            const type = button.dataset.type;
            const itemWrapper = button.parentElement;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', type);

            try {
                const response = await fetch('clear_admin_notification.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    // Visually remove the item
                    itemWrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    itemWrapper.style.opacity = '0';
                    itemWrapper.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        itemWrapper.remove();
                        // Refetch to update counts and check if dropdown should be empty
                        fetchAdminNotifications(); 
                    }, 300);
                } else {
                    alert(result.message); // Show alert for actions that can't be dismissed
                }
            } catch (error) {
                console.error('Error dismissing notification:', error);
                alert('An error occurred. Please try again.');
            }
        }

        messageDropdown.addEventListener('click', handleDismiss);
        reservationDropdown.addEventListener('click', handleDismiss);

        // Initial fetch and polling
        fetchAdminNotifications();
        setInterval(fetchAdminNotifications, 30000); // Check for new notifications every 30 seconds
    });
    </script>
</body>
</html>