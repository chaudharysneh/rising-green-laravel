@extends('layouts.app')

@section('page_title', 'Tasks - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden task-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Task</h1>
                        <p class="text-muted small mb-0">Update task details for the team.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('tasks.view')
                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('tasks.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/tasks/{{ $task->id }}" id="taskForm"
                    class="needs-validation ajax-task-form js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-diagram-project me-2 text-muted"></i>Estimates <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2" style="min-width: 0;">
                                <div class="flex-grow-1 w-100" style="min-width: 0;">
                                    <select name="estimate_id" id="estimate_id"
                                        class="form-select @error('estimate_id') is-invalid @enderror" required>
                                        <option value="">Select Estimates</option>
                                        @foreach($estimates as $estimate)
                                            <option value="{{ $estimate->estimate_id }}"
                                                @selected(old('estimate_id', $selectedEstimateId) == $estimate->estimate_id)>
                                                {{ $estimate->estimate_name ?: $estimate->estimate_no ?: 'Estimate #' . $estimate->estimate_id }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @can('estimates.create')
                                    <button type="button" class="btn btn-dark-blue flex-shrink-0" id="taskQuickEstimateBtn" title="Quick Estimate">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                @endcan
                            </div>
                            <div class="invalid-feedback d-block" id="estimate_id-error">{{ $errors->first('estimate_id') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-user me-2 text-muted"></i>Assigned To <span class="text-danger">*</span></label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id"
                                    class="form-select @error('assigned_user_id') is-invalid @enderror">
                                    <option value="">-- Search User --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', $task->assigned_user_id) == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback d-block" id="assigned_user_id-error">
                                {{ $errors->first('assigned_user_id') }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-list-check me-2 text-muted"></i>Task Title <span class="text-danger">*</span></label>
                            <input name="title" id="title" value="{{ old('title', $task->title) }}"
                                class="form-control @error('title') is-invalid @enderror" required>
                            <div class="invalid-feedback d-block" id="title-error">{{ $errors->first('title') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-bars me-2 text-muted"></i>Description</label>
                            <textarea name="description" id="description" rows="2"
                                class="form-control @error('description') is-invalid @enderror"
                                required>{{ old('description', $task->description) }}</textarea>
                            <div class="invalid-feedback d-block" id="description-error">{{ $errors->first('description') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-days me-2 text-muted"></i>Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" id="due_date"
                                value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}"
                                min="{{ date('Y-m-d') }}"
                                class="form-control @error('due_date') is-invalid @enderror" required>
                            <div class="invalid-feedback d-block" id="due_date-error">{{ $errors->first('due_date') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-arrow-up-long me-2 text-muted"></i>Priority</label>
                            <select name="priority" id="priority"
                                class="form-select searchable-select @error('priority') is-invalid @enderror" required>
                                <option value="">Select Priority</option>
                                <option value="low" @selected(old('priority', $task->priority) === 'low')>Low</option>
                                <option value="medium" @selected(old('priority', $task->priority) === 'medium')>Medium
                                </option>
                                <option value="high" @selected(old('priority', $task->priority) === 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error">{{ $errors->first('priority') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-circle-info me-2 text-muted"></i>Status <span class="text-danger">*</span></label>
                            <select name="status" id="status"
                                class="form-select searchable-select @error('status') is-invalid @enderror js-status-comment-trigger"
                                required>
                                <option value="">Select Status</option>
                                <option value="pending" @selected(old('status', $task->status) === 'pending')>Pending</option>
                                <option value="in_progress" @selected(old('status', $task->status) === 'in_progress')>In
                                    Progress</option>
                                <option value="completed" @selected(old('status', $task->status) === 'completed')>Completed
                                </option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error">{{ $errors->first('status') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-tags me-2 text-muted"></i>Task Type <span class="text-danger">*</span></label>
                            <select name="task_type" id="task_type" class="form-select searchable-select @error('task_type') is-invalid @enderror" required>
                                <option value="">Select Task Type</option>
                                <option value="Normal task" @selected(old('task_type', $task->task_type) === 'Normal task')>Normal task</option>
                                <option value="Site visit" @selected(old('task_type', $task->task_type) === 'Site visit')>Site visit</option>
                            </select>
                            <div class="invalid-feedback d-block" id="task_type-error">{{ $errors->first('task_type') }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('tasks.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @include('crm.partials.status-history-table', ['histories' => $task->statusHistories])
    </div>
    @can('estimates.create')
        @include('crm.estimates.partials.quick-estimate-modals')

        <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable quick-estimate-nested-modal">
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
    @endcan
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
        @can('estimates.create')
            <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/estimates.css') }}?v={{ filemtime(public_path('css/estimates.css')) }}">
        @endcan
        <style>
            .select2-results__options {
                max-height: 200px !important;
                overflow-y: auto !important;
            }
            #quickEstimateModal .modal-dialog { z-index: 1055; }
            #addCustomerModal, #quickAddBomModal { z-index: 1065 !important; }
            body.modal-open .modal-backdrop.show ~ .modal-backdrop.show { z-index: 1060; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        @can('estimates.create')
            @include('crm.estimates.partials.quick-estimate-scripts')
            <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/estimates.js') }}?v={{ filemtime(public_path('js/estimates.js')) }}"></script>
        @endcan
        <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/tasks.js') }}"></script>
        <script>
            $(document).ready(function() {
                $('#estimate_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#estimate_id').closest('.flex-grow-1')
                });

                $('#assigned_user_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                $('#taskQuickEstimateBtn').on('click', function(event) {
                    event.preventDefault();

                    window.quickEstimateDealContext = {
                        onCreated: function(estimateData) {
                            const estimateId = String(estimateData.estimate_id || '');
                            if (!estimateId) {
                                return;
                            }

                            const estimateName = estimateData.estimate_name || ('Estimate #' + estimateId);
                            let option = document.querySelector('#estimate_id option[value="' + estimateId.replace(/"/g, '\\"') + '"]');
                            if (!option) {
                                option = new Option(estimateName, estimateId, true, true);
                                $('#estimate_id').append(option);
                            }

                            $('#estimate_id').val(estimateId).trigger('change');
                        }
                    };

                    bootstrap.Modal.getOrCreateInstance(document.getElementById('quickEstimateModal')).show();
                });

                flatpickr("#due_date", {
                    allowInput: true,
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y",
                    minDate: "today"
                });
            });
        </script>
    @endpush
@endsection

