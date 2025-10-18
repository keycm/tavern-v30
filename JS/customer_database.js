document.addEventListener('DOMContentLoaded', () => {
    // --- User Form Modal Elements ---
    const userModal = document.getElementById('userModal');
    const userModalCloseButton = userModal.querySelector('.close-button');
    const addNewUserBtn = document.getElementById('addNewUserBtn');
    const userForm = document.getElementById('userForm');
    const usersTableBody = document.getElementById('usersTableBody');
    const userSearchInput = document.getElementById('userSearch');

    const modalTitle = document.getElementById('modalTitle');
    const userIdInput = document.getElementById('userId');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const retypePasswordInput = document.getElementById('retype_password');
    const passwordHelp = document.getElementById('passwordHelp');
    
    // --- NEW Alert/Confirm Modal Elements ---
    const alertModal = document.getElementById('alertModal');
    const alertModalTitle = document.getElementById('alertModalTitle');
    const alertModalMessage = document.getElementById('alertModalMessage');
    const alertModalActions = document.getElementById('alertModalActions');
    const alertModalCloseBtn = alertModal.querySelector('.close-button');

    // --- NEW Modal Functions ---

    /**
     * Shows a modal with a title, message, and an "OK" button.
     * @param {string} title - The title of the modal.
     * @param {string} message - The message to display.
     * @param {function} [callback=null] - An optional function to run after the user clicks "OK".
     */
    const showAlert = (title, message, callback = null) => {
        alertModalTitle.textContent = title;
        alertModalMessage.textContent = message;
        alertModalActions.innerHTML = '<button class="btn" id="alertOkBtn" style="background-color: #007bff; color: white;">OK</button>';
        alertModal.style.display = 'flex';

        document.getElementById('alertOkBtn').onclick = () => {
            alertModal.style.display = 'none';
            if (callback) callback();
        };
    };

    /**
     * Shows a confirmation modal with "Cancel" and "Yes" buttons.
     * @param {string} title - The title of the modal.
     * @param {string} message - The confirmation question.
     * @param {function} callback - A function that receives `true` if "Yes" is clicked, `false` otherwise.
     */
    const showConfirm = (title, message, callback) => {
        alertModalTitle.textContent = title;
        alertModalMessage.textContent = message;
        alertModalActions.innerHTML = `
            <button class="btn" id="confirmCancelBtn" style="background-color: #6c757d; color: white;">Cancel</button>
            <button class="btn" id="confirmOkBtn" style="background-color: #dc3545; color: white;">Yes, Proceed</button>
        `;
        alertModal.style.display = 'flex';

        document.getElementById('confirmOkBtn').onclick = () => {
            alertModal.style.display = 'none';
            callback(true);
        };
        document.getElementById('confirmCancelBtn').onclick = () => {
            alertModal.style.display = 'none';
            callback(false);
        };
    };

    // --- Close Modal Logic ---
    alertModalCloseBtn.onclick = () => alertModal.style.display = 'none';
    window.addEventListener('click', (event) => {
        if (event.target === alertModal) {
            alertModal.style.display = 'none';
        }
    });

    const openModalForEdit = (user) => {
        userForm.reset();
        modalTitle.textContent = 'Edit Customer';
        userIdInput.value = user.id;
        usernameInput.value = user.username;
        emailInput.value = user.email;
        passwordInput.placeholder = "New password (optional)";
        passwordHelp.style.display = 'block';
        passwordInput.required = false;
        retypePasswordInput.required = false;
        userModal.style.display = 'flex';
    };

    const openModalForAdd = () => {
        userForm.reset();
        modalTitle.textContent = 'Add New Customer';
        userIdInput.value = '';
        passwordInput.placeholder = "Create a password";
        passwordHelp.style.display = 'none';
        passwordInput.required = true;
        retypePasswordInput.required = true;
        userModal.style.display = 'flex';
    };
    
    const closeUserModal = () => {
        userModal.style.display = 'none';
    };
    
    addNewUserBtn.addEventListener('click', openModalForAdd);
    userModalCloseButton.addEventListener('click', closeUserModal);
    window.addEventListener('click', (event) => {
        if (event.target === userModal) {
            closeUserModal();
        }
    });
    
    // --- CRUD and Verification Operations ---
    usersTableBody.addEventListener('click', (event) => {
        const target = event.target;
        const row = target.closest('tr');
        if (!row) return;

        const userId = row.dataset.userId;

        if (target.classList.contains('view-edit-btn')) {
            const userData = { id: userId, username: row.dataset.username, email: row.dataset.email };
            openModalForEdit(userData);
        }

        if (target.classList.contains('delete-btn')) {
            showConfirm('Confirm Deletion', `Are you sure you want to delete this user (${row.dataset.username})? This action cannot be undone.`, (confirmed) => {
                if (confirmed) {
                    deleteUser(userId);
                }
            });
        }
        
        if (target.classList.contains('verify-btn')) {
            showConfirm('Confirm Verification', `Are you sure you want to manually verify this user (${row.dataset.username})?`, (confirmed) => {
                if (confirmed) {
                    verifyUser(userId, target);
                }
            });
        }
    });

    userForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const password = passwordInput.value;
        const retypePassword = retypePasswordInput.value;

        if (password !== retypePassword) {
            showAlert('Error', 'The new passwords do not match.');
            return;
        }

        const formData = new FormData(userForm);
        formData.append('action', 'saveUser');

        try {
            const response = await fetch('manage_user.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            showAlert(result.success ? 'Success!' : 'Error', result.message, () => {
                if (result.success) {
                    closeUserModal();
                    location.reload();
                }
            });
        } catch (error) {
            console.error('Error submitting form:', error);
            showAlert('Error', 'An unexpected network error occurred.');
        }
    });

    async function deleteUser(id) {
        const formData = new FormData();
        formData.append('action', 'deleteUser');
        formData.append('user_id', id);

        try {
            const response = await fetch('manage_user.php', { method: 'POST', body: formData });
            const result = await response.json();
            showAlert(result.success ? 'Success!' : 'Error', result.message, () => {
                if (result.success) location.reload();
            });
        } catch (error) {
            console.error('Error deleting user:', error);
            showAlert('Error', 'An unexpected network error occurred.');
        }
    }
    
    async function verifyUser(id, buttonElement) {
        const formData = new FormData();
        formData.append('user_id', id);

        try {
            const response = await fetch('verify_user.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            showAlert(result.success ? 'Success!' : 'Error', result.message, () => {
                if (result.success) {
                    const statusCell = buttonElement.closest('tr').querySelector('.status-badge');
                    statusCell.classList.remove('pending');
                    statusCell.classList.add('confirmed');
                    statusCell.textContent = 'Verified';
                    buttonElement.remove();
                }
            });
        } catch (error) {
            console.error('Error verifying user:', error);
            showAlert('Error', 'An unexpected network error occurred.');
        }
    }

    // --- Search Functionality ---
    userSearchInput.addEventListener('keyup', () => {
        const filter = userSearchInput.value.toLowerCase();
        const rows = usersTableBody.querySelectorAll('tr');

        rows.forEach(row => {
            const username = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            if (username.includes(filter) || email.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});