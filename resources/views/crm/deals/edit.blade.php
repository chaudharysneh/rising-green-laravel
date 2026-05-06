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
                            <select name="customer_id" id="customer_id" class="form-select"
                                data-search-url="{{ route('customers.search.api') }}" data-search-type="customer"
                                data-search-placeholder="Select Customer" required>
                                <option value="">Select Customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-email="{{ $customer->email }}" data-phone="{{ $customer->phone }}" @selected(old('customer_id', $deal->customer_id) == $customer->id)>{{ $customer->name }}</option>
                                @endforeach
                            </select>
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

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050" id="toastContainer"></div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <!-- <script src="{{ asset('js/deal.js') }}"></script> -->
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/deal.js') }}"></script>
@endpush
