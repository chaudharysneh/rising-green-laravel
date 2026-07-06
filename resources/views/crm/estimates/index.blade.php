@extends('layouts.app')

@section('page_title', 'Estimates')

@push('styles')
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/estimates.css') }}?v={{ filemtime(public_path('css/estimates.css')) }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        .crm-filter-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        .crm-filter-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .crm-filter-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }
        .crm-filter-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }

        #quickEstimateModal .modal-dialog {
            z-index: 1055;
        }

        #addCustomerModal,
        #quickAddBomModal {
            z-index: 1065 !important;
        }

        body.modal-open .modal-backdrop.show ~ .modal-backdrop.show {
            z-index: 1060;
        }

        #quickEstimateModal .quick-bom-row .quick-bom-select-col {
            min-width: 0;
        }

        .select2-dropdown,
        .select2-container--open {
            z-index: 99999 !important;
        }

        #quickEstimateModal .d-flex:has(.is-invalid) ~ .invalid-feedback {
            display: block !important;
        }

        @media (max-width: 767.98px) {
            #addCustomerModal.modal,
            #quickAddBomModal.modal {
                padding-left: 1.25rem !important;
                padding-right: 1.25rem !important;
            }

            #addCustomerModal .modal-dialog,
            #quickAddBomModal .modal-dialog,
            .quick-estimate-nested-modal {
                margin: 1.5rem auto !important;
                max-width: min(340px, calc(100vw - 2.5rem)) !important;
                width: 100% !important;
            }

            #addCustomerModal .modal-body,
            #quickAddBomModal .modal-body {
                padding: 1rem;
            }
        }

        @media (max-width: 767.98px) {
            #quickEstimateModal {
                padding-bottom: 220px !important;
            }
            #quickEstimateModal .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
                align-items: flex-start;
                min-height: calc(100% - 1rem);
            }

            #quickEstimateModal .modal-content {
                margin-bottom: 3rem;
            }

            #quickEstimateModal .modal-body {
                padding-bottom: 3rem !important;
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
                overflow-x: clip;
            }

            #quickEstimateModal .modal-content {
                overflow-x: clip;
            }

            #quickEstimateModal .quick-totals-card .totals-row > .input-small {
                width: 120px;
                min-width: 120px;
                max-width: 160px;
            }

            /* Responsive Multi-Step Logic */
            #quickEstimateModal .quick-step-1,
            #quickEstimateModal .quick-step-2,
            #quickEstimateModal .quick-step-3 {
                display: none !important;
            }
            #quickEstimateModal .active-step {
                display: block !important;
            }

            #quickEstimateModal .active-step > .row {
                display: flex !important;
                flex-wrap: wrap;
            }
            .quick-bom-row-grid .quick-bom-select-col,
            .quick-bom-row-grid .quick-bom-make-col {
                grid-column: span 2;
            }
            
            /* Step indicator */
            .quick-step-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 15px;
            }
            .quick-step-dot {
                height: 8px;
                width: 100%;
                background: #e9ecef;
                border-radius: 10px;
                transition: 0.3s;
            }
            .quick-step-dot.active {
                background: #121a33;
            }
        }
        @media (min-width: 768px) {
            .quick-step-indicator {
                display: none !important;
            }
            .mobile-wizard-btn {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header border-bottom-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Estimates</h4>
                    <p class="text-muted small mb-0">Track all estimate quotations and proposals.</p>
                </div>
                <div class="d-flex flex-row gap-2 estimate-header-actions w-100 w-md-auto">
                    @can('estimates.create')
                        <button type="button" class="btn btn-outline-dark-blue flex-fill flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#quickEstimateModal">
                            <i class="bi bi-lightning-charge me-1"></i>Quick Estimate
                        </button>
                        <a href="{{ route('estimates.create') }}" class="btn btn-dark-blue flex-fill flex-md-grow-0">
                            <i class="bi bi-plus-lg me-1"></i>Add Estimate
                        </a>
                    @endcan
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h6 class="fw-bold mb-0">Estimates List</h6>
                <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                    <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control crm-search-input border-0" placeholder="Search estimates..." id="estimatesSearch" value="{{ request('search') }}">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(!auth()->user()->isAdmin())
            <div class="px-4 pt-3">
                <ul class="nav nav-tabs crm-filter-tabs" id="estimateFilterTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="created-by-me-tab" data-bs-toggle="tab" data-filter="created_by_me" type="button" role="tab">
                            Created By Me
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="assigned-to-me-tab" data-bs-toggle="tab" data-filter="assigned_to_me" type="button" role="tab">
                            Assigned To Me
                        </button>
                    </li>
                </ul>
            </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 responsive-table" id="estimatesTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Sr.No</th>
                            <th>Customer Name</th>
                            <th class="d-none d-md-table-cell">Estimate No</th>
                            <th class="d-none d-md-table-cell">Estimate Date</th>
                            <th class="d-none d-md-table-cell">Status</th>
                            <th class="text-end pe-4 d-none d-md-table-cell" style="width: 150px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="estimatesPagination" class="px-4 pb-3 pt-0"></div>
        </div>
    </div>
</div>

@can('estimates.create')
@php
    $bomTaxOptions = collect($gstTaxes ?? [])->map(function ($tax) {
        $taxName = strtoupper((string) $tax->name);
        $label = (string) $tax->name;

        if (str_contains($taxName, 'CGST') && str_contains($taxName, 'SGST')) {
            $label = 'CGST + SGST';
        } elseif (str_contains($taxName, 'IGST')) {
            $label = 'IGST';
        }

        return ['label' => $label, 'rate' => (float) $tax->rate];
    })->filter(function ($taxOption) {
        return (float) ($taxOption['rate'] ?? 0) > 0;
    })->values();
@endphp
<div class="modal fade" id="quickEstimateModal" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <form id="quickEstimateForm" novalidate class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-white">Quick Estimate</h5>
                    <p class="small text-white-50 mb-0">Create a basic estimate with default/static settings.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                    <!-- Mobile Step Indicator -->
                    <div class="quick-step-indicator d-md-none">
                        <div class="quick-step-dot active" id="qdot-1"></div>
                        <div class="quick-step-dot" id="qdot-2"></div>
                        <div class="quick-step-dot" id="qdot-3"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4 quick-step-1 active-step">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2" style="min-width: 0;">
                                <div class="flex-grow-1 w-100 quick-customer-select-wrap" style="min-width: 0;">
                                    <select class="form-select" name="customer_id" id="quick_estimate_customer_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers ?? [] as $customer)
                                            <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" id="quickEstimateAddCustomerBtn" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="quick_customer_id-error">Please select a customer.</div>
                        </div>
                        <div class="col-12 col-md-8 quick-step-1 active-step">
                            <div class="row g-2">
                                <div class="col-6 quick-step-field-col">
                                    <label class="form-label fw-semibold">Estimate Name</label>
                                    <input type="text" class="form-control" name="estimate_name" id="quick_estimate_name" placeholder="Auto from custom">
                                </div>
                                <div class="col-6 quick-type-select-wrap quick-step-field-col">
                                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" id="quick_estimate_type" required>
                                        <option value="" selected>Select Type</option>
                                        <option value="residential">Residential</option>
                                        <option value="commercial">Commercial</option>
                                        <option value="industrial">Industrial</option>
                                        <option value="common meter">Common Meter</option>
                                        <option value="ground mounted">Ground Mounted</option>
                                    </select>
                                    <div class="invalid-feedback" id="quick_type-error">Please select type.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-8 quick-step-1 active-step">
                            <div class="row g-2">
                                <div class="col-6 quick-step-field-col">
                                    <label class="form-label fw-semibold">Quantity (kW) <span class="text-danger">*</span></label>
                                    <input type="number" min="1" step="1" class="form-control" name="quantity" id="quick_quantity" placeholder="Enter kW" required>
                                    <div class="invalid-feedback" id="quick_quantity-error">Please enter quantity.</div>
                                </div>
                                <div class="col-6 quick-step-field-col">
                                    <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                                    <input type="number" min="1" step="1" class="form-control" name="price" id="quick_price" placeholder="Enter price" required>
                                    <div class="invalid-feedback" id="quick_price-error">Please enter price.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 quick-step-1 active-step quick-template-select-wrap" id="quick_template_wrapper">
                            <label class="form-label fw-semibold">Quotation Template <span class="text-danger">*</span></label>
                            <select class="form-select" name="template_id" id="quick_template_id" required>
                                <option value="">Select Template</option>
                                @foreach ($templates ?? [] as $template)
                                    <option value="{{ $template->id }}">{{ $template->template_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="quick_template_id-error">Please select template.</div>
                        </div>
                        <div class="col-12 quick-step-2">
                            <label class="form-label fw-semibold">BOM Details <span class="text-danger">*</span></label>
                            <div class="border rounded-3 bg-light p-3">
                                <div id="quickBomRows" class="d-flex flex-column gap-2">
                                    <div class="quick-bom-row bg-white border rounded-3 p-2">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-12 col-md-3 quick-bom-select-col">
                                                <label class="form-label small fw-semibold">BOM <span class="text-danger">*</span></label>
                                                <div class="d-flex align-items-start gap-2">
                                                    <select class="form-select quick-bom-select" name="quick_bom_id[]">
                                                        <option value="">Select BOM</option>
                                                        @foreach ($bomProducts ?? [] as $bom)
                                                            <option value="{{ $bom->id }}"
                                                                data-name="{{ $bom->product_name }}"
                                                                data-price="{{ $bom->price ?? 0 }}"
                                                                data-categories='{{ json_encode($bom->categories->pluck('name')->toArray()) }}'
                                                                data-tax-rate="{{ $bom->tax_rate ?? 0 }}">
                                                                {{ $bom->product_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-dark-blue flex-shrink-0 quick-estimate-add-bom-btn" title="Add New BOM">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 quick-bom-make-col">
                                                <label class="form-label small fw-semibold">Make <span class="text-danger">*</span></label>
                                                <select class="form-select quick-bom-make-select" name="quick_bom_make[]" disabled>
                                                    <option value="">Select Make</option>
                                                </select>
                                                <div class="invalid-feedback quick-bom-make-error">Please select make.</div>
                                            </div>
                                            <div class="col-6 col-md-1 quick-bom-qty-col">
                                                <label class="form-label small fw-semibold">Qty <span class="text-danger">*</span></label>
                                                <input type="number" min="1" step="1" class="form-control quick-bom-qty" name="quick_bom_qty[]" value="1">
                                            </div>
                                            <div class="col-6 col-md-2 quick-bom-money-col">
                                                <label class="form-label small fw-semibold">Unit Price</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-price" name="quick_bom_price[]" value="0">
                                            </div>
                                            <div class="col-12 col-md-1 quick-bom-money-col quick-bom-amount-col">
                                                <label class="form-label small fw-semibold">Amount</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-amount" value="0" readonly>
                                            </div>
                                            <div class="col-12 col-md-2 quick-bom-tax-col">
                                                <label class="form-label small fw-semibold">Tax</label>
                                                <select name="quick_bom_tax_rate[]" class="form-select quick-bom-tax-rate">
                                                    <option value="0" data-label="">No Tax</option>
                                                    @foreach ($bomTaxOptions as $taxOption)
                                                        <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}">
                                                            {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-1">
                                                <button type="button" class="btn btn-outline-danger w-100 quick-remove-bom-row" style="display:none;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-dark-blue btn-sm mt-3" id="quickAddBomRow">
                                    <i class="bi bi-plus-circle me-1"></i>Add More BOM
                                </button>
                                <div class="quick-bom-instruction" id="quick_bom_id-error" style="display:none;" role="status">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Please select at least one BOM.
                                </div>
                            </div>
                        </div>
                        <div class="col-12 quick-step-3">
                            <div class="totals-card quick-totals-card rounded-3">
                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subtotal:</span>
                                    <span id="quick_subtotal_display" class="fw-bold text-dark">0.00</span>
                                </div>

                                <div class="totals-row align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="switch mb-0">
                                            <input type="checkbox" id="quick_apply_gst" checked>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="small fw-semibold">Apply GST</span>
                                    </div>
                                </div>

                                <div id="quick_gst_fields_box">
                                    <div class="totals-row">
                                        <span class="small text-muted">Select BOM tax to apply GST.</span>
                                        <span class="small">0.00</span>
                                    </div>
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Discount:</span>
                                    <input type="number" id="quick_discount" value="0" step="1" class="input-small">
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subsidy:</span>
                                    <input type="number" id="quick_subsidy_amount" value="0" step="1" class="input-small">
                                </div>

                                <hr class="my-2">

                                <div class="totals-row total-row mb-0">
                                    <span class="h6 mb-0 fw-bold">Total Payable:</span>
                                    <span id="quick_final_total_display" class="h5 mb-0 fw-bold">0.00</span>
                                </div>
                            </div>
                            <input type="hidden" id="quick_subtotal" value="0">
                            <input type="hidden" id="quick_final_total" value="0">
                            <input type="hidden" id="quick_gst" value="0">
                        </div>
                        <div class="col-12 quick-step-3">
                            <label class="form-label fw-semibold">Comment</label>
                            <textarea class="form-control" name="comment" id="quick_estimate_comment" rows="3" placeholder="Optional comment"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top rounded-bottom-4 d-flex justify-content-between pb-3">
                    <button type="button" class="btn btn-outline-dark-blue d-none d-md-block" data-bs-dismiss="modal">Cancel</button>
                    
                    <!-- Mobile Wizard Buttons -->
                    <button type="button" class="btn btn-outline-dark-blue mobile-wizard-btn quick-prev-btn" style="display: none !important;">Back</button>
                    
                    <div class="d-flex ms-auto">
                        <button type="button" class="btn btn-dark-blue mobile-wizard-btn quick-next-btn">Next</button>
                        <button type="submit" class="btn btn-dark-blue quick-submit-btn" id="quickEstimateSubmitBtn">
                            <span class="submit-label">Create Estimate</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<div class="modal fade" id="estimateDocsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Customer Documents</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="estimateDocsEstimateId">
                <div class="mb-3">
                    <input type="file" id="estimateDocsFiles" class="form-control" multiple>
                    <div class="invalid-feedback d-block" id="estimateDocsFilesError" style="display:none;"></div>
                </div>
                <div id="estimateDocsList" class="d-flex flex-column gap-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark-blue" id="estimateDocsUploadBtn">Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable quick-estimate-nested-modal">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add New Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addCustomerQuickForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_customer_name" required>
                        <div class="invalid-feedback">Please enter customer name</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_customer_number" required>
                        <div class="invalid-feedback">Please enter mobile number</div>
                    </div>
                    <div class="mb-3">
                        <a href="#" class="small text-decoration-none" id="quickEstimateToggleAddress">+ Add Address (Optional)</a>
                    </div>
                    <div class="mb-0 d-none" id="quick_address_container">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea class="form-control" id="quick_customer_address" rows="2" placeholder="Address"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="saveQuickCustomerBtn">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add BOM Modal -->
<div class="modal fade" id="quickAddBomModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable quick-estimate-nested-modal">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add New BOM</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="quickAddBomForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">BOM Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_bom_name" required>
                        <div class="invalid-feedback" id="quick_bom_name-error">Please enter BOM name</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Make <span class="text-danger">*</span></label>
                        <select class="form-select quick-bom-make-select" id="quick_bom_category_id" required>
                            <option value="">Select Make</option>
                            @foreach ($categories ?? [] as $category)
                                <option value="{{ $category->id }}" data-name="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="quick_bom_category_id-error">Please select make</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Unit Price <span class="text-danger">*</span></label>
                        <input type="number" min="0" step="1" class="form-control" id="quick_bom_price" required>
                        <div class="invalid-feedback" id="quick_bom_price-error">Please enter unit price</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue" id="saveQuickBomBtn">Save BOM</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        @php
            $templateComments = ($templates ?? collect())->mapWithKeys(function ($template) {
                $formData = is_array($template->form_data) ? $template->form_data : (json_decode($template->form_data ?? '[]', true) ?: []);
                $comment = is_array($formData['estimate_comment'] ?? null) ? $formData['estimate_comment'] : [];
                return [
                    (string) $template->id => [
                        'active' => (int) ($comment['active'] ?? 0),
                        'content' => (string) ($comment['content'] ?? ''),
                    ],
                ];
            });
        @endphp
        window.estimateTemplateComments = @json($templateComments);
        window.subsidiesData = @json($subsidies ?? []);
        window.estimateBomQuickAddConfig = {
            storeUrl: @json(route('api.bom-products.store')),
            makeStoreUrl: @json(route('api.make.store'))
        };
        window.crmUserPermissions = {
            estimates: {
                view: @json(auth()->user()?->hasMatrixPermission('view_estimates')),
                create: @json(auth()->user()?->hasMatrixPermission('create_estimates')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_estimates')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_estimates')),
            }
        };

        $(document).ready(function() {
            let currentStep = 1;
            const totalSteps = 3;

            function updateWizardUI() {
                if (window.innerWidth >= 768) {
                    $('.quick-submit-btn').show();
                    return;
                }
                
                // Hide all steps, show current
                $('#quickEstimateModal .active-step').removeClass('active-step');
                $('#quickEstimateModal .quick-step-' + currentStep).addClass('active-step');
                
                // Update dots
                $('.quick-step-dot').removeClass('active');
                $('#qdot-' + currentStep).addClass('active');

                if (currentStep === 1) {
                    $('.quick-prev-btn').attr('style', 'display: none !important');
                    $('.quick-next-btn').show();
                    $('.quick-submit-btn').hide();
                } else if (currentStep === totalSteps) {
                    $('.quick-prev-btn').attr('style', 'display: inline-block !important');
                    $('.quick-next-btn').hide();
                    $('.quick-submit-btn').show();
                } else {
                    $('.quick-prev-btn').attr('style', 'display: inline-block !important');
                    $('.quick-next-btn').show();
                    $('.quick-submit-btn').hide();
                }
            }

            $('.quick-next-btn').click(function() {
                const isValid = typeof window.validateQuickEstimateWizardStep === 'function'
                    ? window.validateQuickEstimateWizardStep(currentStep)
                    : true;

                if (isValid && currentStep < totalSteps) {
                    currentStep++;
                    updateWizardUI();
                }
            });

            $('.quick-prev-btn').click(function() {
                if (currentStep > 1) {
                    currentStep--;
                    updateWizardUI();
                }
            });

            // Dynamically clear validation errors using event delegation
            $('#quickEstimateForm').on('change input', '[required], .quick-bom-select, .quick-bom-make-select, .quick-bom-qty, .quick-bom-price', function() {
                const val = $(this).val();
                if (val || $(this).hasClass('quick-bom-qty') || $(this).hasClass('quick-bom-price')) {
                    if (typeof window.markQuickEstimateFieldInvalid === 'function') {
                        window.markQuickEstimateFieldInvalid(this, false);
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                    if ($(this).hasClass('quick-bom-select') && typeof window.updateQuickBomErrorVisibility === 'function') {
                        window.updateQuickBomErrorVisibility(document.getElementById('quickEstimateForm'));
                    }
                    if ($(this).hasClass('quick-bom-make-select')) {
                        $(this).closest('.quick-bom-row').find('.quick-bom-make-error').removeClass('d-block');
                    }
                }
            });

            $('#quickEstimateModal').on('hidden.bs.modal', function () {
                currentStep = 1;
                updateWizardUI();
            });

            // Initial setup
            updateWizardUI();
            $(window).resize(updateWizardUI);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/estimates.js') }}?v={{ filemtime(public_path('js/estimates.js')) }}"></script>
@endpush



