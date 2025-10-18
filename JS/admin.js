document.addEventListener('DOMContentLoaded', () => {
    // --- Modal Functionality ---
    const reservationModal = document.getElementById('reservationModal');
    const closeButton = document.querySelector('.modal .close-button');
    const modalDetails = document.getElementById('modalDetails');
    const modalConfirmBtn = document.querySelector('.modal-confirm-btn');
    const modalDeleteBtn = document.querySelector('.modal-delete-btn');
    const modalDeclineBtn = document.querySelector('.modal-decline-btn');

    let currentReservationId = null;

    // Open Modal
    function openModal(reservationData) {
        if (!reservationData || Object.keys(reservationData).length === 0) {
            return;
        }
        modalDetails.innerHTML = '';
        currentReservationId = reservationData['Reservation ID'];
        for (const key in reservationData) {
            if (Object.hasOwnProperty.call(reservationData, key)) {
                const p = document.createElement('p');
                p.innerHTML = `<strong>${key}:</strong> ${reservationData[key]}`;
                modalDetails.appendChild(p);
            }
        }
        reservationModal.style.display = 'flex';
    }

    // Close Modal
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            reservationModal.style.display = 'none';
            currentReservationId = null;
        });
    }
    window.addEventListener('click', (event) => {
        if (event.target === reservationModal) {
            reservationModal.style.display = 'none';
            currentReservationId = null;
        }
    });

    // --- Action Buttons Functionality ---
    const reservationTableBody = document.querySelector('table tbody');
    if (reservationTableBody) {
        reservationTableBody.addEventListener('click', async (event) => {
            const target = event.target;
            if (target.tagName === 'BUTTON' && target.closest('.actions')) {
                const row = target.closest('tr');
                if (!row) return;
                const fullReservationJson = row.dataset.fullReservation;
                try {
                    const fullReservationData = JSON.parse(fullReservationJson);
                    if (target.classList.contains('view-btn')) {
                        openModal(fullReservationData);
                    }
                } catch (e) {
                    console.error("Error parsing reservation data:", e);
                }
            }
        });
    }

    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener('click', async () => {
            if (currentReservationId) {
                const row = document.querySelector(`tr[data-reservation-id="${currentReservationId}"]`);
                modalConfirmBtn.classList.add('btn-loading');
                modalDeclineBtn.disabled = true;
                modalDeleteBtn.disabled = true;
                try {
                    await updateReservation(currentReservationId, 'Confirmed', 'update', row, 'update_reservation_status.php');
                    location.reload(); // Reload to update stats
                } finally {
                    modalConfirmBtn.classList.remove('btn-loading');
                    modalDeclineBtn.disabled = false;
                    modalDeleteBtn.disabled = false;
                    reservationModal.style.display = 'none';
                }
            }
        });
    }

    if (modalDeclineBtn) {
        modalDeclineBtn.addEventListener('click', async () => {
            if (currentReservationId) {
                const row = document.querySelector(`tr[data-reservation-id="${currentReservationId}"]`);
                modalDeclineBtn.classList.add('btn-loading');
                modalConfirmBtn.disabled = true;
                modalDeleteBtn.disabled = true;
                try {
                    await updateReservation(currentReservationId, 'Declined', 'update', row, 'update_reservation_status.php');
                    location.reload(); // Reload to update stats
                } finally {
                    modalDeclineBtn.classList.remove('btn-loading');
                    modalConfirmBtn.disabled = false;
                    modalDeleteBtn.disabled = false;
                    reservationModal.style.display = 'none';
                }
            }
        });
    }

    if (modalDeleteBtn) {
        modalDeleteBtn.addEventListener('click', async () => {
            if (currentReservationId && confirm('Are you sure you want to move this reservation to the deletion history?')) {
                const row = document.querySelector(`tr[data-reservation-id="${currentReservationId}"]`);
                await updateReservation(currentReservationId, null, 'delete', row, 'update_reservation.php');
                reservationModal.style.display = 'none';
            }
        });
    }

    async function updateReservation(reservationId, newStatus, actionType, rowElement, targetPhpFile) {
        const formData = new URLSearchParams();
        formData.append('reservation_id', reservationId);
        formData.append('action', actionType);
        if (actionType === 'update') {
            formData.append('status', newStatus);
        }

        try {
            const response = await fetch(targetPhpFile, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();
            if (!result.success) {
                console.error('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // --- Calendar Widget Functionality (using FullCalendar) ---
    if ($('#calendar').length) {
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaDay'
            },
            events: 'get_reservations.php',
            editable: false,
            droppable: false,
            eventLimit: true,
        });
    }


    // --- Search and Stats Update ---
    const searchInputTop = document.getElementById('reservationSearchTop');
    if (searchInputTop) {
        searchInputTop.addEventListener('keyup', () => {
            const filter = searchInputTop.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(row => {
                const customerName = row.querySelector('.customer-info strong')?.textContent.toLowerCase() || '';
                const customerEmail = row.querySelector('.customer-info small')?.textContent.toLowerCase() || '';
                if (customerName.includes(filter) || customerEmail.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});