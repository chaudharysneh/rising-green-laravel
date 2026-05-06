/**
 * PDF Builder consolidated JS
 * Combines list and form logic, modernizes delete functionality
 */
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    // ==================== LIST / INDEX PAGE LOGIC ====================
    function initPdfList() {
        const tableBody = document.getElementById("templatesTableBody");
        if (!tableBody) return;

        const paginationContainer = document.getElementById("templatePaginationContainer");
        const searchInput = document.getElementById("templateSearch");

        // ✅ RENDER ROWS
        function renderRows(items) {
            if (!items || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-file-earmark-pdf display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No templates found.</p>
                            <a href="/pdfbuilder/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Template</a>
                        </td>
                    </tr>`;
                return;
            }

            tableBody.innerHTML = items.map(template => {
                const createdDate = new Date(template.created_at);
                const dateStr = createdDate.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
                const timeStr = createdDate.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });

                const viewUrl = `/pdfbuilder/view/${template.id}`;
                const editUrl = `/pdfbuilder/edit/${template.id}`;

                return `
                <tr class="template-row">
                    <td class="ps-4 fw-semibold text-muted template-id">${template.id}</td>
                    <td class="template-name">
                        <div class="d-flex align-items-center gap-2 py-1">
                            <span class="fw-bold text-dark-blue">${template.template_name}</span>
                        </div>
                    </td>

                    <td class="text-muted d-none d-md-table-cell">
                        <div class="small fw-semibold text-dark">${dateStr}</div>
                        <div class="small opacity-75" style="font-size: 0.75rem;">${timeStr}</div>
                    </td>

                    <td class="text-end pe-4 d-none d-md-table-cell">
                        <div class="d-inline-flex align-items-center gap-2">
                            <a href="${viewUrl}" class="btn crm-action-btn btn-sm" target="_blank" title="View PDF"><i class="bi bi-eye"></i></a>
                            <a href="${editUrl}" class="btn crm-action-btn btn-sm" title="Edit Template"><i class="bi bi-pencil"></i></a>
                            <button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${template.id}" title="Delete"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                    <td class="text-center d-md-none">
                        <button type="button" class="btn-user-expand" data-template-id="${template.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
                <tr class="details-row d-md-none" id="details-${template.id}" style="display: none;">
                    <td colspan="5" class="p-0">
                        <div class="details-content">
                            <div class="row g-3">
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-file-alt"></i> Template Name :</div>
                                    <div class="expand-value">${template.template_name}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="expand-label"><i class="fa-solid fa-calendar"></i> Created :</div>
                                    <div class="expand-value">${dateStr} ${timeStr}</div>
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                    <div class="d-flex justify-content-end flex-wrap gap-2">
                                        <a href="${viewUrl}" class="btn crm-action-btn btn-sm" target="_blank"><i class="bi bi-eye"></i></a>
                                        <a href="${editUrl}" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>
                                        <button class="btn crm-action-btn btn-sm text-danger delete-btn" data-id="${template.id}"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join("");

            // Re-attach expand logic is done via delegation now or in renderRows
            // We use delegation for simplicity like meeting.js
        }

        let timer;
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchTemplates(1), 400);
            });
        }

        // ✅ FETCH API
        window.fetchTemplates = function(page = 1) {
            let baseUrl = window.pdfbuilderApiUrl || "/api/v1/pdfbuilderApi/templet";
            let url = baseUrl + (baseUrl.indexOf('?') !== -1 ? "&" : "?") + `page=${page}`;

            if (searchInput && searchInput.value.trim()) {
                url += `&search=${encodeURIComponent(searchInput.value.trim())}`;
            }

            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                beforeSend: function () {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
                },
                success: function (res) {
                    if (res.success && res.data) {
                        renderRows(res.data.data || []);
                        renderPagination(res.data);
                    }
                },
                error: function (xhr) {
                    console.error("Fetch Templates Error:", xhr);
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5">
                        <div class="text-danger mb-2"><i class="bi bi-exclamation-octagon display-4"></i></div>
                        <div>Error loading templates. Please try again.</div>
                        <small class="text-muted">${xhr.statusText || 'Network Error'}</small>
                    </td></tr>`;
                },
            });
        };

        function renderPagination(data) {
            if (!paginationContainer) return;
            if (data.total === 0) { paginationContainer.innerHTML = ""; return; }

            const from = data.from || 0;
            const to = data.to || 0;
            const total = data.total || 0;
            const currentPage = data.current_page || 1;
            const lastPage = data.last_page || 1;

            let html = `<div class="crm-pagination-container">
                <div class="text-muted small">Showing ${from} to ${to} of ${total} results</div>
                <ul class="pagination crm-pagination mb-0">`;

            if (data.prev_page_url) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
            } else {
                html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
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
                html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
            }

            html += `</ul></div>`;
            paginationContainer.innerHTML = html;

            document.querySelectorAll(".page-link[data-page]").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    fetchTemplates(this.dataset.page);
                });
            });
        }

        fetchTemplates();
    }

    // ✅ DELETE FUNCTION (Modernized like meeting.js)
    function deleteTemplate(id, button) {
        window.showDeleteConfirm("You won't be able to revert this!").then((result) => {
            if (!result.isConfirmed) return;

            const btn = $(button);
            window.buttonLoader(btn, "Deleting", true);

            const apiUrl = (window.pdfbuilderApiDeletePath || "/api/v1/pdfbuilderApi/delete/") + id;
            $.ajax({
                url: apiUrl,
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                success: function (res) {
                    window.buttonLoader(btn, "", false);
                    if (res.status || res.success) {
                        if (window.fetchTemplates) window.fetchTemplates();
                        window.showAlert('success', res.message || 'Template deleted.');
                    } else {
                        window.showAlert('error', res.message || "Delete failed");
                    }
                },
                error: function (xhr) {
                    window.buttonLoader(btn, "", false);
                    window.showAlert('error', "Something went wrong");
                },
            });
        });
    }

    // ==================== FORM / CREATE / EDIT LOGIC ====================
    function initPdfForm() {
        const form = document.getElementById('pdf-builder-form');
        const beforeContainer = document.getElementById('before-blocks-container');
        const afterContainer = document.getElementById('after-blocks-container');
        const addBeforeBtn = document.getElementById('add-before-block');
        const addAfterBtn = document.getElementById('add-after-block');

        if (!form && !beforeContainer && !afterContainer) return;

        // Handle Header Image Preview
        const headerImgInput = document.getElementById('header_img_input');
        if (headerImgInput) {
            headerImgInput.addEventListener('change', function(e) {
                const preview = document.getElementById('header_img_preview');
                const file = e.target.files[0];
                if (file && preview) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        preview.src = event.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Initialize CKEditor for existing textareas
        document.querySelectorAll('.ckeditor-textarea').forEach(textarea => {
            initCKEditor(textarea.id);
        });

        function initCKEditor(id) {
            if (typeof CKEDITOR !== 'undefined') {
                if (CKEDITOR.instances[id]) {
                    CKEDITOR.instances[id].destroy();
                }
                CKEDITOR.replace(id, {
                    height: 200,
                });
            }
        }

        function createBlock(type, index) {
            const id = `${type}_${Date.now()}`;
            const block = document.createElement('div');
            block.className = 'card mb-4 border shadow-none block-item position-relative';
            block.innerHTML = `
                <div class="card-body p-4 pt-5">
                    <button type="button" class="btn btn-danger btn-sm remove-block position-absolute" style="top: 15px; right: 15px;">
                        <i class="fa-solid fa-trash-can me-1"></i> Remove
                    </button>
                    
                    <div class="mb-4">
                        <label class="form-label d-flex align-items-center gap-2 mb-2 fw-semibold">
                            <i class="fa-solid fa-image text-primary"></i> Image
                        </label>
                        <div class="upload-drag-area p-4 border border-dashed rounded-4 text-center bg-light cursor-pointer position-relative">
                            <input type="file" name="${type}_image[${id}]" class="form-control d-none file-input-capture" accept="image/*">
                            <input type="hidden" name="${type}_id[]" value="${id}">
                            <div class="upload-placeholder">
                                <i class="fa-solid fa-cloud-arrow-up fs-1 text-primary mb-2"></i>
                                <p class="mb-0 text-muted">Drag & drop files or <span class="text-primary fw-bold">Browse</span></p>
                            </div>
                            <div class="preview-area d-none">
                                <img src="" class="img-fluid rounded-3 mb-2" style="max-height: 150px;">
                                <p class="filename mb-0 small text-muted"></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-flex align-items-center gap-2 mb-2 fw-semibold">
                            <i class="fa-solid fa-heading text-primary"></i> Title
                        </label>
                        <input type="text" name="${type}_title[]" class="form-control rounded-3" placeholder="Enter title">
                    </div>

                    <div class="mb-0">
                        <label class="form-label d-flex align-items-center gap-2 mb-2 fw-semibold">
                            <i class="fa-solid fa-align-left text-primary"></i> Content
                        </label>
                        <textarea name="${type}_content[]" id="editor_${id}" class="form-control ckeditor-textarea"></textarea>
                    </div>
                </div>
            `;

            // Handle File Input Trigger
            const dragArea = block.querySelector('.upload-drag-area');
            const fileInput = block.querySelector('.file-input-capture');
            const previewArea = block.querySelector('.preview-area');
            const placeholder = block.querySelector('.upload-placeholder');
            const previewImg = block.querySelector('.preview-area img');
            const filenameTxt = block.querySelector('.filename');

            dragArea.addEventListener('click', () => fileInput.click());
            
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        filenameTxt.textContent = fileInput.files[0].name;
                        placeholder.classList.add('d-none');
                        previewArea.classList.remove('d-none');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });

            // Handle Remove
            block.querySelector('.remove-block').addEventListener('click', () => {
                if (confirm('Are you sure you want to remove this block?')) {
                    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[`editor_${id}`]) {
                        CKEDITOR.instances[`editor_${id}`].destroy();
                    }
                    block.remove();
                }
            });

            return block;
        }

        if (addBeforeBtn) {
            addBeforeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const block = createBlock('before');
                beforeContainer.appendChild(block);
                initCKEditor(block.querySelector('.ckeditor-textarea').id);
            });
        }

        if (addAfterBtn) {
            addAfterBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const block = createBlock('after');
                afterContainer.appendChild(block);
                initCKEditor(block.querySelector('.ckeditor-textarea').id);
            });
        }

        // Handle existing blocks removal
        document.querySelectorAll('.remove-block').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this block?')) {
                    const block = this.closest('.block-item');
                    const textarea = block.querySelector('.ckeditor-textarea');
                    if (textarea && typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[textarea.id]) {
                        CKEDITOR.instances[textarea.id].destroy();
                    }
                    block.remove();
                }
            });
        });

        // Handle existing image triggers
        document.querySelectorAll('.upload-drag-area').forEach(dragArea => {
            const fileInput = dragArea.querySelector('.file-input-capture');
            if (fileInput) {
                dragArea.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', function() {
                    const previewImg = dragArea.querySelector('.preview-area img');
                    const filenameTxt = dragArea.querySelector('.filename');
                    const placeholder = dragArea.querySelector('.upload-placeholder');
                    const previewArea = dragArea.querySelector('.preview-area');

                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (previewImg) previewImg.src = e.target.result;
                            if (filenameTxt) filenameTxt.textContent = fileInput.files[0].name;
                            if (placeholder) placeholder.classList.add('d-none');
                            if (previewArea) previewArea.classList.remove('d-none');
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }

                if (typeof CKEDITOR !== 'undefined') {
                    Object.values(CKEDITOR.instances).forEach(instance => instance.updateElement());
                }

                const submitButton = form.querySelector('button[type="submit"]');
                const formData = new FormData(form);

                form.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));

                if (typeof window.buttonLoader === 'function' && submitButton) {
                    window.buttonLoader($(submitButton), 'Saving...', true);
                }

                $.ajax({
                    url: form.getAttribute('action'),
                    type: form.getAttribute('method') || 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    success: function (response) {
                        if (typeof window.buttonLoader === 'function' && submitButton) {
                            window.buttonLoader($(submitButton), '', false);
                        }

                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', response.message || 'Template saved successfully.', '', response.redirect || '/pdfbuilder');
                        }
                    },
                    error: function (xhr) {
                        if (typeof window.buttonLoader === 'function' && submitButton) {
                            window.buttonLoader($(submitButton), '', false);
                        }

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            Object.entries(xhr.responseJSON.errors).forEach(([key, messages]) => {
                                const field = form.querySelector(`[name="${key}"]`);
                                const errorElement = document.getElementById(`${key}-error`);

                                if (field) {
                                    field.classList.add('is-invalid');
                                }

                                if (errorElement) {
                                    errorElement.textContent = messages[0];
                                }
                            });

                            form.classList.add('was-validated');
                            return;
                        }

                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', xhr.responseJSON?.message || 'Something went wrong');
                        }
                    }
                });
            });
        }
    }

    // ==================== GLOBAL INITIALIZATION ====================
    $(document).ready(function () {
        initPdfList();
        initPdfForm();

        // Standardized delete handler using delegation
        $(document).on("click", ".delete-btn", function () {
            deleteTemplate($(this).data("id"), this);
        });

        // Expand handler using delegation
        $(document).on("click", ".btn-user-expand", function () {
            const id = this.dataset.templateId;
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

    // Expose necessary functions globally if needed
    window.deleteTemplate = deleteTemplate;
})();
