@extends('layouts.app')

@section('page_title', 'Tasks - Create')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden task-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Task</h1>
                        <p class="text-muted small mb-0">Create a new task for the team.</p>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/tasks" id="taskForm" class="needs-validation ajax-task-form" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-diagram-project me-2 text-muted"></i>Estimates</label>
                            <select name="estimate_id" id="estimate_id"
                                class="form-select @error('estimate_id') is-invalid @enderror" required>
                                <option value="">Select Estimates</option>
                                @foreach($estimates as $estimate)
                                    <option value="{{ $estimate->estimate_id }}" @selected(old('estimate_id') == $estimate->estimate_id)>
                                        {{ $estimate->estimate_name ?: $estimate->estimate_no ?: 'Estimate #' . $estimate->estimate_id }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" id="estimate_id-error">{{ $errors->first('estimate_id') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-user me-2 text-muted"></i>Assigned To</label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id"
                                    class="form-select @error('assigned_user_id') is-invalid @enderror"
                                    data-search-url="{{ route('api.users.search') }}" data-search-type="user"
                                    data-search-placeholder="-- Search User --">
                                    <option value="">-- Search User --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id', auth()->id()) == $user->id)>{{ $user->name }}</option>
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
                            <label class="form-label fw-semibold"><i class="fa-solid fa-list-check me-2 text-muted"></i>Task Title</label>
                            <input name="title" id="title" value="{{ old('title') }}"
                                class="form-control @error('title') is-invalid @enderror" placeholder="Enter task title" required>
                            <div class="invalid-feedback d-block" id="title-error">{{ $errors->first('title') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-bars me-2 text-muted"></i>Description</label>
                            <textarea name="description" id="description" rows="1"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Enter task description">{{ old('description') }}</textarea>
                            <div class="invalid-feedback d-block" id="description-error">{{ $errors->first('description') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-days me-2 text-muted"></i>Due Date</label>
                            <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}"
                                min="{{ date('Y-m-d') }}"
                                class="form-control js-date @error('due_date') is-invalid @enderror" required>
                            <div class="invalid-feedback d-block" id="due_date-error">{{ $errors->first('due_date') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-arrow-up-long me-2 text-muted"></i>Priority</label>
                            <select name="priority" id="priority"
                                class="form-select @error('priority') is-invalid @enderror">
                                <option value="">Select Priority</option>
                                <option value="low" @selected(old('priority') === 'low')>Low</option>
                                <option value="medium" @selected(old('priority') === 'medium')>Medium</option>
                                <option value="high" @selected(old('priority') === 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error">{{ $errors->first('priority') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-circle-info me-2 text-muted"></i>Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="">Select Status</option>
                                <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                                <option value="in_progress" @selected(old('status') === 'in_progress')>In Progress</option>
                                <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error">{{ $errors->first('status') }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('tasks.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/tasks.js') }}"></script>
@endpush
@endsection
