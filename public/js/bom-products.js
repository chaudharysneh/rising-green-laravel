(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        initIndex();
        initForm();
    }

    function notify(message, type = "info", redirectUrl = null) {
        if (typeof window.showAlert === "function") {
            window.showAlert(type, message, "", redirectUrl);
            return;
        }

        alert(message);
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) return "";

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDate(value) {
        if (!value) return "-";
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return "-";

        return date.toLocaleString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    function initIndex() {
        const config = window.bomProductsConfig || {};
        const tableBody = document.querySelector("#bomProductsTable tbody");
        const searchInput = document.getElementById("bomProductsSearch");
        const paginationContainer = document.getElementById("bomProductsPagination");
        const permissions = window.crmUserPermissions?.bom || {};

        if (!config.indexUrl || !tableBody || !searchInput || !paginationContainer) return;

        let searchTimer = null;

        function buildUrl(template, id) {
            return (template || "").replace("__ID__", id);
        }

        function renderRows(items, meta) {
            if (!items?.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="bi bi-inbox display-1 opacity-25"></i></div>
                            <p class="text-muted">No BOM records found.</p>
                            ${permissions.create ? '<a href="/add-product" class="btn btn-dark-blue btn-sm rounded-pill px-4">Add BOM</a>' : ''}
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items.map((item, index) => {
                const srNo = meta && meta.from ? meta.from + index : index + 1;
                const name = escapeHtml(item.product_name || "-");
                const makes = item.categories?.map(c => escapeHtml(c.name)).join(", ") || "-";
                const technology = escapeHtml(item.technology?.title || "-");
                const warranty = escapeHtml(item.warranty?.title || "-");
                const createdAt = escapeHtml(formatDate(item.created_at));

                return `
                    <tr>
                        <td class="ps-4"><span class="text-muted small fw-medium">${srNo}</span></td>
                        <td><div class="fw-bold small">${name}</div></td>
                        <td class="d-none d-md-table-cell">${makes}</td>
                        <td class="d-none d-md-table-cell">${technology}</td>
                        <td class="d-none d-md-table-cell">${warranty}</td>
                        <td class="d-none d-md-table-cell">${createdAt}</td>
                        <td class="text-center d-none d-md-table-cell">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                ${permissions.edit ? `<a href="${buildUrl(config.editUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="${buildUrl(config.showUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteBom" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-id="${item.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border" id="bom-details-${item.id}" style="display:none;">
                        <td colspan="8" class="p-0">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> MAKE :</div>
                                        <div class="expand-value text-end">${makes}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-microchip"></i> TECHNOLOGY :</div>
                                        <div class="expand-value text-end">${technology}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-shield-halved"></i> WARRANTY :</div>
                                        <div class="expand-value text-end">${warranty}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-calendar-days"></i> CREATED :</div>
                                        <div class="expand-value text-end">${createdAt}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gears"></i> ACTIONS :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `<a href="${buildUrl(config.editUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                            ${permissions.view ? `<a href="${buildUrl(config.showUrlTemplate, item.id)}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger deleteBom" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");

            tableBody.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const detailsRow = document.getElementById(`bom-details-${this.dataset.id}`);
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

        function renderPagination(data) {
            if (!data || data.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `<div class="crm-pagination-container"><div class="text-muted small">Showing ${from} to ${to} of ${total} results</div><ul class="pagination crm-pagination mb-0">`;
            html += data.prev_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>` : `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;

            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += i === currentPage ? `<li class="page-item active"><span class="page-link">${i}</span></li>` : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            html += data.next_page_url ? `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>` : `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            html += "</ul></div>";
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll(".page-link[data-page]").forEach((link) => {
                link.addEventListener("click", (event) => {
                    event.preventDefault();
                    fetchProducts(Number(link.dataset.page));
                });
            });
        }

        async function fetchProducts(page = 1) {
            const params = new URLSearchParams({ page });
            if (searchInput.value.trim()) params.set("search", searchInput.value.trim());

            tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;

            try {
                const response = await fetch(`${config.indexUrl}?${params.toString()}`, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                });
                const payload = await response.json();

                if (!response.ok) throw new Error(payload?.message || "Request failed");

                renderRows(payload?.data?.data || [], payload?.data);
                renderPagination(payload?.data);
            } catch (_) {
                tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">Error loading BOM records.</td></tr>`;
                paginationContainer.innerHTML = "";
            }
        }

        document.addEventListener("click", async (event) => {
            const deleteBtn = event.target.closest(".deleteBom");
            if (!deleteBtn) return;

            event.preventDefault();
            const result = await window.showDeleteConfirm("This BOM record will be deleted!");
            if (!result.isConfirmed) return;

            const formData = new FormData();
            formData.append("_method", "DELETE");

            try {
                const response = await fetch(buildUrl(config.destroyUrlTemplate, deleteBtn.dataset.id), {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    },
                    body: formData,
                    credentials: "same-origin",
                });
                const payload = await response.json();

                if (!response.ok) throw new Error(payload?.message || "Request failed");

                notify(payload?.message || "BOM product deleted successfully.", "success");
                fetchProducts(1);
            } catch (error) {
                notify(error.message || "Unable to delete BOM product.", "error");
            }
        });

        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fetchProducts(1), 300);
        });

        fetchProducts(1);
    }

    function initForm() {
        const form = document.querySelector(".ajax-bom-form");
        if (!form) {
            console.warn('BOM form not found on this page');
            return;
        }
        
        console.log('BOM form initialized:', form);
        console.log('Form action:', form.action);
        console.log('Form config:', window.bomFormConfig);
        
        const makeSelect = document.getElementById("category_id");
        const makeTagsDisplay = document.getElementById("make-tags-display");
        const makeInputWrapper = document.querySelector(".make-input-wrapper");
        const makeDropdownList = document.getElementById("make-dropdown-list");
        const makeOptions = document.querySelectorAll(".make-option");
        const saveMakeBtn = document.getElementById("save-make-btn");
        const addMakeModalEl = document.getElementById("addMakeModal");

        // Check if all required elements exist
        if (!makeSelect || !makeTagsDisplay || !makeInputWrapper || !makeDropdownList) {
            return;
        }

        // Initialize tags display
        function updateTagsDisplay() {
            makeTagsDisplay.innerHTML = '';
            const selectedOptions = Array.from(makeSelect.selectedOptions);
            
            selectedOptions.forEach(option => {
                const tag = document.createElement('div');
                tag.className = 'make-tag';
                tag.innerHTML = `
                    ${option.text}
                    <span class="remove-tag" data-value="${option.value}">×</span>
                `;
                makeTagsDisplay.appendChild(tag);
            });

            // Update dropdown menu selected state
            makeOptions.forEach(opt => {
                if (selectedOptions.some(sel => sel.value === opt.dataset.value)) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        }

        // Show dropdown when clicking on the wrapper
        makeInputWrapper.addEventListener('click', (e) => {
            if (!e.target.closest('.remove-tag')) {
                makeDropdownList.style.display = makeDropdownList.style.display === 'none' ? 'block' : 'none';
            }
        });

        // Handle option selection
        makeOptions.forEach(option => {
            option.addEventListener('click', () => {
                const value = option.dataset.value;
                const selectOption = makeSelect.querySelector(`option[value="${value}"]`);
                
                if (selectOption.selected) {
                    selectOption.selected = false;
                } else {
                    selectOption.selected = true;
                }
                
                updateTagsDisplay();
                
                // Clear validation error when selection is made
                makeInputWrapper.classList.remove("is-invalid");
                const errorDiv = document.getElementById("category_id-error");
                if (errorDiv) errorDiv.textContent = "";
            });
        });

        // Handle tag removal
        makeTagsDisplay.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-tag')) {
                const value = e.target.dataset.value;
                const selectOption = makeSelect.querySelector(`option[value="${value}"]`);
                selectOption.selected = false;
                updateTagsDisplay();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.make-input-wrapper') && !e.target.closest('#make-dropdown-list')) {
                makeDropdownList.style.display = 'none';
            }
        });

        // Initialize on page load
        updateTagsDisplay();

        // Add Make modal functionality
        if (saveMakeBtn) {
            saveMakeBtn.addEventListener("click", async function () {
                const nameInput = document.getElementById("new-make-name");
                const imageInput = document.getElementById("new-make-image");
                const nameError = document.getElementById("new-make-name-error");
                const imageError = document.getElementById("new-make-image-error");

                nameInput.classList.remove("is-invalid");
                nameError.textContent = "";
                imageError.textContent = "";

                const formData = new FormData();
                formData.append("name", nameInput.value || "");
                if (imageInput.files && imageInput.files[0]) {
                    formData.append("image", imageInput.files[0]);
                }

                try {
                    const response = await fetch("/api/make", {
                        method: "POST",
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                        },
                        body: formData,
                        credentials: "same-origin",
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        const error = new Error(payload?.message || "Request failed");
                        error.payload = payload;
                        throw error;
                    }

                    if (payload?.data) {
                        // Add new option to select
                        const option = document.createElement("option");
                        option.value = payload.data.id;
                        option.textContent = payload.data.name;
                        option.selected = true;
                        makeSelect.appendChild(option);

                        // Add new option to dropdown menu
                        const dropdownOption = document.createElement("div");
                        dropdownOption.className = "make-option p-2 selected";
                        dropdownOption.dataset.value = payload.data.id;
                        dropdownOption.style.cursor = "pointer";
                        dropdownOption.style.padding = "0.5rem";
                        dropdownOption.style.borderBottom = "1px solid #f0f0f0";
                        dropdownOption.textContent = payload.data.name;
                        makeDropdownList.appendChild(dropdownOption);

                        updateTagsDisplay();
                    }

                    bootstrap.Modal.getInstance(addMakeModalEl)?.hide();
                    notify(payload?.message || "Make created successfully.", "success");
                } catch (error) {
                    const errors = error.payload?.errors || {};
                    if (errors.name?.length) {
                        nameInput.classList.add("is-invalid");
                        nameError.textContent = errors.name[0];
                    }
                    if (errors.image?.length) {
                        imageError.textContent = errors.image[0];
                    }
                    if (!errors.name && !errors.image) {
                        notify(error.message || "Unable to create make right now.", "error");
                    }
                }
            });
        }

        function clearErrors() {
            form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
            form.querySelectorAll(".invalid-feedback").forEach((el) => {
                if (!el.classList.contains("d-block")) el.textContent = "";
                else el.textContent = "";
            });
        }

        function showErrors(errors) {
            Object.entries(errors || {}).forEach(([field, messages]) => {
                const input = form.querySelector(`[name="${field}"]`);
                const errorDiv = document.getElementById(`${field}-error`);

                if (input) input.classList.add("is-invalid");
                
                // Special handling for Make field (category_id) - add is-invalid to wrapper
                if (field === "category_id" && makeInputWrapper) {
                    makeInputWrapper.classList.add("is-invalid");
                }
                
                if (errorDiv) errorDiv.textContent = messages[0];
            });
        }

        let isSubmitting = false;

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            
            // Prevent duplicate submissions
            if (isSubmitting) {
                console.warn('Form submission already in progress');
                return;
            }
            
            isSubmitting = true;
            clearErrors();

            const submitBtn = document.getElementById("submitBtn");
            const originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            const formData = new FormData(form);

            try {
                console.log('=== BOM Form Submission Started ===');
                console.log('Form action:', form.action);
                console.log('Form data keys:', Array.from(formData.keys()));
                
                // Log all form data
                for (let [key, value] of formData.entries()) {
                    if (key === 'image') {
                        console.log(`${key}: [File]`);
                    } else {
                        console.log(`${key}: ${value}`);
                    }
                }
                
                const response = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    },
                    body: formData,
                    credentials: "same-origin",
                });
                
                console.log('Response status:', response.status);
                console.log('Response statusText:', response.statusText);
                console.log('Response headers Content-Type:', response.headers.get('content-type'));
                
                let payload;
                const responseText = await response.text();
                console.log('Response text length:', responseText.length);
                
                try {
                    payload = JSON.parse(responseText);
                    console.log('Parsed payload:', payload);
                } catch (parseError) {
                    console.error('Failed to parse JSON response:', parseError);
                    console.log('Response text:', responseText.substring(0, 500));
                    throw new Error('Invalid JSON response from server: ' + responseText.substring(0, 200));
                }

                if (!response.ok) {
                    const error = new Error(payload?.message || `Request failed with status ${response.status}`);
                    error.payload = payload;
                    error.status = response.status;
                    throw error;
                }

                console.log('=== Form submission successful ===');
                console.log('Redirect URL:', payload?.redirect || window.bomFormConfig?.redirectUrl || "/all_product");
                console.log('Calling notify function...');
                
                notify(payload?.message || "BOM product saved successfully.", "success", payload?.redirect || window.bomFormConfig?.redirectUrl || "/all_product");
                
                console.log('Notify function called');
            } catch (error) {
                console.error('=== Form submission error ===');
                console.error('Error message:', error.message);
                console.error('Error status:', error.status);
                console.error('Error payload:', error.payload);
                
                // Handle duplicate submission (409 Conflict)
                if (error.status === 409) {
                    console.log('Duplicate submission detected');
                    notify(error.message || "This BOM product was just created. Redirecting...", "info", error.payload?.redirect || window.bomFormConfig?.redirectUrl || "/all_product");
                    return;
                }
                
                if (error.payload?.errors) {
                    console.log('Validation errors found:', error.payload.errors);
                    showErrors(error.payload.errors);
                } else {
                    console.log('Showing generic error message');
                    notify(error.message || "Something went wrong while saving BOM product.", "error");
                }
                
                // Re-enable submit button on error so user can retry
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
                isSubmitting = false;
            }
        });

        form.addEventListener("input", (event) => {
            if (event.target.matches("input, select, textarea")) {
                event.target.classList.remove("is-invalid");
                const errorDiv = document.getElementById(`${event.target.name}-error`);
                if (errorDiv) errorDiv.textContent = "";
                if (event.target.name === "category_id[]") {
                    event.target.closest(".product-category-inline")?.classList.remove("is-invalid");
                }
            }
        });

        form.addEventListener("change", (event) => {
            if (event.target.name === "category_id[]") {
                event.target.classList.remove("is-invalid");
                event.target.closest(".product-category-inline")?.classList.remove("is-invalid");
                const errorDiv = document.getElementById("category_id-error");
                if (errorDiv) errorDiv.textContent = "";
            }
        });

        document.getElementById("image")?.addEventListener("change", function () {
            const file = this.files && this.files[0];
            const previewWrap = document.getElementById("bom-image-preview-wrap");
            const previewImage = document.getElementById("bom-image-preview");
            if (!file || !previewWrap || !previewImage) return;

            const objectUrl = URL.createObjectURL(file);
            previewImage.src = objectUrl;
            previewWrap.classList.remove("d-none");
            previewImage.onload = function () {
                URL.revokeObjectURL(objectUrl);
            };
        });
    }
})();
