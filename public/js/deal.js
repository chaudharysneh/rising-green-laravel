(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    function init() {
        const permissions = window.crmUserPermissions?.deals || {};
        const searchInput = document.getElementById("dealsSearch");
        const tableBody = document.querySelector("#dealsTable tbody");
        const paginationContainer = document.getElementById(
            "paginationContainer",
        );

        if (!searchInput || !tableBody || !paginationContainer) {
            return;
        }

        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const authHeaders = (extraHeaders = {}) =>
            typeof window.crmApplyAuthHeaders === "function"
                ? window.crmApplyAuthHeaders(extraHeaders)
                : extraHeaders;

        function showToast(message, type = "info") {
            const mappedType =
                {
                    success: "success",
                    error: "error",
                    warning: "warning",
                    info: "info",
                }[type] || "info";

            if (typeof window.showAlert === "function") {
                window.showAlert(mappedType, message);
                return;
            }

            alert(message);
        }

        function deleteDeal(dealId, button) {
            window.showDeleteConfirm("Are you sure you want to delete this deal?").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const originalHtml = button.innerHTML;
                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                button.disabled = true;

                $.ajax({
                    url: `/api/deals/${dealId}`,
                    type: "DELETE",
                    headers: authHeaders({
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                    success: function (data) {
                        if (data.success) {
                            showToast(
                                data.message || "Deal deleted successfully!",
                                "success",
                            );
                            fetchDeals();
                        } else {
                            showToast(
                                data.message || "Failed to delete deal.",
                                "error",
                            );
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    },
                    error: function () {
                        showToast("An error occurred. Please try again.", "error");
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    },
                });
            });
        }

        const renderPagination = (page) => {
            if (!page || page.total === 0) {
                paginationContainer.innerHTML = "";
                return;
            }

            const from = page.from || 0;
            const to = page.to || 0;
            const total = page.total || 0;

            let paginationHtml = `
                <div class="crm-pagination-container">
                    <div class="text-muted small">
                        Showing ${from} to ${to} of ${total} results
                    </div>
                    <ul class="pagination crm-pagination mb-0">
            `;

            if (page.prev_page_url) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-url="${page.prev_page_url}" aria-label="Previous">
                            <span aria-hidden="true">Previous</span>
                        </a>
                    </li>
                `;
            } else {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">Previous</span>
                    </li>
                `;
            }

            const currentPage = page.current_page || 1;
            const lastPage = page.last_page || 1;

            for (let i = 1; i <= lastPage; i++) {
                if (i === currentPage) {
                    paginationHtml += `
                        <li class="page-item active">
                            <span class="page-link">${i}</span>
                        </li>
                    `;
                } else {
                    const pageUrl = (page.path || '/api/deals') + '?page=' + i;
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link" href="#" data-url="${pageUrl}">${i}</a>
                        </li>
                    `;
                }
            }

            if (page.next_page_url) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-url="${page.next_page_url}" aria-label="Next">
                            <span aria-hidden="true">Next</span>
                        </a>
                    </li>
                `;
            } else {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">Next</span>
                    </li>
                `;
            }

            paginationHtml += `
                            </ul>
                </div>
            `;

            paginationContainer.innerHTML = paginationHtml;

            document
                .querySelectorAll(".page-link[data-url]")
                .forEach((link) => {
                    link.addEventListener("click", (e) => {
                        e.preventDefault();
                        fetchDeals(link.dataset.url);
                    });
                });
        };

        const renderRows = (items) => {
            if (!items || !items.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-file-earmark-text display-1 opacity-25"></i>
                            </div>
                            <p class="text-muted">No deals found.</p>
                            ${permissions.create ? '<a href="/deals/create" class="btn btn-dark-blue btn-sm rounded-pill px-4">Create Your First Deal</a>' : ''}
                        </td>
                    </tr>`;
                return;
            }

            const statusBadgeMeta = (status) => {
                const name = (status?.name || "").toLowerCase().trim();
                const color = status?.color || "";

                if (color) {
                    return {
                        className: "",
                        style: `background-color: ${color}; color: #fff;`,
                    };
                }

                switch (name) {
                    case "new":
                    case "open":
                        return { className: "bg-primary text-white", style: "" };
                    case "qualified":
                        return { className: "bg-info text-dark", style: "" };
                    case "proposal":
                        return { className: "bg-warning text-dark", style: "" };
                    case "negotiation":
                    case "in-process":
                    case "in process":
                        return { className: "bg-dark text-white", style: "" };
                    case "won":
                        return { className: "bg-success text-white", style: "" };
                    case "lost":
                        return { className: "bg-danger text-white", style: "" };
                    case "paused":
                        return { className: "bg-secondary text-white", style: "" };
                    default:
                        return { className: "bg-secondary text-white", style: "" };
                }
            };

            tableBody.innerHTML = items
                .map((deal, index) => {
                    const currencySymbol =
                        deal.currency?.symbol || deal.currency?.code || "";
                    const amount =
                        deal.amount !== null && deal.amount !== undefined
                            ? Number(deal.amount).toLocaleString("en-US", {
                                  minimumFractionDigits: 2,
                                  maximumFractionDigits: 2,
                              })
                            : "0.00";

                    const statusName = deal.status?.name || "-";
                    const statusMeta = statusBadgeMeta(deal.status);
                    const customerName = deal.customer?.name || "-";
                    const estimateName =
                        deal.estimate?.estimate_name ||
                        deal.title ||
                        "-";
                    const rowNumber =
                        deal.row_number ||
                        deal.sr_no ||
                        deal.serial_no ||
                        deal.srNo ||
                        deal.srno ||
                        index + 1;

                    const statusHtml = `<span class="badge rounded-pill px-3 d-inline-flex align-items-center justify-content-center ${statusMeta.className}" style="${statusMeta.style}; min-width: 98px;">${statusName}</span>`;
                    return `
                    <tr>
                        <td class="text-center" data-label="Sr.No">${rowNumber}</td>
                        <td class="text-center" data-label="Customer Name">${customerName}</td>
                        <td class="d-none d-md-table-cell text-center" data-label="Estimate Name">${estimateName}</td>
                        <td class="d-none d-md-table-cell text-center" data-label="Estimate Amount">${currencySymbol}${amount}</td>
                        <td class="d-none d-md-table-cell text-center" data-label="Status">
                            ${statusHtml}
                        </td>
                        <td class="d-none d-md-table-cell text-center" data-label="Action">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2 w-100">
                                ${permissions.edit ? `<a href="/deals/${deal.id}/edit" class="btn crm-action-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>` : ''}
                                ${permissions.view ? `<a href="/deals/${deal.id}" class="btn crm-action-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>` : ''}
                                ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-deal-id="${deal.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
                            </div>
                        </td>
                        <td class="text-center d-md-none">
                            <button type="button" class="btn-user-expand" data-deal-id="${deal.id}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="details-row d-md-none border-0" id="details-${deal.id}" style="display: none;">
                        <td colspan="4" class="p-0 border">
                            <div class="details-content">
                                <div class="row g-3">
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-hashtag"></i> Sr.No :</div>
                                        <div class="expand-value">${rowNumber}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-building"></i> Customer Name :</div>
                                        <div class="expand-value">${customerName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-file-lines"></i> Estimate Name :</div>
                                        <div class="expand-value">${estimateName}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-sack-dollar"></i> Estimate Amount :</div>
                                        <div class="expand-value">${currencySymbol}${amount}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>
                                        <div class="expand-value">${statusHtml}</div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                                        <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                                            ${permissions.edit ? `<a href="/deals/${deal.id}/edit" class="btn crm-action-btn btn-sm"><i class="bi bi-pencil"></i></a>` : ''}
                                            ${permissions.view ? `<a href="/deals/${deal.id}" class="btn crm-action-btn btn-sm"><i class="bi bi-eye"></i></a>` : ''}
                                            ${permissions.delete ? `<button type="button" class="btn crm-action-btn btn-sm text-danger delete-btn" data-deal-id="${deal.id}"><i class="bi bi-trash"></i></button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
                })
                .join("");

            document.querySelectorAll(".delete-btn").forEach((button) => {
                button.addEventListener("click", function (e) {
                    e.preventDefault();
                    const dealId = this.dataset.dealId;
                    deleteDeal(dealId, this);
                });
            });

            document.querySelectorAll(".btn-user-expand").forEach((button) => {
                button.addEventListener("click", function () {
                    const id = this.dataset.dealId;
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
        };

        const fetchDeals = (url = null) => {
            let apiUrl = url || "/api/deals";

            const params = new URLSearchParams();
            if (searchInput.value.trim()) {
                params.set("search", searchInput.value.trim());
            }

            const urlObj = new URL(apiUrl, window.location.origin);
            params.forEach((value, key) => {
                urlObj.searchParams.set(key, value);
            });

            $.ajax({
                url: urlObj.toString(),
                type: "GET",
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                }),
                success: function (data) {
                    if (data.success && data.data) {
                        const page = data.data;
                        renderRows(page.data || []);
                        renderPagination(page);
                    }
                },
                error: function () {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted mb-3"><i class="bi bi-exclamation-triangle display-1 opacity-25"></i></div>
                                <p class="text-muted">Error loading deals. Please try again.</p>
                            </td>
                        </tr>`;
                },
            });
        };

        let searchTimer;
        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => fetchDeals(), 300);
        });

        fetchDeals();
    }
})();

// =========================================== Submit ===========================================

$(document).ready(function () {
    function showToast(message, type = "info") {
        const mappedType =
            {
                success: "success",
                error: "error",
                warning: "warning",
                info: "info",
            }[type] || "info";

        if (typeof window.showAlert === "function") {
            window.showAlert(mappedType, message);
            return;
        }

        alert(message);
    }

    const authHeaders = (extraHeaders = {}) =>
        typeof window.crmApplyAuthHeaders === "function"
            ? window.crmApplyAuthHeaders(extraHeaders)
            : extraHeaders;

    function setSelectValue(select, value) {
        if (!select || value === undefined || value === null || value === "") {
            return;
        }

        if (select.tomselect) {
            select.tomselect.setValue(String(value), true);
            return;
        }

        $(select).val(String(value)).trigger("change");
    }

    function initDealEstimateSync() {
        const estimateSelect = document.getElementById("estimate_id");
        const customerSelect = document.getElementById("customer_id");
        const amountInput = document.getElementById("amount");
        const titleInput = document.getElementById("title");

        if (!estimateSelect || !customerSelect || !amountInput || !titleInput) {
            return;
        }

        const estimateOptions = Array.from(estimateSelect.options)
            .filter((option) => option.value)
            .map((option) => ({
                value: option.value,
                text: option.textContent,
                customerId: option.dataset.customerId || "",
                amount: option.dataset.amount || "",
                title: option.dataset.title || "",
                selected: option.selected,
            }));

        const estimatePlaceholder = estimateSelect.options[0]?.textContent || "Select Estimate";

        const rebuildEstimateOptions = (customerId, preferredEstimateId = "") => {
            const matchingOptions = estimateOptions.filter(
                (option) => option.customerId && option.customerId === String(customerId || ""),
            );

            estimateSelect.innerHTML = `<option value="">${estimatePlaceholder}</option>`;

            matchingOptions.forEach((option) => {
                const optionEl = document.createElement("option");
                optionEl.value = option.value;
                optionEl.textContent = option.text;
                optionEl.dataset.customerId = option.customerId;
                optionEl.dataset.amount = option.amount;
                optionEl.dataset.title = option.title;
                if (preferredEstimateId && String(preferredEstimateId) === String(option.value)) {
                    optionEl.selected = true;
                }
                estimateSelect.appendChild(optionEl);
            });

            if (estimateSelect.tomselect) {
                estimateSelect.tomselect.sync();
            }

            if (!matchingOptions.length || !preferredEstimateId) {
                setSelectValue(estimateSelect, "");
            }
        };

        const syncEstimateDetails = () => {
            const option = estimateSelect.options[estimateSelect.selectedIndex];

            if (!option || !option.value) {
                const customerOption =
                    customerSelect.options[customerSelect.selectedIndex];
                titleInput.value = customerOption?.text?.trim()
                    ? `Deal - ${customerOption.text.trim()}`
                    : "";
                return;
            }

            const estimateAmount = option.dataset.amount || "";
            const estimateTitle = option.dataset.title || "";
            const customerId = option.dataset.customerId || "";

            if (customerId) {
                setSelectValue(customerSelect, customerId);
            }

            if (estimateAmount) {
                amountInput.value = estimateAmount;
            }

            if (estimateTitle) {
                titleInput.value = estimateTitle;
            }
        };

        estimateSelect.addEventListener("change", syncEstimateDetails);
        customerSelect.addEventListener("change", () => {
            rebuildEstimateOptions(customerSelect.value);
            amountInput.value = "";
            const customerOption =
                customerSelect.options[customerSelect.selectedIndex];
            titleInput.value = customerOption?.text?.trim()
                ? `Deal - ${customerOption.text.trim()}`
                : "";
        });

        const selectedEstimateId = estimateSelect.value;
        const selectedEstimateOption = estimateOptions.find(
            (option) => String(option.value) === String(selectedEstimateId),
        );

        if (selectedEstimateOption?.customerId && !customerSelect.value) {
            setSelectValue(customerSelect, selectedEstimateOption.customerId);
        }

        rebuildEstimateOptions(
            customerSelect.value,
            selectedEstimateId || selectedEstimateOption?.value || "",
        );
        syncEstimateDetails();
    }

    function initDefaultDealStatus() {
        const statusSelect = document.getElementById("status_id");

        if (!statusSelect || statusSelect.value) {
            return;
        }

        const pendingOption = Array.from(statusSelect.options).find(
            (option) =>
                option.value &&
                option.textContent.trim().toLowerCase() === "pending",
        );

        if (!pendingOption) {
            return;
        }

        setSelectValue(statusSelect, pendingOption.value);
    }

    function clearErrors($form) {
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".ts-wrapper.is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").html("");
        $form.find(".invalid-feedback.ajax-error").remove();
        $form.find(".ajax-alert").remove();
    }

    function showErrors($form, errors) {
        $.each(errors, function (field, messages) {
            const input = $form.find(`[name="${field}"]`);
            const errorDiv = $form.find(`#${field}-error`);

            if (input.length) {
                input.addClass("is-invalid");
                if (input.is("select")) {
                    input.next(".ts-wrapper").addClass("is-invalid");
                }
                if (errorDiv.length) {
                    errorDiv.html(messages[0]);
                } else {
                    input.after(`<div class="invalid-feedback ajax-error">${messages[0]}</div>`);
                }
            }
        });
    }

    initDealEstimateSync();
    initDefaultDealStatus();

    $("body").on("submit", ".ajax-deal-form", function (e) {
        e.preventDefault();
        const $form = $(this);
        const btn = $form.find('button[type="submit"]');
        const originalText = btn.html();
        const redirectUrl = "/deals";
        const isEdit = $form.find('input[name="_method"][value="PUT"]').length > 0;

        clearErrors($form);

        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        const formData = new FormData(this);

        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            }),
            success: function (response) {
                if (isEdit && response.history_entry && window.crmStatusHistory) {
                    showToast(
                        response.message || "Deal saved successfully!",
                        "success",
                    );
                    $form.find('input[name="status_comment"]').val("");
                    window.crmStatusHistory.prepend(response.history_entry);
                    return;
                }

                showToast(
                    response.message || "Deal saved successfully!",
                    "success",
                );
                setTimeout(function () {
                    window.location.href = response.redirect || redirectUrl;
                }, 300);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        showErrors($form, response.errors);
                    }
                } else {
                    const response = xhr.responseJSON || {};
                    showToast(
                        response.message || "An error occurred. Please try again.",
                        "error",
                    );
                }
            },
            complete: function () {
                btn.prop("disabled", false).html(originalText);
            },
        });
    });

    $("input, select, textarea").on("input change", function () {
        $(this).removeClass("is-invalid");
        if ($(this).is("select")) {
            $(this).next(".ts-wrapper").removeClass("is-invalid");
        }
        $(`#${$(this).attr("id")}-error`).html("");
    });
});
