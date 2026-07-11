(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.customers || {};
        const tableBody = document.getElementById("customersTable");

        if (!tableBody) {
            // Probably on create/edit page
            return;
        }

        const paginationContainer = document.getElementById("customerPaginationContainer");
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        // ✅ DELETE FUNCTION
        function deleteCustomer(id, button) {
            if (window.showDeleteConfirm) {
                window.showDeleteConfirm("This customer and all associated data will be deleted!").then((result) => {
                    if (result.isConfirmed) {
                        performDelete(id, button);
                    }
                });
            } else {
                if (confirm("Are you sure you want to delete this customer?")) {
                    performDelete(id, button);
                }
            }
        }

        function performDelete(id, button) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;

            $.ajax({
                url: `/api/customers/${id}`,
                type: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                success: function (res) {
                    if (res.success) {
                        if (window.showAlert) {
                            window.showAlert('success', res.message || "Customer deleted successfully", 'Success');
                        }
                        fetchCustomers();
                    } else {
                        alert(res.message || "Delete failed");
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || "Something went wrong";
                    alert(message);
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                },
            });
        }

        // ✅ RENDER ROWS
        function renderRows(items) {
            if (!items || items.length === 0) {
                const createButtonHtml = permissions.create
                    ? `<a href="/masters/customers/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">
                                Add Your First Customer
                            </a>`
                    : '';

                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-people display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No customers found.</p>
                            ${createButtonHtml}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map((customer, index) => {
                const date = customer.created_at ? new Date(customer.created_at).toLocaleDateString("en-GB", {
                    day: "2-digit",
                    month: "short",
                    year: "numeric",
                }) : "-";
                
                // Get flags from API response
                const isCreator = customer.is_creator === true;
                const canUpdate = customer.can_update === true;
                const canDelete = customer.can_delete === true;
                
                // View button - always available
                const viewButton = permissions.view
                    ? `<a href="/masters/customers/${customer.id}" class="btn crm-action-btn btn-sm" title="View">
                                <i class="bi bi-eye"></i>
                            </a>`
                    : "";
                
                // Edit button - all staff can edit any customer
                let editButton = "";
                if (permissions.edit && canUpdate) {
                    editButton = `<a href="/masters/customers/${customer.id}/edit" class="btn crm-action-btn btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>`;
                }
                
                // Delete button - all staff can delete any customer
                let deleteButton = "";
                if (permissions.delete && canDelete) {
                    deleteButton = `<button class="btn crm-action-btn btn-sm text-danger delete-btn"
                                data-id="${customer.id}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>`;
                }

                const actionButtons = `
                    <div class="d-inline-flex align-items-center justify-content-end gap-2 w-100">
                        ${viewButton}
                        ${editButton}
                        ${deleteButton}
                    </div>
                `;

                return `
                <tr>
                    <td class="ps-4" data-label="Sr.No">${index + 1}</td>
                    <td data-label="Customer Name">
                        <div class="fw-bold small">${customer.name}</div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Email">
                        <div class="small">${customer.email ? `<a href="mailto:${customer.email}">${customer.email}</a>` : '-'}</div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Phone">
                        <div class="small">${customer.phone ? `<a href="tel:${customer.phone}">${customer.phone}</a>` : '-'}</div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Created At">
                        <div class="small fw-semibold">${date}</div>
                    </td>
                    <td class="text-end pe-4 d-none d-md-table-cell">
                        ${actionButtons}
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-id="${customer.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${customer.id}" style="display: none;">
                    <td colspan="4" class="p-0 border-0">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-envelope"></i> Email :</div>
                                    <div class="expand-value">${customer.email ? `<a href="mailto:${customer.email}">${customer.email}</a>` : '-'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-phone"></i> Phone :</div>
                                    <div class="expand-value">${customer.phone ? `<a href="tel:${customer.phone}">${customer.phone}</a>` : '-'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-day"></i> Created At :</div>
                                    <div class="expand-value">${date}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${viewButton}
                                        ${editButton}
                                        ${deleteButton}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join("");

            // attach delete
            document.querySelectorAll(".delete-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    deleteCustomer(this.dataset.id, this);
                });
            });

            // attach expand
            document.querySelectorAll(".btn-user-expand").forEach(btn => {
                btn.addEventListener("click", function () {
                    const id = this.dataset.id;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector("i");

                    if (detailsRow.style.display === "none") {
                        detailsRow.style.display = "table-row";
                        icon.classList.replace("fa-plus", "fa-minus");
                        this.classList.add("active");
                    } else {
                        detailsRow.style.display = "none";
                        icon.classList.replace("fa-minus", "fa-plus");
                        this.classList.remove("active");
                    }
                });
            });
        }

        let timer;
        const searchInput = document.getElementById("customerSearch");
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchCustomers(1), 400);
            });
        }

        // ✅ FETCH API
        function fetchCustomers(page = 1) {
            let url = `/api/customers?page=${page}`;

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
                beforeSend: function () {
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </td>
                    </tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        // Handle both paginated and non-paginated responses
                        const items = res.data.data || res.data;
                        renderRows(items);
                        if (res.data.current_page) {
                            renderPagination(res.data);
                        } else {
                            paginationContainer.innerHTML = "";
                        }
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            Error loading customers
                        </td>
                    </tr>`;
                },
            });
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
                    <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                    <ul class="pagination crm-pagination mb-0">
            `;

            // Previous
            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }

            // Pages (limited to 5 for simplicity, similar to followup.js logic)
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage
                        ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
                        : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Next
            if (data.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += "</ul></div>";

            paginationContainer.innerHTML = html;

            // Click events
            document.querySelectorAll(".page-link[data-page]").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    fetchCustomers(this.dataset.page);
                });
            });
        }

        // ✅ INITIAL LOAD
        fetchCustomers();
    }
})();

function showImportDialog() {
    Swal.fire({
        html: `
            <div class="mt-4 mb-3">
                <i class="fa-solid fa-file-csv fa-4x text-dark-blue"></i>
            </div>
            <h2 class="fw-bold mb-3" style="font-size: 1.75rem; color: #333;">Import Customers</h2>
            <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.5;">
                Would you like to import a CSV or download the demo template?
            </p>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa-solid fa-upload me-1"></i> Import CSV',
        denyButtonText: '<i class="fa-solid fa-download me-1"></i> Download Demo',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-outline-dark-blue me-2',
            denyButton: 'btn btn-dark-blue me-2',
            cancelButton: 'btn btn-outline-dark-blue'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('customersImportFile').click();
        } else if (result.isDenied) {
            downloadCustomerCsvDemo();
        }
    });
}

$(document).ready(function () {

    // Wizard
    var $form = $('#customerForm');
    if (!$form.length) {
        return;
    }
    
    var $steps     = $form.find('.customer-form-step');
    var $stepBtns  = $('#customerFormSteps button[data-step]');
    var $prevBtn   = $form.find('.prev-step');
    var $nextBtn   = $form.find('.next-step');
    var $cancelBtn = $form.find('.cancel-step');
    var $submitBtn = $form.find('button[type="submit"]');
    var totalSteps = $steps.length;
    var currentStep = 1;
    var currentCustomerId = $form.data('id') || null;
    var freeStepNavigation = $form.data('free-step-navigation') === true;
    var alwaysShowSubmit = $form.data('always-show-submit') === true;

        function showStep(step) {
            currentStep = step;
            $steps.addClass('d-none').filter('[data-step="' + step + '"]').removeClass('d-none');
            $stepBtns.removeClass('active').filter('[data-step="' + step + '"]').addClass('active');
            $prevBtn.toggleClass('d-none', step === 1);
            $cancelBtn.toggleClass('d-none', step !== 1);
            $nextBtn.toggleClass('d-none', step === totalSteps);
            $submitBtn.toggleClass('d-none', !alwaysShowSubmit && step !== totalSteps);
        }

        function scrollToFirstInvalid() {
            var $firstInvalid = $form.find('.is-invalid:visible').first();
            if ($firstInvalid.length) {
                $('html, body').animate({
                    scrollTop: $firstInvalid.offset().top - 100
                }, 300);
            }
        }

        function validateCurrentStep() {
            var $currentStep = $steps.filter('[data-step="' + currentStep + '"]');
            var originalIgnore = validator.settings.ignore;
            validator.settings.ignore = ':hidden';
            var valid = validator.form();
            validator.settings.ignore = originalIgnore;

            if ($currentStep.find('.is-invalid:visible').length) {
                valid = false;
            }

            return valid;
        }

        function checkDuplicateEmailPhone(email, phone, callback) {
            if (!email && !phone) {
                return callback(false);
            }

            var $emailField = $form.find('[name="email"]');
            var $phoneField = $form.find('[name="phone"]');
            
            // Remove ALL invalid feedback divs (not just the next one)
            $emailField.removeClass('is-invalid').nextAll('.invalid-feedback').remove();
            $phoneField.removeClass('is-invalid').nextAll('.invalid-feedback').remove();

            function applyErrors(customers) {
                var filteredCustomers = (customers || []).filter(function (customer) {
                    return !(currentCustomerId && customer.id == currentCustomerId);
                });

                var duplicateEmails = filteredCustomers.filter(function (customer) {
                    return email && customer.email && customer.email.toLowerCase() === email.toLowerCase();
                });
                var duplicatePhones = filteredCustomers.filter(function (customer) {
                    return phone && customer.phone && customer.phone === phone;
                });

                if (duplicateEmails.length) {
                    $emailField.addClass('is-invalid');
                    $emailField.after('<div class="invalid-feedback d-block">The email has already been taken.</div>');
                }
                if (duplicatePhones.length) {
                    $phoneField.addClass('is-invalid');
                    $phoneField.after('<div class="invalid-feedback d-block">The phone has already been taken.</div>');
                }

                return duplicateEmails.length || duplicatePhones.length;
            }

            function searchQuery(query, done) {
                $.ajax({
                    url: '/api/customers/search',
                    type: 'GET',
                    cache: false,
                    data: {
                        q: query,
                        exclude_id: currentCustomerId
                    },
                    success: function (res) {
                        done(res || []);
                    },
                    error: function () {
                        done([]);
                    }
                });
            }

            if (email) {
                searchQuery(email, function (customers) {
                    if (applyErrors(customers)) {
                        return callback(true);
                    }
                    if (phone) {
                        searchQuery(phone, function (phoneCustomers) {
                            callback(applyErrors(phoneCustomers));
                        });
                        return;
                    }
                    callback(false);
                });
                return;
            }

            searchQuery(phone, function (customers) {
                callback(applyErrors(customers));
            });
        }

        $.validator.addMethod('strictEmail', function(value, element) {
            return this.optional(element) || /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/i.test(value);
        }, 'Please enter a valid email address.');

        $.validator.addMethod('validImage', function(value, element) {
            if (element.files.length === 0) {
                return true; // Image is optional
            }
            var file = element.files[0];
            var validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            return validImageTypes.includes(file.type);
        }, 'Invalid image format');

        $.validator.addMethod('imageSize', function(value, element) {
            if (element.files.length === 0) {
                return true; // Image is optional
            }
            var file = element.files[0];
            var maxSize = 2048 * 1024; // 2MB in bytes
            return file.size <= maxSize;
        }, 'Image size must not exceed 2MB');

        var validator = $form.validate({
            ignore: ':hidden',
            onkeyup: false,
            onfocusout: false,
            onclick: false,
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            errorElement: 'div',
            rules: {
                name: { required: true, maxlength: 255 },
                email: { strictEmail: true, maxlength: 255 },
                phone: { required: true, digits: true, minlength: 10, maxlength: 10 },
                whatsapp: { maxlength: 50 },
                image: { validImage: true, imageSize: true },
                company_name: { maxlength: 255 },
                website: { maxlength: 255 },
                tax_number: { maxlength: 100 },
                type: { maxlength: 100 },
                country_id: { digits: true },
                city_id: { digits: true },
                dob: { date: true },
                anniversary_date: { date: true }
            },
            messages: {
                name: { required: 'Please enter customer name.' },
                phone: {
                    required: 'Please enter phone number.',
                    minlength: 'Phone number must be at least 10 digits.',
                    maxlength: 'Phone number must not be greater than 10 digits.'
                },
                email: { strictEmail: 'Please enter a valid email address.' }
            },
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback d-block');
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
        });

        $nextBtn.on('click', function (e) {
            e.preventDefault();
            if (freeStepNavigation) {
                showStep(Math.min(currentStep + 1, totalSteps));
                return;
            }
            if (!validateCurrentStep()) {
                scrollToFirstInvalid();
                return;
            }

            if (currentStep === 1) {
                var email = $form.find('[name="email"]').val().trim();
                var phone = $form.find('[name="phone"]').val().trim();

                if (email || phone) {
                    checkDuplicateEmailPhone(email, phone, function (hasDuplicate) {
                        if (!hasDuplicate) {
                            showStep(Math.min(currentStep + 1, totalSteps));
                        } else {
                            scrollToFirstInvalid();
                        }
                    });
                    return;
                }
            }

            showStep(Math.min(currentStep + 1, totalSteps));
        });

        $prevBtn.on('click', function () {
            showStep(Math.max(currentStep - 1, 1));
        });

        $stepBtns.on('click', function () {
            var targetStep = Number($(this).data('step'));
            if (freeStepNavigation) {
                showStep(targetStep);
                return;
            }
            if (targetStep > currentStep) {
                if (!validateCurrentStep()) {
                    scrollToFirstInvalid();
                    return;
                }

                if (currentStep === 1) {
                    var email = $form.find('[name="email"]').val().trim();
                    var phone = $form.find('[name="phone"]').val().trim();
                    if (email || phone) {
                        checkDuplicateEmailPhone(email, phone, function (hasDuplicate) {
                            if (!hasDuplicate) {
                                showStep(targetStep);
                            } else {
                                scrollToFirstInvalid();
                            }
                        });
                        return;
                    }
                }
            }
            showStep(targetStep);
        });

        showStep(1);

    // Submit
    $('body').on('submit', '#customerForm', function (e) {
        e.preventDefault();
        var $form  = $(this);

        var submitValidator = $form.validate();
        var originalIgnore = submitValidator.settings.ignore;
        submitValidator.settings.ignore = [];
        var formIsValid = $form.valid();
        submitValidator.settings.ignore = originalIgnore;

        if (!formIsValid) {
            var $firstInvalid = $form.find('.is-invalid').first();
            if ($firstInvalid.length) {
                var invalidStep = Number($firstInvalid.closest('.customer-form-step').data('step'));
                if (invalidStep) {
                    showStep(invalidStep);
                }
                $('html, body').animate({
                    scrollTop: $firstInvalid.offset().top - 100
                }, 300);
            }
            return;
        }

        var id     = $form.data('id');
        var apiUrl = id ? '/api/customers/' + id : '/api/customers';
        var $btn   = $form.find('button[type="submit"]');
        var orig   = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        var formData = new FormData(this);
        if (id) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function (res) {
                if (window.showAlert) window.showAlert('success', res.message || 'Customer saved successfully.');
                setTimeout(function () { window.location.href = '/masters/customers'; }, 300);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html(orig);
                
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Show validation errors on each field
                    $.each(xhr.responseJSON.errors, function (fieldName, msgs) {
                        var $field = $form.find('[name="' + fieldName + '"]');
                        if ($field.length) {
                            $field.addClass('is-invalid');
                            $field.after('<div class="invalid-feedback d-block">' + msgs[0] + '</div>');
                        }
                    });
                    
                    // Scroll to first error field
                    var $firstError = $form.find('.is-invalid').first();
                    if ($firstError.length) {
                        var errorStep = Number($firstError.closest('.customer-form-step').data('step'));
                        if (errorStep) {
                            showStep(errorStep);
                        }
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 300);
                    }
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.';
                    if (window.showAlert) {
                        window.showAlert('error', msg);
                    } else {
                        alert(msg);
                    }
                }
            }
        });
    });

    // clear inline errors on input (but NOT on address textarea to prevent TomSelect flickering)
    let errorClearTimeout;
    $('body').on('input', '#customerForm input:not([type="file"]), #customerForm textarea[name!="address"]', function () {
        clearTimeout(errorClearTimeout);
        errorClearTimeout = setTimeout(() => {
            $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
        }, 100);
    });
    
    // For address textarea, only clear errors on blur to prevent dropdown flickering
    $('body').on('blur', '#customerForm textarea[name="address"]', function () {
        $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
    });
});
