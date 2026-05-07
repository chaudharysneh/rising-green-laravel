(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.invoices || {};
        const tableBody = document.querySelector("#invoicesTable");
        const invoiceForm = document.querySelector(".ajax-invoice-form");

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        if (tableBody) {
            initInvoiceList(tableBody, permissions, csrfToken);
        }

        if (invoiceForm) {
            initInvoiceForm(invoiceForm, csrfToken);
        }
    }

    // ✅ INVOICE LIST LOGIC
    function initInvoiceList(tableBody, permissions, csrfToken) {
        const paginationContainer = document.getElementById("invoicePaginationContainer");
        const searchInput = document.getElementById("invoiceSearch");

        let currentFilter = 'created_by_me'; // Default filter for staff

        // Tab click handlers
        document.querySelectorAll('#invoiceFilterTabs button[data-filter]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                currentFilter = this.dataset.filter;
                fetchInvoices(1);
            });
        });

        function showToast(message, type = "info") {
            if (typeof window.showAlert === "function") {
                window.showAlert(type, message);
            } else {
                alert(message);
            }
        }

        function deleteInvoice(id, button) {
            window.showDeleteConfirm("This invoice will be deleted!").then((result) => {
                if (result.isConfirmed) {
                    const originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    button.disabled = true;

                    $.ajax({
                        url: `/api/invoices/${id}`,
                        type: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        success: function (res) {
                            if (res.success) {
                                showToast(res.message || "Invoice deleted successfully.", "success");
                                fetchInvoices();
                            } else {
                                showToast(res.message || "Delete failed", "error");
                                button.innerHTML = originalHtml;
                                button.disabled = false;
                            }
                        },
                        error: function (xhr) {
                            showToast(xhr?.responseJSON?.message || "Something went wrong", "error");
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        },
                    });
                }
            });
        }

        function formatDate(value) {
            if (!value) return "-";
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return "-";
            return date.toLocaleDateString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        }

        function renderRows(items, meta) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-file-earmark-text display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No invoices found.</p>
                            ${permissions.create ? '<a href="/invoices/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Invoice</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map((invoice, index) => {
                const customer = invoice.customer;
                const invDate = invoice.invoice_date ? new Date(invoice.invoice_date) : null;
                const dueDate = invoice.due_date ? new Date(invoice.due_date) : null;
                const statusName = (invoice.status || 'unpaid').charAt(0).toUpperCase() + (invoice.status || 'unpaid').slice(1);
                
                const statusBadgeClass = invoice.status === 'paid' ? 'bg-success text-white' : (invoice.status === 'cancelled' ? 'bg-danger text-white' : 'bg-warning text-dark');
                const rowNumber = (meta.from || 0) + index;

                return `
                <tr>
                    <td class="text-center" data-label="Sr.No">${rowNumber}</td>
                    <td class="text-start" data-label="Customer Name">
                        <div class="fw-bold text-dark">${customer?.name ?? 'Unknown'}</div>
                        <div class="text-muted small d-none d-md-block">${customer?.email ?? ''}</div>
                    </td>
                    <td class="text-center d-none d-md-table-cell" data-label="Invoice No">
                        <span class="badge bg-soft-info text-info rounded-pill px-3">#${invoice.invoice_no ?? '-'}</span>
                    </td>
                    <td class="text-center d-none d-md-table-cell" data-label="Invoice Date">${formatDate(invDate)}</td>
                    <td class="text-center d-none d-md-table-cell" data-label="Due Date">${formatDate(dueDate)}</td>
                    <td class="text-center d-none d-md-table-cell" data-label="Status">
                        ${permissions.edit ? `
                        <button class="btn ${statusBadgeClass} btn-sm rounded-pill px-3 border-0 change-status-btn" 
                            data-id="${invoice.id}" 
                            data-current-status="${invoice.status || 'unpaid'}"
                            style="cursor: pointer;">
                            ${statusName}
                        </button>` : `
                        <span class="badge rounded-pill px-3 ${statusBadgeClass}">${statusName}</span>`}
                    </td>
                    <td class="text-center d-none d-md-table-cell" data-label="Action">
                        <div class="d-inline-flex align-items-center gap-2">
                            ${permissions.view ? `<a href="/invoices/${invoice.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                            ${permissions.edit && invoice.status !== 'paid' ? `<a href="/invoices/${invoice.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : permissions.edit && invoice.status === 'paid' ? `<button class="btn crm-action-btn btn-sm" disabled title="Cannot edit paid invoice"><i class="bi bi-pencil"></i></button>` : ''}
                            ${permissions.view ? `<a href="/invoices/${invoice.id}/pdf" class="btn crm-action-btn btn-sm" target="_blank" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>` : ''}
                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-invoice-id="${invoice.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-invoice-id="${invoice.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none border-0" id="details-${invoice.id}" style="display: none;">
                    <td colspan="4" class="p-0 border">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-hashtag"></i> Sr.No :</div>
                                    <div class="expand-value">${rowNumber}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-file-invoice"></i> Invoice No :</div>
                                    <div class="expand-value">#${invoice.invoice_no ?? '-'}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> Date :</div>
                                    <div class="expand-value">${formatDate(invDate)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-regular fa-clock"></i> Due Date :</div>
                                    <div class="expand-value">${formatDate(dueDate)}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                    <div class="expand-value">
                                        <span class="badge rounded-pill px-3 ${statusBadgeClass}">${statusName}</span>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        ${permissions.view ? `<a href="/invoices/${invoice.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a>` : ''}
                                        ${permissions.edit ? `<a href="/invoices/${invoice.id}/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>` : ''}
                                        ${permissions.view ? `<a href="/invoices/${invoice.id}/pdf" class="btn crm-action-btn btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>` : ''}
                                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-invoice-id="${invoice.id}"><i class="bi bi-trash"></i></button>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join("");

            // Attach events
            tableBody.querySelectorAll(".delete-btn").forEach(btn => btn.addEventListener("click", function () { deleteInvoice(this.dataset.invoiceId, this); }));
            tableBody.querySelectorAll(".btn-user-expand").forEach(button => {
                button.addEventListener("click", function () {
                    const id = this.dataset.invoiceId;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector("i");
                    if (!detailsRow) return;
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

        $(document).on('click', '.change-status-btn', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const invoiceId = $btn.data('id');
            const currentStatus = $btn.data('current-status');
            
            // Toggle between paid and unpaid
            const newStatus = currentStatus === 'paid' ? 'unpaid' : 'paid';
            
            const originalHtml = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
            
            $.ajax({
                url: `/api/invoices/${invoiceId}/status`,
                method: 'PATCH',
                data: { status: newStatus, _token: csrfToken },
                success: function (res) {
                    if (res.success) {
                        const label = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        $btn.text(label)
                            .data('current-status', newStatus)
                            .removeClass('bg-success bg-warning bg-danger text-white text-dark')
                            .addClass(newStatus === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark')
                            .prop('disabled', false);
                        
                        // Update edit button visibility
                        const $row = $btn.closest('tr');
                        const $editBtn = $row.find('a[title="Edit"]');
                        if (newStatus === 'paid') {
                            $editBtn.replaceWith('<button class="btn crm-action-btn btn-sm" disabled title="Cannot edit paid invoice"><i class="bi bi-pencil"></i></button>');
                        } else {
                            const invoiceId = $btn.data('id');
                            $editBtn.replaceWith(`<a href="/invoices/${invoiceId}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>`);
                        }
                        
                        showToast(res.message || 'Status updated', 'success');
                    }
                },
                error: function (xhr) {
                    $btn.html(originalHtml).prop('disabled', false);
                    showToast(xhr.responseJSON?.message || 'Update failed', 'error');
                }
            });
        });

        function fetchInvoices(page = 1) {
            let url = `/api/invoices?page=${page}`;
            if (searchInput && searchInput.value.trim()) url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            
            // Add filter parameter for staff users
            if (currentFilter) {
                url += `&filter=${currentFilter}`;
            }

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                beforeSend: function () {
                    tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        renderRows(res.data.data || [], res.data);
                        renderPagination(res.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5">Error loading invoices</td></tr>`;
                },
            });
        }

        function renderPagination(data) {
            if (!paginationContainer) return;
            if (data.total === 0) { paginationContainer.innerHTML = ""; return; }
            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;
            let html = `<div class="crm-pagination-container"><div class="text-muted small">Showing ${from} to ${to} of ${total} results</div><ul class="pagination crm-pagination mb-0">`;
            if (data.prev_page_url) html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            else html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage ? `<li class="page-item active"><span class="page-link">${i}</span></li>` : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            if (data.next_page_url) html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            else html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            html += `</ul></div>`;
            paginationContainer.innerHTML = html;
            paginationContainer.querySelectorAll(".page-link[data-page]").forEach(link => { link.addEventListener("click", function (e) { e.preventDefault(); fetchInvoices(this.dataset.page); }); });
        }

        let timer;
        if (searchInput) {
            searchInput.addEventListener("input", function() {
                clearTimeout(timer);
                timer = setTimeout(() => fetchInvoices(1), 300);
            });
        }

        fetchInvoices();
    }

    // ✅ INVOICE FORM LOGIC (CREATE/EDIT)
    function initInvoiceForm(form, csrfToken) {
        const applyGst = document.getElementById('apply_gst');
        const gstFieldsBox = document.getElementById('gst_fields_box');
        const gstPercent = document.getElementById('gst_percent');
        const discount = document.getElementById('discount');
        const subsidyAmount = document.getElementById('subsidy_amount');
        const priceInput = document.getElementById('price');
        const qtyInput = document.getElementById('quantity');
        
        const subtotalDisplay = document.getElementById('subtotal_display');
        const finalTotalDisplay = document.getElementById('final_total_display');
        
        const subtotalInput = document.getElementById('subtotal');
        const finalTotalInput = document.getElementById('final_total');
        const gstInput = document.getElementById('gst');
        
        const structureCheck = document.getElementById('solar_structure_charges_check');
        const structureInputBox = document.getElementById('structure-charges-input');
        const structureInput = document.getElementById('solar_structure_charges');

        function calculateTotals() {
            let price = parseFloat(priceInput?.value) || 0;
            let qty = parseFloat(qtyInput?.value) || 0;
            let subtotal = price * qty;
            
            if (structureCheck && structureCheck.checked) {
                subtotal += (parseFloat(structureInput.value) || 0);
            }
            
            if (subtotalDisplay) subtotalDisplay.innerText = subtotal.toFixed(2);
            if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
            
            let gstAmount = 0;
            if (applyGst && applyGst.checked) {
                let pct = parseFloat(gstPercent.value) || 0;
                gstAmount = subtotal * (pct / 100);
                if (gstInput) gstInput.value = pct;
            } else if (gstInput) {
                gstInput.value = 0;
            }
            
            let discountVal = parseFloat(discount.value) || 0;
            let subsidyVal = parseFloat(subsidyAmount.value) || 0;
            let finalTotal = subtotal + gstAmount - discountVal - subsidyVal;
            
            if (finalTotalDisplay) finalTotalDisplay.innerText = finalTotal.toFixed(2);
            if (finalTotalInput) finalTotalInput.value = finalTotal.toFixed(2);
        }

        if (applyGst) {
            applyGst.addEventListener('change', function() {
                if (gstFieldsBox) gstFieldsBox.style.display = this.checked ? 'block' : 'none';
                calculateTotals();
            });
        }

        [priceInput, qtyInput, gstPercent, discount, subsidyAmount, structureInput].forEach(el => {
            if (el) el.addEventListener('input', calculateTotals);
        });
        
        if (structureCheck) {
            structureCheck.addEventListener('change', function() {
                if (structureInputBox) structureInputBox.style.display = this.checked ? 'block' : 'none';
                calculateTotals();
            });
        }

        // BOM Logic
        const addBomBtn = document.getElementById('add_more_bom');
        const bomContainer = document.getElementById('bomContainer');
        
        function hydrateBomRow(row) {
            const productSelect = row.querySelector('.product-select');
            const makeSelect = row.querySelector('.product-make');
            if (productSelect && makeSelect && productSelect.value) {
                populateMakeOptions(productSelect, makeSelect, makeSelect.dataset.selected || '');
            }
        }

        function populateMakeOptions(productSelect, makeSelect, selectedValue) {
            const option = productSelect.options[productSelect.selectedIndex];
            const categories = option?.dataset?.categories;
            makeSelect.innerHTML = '<option value="">Select Make</option>';
            if (categories) {
                try {
                    const catList = JSON.parse(categories);
                    catList.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c;
                        opt.innerText = c;
                        if (selectedValue === c) opt.selected = true;
                        makeSelect.appendChild(opt);
                    });
                    makeSelect.disabled = false;
                } catch (e) {
                    makeSelect.disabled = true;
                }
            } else {
                makeSelect.disabled = true;
            }
        }

        function attachBomEvents(row) {
            const productSelect = row.querySelector('.product-select');
            const makeSelect = row.querySelector('.product-make');
            const deleteBtn = row.querySelector('.delete-bom-row');
            
            if (productSelect && makeSelect) {
                productSelect.addEventListener('change', function() {
                    populateMakeOptions(this, makeSelect, '');
                });
            }
            
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    if (bomContainer.querySelectorAll('.bom-row').length > 1) {
                        row.remove();
                    }
                });
            }
        }

        function showAllBomDeleteButtons() {
            if (!bomContainer) return;
            bomContainer.querySelectorAll('.delete-bom-row').forEach(btn => {
                btn.style.display = 'inline-flex';
                btn.classList.add('d-flex', 'justify-content-center', 'align-items-center');
            });
        }
        
        if (addBomBtn && bomContainer) {
            addBomBtn.addEventListener('click', function() {
                const firstRow = bomContainer.querySelector('.bom-row');
                if (firstRow) {
                    const newRow = firstRow.cloneNode(true);
                    newRow.querySelectorAll('input, select').forEach(el => {
                        if (el.tagName === 'SELECT') {
                            el.value = '';
                            if (el.classList.contains('product-make')) {
                                el.disabled = true;
                                el.innerHTML = '<option value="">Select Make</option>';
                                el.dataset.selected = '';
                            }
                        } else if (el.type === 'number') {
                            el.value = '1';
                        }
                    });
                    const deleteBtn = newRow.querySelector('.delete-bom-row');
                    if (deleteBtn) {
                        deleteBtn.style.display = 'block';
                    }
                    attachBomEvents(newRow);
                    bomContainer.appendChild(newRow);
                    showAllBomDeleteButtons();
                }
            });
        }
        
        if (bomContainer) {
            bomContainer.querySelectorAll('.bom-row').forEach(row => {
                hydrateBomRow(row);
                attachBomEvents(row);
            });
        }

        // Form Submission logic
        $(form).on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const url = $form.attr('action');
            const btn = $form.find('button[type=submit]');
            const originalHtml = btn.html();
            const method = $form.find('input[name="_method"]').val() || 'POST';

            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback.ajax-error').remove();

            const formData = new FormData(this);
            btn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-2"></span>${method === 'PUT' ? 'Updating...' : 'Creating...'}`);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                success: function (res) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', res.message || 'Invoice saved.', 'Success', res.redirect || '/invoices');
                    } else {
                        window.location.href = res.redirect || '/invoices';
                    }
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html(originalHtml);
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        handleValidationErrors($form, xhr.responseJSON.errors);
                    } else {
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        if (typeof window.showAlert === 'function') window.showAlert('error', msg);
                        else alert(msg);
                    }
                }
            });
        });

        function handleValidationErrors($form, errors) {
            Object.keys(errors).forEach(key => {
                const messages = errors[key];
                let inputName = key.replace(/\.(\d+)\./g, '[$1][');
                let $input = $form.find(`[name="${inputName}"]`);
                if (!$input.length) {
                    const parts = key.split('.');
                    if (parts.length === 3) $input = $form.find(`[name^="${parts[0]}[${parts[1]}][${parts[2]}]"]`);
                }
                if ($input.length) {
                    $input.addClass('is-invalid');
                    $input.after(`<div class="invalid-feedback ajax-error">${messages[0]}</div>`);
                }
            });
        }
    }

    if ($.fn.select2) {
        $('.select2').select2({ width: '100%', theme: 'bootstrap-5' });
    }
})();
