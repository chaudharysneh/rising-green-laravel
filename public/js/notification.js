(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const listContainer = document.getElementById("notificationList");
        if (!listContainer) return;

        const paginationContainer = document.getElementById("notificationsPagination");
        const csrfToken = window.crmCsrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
        const notificationsListUrl = window.crmNotificationsListUrl || "/notifications/list";

        function formatDate(dateValue) {
            if (!dateValue) return "Not Set";
            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return "Not Set";

            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) return "just now";
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + "m ago";
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + "h ago";
            if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + "d ago";

            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        }

        function renderNotifications(items) {
            if (!items || items.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center text-muted py-5">
                        No notifications found.
                    </div>`;
                return;
            }

            listContainer.innerHTML = items.map(notification => `
                <div class="notification-row align-items-center">
                    <span class="notification-avatar">
                        <i class="bi bi-bell"></i>
                    </span>
                    <div class="d-flex flex-grow-1 justify-content-between align-items-center">
                        <div class="notification-message">
                            ${notification.notification_text}
                        </div>
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="notification-time">
                                ${formatDate(notification.created_at)}
                            </div>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <button class="dropdown-item mark-as-read-ajax fw-semibold" type="button"
                                data-id="${notification.id}">
                                <i class="fa-solid fa-check-double me-2"></i>Mark as Read
                            </button>
                        </div>
                    </div>
                </div>
            `).join("");

            attachEvents();
        }

        function renderPagination(data) {
            if (!paginationContainer) return;
            if (data.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `
                <div class="crm-pagination-container">
                    <div class="text-muted small fw-medium">
                        Showing ${from} to ${to} of ${total} results
                    </div>
                    <ul class="pagination crm-pagination mb-0">`;

            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            if (data.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            document.querySelectorAll('.page-link[data-page]').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    fetchNotifications(this.dataset.page);
                });
            });
        }

        function fetchNotifications(page = 1) {
            const separator = notificationsListUrl.includes("?") ? "&" : "?";
            $.ajax({
                url: `${notificationsListUrl}${separator}page=${page}`,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                beforeSend: function () {
                    listContainer.innerHTML = `
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        renderNotifications(res.data.data);
                        renderPagination(res.data);
                    }
                },
                error: function () {
                    listContainer.innerHTML = `
                        <div class="text-center text-danger py-5">
                            Error loading notifications. Please try again.
                        </div>`;
                }
            });
        }

        function attachEvents() {
            document.querySelectorAll('.mark-as-read-ajax').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const row = this.closest('.notification-row');
                    
                    fetch(`/notifications/${id}/read`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            row.style.opacity = '0.5';
                            row.style.pointerEvents = 'none';
                            setTimeout(() => {
                                fetchNotifications();
                            }, 500);
                        } else {
                            alert(data.message || 'Error marking notification as read');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            });
        }

        fetchNotifications();
    }
})();
