(function () {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initEstimatesIndex();
        initEstimateForm();
    }

    function initEstimatesIndex() {
        const permissions = window.crmUserPermissions?.estimates || {};
        const tableBody = document.querySelector('#estimatesTable tbody');
        const paginationContainer = document.getElementById('estimatesPagination');
        const searchInput = document.getElementById('estimatesSearch');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const docsModalElement = document.getElementById('estimateDocsModal');
        const docsEstimateIdInput = document.getElementById('estimateDocsEstimateId');
        const docsFilesInput = document.getElementById('estimateDocsFiles');
        const docsList = document.getElementById('estimateDocsList');
        const docsUploadBtn = document.getElementById('estimateDocsUploadBtn');
        const docsFilesError = document.getElementById('estimateDocsFilesError');
        const docsModal = docsModalElement && window.bootstrap ? new bootstrap.Modal(docsModalElement) : null;

        if (!tableBody || !paginationContainer || !searchInput) {
            return;
        }

        // Get filter from URL parameter or default to 'created_by_me'
        const urlParams = new URLSearchParams(window.location.search);
        let currentFilter = urlParams.get('filter') || 'created_by_me';

        // Set the filter in URL if not present (for first load)
        if (document.getElementById('estimateFilterTabs') && !urlParams.has('filter')) {
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('filter', currentFilter);
            window.history.replaceState({}, '', newUrl);
        }

        // Activate the correct tab based on URL parameter
        if (currentFilter) {
            document.querySelectorAll('#estimateFilterTabs button[data-filter]').forEach(function(tab) {
                if (tab.dataset.filter === currentFilter) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }

        // Tab click handlers
        document.querySelectorAll('#estimateFilterTabs button[data-filter]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                currentFilter = this.dataset.filter;
                
                // Update URL without page reload
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('filter', currentFilter);
                window.history.replaceState({}, '', newUrl);
                
                fetchEstimates(1);
            });
        });

        initQuickEstimateModal();

        function formatDate(dateValue) {
            if (!dateValue) return '-';
            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return '-';
            return date.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getStatusBadge(status) {
            const normalized = String(status || '').toLowerCase();
            const classes = {
                pending: 'btn-secondary',
                approved: 'btn-success',
                rejected: 'btn-danger',
                converted: 'btn-info',
                completed: 'btn-info',
            };
            const label = normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-';
            return `<button type="button" class="btn btn-sm rounded-pill px-4 estimate-status-btn ${classes[normalized] || 'btn-secondary'}" data-status="${escapeHtml(normalized)}">${escapeHtml(label)}</button>`;
        }

        function bindDeleteButtons() {
            document.querySelectorAll('#estimatesTable .btn-user-expand').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const detailsRow = document.getElementById(`details-${id}`);
                    const icon = this.querySelector('i');

                    if (detailsRow.style.display === 'none') {
                        detailsRow.style.display = 'table-row';
                        icon.classList.replace('fa-plus', 'fa-minus');
                        this.classList.add('active');
                    } else {
                        detailsRow.style.display = 'none';
                        icon.classList.replace('fa-minus', 'fa-plus');
                        this.classList.remove('active');
                    }
                });
            });

            document.querySelectorAll('#estimatesTable .delete-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.showDeleteConfirm('This estimate will be deleted!').then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        const originalHtml = button.innerHTML;
                        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                        button.disabled = true;

                        $.ajax({
                            url: `/api/estimates/${button.dataset.id}`,
                            type: 'DELETE',
                            dataType: 'json',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                            success: function (response) {
                                if (response.success) {
                                    if (typeof window.showAlert === 'function') {
                                        window.showAlert('success', response.message || 'Estimate deleted successfully.');
                                    }
                                    fetchEstimates(1);
                                    return;
                                }

                                if (typeof window.showAlert === 'function') {
                                    window.showAlert('error', response.message || 'Delete failed.');
                                }
                                button.innerHTML = originalHtml;
                                button.disabled = false;
                            },
                            error: function (xhr) {
                                if (typeof window.showAlert === 'function') {
                                    window.showAlert('error', xhr?.responseJSON?.message || 'Something went wrong while deleting the estimate.');
                                }
                                button.innerHTML = originalHtml;
                                button.disabled = false;
                            },
                        });
                    });
                });
            });

            document.querySelectorAll('#estimatesTable .docs-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    openEstimateDocsModal(button.dataset.id);
                });
            });

            document.querySelectorAll('#estimatesTable .estimate-status-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const estimateId = button.dataset.id;
                    const currentStatus = button.dataset.status;
                    const nextStatus = currentStatus === 'approved' ? 'pending' : 'approved';
                    updateEstimateStatus(estimateId, nextStatus, button);
                });
            });
        }

        function initQuickEstimateModal() {
            const form = document.getElementById('quickEstimateForm');
            const modalEl = document.getElementById('quickEstimateModal');
            const customerSelect = document.getElementById('quick_estimate_customer_id');
            const nameInput = document.getElementById('quick_estimate_name');
            const submitBtn = document.getElementById('quickEstimateSubmitBtn');
            const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

            if (!form || !customerSelect || !submitBtn) {
                return;
            }

            const initQuickEstimateSelects = function () {
                if (!window.jQuery || !window.jQuery.fn.select2) {
                    return;
                }

                const $modal = window.jQuery(modalEl || document.body);
                window.jQuery('#quick_estimate_customer_id, #quick_template_id, .quick-bom-select').each(function () {
                    const $select = window.jQuery(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        return;
                    }

                    $select.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $modal.find('.modal-content').length ? $modal.find('.modal-content') : ($modal.length ? $modal : window.jQuery(document.body)),
                        minimumResultsForSearch: 0
                    });
                });
            };

            const initQuickBomSelect = function (select) {
                if (!select || !window.jQuery || !window.jQuery.fn.select2) {
                    return;
                }

                const $select = window.jQuery(select);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: window.jQuery(modalEl).find('.modal-content').length ? window.jQuery(modalEl).find('.modal-content') : window.jQuery(modalEl || document.body),
                    minimumResultsForSearch: 0
                });
            };

            const calculateQuickBomRow = function (row) {
                const qty = parseFloat(row.querySelector('.quick-bom-qty')?.value || 0);
                const price = parseFloat(row.querySelector('.quick-bom-price')?.value || 0);
                const amountInput = row.querySelector('.quick-bom-amount');
                if (amountInput) {
                    amountInput.value = formatStepOneInputValue(qty * price);
                }
            };

            const syncQuickBomRowFromSelection = function (row) {
                const select = row.querySelector('.quick-bom-select');
                const option = select?.options[select.selectedIndex];
                const makeInput = row.querySelector('.quick-bom-make');
                const priceInput = row.querySelector('.quick-bom-price');
                const qtyInput = row.querySelector('.quick-bom-qty');

                if (makeInput) {
                    makeInput.value = option?.dataset?.make || '';
                }
                if (priceInput) {
                    priceInput.value = formatStepOneInputValue(option?.dataset?.price || 0);
                }
                if (qtyInput && !parseFloat(qtyInput.value || 0)) {
                    qtyInput.value = '1';
                }
                calculateQuickBomRow(row);
            };

            const updateQuickBomDeleteButtons = function () {
                const rows = form.querySelectorAll('.quick-bom-row');
                rows.forEach(function (row) {
                    const removeBtn = row.querySelector('.quick-remove-bom-row');
                    if (removeBtn) {
                        removeBtn.style.display = rows.length > 1 ? 'block' : 'none';
                    }
                });
            };

            const attachQuickBomRowHandlers = function (row) {
                const select = row.querySelector('.quick-bom-select');
                const qtyInput = row.querySelector('.quick-bom-qty');
                const priceInput = row.querySelector('.quick-bom-price');
                const removeBtn = row.querySelector('.quick-remove-bom-row');

                initQuickBomSelect(select);

                select?.addEventListener('change', function () {
                    syncQuickBomRowFromSelection(row);
                });
                if (window.jQuery && select) {
                    window.jQuery(select).off('change.quickBom select2:select.quickBom').on('change.quickBom select2:select.quickBom', function () {
                        syncQuickBomRowFromSelection(row);
                    });
                }
                qtyInput?.addEventListener('input', function () {
                    calculateQuickBomRow(row);
                });
                priceInput?.addEventListener('input', function () {
                    calculateQuickBomRow(row);
                });
                removeBtn?.addEventListener('click', function () {
                    row.remove();
                    updateQuickBomDeleteButtons();
                });
            };

            const quickBomRows = document.getElementById('quickBomRows');
            quickBomRows?.querySelectorAll('.quick-bom-row').forEach(attachQuickBomRowHandlers);
            updateQuickBomDeleteButtons();

            document.getElementById('quickAddBomRow')?.addEventListener('click', function () {
                const firstRow = quickBomRows?.querySelector('.quick-bom-row');
                if (!firstRow || !quickBomRows) {
                    return;
                }

                const clone = firstRow.cloneNode(true);
                clone.querySelectorAll('.select2-container').forEach(function (el) { el.remove(); });
                clone.querySelectorAll('.select2-hidden-accessible').forEach(function (el) {
                    el.classList.remove('select2-hidden-accessible');
                    el.removeAttribute('data-select2-id');
                    el.removeAttribute('tabindex');
                    el.removeAttribute('aria-hidden');
                });
                clone.querySelectorAll('option').forEach(function (option) {
                    option.removeAttribute('data-select2-id');
                    option.selected = option.value === '';
                });
                clone.querySelector('.quick-bom-make').value = '';
                clone.querySelector('.quick-bom-qty').value = '1';
                clone.querySelector('.quick-bom-price').value = '0';
                clone.querySelector('.quick-bom-amount').value = '0';
                quickBomRows.appendChild(clone);
                attachQuickBomRowHandlers(clone);
                updateQuickBomDeleteButtons();
            });

            const fillQuickCommentFromTemplate = function (overwrite) {
                const templateSelect = document.getElementById('quick_template_id');
                const commentField = document.getElementById('quick_estimate_comment');
                const templates = window.estimateTemplateComments || {};

                if (!templateSelect || !commentField) {
                    return;
                }

                const config = templates[String(templateSelect.value)] || {};
                if (parseInt(config.active || 0, 10) !== 1) {
                    return;
                }

                const commentText = htmlToPlainText(config.content || '');
                if (commentText && (overwrite || !commentField.value.trim())) {
                    commentField.value = commentText;
                    commentField.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };

            const fillQuickEstimateName = function () {
                const selected = customerSelect.options[customerSelect.selectedIndex];
                if (!customerSelect.value) {
                    if (nameInput && nameInput.value === 'EST-Select Customer') {
                        nameInput.value = '';
                    }
                    return;
                }
                const customerName = selected?.dataset?.name || selected?.textContent?.trim() || '';
                if (nameInput && customerName) {
                    nameInput.value = 'EST-' + customerName;
                }
            };

            customerSelect.addEventListener('change', fillQuickEstimateName);
            if (window.jQuery) {
                window.jQuery(customerSelect).on('change select2:select', fillQuickEstimateName);
            }

            const quickTemplateSelect = document.getElementById('quick_template_id');
            quickTemplateSelect?.addEventListener('change', function () {
                fillQuickCommentFromTemplate(true);
            });
            if (window.jQuery && quickTemplateSelect) {
                window.jQuery(quickTemplateSelect).on('change select2:select', function () {
                    fillQuickCommentFromTemplate(true);
                });
            }

            modalEl?.addEventListener('shown.bs.modal', function () {
                initQuickEstimateSelects();
                const templateSelect = document.getElementById('quick_template_id');
                if (templateSelect) {
                    templateSelect.value = '';
                    templateSelect.dataset.userSelected = '';
                    if (window.jQuery && window.jQuery.fn.select2) {
                        window.jQuery(templateSelect).val('').trigger('change.select2');
                    }
                }
                fillQuickEstimateName();
                fillQuickCommentFromTemplate(false);
            });

            quickTemplateSelect?.addEventListener('change', function () {
                this.dataset.userSelected = this.value ? '1' : '';
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                form.querySelectorAll('.is-invalid').forEach(function (field) {
                    field.classList.remove('is-invalid');
                });

                const customerId = form.customer_id.value;
                const quantity = parseFloat(form.quantity.value || 0);
                const price = parseFloat(form.price.value || 0);
                const templateId = form.template_id.value;
                const bomRows = Array.from(form.querySelectorAll('.quick-bom-row'));
                const products = [];

                let hasError = false;
                if (!customerId) {
                    customerSelect.classList.add('is-invalid');
                    hasError = true;
                }
                if (!(quantity > 0)) {
                    document.getElementById('quick_quantity')?.classList.add('is-invalid');
                    hasError = true;
                }
                if (!(price > 0)) {
                    document.getElementById('quick_price')?.classList.add('is-invalid');
                    hasError = true;
                }
                if (!templateId) {
                    document.getElementById('quick_template_id')?.classList.add('is-invalid');
                    hasError = true;
                }
                bomRows.forEach(function (row) {
                    const select = row.querySelector('.quick-bom-select');
                    const option = select?.options[select.selectedIndex];
                    const bomId = select?.value || '';
                    const rowQty = parseFloat(row.querySelector('.quick-bom-qty')?.value || 0);
                    const rowPrice = parseFloat(row.querySelector('.quick-bom-price')?.value || 0);

                    if (!bomId) {
                        return;
                    }

                    if (!(rowQty > 0)) {
                        row.querySelector('.quick-bom-qty')?.classList.add('is-invalid');
                        hasError = true;
                    }
                    if (rowPrice < 0) {
                        row.querySelector('.quick-bom-price')?.classList.add('is-invalid');
                        hasError = true;
                    }

                    products.push({
                        product_id: String(bomId),
                        name: option?.dataset?.name || option?.textContent?.trim() || '',
                        description: '',
                        category_name: row.querySelector('.quick-bom-make')?.value || option?.dataset?.make || '',
                        quantity: rowQty,
                        price: rowPrice,
                    });
                });
                if (!products.length) {
                    document.getElementById('quick_bom_id-error').style.display = 'block';
                    form.querySelector('.quick-bom-select')?.classList.add('is-invalid');
                    hasError = true;
                } else {
                    document.getElementById('quick_bom_id-error').style.display = 'none';
                }
                if (hasError) {
                    return;
                }

                const customerOption = customerSelect.options[customerSelect.selectedIndex];
                const customerName = customerOption?.dataset?.name || customerOption?.textContent?.trim() || 'Customer';

                const formData = new FormData();
                formData.set('customer_id', customerId);
                formData.set('estimate_name', (nameInput?.value || '').trim() || ('EST-' + customerName));
                formData.set('type', form.type.value || 'residential');
                formData.set('quantity', String(quantity));
                formData.set('price', String(price));
                formData.set('template_id', templateId);
                formData.set('solar_meter_charges', 'as_per_actual');
                formData.set('estimate_date', new Date().toISOString().slice(0, 10));
                formData.set('products', JSON.stringify(products));
                formData.set('apply_gst', '0');
                formData.set('gst', '0');
                formData.set('discount', '0');
                formData.set('subsidy_amount', '0');
                formData.set('solar_structure_charges', '0');
                formData.set('comment', form.comment.value || '');

                const originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating...';

                fetch('/api/estimates', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok) {
                                throw payload;
                            }
                            return payload;
                        });
                    })
                    .then(function (payload) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', payload.message || 'Estimate created successfully.');
                        }
                        form.reset();
                        modal?.hide();
                        fetchEstimates(1);
                    })
                    .catch(function (error) {
                        const errors = error?.errors || {};
                        const firstMessage = Object.values(errors)[0]?.[0] || error?.message || 'Unable to create estimate.';
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', firstMessage);
                        } else {
                            alert(firstMessage);
                        }
                    })
                    .finally(function () {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    });
            });
        }

        function updateEstimateStatus(estimateId, status, button) {
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Saving...';

            $.ajax({
                url: `/api/estimates/${estimateId}/status`,
                type: 'PATCH',
                dataType: 'json',
                data: { status: status },
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', response.message || 'Status updated successfully.');
                        }
                        fetchEstimates(1);
                        return;
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', response.message || 'Unable to update estimate status.');
                    }
                    button.disabled = false;
                    button.textContent = originalText;
                },
                error: function (xhr) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', xhr.responseJSON?.message || 'Unable to update estimate status.');
                    }
                    button.disabled = false;
                    button.textContent = originalText;
                },
            });
        }

        function renderDocsList(estimateId, docs) {
            if (!docsList) {
                return;
            }

            if (!docs || !docs.length) {
                docsList.innerHTML = '<div class="text-muted small">No customer documents uploaded yet.</div>';
                return;
            }

            docsList.innerHTML = docs.map(function (doc, index) {
                const downloadUrl = `/estimates/${estimateId}/customer-docs/${index}/download`;
                return `
                    <div class="border rounded px-3 py-2 d-flex justify-content-between align-items-center gap-3">
                        <div class="small text-truncate">${escapeHtml(doc.original_name || 'Document')}</div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="${downloadUrl}" class="btn btn-sm btn-outline-primary" target="_blank">Download</a>
                            ${permissions.edit ? `<button type="button" class="btn btn-sm btn-outline-danger delete-doc-btn" data-estimate-id="${estimateId}" data-doc-index="${index}">Delete</button>` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            docsList.querySelectorAll('.delete-doc-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteEstimateDocument(button.dataset.estimateId, button.dataset.docIndex);
                });
            });
        }

        function loadEstimateDocs(estimateId) {
            $.ajax({
                url: `/api/estimates/${estimateId}/customer-docs`,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    renderDocsList(estimateId, response.data?.docs || []);
                },
                error: function () {
                    renderDocsList(estimateId, []);
                },
            });
        }

        function openEstimateDocsModal(estimateId) {
            if (!docsModal || !docsEstimateIdInput || !docsFilesInput) {
                return;
            }

            docsEstimateIdInput.value = estimateId;
            docsFilesInput.value = '';
            if (docsFilesError) {
                docsFilesError.style.display = 'none';
                docsFilesError.textContent = '';
            }
            loadEstimateDocs(estimateId);
            docsModal.show();
        }

        function uploadEstimateDocuments() {
            const estimateId = docsEstimateIdInput?.value;
            if (!estimateId || !docsFilesInput) {
                return;
            }

            if (!docsFilesInput.files || !docsFilesInput.files.length) {
                if (docsFilesError) {
                    docsFilesError.textContent = 'Please select at least one file.';
                    docsFilesError.style.display = 'block';
                }
                return;
            }

            const formData = new FormData();
            Array.from(docsFilesInput.files).forEach(function (file) {
                formData.append('files[]', file);
            });

            docsUploadBtn.disabled = true;
            docsUploadBtn.textContent = 'Uploading...';

            $.ajax({
                url: `/api/estimates/${estimateId}/customer-docs`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (docsFilesError) {
                        docsFilesError.style.display = 'none';
                        docsFilesError.textContent = '';
                    }
                    docsFilesInput.value = '';
                    renderDocsList(estimateId, response.data?.docs || []);
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Customer documents uploaded successfully.');
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.errors?.files?.[0]
                        || xhr.responseJSON?.errors?.['files.0']?.[0]
                        || xhr.responseJSON?.message
                        || 'Unable to upload customer documents.';

                    if (docsFilesError) {
                        docsFilesError.textContent = message;
                        docsFilesError.style.display = 'block';
                    }
                },
                complete: function () {
                    docsUploadBtn.disabled = false;
                    docsUploadBtn.textContent = 'Upload';
                },
            });
        }

        function deleteEstimateDocument(estimateId, docIndex) {
            $.ajax({
                url: `/api/estimates/${estimateId}/customer-docs/${docIndex}`,
                type: 'DELETE',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                success: function (response) {
                    renderDocsList(estimateId, response.data?.docs || []);
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Customer document deleted successfully.');
                    }
                },
                error: function (xhr) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', xhr.responseJSON?.message || 'Unable to delete customer document.');
                    }
                },
            });
        }

        function renderRows(items, meta) {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No estimates found.</p>
                            ${permissions.create ? '<a href="/estimates/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add Your First Estimate</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(function (estimate, index) {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const customerName = escapeHtml(estimate.customer?.name || '-');
                const estimateNo = escapeHtml(estimate.estimate_no || '-');
                const estimateDate = escapeHtml(formatDate(estimate.estimate_date));
                const statusValue = String(estimate.status || '').toLowerCase();
                const statusBadge = permissions.edit
                    ? getStatusBadge(estimate.status).replace('data-status=', `data-id="${estimate.estimate_id}" data-status=`)
                    : `<span class="badge ${statusValue === 'approved' ? 'bg-success' : 'bg-warning text-dark'}">${escapeHtml(String(estimate.status || '').charAt(0).toUpperCase() + String(estimate.status || '').slice(1))}</span>`;
                const isApproved = statusValue === 'approved';
                const editAction = permissions.edit
                    ? (isApproved
                        ? `<span class="btn crm-action-btn btn-sm text-muted disabled" title="Editing disabled for approved estimate" style="opacity:.5;cursor:not-allowed;"><i class="bi bi-pencil"></i></span>`
                        : `<a href="/estimates/${estimate.estimate_id}/edit" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener" title="Edit"><i class="bi bi-pencil"></i></a>`)
                    : '';

                const actionsHtml = `
                    <div class="d-inline-flex align-items-center gap-2 justify-content-center justify-content-md-end w-100">
                        ${editAction}
                        ${permissions.edit ? `<button type="button" class="btn crm-action-btn btn-sm docs-btn" data-id="${estimate.estimate_id}" title="Customer Documents"><i class="bi bi-upload"></i></button>` : ''}
                        ${permissions.view ? `<a href="/estimates/${estimate.estimate_id}" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener" title="View"><i class="bi bi-eye"></i></a>` : ''}
                        ${permissions.view ? `<a href="/estimates/${estimate.estimate_id}/pdf" class="btn crm-action-btn btn-sm" target="_blank" rel="noopener" title="Download PDF"><i class="bi bi-file-pdf"></i></a>` : ''}
                        ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${estimate.estimate_id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                    </div>`;

                return `
                    <tr>
                        <td class="ps-4" data-label="Sr.No">${srNo}</td>
                        <td data-label="Customer Name">
                            <div class="fw-bold small text-dark">${customerName}</div>
                        </td>
                        <td class="d-none d-md-table-cell" data-label="Estimate No">${estimateNo}</td>
                        <td class="d-none d-md-table-cell" data-label="Estimate Date">${estimateDate}</td>
                        <td class="d-none d-md-table-cell" data-label="Status">${statusBadge}</td>
                        <td class="text-end pe-4 d-none d-md-table-cell" data-label="Actions">${actionsHtml}</td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-id="${estimate.estimate_id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${estimate.estimate_id}" style="display: none;">
                        <td colspan="4" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-file-invoice"></i> Estimate No :</div>
                                        <div class="expand-value">${estimateNo}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-day"></i> Date :</div>
                                        <div class="expand-value">${estimateDate}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                        <div class="expand-value">${statusBadge}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${actionsHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');

            bindDeleteButtons();
        }

        function renderPagination(data) {
            if (!data || data.total === 0) {
                paginationContainer.innerHTML = '';
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
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            if (data.next_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }

            html += '</ul></div>';
            paginationContainer.innerHTML = html;

            document.querySelectorAll('#estimatesPagination .page-link[data-page]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchEstimates(this.dataset.page);
                });
            });
        }

        function fetchEstimates(page) {
            const url = new URL('/api/estimates', window.location.origin);
            url.searchParams.set('page', page || 1);

            if (searchInput.value.trim()) {
                url.searchParams.set('search', searchInput.value.trim());
            }

            if (currentFilter && document.getElementById('estimateFilterTabs')) {
                url.searchParams.set('filter', currentFilter);
            }

            $.ajax({
                url: url.toString(),
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                beforeSend: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>`;
                },
                success: function (response) {
                    if (response.success && response.data) {
                        renderRows(response.data.data || [], response.data);
                        renderPagination(response.data);
                    }
                },
                error: function () {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-5">Error loading estimates</td></tr>';
                    paginationContainer.innerHTML = '';
                },
            });
        }

        let searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                fetchEstimates(1);
            }, 400);
        });

        if (docsUploadBtn) {
            docsUploadBtn.addEventListener('click', uploadEstimateDocuments);
        }

        fetchEstimates(1);
    }

    function restrictNegative(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('keydown', function(e) {
            if (e.key === '-') {
                e.preventDefault();
            }
        });
        inputEl.addEventListener('input', function() {
            if (parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });
    }

    function formatStepOneInputValue(value) {
        return String(Math.round(parseFloat(value || 0)));
    }

    function initEstimateForm() {
        const form = document.querySelector('.ajax-estimate-form');
        if (!form) {
            return;
        }

        initBomHandlers();
        initCalculations();
        initQuickAddBom();
        initEstimateNameFromCustomer();
        initTemplateCommentAutofill();

        $('body').off('submit.estimate').on('submit.estimate', '.ajax-estimate-form', function (e) {
            e.preventDefault();
            const $form = $(this);
            const btn = $form.find('button[type="submit"]');
            const originalText = btn.html();

            clearErrors($form);

            if (!runExactValidation($form)) {
                this.classList.add('was-validated');
                return;
            }

            const bomProducts = collectBomData();
            const formData = new FormData(this);
            const totalTaxRate = getSelectedTaxBreakdown(null).totalRate;
            formData.set('products', JSON.stringify(bomProducts));
            formData.set('apply_gst', document.getElementById('apply_gst')?.checked ? '1' : '0');
            formData.set('gst', document.getElementById('apply_gst')?.checked ? totalTaxRate.toFixed(2) : '0');
            formData.set('total', document.getElementById('subtotal')?.value || '0');
            formData.set('final_total', document.getElementById('final_total')?.value || '0');
            formData.set('solar_structure_charges', document.getElementById('solar_structure_charges_check')?.checked ? (document.getElementById('solar_structure_charges')?.value || '0') : '0');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    Accept: 'application/json',
                },
                success: function (response) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', response.message || 'Estimate saved successfully.', 'Success!');
                    }
                    setTimeout(function () {
                        window.location.href = response.redirect || '/estimates';
                    }, 300);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors($form, xhr.responseJSON.errors);
                        return;
                    }

                    const message = xhr.responseJSON?.message || 'Something went wrong while submitting the estimate.';
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', message);
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                },
            });
        });

        $('body').off('input.estimate change.estimate').on('input.estimate change.estimate', '.ajax-estimate-form input, .ajax-estimate-form select, .ajax-estimate-form textarea', function () {
            const $field = $(this);
            $field.removeClass('is-invalid');
            const id = $field.attr('id');
            if (id) {
                $('#' + id + '-error').html('');
            }
            if ($field.closest('#bomContainer').length) {
                toggleBomError(false);
            }
            $field.siblings('.invalid-feedback.ajax-error').remove();
        });
    }

    function initEstimateNameFromCustomer() {
        const customerSelect = document.getElementById('select_customer');
        const estimateNameInput = document.getElementById('estimate_name');

        if (!customerSelect || !estimateNameInput) {
            return;
        }

        $(customerSelect).off('change.estimateName').on('change.estimateName', function () {
            const selectedOption = this.options[this.selectedIndex];
            const customerName = (selectedOption?.textContent || '').trim();

            if (!this.value || !customerName) {
                return;
            }

            estimateNameInput.value = 'EST-' + customerName;
            estimateNameInput.classList.remove('is-invalid');
            const error = document.getElementById('estimate_name-error');
            if (error) {
                error.textContent = '';
            }
        });
    }

    let quickBomTargetRow = null;

    function initQuickAddBom() {
        const form = document.getElementById('quickAddBomForm');
        const saveBtn = document.getElementById('saveQuickBomBtn');
        const config = window.estimateBomQuickAddConfig || {};

        if (!form || !saveBtn || !config.storeUrl) {
            return;
        }

        const nameInput = document.getElementById('quick_bom_name');
        const makeSelect = document.getElementById('quick_bom_category_id');
        const priceInput = document.getElementById('quick_bom_price');

        initQuickBomMakeSelect(makeSelect);

        document.addEventListener('click', function (event) {
            const button = event.target.closest('.quick-add-bom-row');
            if (!button) {
                return;
            }

            if (!validateTopFieldsBeforeBom(true)) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                return;
            }

            quickBomTargetRow = button.closest('.bom-row');
        }, true);

        [nameInput, makeSelect, priceInput].forEach(function (field) {
            if (!field) {
                return;
            }

            const eventName = field.tagName === 'SELECT' ? 'change' : 'input';
            field.addEventListener(eventName, function () {
                field.classList.remove('is-invalid');
                const error = document.getElementById(field.id + '-error');
                if (error) {
                    error.textContent = '';
                }
            });
        });

        saveBtn.addEventListener('click', function () {
            const name = (nameInput?.value || '').trim();
            const makeValue = makeSelect?.value || '';
            const price = parseFloat(priceInput?.value || 0);
            let isValid = true;

            if (!name) {
                showQuickBomFieldError(nameInput, 'Please enter BOM name');
                isValid = false;
            }

            if (!makeValue) {
                showQuickBomFieldError(makeSelect, 'Please select make');
                isValid = false;
            }

            if (!(price >= 0) || priceInput?.value === '') {
                showQuickBomFieldError(priceInput, 'Please enter unit price');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            resolveQuickBomMake(makeSelect, config)
                .then(function (make) {
                    const formData = new FormData();
                    formData.append('product_name', name);
                    formData.append('price', formatStepOneInputValue(price));
                    formData.append('category_id[]', make.id);

                    return fetch(config.storeUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        credentials: 'same-origin',
                    }).then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok) {
                                throw payload;
                            }
                            return { payload, make };
                        });
                    });
                })
                .then(function (result) {
                    const payload = result.payload;
                    const product = payload.data;
                    if (!product) {
                        throw { message: 'BOM created but response was invalid.' };
                    }

                    addBomProductToEstimateRows(product, result.make);
                    form.reset();
                    if ($.fn.select2 && $(makeSelect).hasClass('select2-hidden-accessible')) {
                        $(makeSelect).val(null).trigger('change');
                    }
                    const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddBomModal'));
                    if (modal) {
                        modal.hide();
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', payload.message || 'BOM added successfully.');
                    }
                })
                .catch(function (error) {
                    if (error?.errors) {
                        showQuickBomApiErrors(error.errors);
                        return;
                    }

                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', error?.message || 'Unable to add BOM.');
                    } else {
                        alert(error?.message || 'Unable to add BOM.');
                    }
                })
                .finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                });
        });
    }

    function initQuickBomMakeSelect(makeSelect) {
        if (!makeSelect || !$.fn.select2) {
            return;
        }

        const modal = $('#quickAddBomModal');
        $(makeSelect).select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: modal.length ? modal : $(document.body),
            tags: true,
            placeholder: 'Search or type new Make',
            createTag: function (params) {
                const term = $.trim(params.term);
                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true,
                };
            },
            templateResult: function (data) {
                if (data.newTag) {
                    return $('<span>Add new make: <strong></strong></span>').find('strong').text(data.text).end();
                }

                return data.text;
            },
        });
    }

    function resolveQuickBomMake(makeSelect, config) {
        const selectedOption = makeSelect?.options[makeSelect.selectedIndex];
        const selectedValue = makeSelect?.value || '';
        const isExistingMake = selectedValue !== '' && /^\d+$/.test(String(selectedValue));

        if (isExistingMake) {
            return Promise.resolve({
                id: selectedValue,
                name: selectedOption?.textContent?.trim() || '',
            });
        }

        const makeName = selectedOption?.textContent?.trim() || selectedValue.trim();
        if (!makeName) {
            return Promise.reject({ errors: { category_id: ['Please select make'] } });
        }

        if (!config.makeStoreUrl) {
            return Promise.reject({ message: 'Make create URL is not configured.' });
        }

        const formData = new FormData();
        formData.append('name', makeName);

        return fetch(config.makeStoreUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok) {
                    throw payload;
                }

                addCreatedMakeToQuickBomSelect(makeSelect, payload.data);
                return payload.data;
            });
        });
    }

    function addCreatedMakeToQuickBomSelect(makeSelect, make) {
        if (!makeSelect || !make) {
            return;
        }

        let option = makeSelect.querySelector('option[value="' + make.id + '"]');
        if (!option) {
            option = new Option(make.name, make.id, true, true);
            option.dataset.name = make.name;
            makeSelect.appendChild(option);
        }

        option.selected = true;
        if ($.fn.select2 && $(makeSelect).hasClass('select2-hidden-accessible')) {
            $(makeSelect).trigger('change');
        }
    }

    function showQuickBomFieldError(field, message) {
        if (!field) {
            return;
        }

        field.classList.add('is-invalid');
        const error = document.getElementById(field.id + '-error');
        if (error) {
            error.textContent = message;
        }
    }

    function showQuickBomApiErrors(errors) {
        const fieldMap = {
            product_name: 'quick_bom_name',
            category_id: 'quick_bom_category_id',
            name: 'quick_bom_category_id',
            price: 'quick_bom_price',
        };

        Object.keys(errors).forEach(function (field) {
            const fieldId = fieldMap[field] || fieldMap[field.replace(/\.\d+$/, '')];
            const input = fieldId ? document.getElementById(fieldId) : null;
            showQuickBomFieldError(input, errors[field][0] || 'Invalid value');
        });
    }

    function addBomProductToEstimateRows(product, selectedMake) {
        const categories = Array.isArray(product.categories) ? product.categories : [];
        let categoryNames = categories.map(function (category) {
            return category.name;
        }).filter(Boolean);
        const selectedMakeName = selectedMake?.name || '';
        if (!categoryNames.length && selectedMakeName) {
            categoryNames = [selectedMakeName];
        }
        const price = formatStepOneInputValue(product.price || 0);

        document.querySelectorAll('.product-select').forEach(function (select) {
            let option = select.querySelector('option[value="' + product.id + '"]');
            if (!option) {
                option = new Option(product.product_name || 'New BOM', product.id, false, false);
                select.appendChild(option);
            }

            option.dataset.name = product.product_name || '';
            option.dataset.desc = product.description || '';
            option.dataset.categories = JSON.stringify(categoryNames);
            option.dataset.price = price;
            option.dataset.meter = product.meter || '';
            option.dataset.nos = product.nos || '';
        });

        const row = getQuickBomTargetRow();
        const productSelect = row?.querySelector('.product-select');
        const makeSelect = row?.querySelector('.product-make');
        const priceInput = row?.querySelector('.product-price');

        if (!row || !productSelect) {
            return;
        }

        productSelect.value = String(product.id);
        if (priceInput) {
            priceInput.value = price;
        }

        $(productSelect).trigger('change');
        if (makeSelect && selectedMakeName) {
            makeSelect.value = selectedMakeName;
            if ($.fn.select2 && $(makeSelect).hasClass('select2-hidden-accessible')) {
                $(makeSelect).trigger('change.select2');
            }
            $(makeSelect).trigger('change');
        }
        quickBomTargetRow = null;
        calculateTotals();
    }

    function getQuickBomTargetRow() {
        const container = document.getElementById('bomContainer');
        if (!container) {
            return null;
        }

        if (quickBomTargetRow && container.contains(quickBomTargetRow)) {
            return quickBomTargetRow;
        }

        const emptyRow = Array.from(container.querySelectorAll('.bom-row')).find(function (row) {
            const select = row.querySelector('.product-select');
            return select && !select.value;
        });

        if (emptyRow) {
            return emptyRow;
        }

        const addBtn = document.getElementById('add_more_bom');
        if (addBtn) {
            addBtn.click();
        }

        const rows = container.querySelectorAll('.bom-row');
        return rows[rows.length - 1] || null;
    }

    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ajax-error').remove();
        $('#products-error').text('Please select at least one BOM').removeClass('d-block').hide();
    }

    function showErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            if (field === 'products') {
                $('#products-error').text(errors[field][0]).addClass('d-block').show();
                return;
            }

            const $field = $form.find(`[name="${field}"]`);
            if ($field.length) {
                $field.addClass('is-invalid');
                const feedback = $form.find('#' + field + '-error');
                if (feedback.length) {
                    feedback.html(errors[field][0]);
                } else {
                    $field.after(`<div class="invalid-feedback ajax-error">${errors[field][0]}</div>`);
                }
            }
        });
    }

    function setFieldError($form, selector, feedbackId, message) {
        const $field = $form.find(selector);
        if ($field.length) {
            $field.addClass('is-invalid');
        }
        const $feedback = $('#' + feedbackId);
        if ($feedback.length) {
            $feedback.text(message).show();
        }
    }

    function runExactValidation($form) {
        let isValid = true;

        const customerId = ($form.find('[name="customer_id"]').val() || '').trim();
        const estimateName = ($form.find('[name="estimate_name"]').val() || '').trim();
        const type = ($form.find('[name="type"]').val() || '').trim();
        const quantity = parseFloat($form.find('[name="quantity"]').val() || 0);
        const price = parseFloat($form.find('[name="price"]').val() || 0);
        const solarMeterCharges = ($form.find('[name="solar_meter_charges"]').val() || '').trim();
        const templateId = ($form.find('[name="template_id"]').val() || '').trim();
        const bomProducts = collectBomData();

        if (!customerId) {
            setFieldError($form, '[name="customer_id"]', 'customer_id-error', 'Please select a customer');
            isValid = false;
        }

        if (!estimateName) {
            setFieldError($form, '[name="estimate_name"]', 'estimate_name-error', 'Please enter estimate name');
            isValid = false;
        }

        if (!type) {
            setFieldError($form, '[name="type"]', 'type-error', 'Please select estimate type');
            isValid = false;
        }

        if (!(quantity > 0)) {
            setFieldError($form, '[name="quantity"]', 'quantity-error', 'Please enter valid quantity (kW)');
            isValid = false;
        }

        if (!(price > 0)) {
            setFieldError($form, '[name="price"]', 'price-error', 'Please enter valid price');
            isValid = false;
        }

        if (!solarMeterCharges) {
            setFieldError($form, '[name="solar_meter_charges"]', 'solar_meter_charges-error', 'Please select solar meter charges');
            isValid = false;
        }

        if (!templateId) {
            setFieldError($form, '[name="template_id"]', 'template_id-error', 'Please select quotation template');
            isValid = false;
        }

        if (!bomProducts.length) {
            $('#products-error').text('Please select at least one BOM').addClass('d-block').show();
            isValid = false;
        }

        return isValid;
    }

    function htmlToPlainText(html) {
        const holder = document.createElement('div');
        holder.innerHTML = String(html || '')
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/p>/gi, '\n')
            .replace(/<\/div>/gi, '\n');
        return (holder.textContent || holder.innerText || '')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function initTemplateCommentAutofill() {
        const templateSelect = document.getElementById('template_id');
        const commentField = document.getElementById('comment');
        const templates = window.estimateTemplateComments || {};

        if (!templateSelect || !commentField) {
            return;
        }

        const fillFromSelectedTemplate = function (overwrite) {
            const config = templates[String(templateSelect.value)] || {};
            if (parseInt(config.active || 0, 10) !== 1) {
                return;
            }

            const commentText = htmlToPlainText(config.content || '');
            if (commentText && (overwrite || !commentField.value.trim())) {
                commentField.value = commentText;
                commentField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        templateSelect.addEventListener('change', function () {
            fillFromSelectedTemplate(true);
        });

        if (window.jQuery) {
            window.jQuery(templateSelect).on('change select2:select', function () {
                fillFromSelectedTemplate(true);
            });
        }

        fillFromSelectedTemplate(false);
    }

    function validateTopFieldsBeforeBom(showErrors) {
        const $form = $('.ajax-estimate-form').first();
        if (!$form.length) {
            return true;
        }

        let isValid = true;
        const quantity = parseFloat($form.find('[name="quantity"]').val() || 0);
        const price = parseFloat($form.find('[name="price"]').val() || 0);
        const requiredFields = [
            ['[name="customer_id"]', 'customer_id-error', 'Please select a customer', function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="estimate_name"]', 'estimate_name-error', 'Please enter estimate name', function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="type"]', 'type-error', 'Please select estimate type', function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="quantity"]', 'quantity-error', 'Please enter valid quantity (kW)', function () { return quantity > 0; }],
            ['[name="price"]', 'price-error', 'Please enter valid price', function () { return price > 0; }],
            ['[name="solar_meter_charges"]', 'solar_meter_charges-error', 'Please select solar meter charges', function ($field) { return !!($field.val() || '').trim(); }],
            ['[name="template_id"]', 'template_id-error', 'Please select quotation template', function ($field) { return !!($field.val() || '').trim(); }],
        ];

        requiredFields.forEach(function (fieldConfig) {
            const $field = $form.find(fieldConfig[0]);
            if (!fieldConfig[3]($field)) {
                isValid = false;
                if (showErrors) {
                    setFieldError($form, fieldConfig[0], fieldConfig[1], fieldConfig[2]);
                }
            }
        });

        if (!isValid && showErrors) {
            $('#products-error').text('Please fill required estimate details before adding BOM').addClass('d-block').show();
        }

        return isValid;
    }

    function toggleBomError(forceShow) {
        const bomError = $('#products-error');
        if (!bomError.length) {
            return;
        }

        if (forceShow === true) {
            bomError.text('Please select at least one BOM').addClass('d-block').show();
            return;
        }

        if (collectBomData().length > 0) {
            bomError.removeClass('d-block').hide();
        }
    }

    function initBomHandlers() {
        const addBtn = document.getElementById('add_more_bom');
        const container = document.getElementById('bomContainer');
        if (!addBtn || !container) {
            return;
        }

        container.querySelectorAll('.bom-row').forEach(function (row) {
            hydrateBomRow(row);
            attachBomRowHandlers(row);
        });

        addBtn.addEventListener('click', function () {
            if (!validateTopFieldsBeforeBom(true)) {
                return;
            }

            const firstRow = container.querySelector('.bom-row');
            if (!firstRow) {
                return;
            }

            const newRow = firstRow.cloneNode(true);
            newRow.querySelectorAll('input, select').forEach(function (el) {
                if (el.tagName === 'SELECT') {
                    el.value = el.classList.contains('product-tax-rate') ? '0' : '';
                    if (el.classList.contains('product-make')) {
                        el.innerHTML = '<option value="">Select Make</option>';
                        el.disabled = true;
                        el.dataset.selected = '';
                    }
                } else if (el.type === 'number') {
                    el.value = '0';
                }
            });
            newRow.querySelectorAll('input[name="product_qty[]"]').forEach(function (input) {
                input.value = '';
                input.placeholder = 'Add Quantity';
            });

            const deleteBtn = newRow.querySelector('.delete-bom-row');
            if (deleteBtn) {
                deleteBtn.style.display = 'block';
            }

            container.appendChild(newRow);
            attachBomRowHandlers(newRow);
        });
    }

    function hydrateBomRow(row) {
        const productSelect = row.querySelector('.product-select');
        const makeSelect = row.querySelector('.product-make');
        if (!productSelect || !makeSelect || !productSelect.value) {
            return;
        }

        populateMakeOptions(productSelect, makeSelect, makeSelect.dataset.selected || '');
    }

    function populateMakeOptions(productSelect, makeSelect, selectedValue) {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const categories = selectedOption?.dataset?.categories;
        makeSelect.innerHTML = '<option value="">Select Make</option>';

        if (!categories) {
            makeSelect.disabled = true;
            return;
        }

        try {
            const categoryList = JSON.parse(categories);
            categoryList.forEach(function (cat) {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                if (selectedValue && selectedValue === cat) {
                    option.selected = true;
                }
                makeSelect.appendChild(option);
            });
            makeSelect.disabled = false;
        } catch (error) {
            makeSelect.disabled = true;
        }
        
        if ($(makeSelect).hasClass('select2-hidden-accessible')) {
            $(makeSelect).trigger('change.select2');
        }
    }

    function attachBomRowHandlers(row) {
        const productSelect = row.querySelector('.product-select');
        const makeSelect = row.querySelector('.product-make');
        const deleteBtn = row.querySelector('.delete-bom-row');
        const qtyInput = row.querySelector('input[name="product_qty[]"]');
        const priceInput = row.querySelector('.product-price');
        const taxSelect = row.querySelector('.product-tax-rate');

        if (qtyInput) {
            restrictNegative(qtyInput);
        }

        if (priceInput) {
            restrictNegative(priceInput);
        }

        if (productSelect) {
            $(productSelect).on('select2:opening', function (event) {
                if (!validateTopFieldsBeforeBom(true)) {
                    event.preventDefault();
                }
            });

            $(productSelect).on('change', function () {
                if (this.value && !validateTopFieldsBeforeBom(true)) {
                    this.value = '';
                    if ($.fn.select2 && $(this).hasClass('select2-hidden-accessible')) {
                        $(this).trigger('change.select2');
                    }
                    return;
                }

                populateMakeOptions(this, makeSelect, '');
                
                const option = this.options[this.selectedIndex];
                let labelText = 'Qty';
                if (option && this.value) {
                    if (priceInput) {
                        priceInput.value = formatStepOneInputValue(option.dataset.price || 0);
                    }
                    
                    const meter = option.dataset.meter;
                    const nos = option.dataset.nos;
                    const hasMeter = meter && String(meter).trim() !== '' && String(meter).toLowerCase() !== 'null';
                    const hasNos = nos && String(nos).trim() !== '' && String(nos).toLowerCase() !== 'null';
                    
                    if (hasMeter) {
                        labelText = 'Qty(meter)';
                    } else if (hasNos) {
                        labelText = 'Qty(nos)';
                    }
                }
                
                const label = row.querySelector('.product-qty-label') || qtyInput?.closest('div')?.querySelector('label');
                if (label) {
                    const icon = label.querySelector('i');
                    if (icon) {
                        label.innerHTML = '';
                        label.appendChild(icon);
                        label.appendChild(document.createTextNode(' ' + labelText));
                        label.insertAdjacentHTML('beforeend', ' <span class="text-danger">*</span>');
                    } else {
                        label.innerHTML = labelText + ' <span class="text-danger">*</span>';
                    }
                }
                
                toggleBomError(false);
                calculateTotals();
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                const container = document.getElementById('bomContainer');
                if (container.querySelectorAll('.bom-row').length > 1) {
                    row.remove();
                    toggleBomError(false);
                    calculateTotals();
                }
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', function () {
                toggleBomError(false);
                calculateTotals();
            });
            qtyInput.addEventListener('change', function () {
                toggleBomError(false);
                calculateTotals();
            });
        }

        if (priceInput) {
            priceInput.addEventListener('input', function () {
                toggleBomError(false);
                calculateTotals();
            });
            priceInput.addEventListener('change', function () {
                toggleBomError(false);
                calculateTotals();
            });
        }

        if (taxSelect) {
            taxSelect.addEventListener('change', function () {
                toggleBomError(false);
                calculateTotals();
            });
        }

        if (makeSelect) {
            makeSelect.addEventListener('change', function () {
                toggleBomError(false);
                calculateTotals();
            });
            
            // Also bind Select2 change event if it's initialized
            $(makeSelect).on('select2:select', function() {
                toggleBomError(false);
                calculateTotals();
            });
        }
    }

    let isPriceManualOverride = false;

    function collectBomData() {
        const rows = document.querySelectorAll('#bomContainer .bom-row');
        const products = [];

        rows.forEach(function (row) {
            const productSelect = row.querySelector('.product-select');
            const makeSelect = row.querySelector('.product-make');
            const qtyInput = row.querySelector('input[name="product_qty[]"]');
            const priceInput = row.querySelector('.product-price');
            const taxSelect = row.querySelector('.product-tax-rate');

            if (productSelect && productSelect.value) {
                const option = productSelect.options[productSelect.selectedIndex];
                
                let itemPrice = parseFloat(option.dataset.price || 0);
                if (priceInput && priceInput.value !== '') {
                    itemPrice = parseFloat(priceInput.value || 0);
                }

                products.push({
                    product_id: productSelect.value,
                    name: option.dataset.name || '',
                    description: option.dataset.desc || '',
                    category_name: makeSelect?.value || '',
                    quantity: parseFloat(qtyInput?.value || 0),
                    price: itemPrice,
                    tax_rate: parseFloat(taxSelect?.value || 0),
                    tax_label: taxSelect?.options[taxSelect.selectedIndex]?.dataset?.label || '',
                });
            }
        });

        return products;
    }



    function getSelectedTaxBreakdown(taxableAmount) {
        const rows = document.querySelectorAll('#bomContainer .bom-row');
        const fieldsBox = document.getElementById('gst_fields_box');
        const buckets = {};
        let totalRate = 0;
        let totalAmount = 0;
        const shouldUpdateDisplay = taxableAmount !== null;
        const shouldApplyTaxes = taxableAmount !== 0;

        rows.forEach(function (row) {
            const select = row.querySelector('.product-select');
            const qtyInput = row.querySelector('input[name="product_qty[]"]');
            const priceInput = row.querySelector('.product-price');
            const taxSelect = row.querySelector('.product-tax-rate');
            const rate = parseFloat(taxSelect?.value || 0);

            if (!select?.value || !rate || !shouldApplyTaxes) {
                return;
            }

            const qty = parseFloat(qtyInput?.value || 0);
            const price = parseFloat(priceInput?.value || 0);
            const rowBaseTotal = qty * price;
            if (rowBaseTotal <= 0) {
                return;
            }

            const selectedOption = taxSelect.options[taxSelect.selectedIndex];
            const label = (selectedOption?.dataset?.label || selectedOption?.textContent || 'GST').trim();

            if (label.toUpperCase().includes('CGST') && label.toUpperCase().includes('SGST')) {
                const halfRate = rate / 2;
                [
                    { label: 'CGST', rate: halfRate },
                    { label: 'SGST', rate: halfRate },
                ].forEach(function (taxLine) {
                    const key = taxLine.label + '|' + taxLine.rate.toFixed(4);
                    const amount = (rowBaseTotal * taxLine.rate) / 100;
                    buckets[key] = buckets[key] || { label: taxLine.label, rate: taxLine.rate, amount: 0 };
                    buckets[key].amount += amount;
                    totalAmount += amount;
                });
            } else {
                const normalizedLabel = label.toUpperCase().includes('IGST') ? 'IGST' : label;
                const key = normalizedLabel + '|' + rate.toFixed(4);
                const amount = (rowBaseTotal * rate) / 100;
                buckets[key] = buckets[key] || { label: normalizedLabel, rate, amount: 0 };
                buckets[key].amount += amount;
                totalAmount += amount;
            }
        });

        totalRate = Object.values(buckets).reduce(function (sum, line) {
            return sum + parseFloat(line.rate || 0);
        }, 0);

        if (fieldsBox && shouldUpdateDisplay) {
            const lines = Object.values(buckets);
            if (!lines.length) {
                fieldsBox.innerHTML = '<div class="totals-row"><span class="small text-muted">Select BOM tax to apply GST.</span><span class="small">0.00</span></div>';
            } else {
                fieldsBox.innerHTML = lines.map(function (line) {
                    const rateText = parseFloat(line.rate || 0).toFixed(2).replace(/\.?0+$/, '');
                    const amountText = parseFloat(line.amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return '<div class="totals-row gst-tax-row">' +
                        '<span class="small">' + line.label + ' (' + rateText + '%):</span>' +
                        '<span class="small gst-tax-amount">' + amountText + '</span>' +
                    '</div>';
                }).join('');
            }
        }

        return { totalRate, totalAmount };
    }

    function calculateTotals() {
        const subtotalField = document.getElementById('subtotal');
        const finalTotalField = document.getElementById('final_total');
        const subtotalDisplay = document.getElementById('subtotal_display');
        const finalTotalDisplay = document.getElementById('final_total_display');
        const priceInput = document.getElementById('price');

        if (!subtotalField || !finalTotalField || !subtotalDisplay || !finalTotalDisplay) {
            return;
        }

        // Dynamically sum up all products to sync total with main price input
        let productsTotal = 0;
        const rows = document.querySelectorAll('#bomContainer .bom-row');
        rows.forEach(function (row) {
            const select = row.querySelector('.product-select');
            const qtyIn = row.querySelector('input[name="product_qty[]"]');
            const priceIn = row.querySelector('.product-price');
            
            if (select && select.value && qtyIn) {
                const qty = parseFloat(qtyIn.value || 0);
                let p = 0;
                if (priceIn && priceIn.value !== '') {
                    p = parseFloat(priceIn.value || 0);
                } else {
                    const opt = select.options[select.selectedIndex];
                    p = parseFloat(opt?.dataset?.price || 0);
                }
                const rowTotal = qty * p;
                productsTotal += rowTotal;

                // Update per-row readout if element exists
                const totalEl = row.querySelector('.product-total');
                if (totalEl) {
                    totalEl.value = formatStepOneInputValue(rowTotal);
                }
            }
        });

        const price = parseFloat(priceInput?.value || 0);
        const structureCharges = document.getElementById('solar_structure_charges_check')?.checked
            ? parseFloat(document.getElementById('solar_structure_charges')?.value || 0)
            : 0;
        const discount = parseFloat(document.getElementById('discount')?.value || 0);
        const subsidy = parseFloat(document.getElementById('subsidy_amount')?.value || 0);

        const basePrice = price + productsTotal;
        const subtotal = basePrice + structureCharges;
        const taxBreakdown = document.getElementById('apply_gst')?.checked
            ? getSelectedTaxBreakdown(subtotal)
            : getSelectedTaxBreakdown(0);
        const gstAmount = taxBreakdown.totalAmount;
        const finalTotal = subtotal + gstAmount - discount - subsidy;

        subtotalDisplay.textContent = subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        finalTotalDisplay.textContent = finalTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        subtotalField.value = subtotal.toFixed(2);
        finalTotalField.value = finalTotal.toFixed(2);

        const gstField = document.getElementById('gst');
        if (gstField) {
            gstField.value = document.getElementById('apply_gst')?.checked ? taxBreakdown.totalRate.toFixed(2) : '0';
        }

        const cgstDisplay = document.getElementById('cgst_display');
        const sgstDisplay = document.getElementById('sgst_display');
        const igstDisplay = document.getElementById('igst_display');

        if (cgstDisplay && sgstDisplay && igstDisplay) {
            const halfGst = gstPercent / 2;
            const cgstAmount = gstPercent > 0 ? (basePrice * halfGst) / 100 : 0;
            const sgstAmount = gstPercent > 0 ? (basePrice * halfGst) / 100 : 0;
            const igstAmount = gstPercent > 0 ? (basePrice * gstPercent) / 100 : 0;

            cgstDisplay.textContent = cgstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            sgstDisplay.textContent = sgstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            igstDisplay.textContent = igstAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const cgstLabel = cgstDisplay.previousElementSibling;
            const sgstLabel = sgstDisplay.previousElementSibling;
            const igstLabel = igstDisplay.previousElementSibling;
            if (cgstLabel) cgstLabel.textContent = `CGST (${halfGst.toFixed(1)}%):`;
            if (sgstLabel) sgstLabel.textContent = `SGST (${halfGst.toFixed(1)}%):`;
            if (igstLabel) igstLabel.textContent = `IGST (${gstPercent.toFixed(1)}%):`;
        }
    }
    function autoCalculateSubsidy() {
        const typeField = document.getElementById('type');
        const quantityField = document.getElementById('quantity');
        const subsidyField = document.getElementById('subsidy_amount');

        if (!typeField || !quantityField || !subsidyField) {
            return;
        }

        const type = typeField.value;
        const kw = parseFloat(quantityField.value || 0);

        // Robust check for subsidiesData whether it comes as array or object
        const rawData = window.subsidiesData;
        let dataArr = [];
        if (Array.isArray(rawData)) {
            dataArr = rawData;
        } else if (rawData && typeof rawData === 'object') {
            dataArr = Object.values(rawData);
        }

        if (!dataArr || dataArr.length === 0) {
            return; // No data available
        }

        // Create a quick lookup map
        const rates = {};
        dataArr.forEach(function(item) {
            if (item && item.category) {
                rates[item.category] = parseFloat(item.amount || 0);
            }
        });

        let calculatedSubsidy = 0;

        if (type === 'residential') {
            const rate0_2 = rates['residential_0_2'] || 0;
            const rate2_3 = rates['residential_2_3'] || 0;
            const maxAbove3 = rates['residential_above_3'] || 0;

            if (kw < 2) {
                calculatedSubsidy = rate0_2;
            } else if (kw >= 2 && kw < 3) {
                calculatedSubsidy = rate2_3;
            } else if (kw >= 3) {
                calculatedSubsidy = maxAbove3;
            }
            // if (kw <= 0) {
            //     calculatedSubsidy = 0;
            // } else if (kw >= 3) {
            //     // Total Cap for 3kW and above
            //     calculatedSubsidy = maxAbove3 ;
            // } else if (kw > 2) {
            //     // Tiered calculation
            //     calculatedSubsidy = rate0_2;
            // } else {
            //     // Up to 2kW calculation
            //     calculatedSubsidy = rate0_2;
            // }
        } else if (type === 'common meter') {
            const rateCommon = rates['common_meter'] || 0;
            calculatedSubsidy = rateCommon;
        } else {
            calculatedSubsidy = 0;
        }

        // Safely update the field value
        subsidyField.value = formatStepOneInputValue(calculatedSubsidy);
    }

    function initCalculations() {
        // Determine if initial price was manually overridden
        const priceInput = document.getElementById('price');
        if (priceInput && priceInput.value !== '') {
            let initialProductsTotal = 0;
            const rows = document.querySelectorAll('#bomContainer .bom-row');
            rows.forEach(function (row) {
                const select = row.querySelector('.product-select');
                const qtyIn = row.querySelector('input[name="product_qty[]"]');
                const priceIn = row.querySelector('.product-price');
                
                if (select && select.value && qtyIn) {
                    const qty = parseFloat(qtyIn.value || 0);
                    let p = 0;
                    if (priceIn && priceIn.value !== '') {
                        p = parseFloat(priceIn.value || 0);
                    } else {
                        const opt = select.options[select.selectedIndex];
                        p = parseFloat(opt?.dataset?.price || 0);
                    }
                    initialProductsTotal += qty * p;
                }
            });

            const currentVal = parseFloat(priceInput.value || 0);
            if (currentVal > 0 && Math.abs(currentVal - initialProductsTotal) > 0.01) {
                isPriceManualOverride = true;
            }
        }

        // Attach robust listeners to all key fields
        const inputs = ['price', 'quantity', 'solar_structure_charges', 'discount', 'subsidy_amount'];
        inputs.forEach(function (id) {
            const input = document.getElementById(id);
            if (input) {
                restrictNegative(input);
                ['input', 'change'].forEach(function(evt) {
                    input.addEventListener(evt, function() {
                        // Flag that user manually overridden price computation if edit made directly in field
                        if (id === 'price') {
                            const v = input.value;
                            isPriceManualOverride = (v !== null && String(v).trim() !== '');
                        }

                        // Special case: if quantity changed, auto-update subsidy first
                        if (id === 'quantity') {
                            autoCalculateSubsidy();
                        }
                        calculateTotals();
                    });
                });
            }
        });

        const typeInput = document.getElementById('type');
        if (typeInput) {
            ['input', 'change'].forEach(function(evt) {
                typeInput.addEventListener(evt, function() {
                    autoCalculateSubsidy();
                    calculateTotals();
                });
            });
        }

        const structureCheckbox = document.getElementById('solar_structure_charges_check');
        const gstCheckbox = document.getElementById('apply_gst');

        if (structureCheckbox) {
            structureCheckbox.addEventListener('change', function () {
                const box = document.getElementById('structure-charges-input');
                if (box) {
                    box.style.display = this.checked ? 'block' : 'none';
                }
                calculateTotals();
            });

            const box = document.getElementById('structure-charges-input');
            if (box) {
                box.style.display = structureCheckbox.checked ? 'block' : 'none';
            }
        }

        if (gstCheckbox) {
            gstCheckbox.addEventListener('change', function () {
                const box = document.getElementById('gst_fields_box');
                if (box) {
                    box.style.display = this.checked ? 'block' : 'none';
                }
                calculateTotals();
            });

            const box = document.getElementById('gst_fields_box');
            if (box) {
                box.style.display = gstCheckbox.checked ? 'block' : 'none';
            }
        }

        // Initial calculation runs
        // If it's a brand new form, initialize subsidy. 
        // For existing edit, let user alter quantity to recalculate.
        const quantityVal = document.getElementById('quantity')?.value;
        if (quantityVal && parseFloat(quantityVal) > 0) {
            // Check if user is creating a new form by verifying hidden input ID absence or looking at path?
            // Actually safe approach: Always ensure valid mathematical state on load
            autoCalculateSubsidy();
        }
        calculateTotals();
    }
})();
