document.addEventListener('DOMContentLoaded', () => {
    const reservationModal = document.getElementById('reservationModal');
    const editReservationForm = document.getElementById('editReservationForm');

    // Edit Modal Fields
    const modalReservationId = document.getElementById('modalReservationId');
    const modalResName = document.getElementById('modalResName');
    const modalResEmail = document.getElementById('modalResEmail');
    const modalResPhone = document.getElementById('modalResPhone');
    const modalResDate = document.getElementById('modalResDate');
    const modalResTime = document.getElementById('modalResTime');
    const modalNumGuests = document.getElementById('modalNumGuests');
    const modalReservationType = document.getElementById('modalReservationType');
    const modalStatus = document.getElementById('modalStatus');
    const modalCreatedAt = document.getElementById('modalCreatedAt');
    const validIdDisplay = document.getElementById('validIdDisplay');
    const modalDeleteBtn = document.querySelector('#reservationModal .modal-delete-btn');

    // Add Modal Fields
    const addReservationModal = document.getElementById('addReservationModal');
    const addReservationBtn = document.getElementById('addReservationBtn');
    const addReservationForm = document.getElementById('addReservationForm');

    // Delete Modal
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    let itemToDelete = { id: null, element: null };

    // NEW: Image Modal Fields
    const imageIdModal = document.getElementById('imageIdModal');
    const modalImageContent = document.getElementById('modalImageContent');
    const closeImageModalBtn = document.querySelector('.close-image-modal');
    
    // General Modal Closing Logic
    const closeButtons = document.querySelectorAll('.modal .close-button');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            button.closest('.modal').style.display = 'none';
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });

    // NEW: Image Modal Event Listeners
    if (imageIdModal) {
        closeImageModalBtn.addEventListener('click', () => {
            imageIdModal.style.display = 'none';
        });
        imageIdModal.addEventListener('click', (e) => {
            if (e.target === imageIdModal) {
                imageIdModal.style.display = 'none';
            }
        });
    }

    // UPDATED: Function to open the main edit modal
    function openReservationModal(reservationData) {
        modalReservationId.value = reservationData.reservation_id;
        modalResName.value = reservationData.res_name;
        modalResEmail.value = reservationData.res_email;
        modalResPhone.value = reservationData.res_phone;
        modalResDate.value = reservationData.res_date;
        modalResTime.value = reservationData.res_time;
        modalNumGuests.value = reservationData.num_guests;
        modalReservationType.value = reservationData.reservation_type;
        modalStatus.value = reservationData.status;
        modalCreatedAt.value = reservationData.created_at;

        if (reservationData.valid_id_path) {
            validIdDisplay.innerHTML = `<button type="button" class="btn btn-small view-id-btn" data-src="${reservationData.valid_id_path}" style="background-color: #007bff; color: white;">View Uploaded ID</button>`;
        } else {
            validIdDisplay.innerHTML = `<p style="color: #888; margin: 0; padding-top: 8px;">No ID was uploaded for this reservation.</p>`;
        }

        reservationModal.style.display = 'flex';
    }

    // NEW: Delegated event listener for the dynamically created "View Uploaded ID" button
    document.body.addEventListener('click', function(event) {
        if (event.target.classList.contains('view-id-btn')) {
            const imgSrc = event.target.dataset.src;
            if (imgSrc && imageIdModal) {
                modalImageContent.src = imgSrc;
                imageIdModal.style.display = 'flex';
            }
        }
    });

    // --- The rest of the file (form submissions, delete logic, pagination) remains the same ---
    if (editReservationForm) {
        editReservationForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(editReservationForm);
            formData.append('action', 'update');
            try {
                const response = await fetch('update_reservation.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            }
        });
    }
    if (addReservationBtn) {
        addReservationBtn.addEventListener('click', () => {
            addReservationForm.reset();
            addReservationModal.style.display = 'flex';
        });
    }
    if (addReservationForm) {
        addReservationForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(addReservationForm);
            formData.append('action', 'create');
            try {
                const response = await fetch('update_reservation.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            }
        });
    }
    function openConfirmDeleteModal(reservationId, rowElement) {
        itemToDelete.id = reservationId;
        itemToDelete.element = rowElement;
        confirmDeleteModal.style.display = 'flex';
    }
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', () => {
            if (itemToDelete.id) {
                deleteReservation(itemToDelete.id, itemToDelete.element);
                confirmDeleteModal.style.display = 'none';
            }
        });
    }
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            confirmDeleteModal.style.display = 'none';
        });
    }
    if (modalDeleteBtn) {
        modalDeleteBtn.addEventListener('click', () => {
            const reservationId = modalReservationId.value;
            if (reservationId) {
                const row = document.querySelector(`tr[data-reservation-id="${reservationId}"]`);
                reservationModal.style.display = 'none';
                openConfirmDeleteModal(reservationId, row);
            }
        });
    }
    async function deleteReservation(reservationId, rowElement) {
        const formData = new URLSearchParams();
        formData.append('reservation_id', reservationId);
        formData.append('action', 'delete');
        try {
            const response = await fetch('update_reservation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();
            if (result.success) { location.reload(); } 
            else { alert('Error: ' + result.message); }
        } catch (error) {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
        }
    }
    const reservationTableBody = document.querySelector('table tbody');
    if (reservationTableBody) {
        reservationTableBody.addEventListener('click', (event) => {
            const target = event.target;
            const row = target.closest('tr');
            if (!row) return;

            if (target.classList.contains('view-edit-btn')) {
                const fullReservationJson = row.dataset.fullReservation;
                try {
                    const reservationData = JSON.parse(fullReservationJson);
                    openReservationModal(reservationData);
                } catch (e) { console.error("Error parsing reservation data:", e); }
            } else if (target.classList.contains('delete-btn')) {
                const reservationId = row.dataset.reservationId;
                openConfirmDeleteModal(reservationId, row);
            }
        });
    }
    const tableBody = document.querySelector('table tbody');
    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    const rowsPerPage = 6;
    let currentPage = 1;
    let filteredRows = allRows;
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const pageNumbersContainer = document.getElementById('pageNumbers');
    function displayPage(page) {
        currentPage = page;
        allRows.forEach(row => row.style.display = 'none');
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedItems = filteredRows.slice(start, end);
        paginatedItems.forEach(row => { row.style.display = ''; });
        updatePaginationUI();
    }
    function updatePaginationUI() {
        const pageCount = Math.ceil(filteredRows.length / rowsPerPage);
        if(prevPageBtn) prevPageBtn.disabled = currentPage === 1;
        if(nextPageBtn) nextPageBtn.disabled = currentPage === pageCount || pageCount === 0;
        if(pageNumbersContainer) {
            pageNumbersContainer.innerHTML = '';
            for (let i = 1; i <= pageCount; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = 'page-number' + (i === currentPage ? ' active' : '');
                pageBtn.addEventListener('click', () => displayPage(i));
                pageNumbersContainer.appendChild(pageBtn);
            }
        }
    }
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) displayPage(currentPage - 1);
        });
    }
    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', () => {
            const pageCount = Math.ceil(filteredRows.length / rowsPerPage);
            if (currentPage < pageCount) displayPage(currentPage + 1);
        });
    }
    const searchInput = document.getElementById('reservationSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            const filter = searchInput.value.toLowerCase();
            filteredRows = allRows.filter(row => {
                const rowText = row.textContent.toLowerCase();
                return rowText.includes(filter);
            });
            displayPage(1);
        });
    }
    const paginationContainer = document.querySelector('.pagination-container');
    if (allRows.length > 0) {
        displayPage(1);
    } else if (paginationContainer) {
        paginationContainer.style.display = 'none';
    }
});