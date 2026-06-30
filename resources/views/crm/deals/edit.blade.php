@extends('layouts.app')

@section('page_title', 'Deals - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden deal-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Deal</h1>
                        <p class="text-muted small mb-0">Update deal details for the customer.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('deals.view')
                            <a href="{{ route('deals.show', $deal) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('deals.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/deals/{{ $deal->id }}" id="dealForm" class="needs-validation ajax-deal-form js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')
                    @php
                        $statusOrder = ['Pending', 'In-Process', 'Paused', 'Lost', 'Won/Confirm'];
                        $filteredStatuses = $statuses->filter(function ($status) use ($statusOrder) {
                            return filled($status->name) && in_array(trim($status->name), $statusOrder, true);
                        })->values();
                        $orderedStatuses = $filteredStatuses->sortBy(function ($status) use ($statusOrder) {
                            $index = array_search($status->name, $statusOrder, true);
                            return $index === false ? 999 : $index;
                        })->values();
                        $defaultStageId = old('stage_id', $deal->stage_id ?: optional($stages->first())->id);
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer </label>
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-grow-1 w-100">
                                    <select name="customer_id" id="customer_id" class="form-select"
                                        data-search-url="{{ route('customers.search.api') }}" data-search-type="customer"
                                        data-search-placeholder="Select Customer" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" data-email="{{ $customer->email }}" data-phone="{{ $customer->phone }}" @selected(old('customer_id', $deal->customer_id) == $customer->id)>{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="customer_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estimate Template </label>
                            <select name="estimate_id" id="estimate_id" class="form-select">
                                <option value="">Select Estimate</option>
                                @foreach ($estimates as $estimate)
                                    <option value="{{ $estimate->estimate_id }}"
                                        data-customer-id="{{ $estimate->customer_id }}"
                                        data-amount="{{ $estimate->amount ?? $estimate->total ?? '' }}"
                                        data-title="{{ $estimate->estimate_name ?: ('Estimate #' . $estimate->estimate_id) }}"
                                        @selected(old('estimate_id', $deal->estimate_id) == $estimate->estimate_id)>
                                        {{ $estimate->estimate_name ?: ('Estimate #' . $estimate->estimate_id) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="estimate_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estimate Amount </label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount" value="{{ old('amount', $deal->amount) }}" class="form-control" required>
                            <div class="invalid-feedback" id="amount-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Time Line </label>
                            <div class="input-group">
                                <input type="number" min="1" name="timeline_value" id="timeline_value" value="{{ old('timeline_value', $deal->timeline_value ?: 3) }}"
                                    class="form-control" required>
                                <select name="timeline_unit" id="timeline_unit" class="form-select" required style="max-width: 200px;">
                                    <option value="days" @selected(old('timeline_unit', $deal->timeline_unit ?: 'days') === 'days')>Days</option>
                                    <option value="months" @selected(old('timeline_unit', $deal->timeline_unit) === 'months')>Months</option>
                                </select>
                            </div>
                            <div class="invalid-feedback d-block" id="timeline_value-error"></div>
                            <div class="invalid-feedback d-block" id="timeline_unit-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Deal Status </label>
                            <select name="status_id" id="status_id" class="form-select js-status-comment-trigger" required>
                                @foreach ($orderedStatuses as $status)
                                    <option value="{{ $status->id }}" @selected(old('status_id', $deal->status_id) == $status->id)>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="status_id-error"></div>
                        </div>

                        <input type="hidden" name="assigned_user_id" value="{{ old('assigned_user_id', $deal->assigned_user_id ?: auth()->id()) }}">
                        <input type="hidden" name="title" id="title" value="{{ old('title', $deal->title) }}">
                        <input type="hidden" name="probability" value="{{ old('probability', $deal->probability ?? 0) }}">
                        <input type="hidden" name="stage_id" value="{{ $defaultStageId }}">
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('deals.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $deal->statusHistories])
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <a href="#" class="small text-decoration-none" onclick="$('#quick_address_container').toggleClass('d-none'); return false;">+ Add Address (Optional)</a>
                        </div>
                        <div class="mb-3 d-none" id="quick_address_container">
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

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050" id="toastContainer"></div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/deal.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#customer_id, #estimate_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
            $('#saveQuickCustomerBtn').click(function() {
                let name = $('#quick_customer_name').val().trim();
                let number = $('#quick_customer_number').val().trim();
                let address = $('#quick_customer_address').val().trim();
                
                $('#quick_customer_name').removeClass('is-invalid').siblings('.invalid-feedback').text('Please enter customer name');
                $('#quick_customer_number').removeClass('is-invalid').siblings('.invalid-feedback').text('Please enter mobile number');
                
                if (!name || !number) {
                    if(!name) $('#quick_customer_name').addClass('is-invalid');
                    if(!number) $('#quick_customer_number').addClass('is-invalid');
                    return;
                }
                
                if (!address) {
                    address = 'Address';
                }
                
                let btn = $(this);
                let originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.ajax({
                    url: '/api/customers',
                    type: 'POST',
                    data: {
                        name: name,
                        phone: number,
                        address: address,
                        status: 'active',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success && res.data) {
                            let selectEl = document.getElementById('customer_id');
                            let newOption = new Option(res.data.name, res.data.id, true, true);
                            $(newOption).attr('data-email', res.data.email || '');
                            $(newOption).attr('data-phone', res.data.phone || '');
                            $('#customer_id').append(newOption).trigger('change');
                            
                            $('#addCustomerModal').modal('hide');
                            $('#addCustomerQuickForm')[0].reset();
                            if (window.showAlert) window.showAlert('success', 'Customer added successfully');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = xhr.responseJSON?.message || 'Failed to add customer';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errors = xhr.responseJSON.errors;
                            if (errors.phone) {
                                $('#quick_customer_number').addClass('is-invalid');
                                $('#quick_customer_number').siblings('.invalid-feedback').text(errors.phone[0]);
                                errorMessage = errors.phone[0];
                            }
                            if (errors.name) {
                                $('#quick_customer_name').addClass('is-invalid');
                                $('#quick_customer_name').siblings('.invalid-feedback').text(errors.name[0]);
                                errorMessage = errors.name[0];
                            }
                        }
                        if (window.showAlert) window.showAlert('error', errorMessage);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            $('#addCustomerModal').on('hidden.bs.modal', function () {
                $('#addCustomerQuickForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('#quick_address_container').addClass('d-none');
            });
        });
    </script>
@endpush
