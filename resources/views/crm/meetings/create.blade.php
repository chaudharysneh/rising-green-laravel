@extends('layouts.app')

@section('page_title', 'Add Meeting')

@section('content')
    <div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Add Meeting</h1>
                    <p class="text-muted small mb-0">Schedule a new meeting with a customer.</p>
                </div>
                <div class="d-flex w-100 w-md-auto gap-2">
                    <a href="{{ route('meetings.index') }}" class="btn btn-dark-blue back-btn flex-grow-1 flex-md-grow-0">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">

                <form method="POST" action="/api/meetings" id="meetingForm" class="needs-validation" novalidate>
                    @csrf

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned For </label>
                            <select name="customer_id" id="customer_id" class="form-select"
                                data-search-url="{{ route('customers.search.api') }}" data-search-type="customer"
                                data-search-placeholder="Select Customer" required>
                                <option value="">Select Customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                        data-phone="{{ $customer->phone }}">
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="customer_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned To</label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id" class="form-select"
                                    data-search-url="{{ route('api.users.search') }}" data-search-type="user"
                                    data-search-placeholder="Select Staff">
                                    <option value="">Select Staff</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', auth()->id()) == $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback" id="assigned_user_id-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Title </label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="Enter meeting title" required>
                            <div class="invalid-feedback" id="title-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status </label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="scheduled" selected>Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="status-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Scheduled On </label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                                value="{{ old('scheduled_at') }}" required>
                            <div class="invalid-feedback" id="scheduled_at-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Type </label>
                            <select name="meeting_type" id="meeting_type" class="form-select" required>
                                <option value="">Select Meeting Type</option>
                                <option value="virtual">Virtual</option>
                                <option value="in-person">In-person</option>
                                <option value="telephonic">Telephonic</option>
                            </select>
                            <div class="invalid-feedback" id="meeting_type-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Agenda </label>
                            <textarea name="agenda" id="agenda" rows="4" class="form-control" placeholder="Enter meeting agenda" required></textarea>
                            <div class="invalid-feedback" id="agenda-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" id="address" rows="4" class="form-control" placeholder="Enter meeting location/address"></textarea>
                            <div class="invalid-feedback" id="address-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('meetings.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-dark-blue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/meeting.js') }}"></script>
@endpush
