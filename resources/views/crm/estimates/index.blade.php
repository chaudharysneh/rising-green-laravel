@extends('layouts.app')

@section('page_title', 'Estimates')

@push('styles')
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
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
                <div class="d-flex flex-wrap gap-2">
                    @can('estimates.create')
                        <button type="button" class="btn btn-outline-dark-blue" data-bs-toggle="modal" data-bs-target="#quickEstimateModal">
                            <i class="bi bi-lightning-charge me-1"></i>Quick Estimate
                        </button>
                        <a href="{{ route('estimates.create') }}" class="btn btn-dark-blue">
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
<div class="modal fade" id="quickEstimateModal" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Quick Estimate</h5>
                    <p class="small text-muted mb-0">Create a basic estimate with default/static settings.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickEstimateForm" novalidate>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <select class="form-select" name="customer_id" id="quick_estimate_customer_id" required>
                                <option value="">Select Customer</option>
                                @foreach ($customers ?? [] as $customer)
                                    <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="quick_customer_id-error">Please select a customer.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estimate Name</label>
                            <input type="text" class="form-control" name="estimate_name" id="quick_estimate_name" placeholder="Auto from customer">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Type</label>
                            <select class="form-select" name="type">
                                <option value="residential" selected>Residential</option>
                                <option value="commercial">Commercial</option>
                                <option value="industrial">Industrial</option>
                                <option value="common meter">Common Meter</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity (kW) <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control" name="quantity" id="quick_quantity" placeholder="Enter kW" required>
                            <div class="invalid-feedback" id="quick_quantity-error">Please enter quantity.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control" name="price" id="quick_price" placeholder="Enter price" required>
                            <div class="invalid-feedback" id="quick_price-error">Please enter price.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quotation Template <span class="text-danger">*</span></label>
                            <select class="form-select" name="template_id" id="quick_template_id" required>
                                <option value="">Select Template</option>
                                @foreach ($templates ?? [] as $template)
                                    <option value="{{ $template->id }}">{{ $template->template_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="quick_template_id-error">Please select template.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">BOM Details <span class="text-danger">*</span></label>
                            <div class="border rounded-3 bg-light p-3">
                                <div id="quickBomRows" class="d-flex flex-column gap-2">
                                    <div class="quick-bom-row bg-white border rounded-3 p-2">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-3">
                                                <label class="form-label small fw-semibold">BOM</label>
                                                <select class="form-select quick-bom-select" name="quick_bom_id[]">
                                                    <option value="">Select BOM</option>
                                                    @foreach ($bomProducts ?? [] as $bom)
                                                        <option value="{{ $bom->id }}"
                                                            data-name="{{ $bom->product_name }}"
                                                            data-price="{{ $bom->price ?? 0 }}"
                                                            data-make="{{ optional($bom->categories->first())->name ?? '' }}">
                                                            {{ $bom->product_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small fw-semibold">Make</label>
                                                <input type="text" class="form-control quick-bom-make" name="quick_bom_make[]" placeholder="Make">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small fw-semibold">Qty</label>
                                                <input type="number" min="1" step="1" class="form-control quick-bom-qty" name="quick_bom_qty[]" value="1">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small fw-semibold">Unit Price</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-price" name="quick_bom_price[]" value="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small fw-semibold">Amount</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-amount" value="0" readonly>
                                            </div>
                                            <div class="col-md-1">
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
                                <div class="invalid-feedback d-block" id="quick_bom_id-error" style="display:none;">Please select at least one BOM.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Comment</label>
                            <textarea class="form-control" name="comment" id="quick_estimate_comment" rows="3" placeholder="Optional comment"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top rounded-bottom-4">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark-blue" id="quickEstimateSubmitBtn">
                        <span class="submit-label">Create Estimate</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<div class="modal fade" id="estimateDocsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
        window.crmUserPermissions = {
            estimates: {
                view: @json(auth()->user()?->hasMatrixPermission('view_estimates')),
                create: @json(auth()->user()?->hasMatrixPermission('create_estimates')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_estimates')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_estimates')),
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/estimates.js') }}?v={{ filemtime(public_path('js/estimates.js')) }}"></script>
@endpush
