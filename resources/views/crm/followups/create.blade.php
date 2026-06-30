@extends('layouts.app')

@section('page_title', 'Follow Ups - Create')

@section('content')
    <div class="container-fluid p-0">

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Schedule Follow Up</h1>
                        <p class="text-muted small mb-0">Create a new follow up record for a lead or customer.</p>
                    </div>
                    <a href="{{ route('followups.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/follow-ups" class="needs-validation ajax-followup-form" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lead Name </label>
                            <select name="lead_id" id="lead_id" class="form-select select2" required>
                                <option value="">Select Lead</option>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected(old('lead_id', request('lead_id')) == $lead->id)>{{ $lead->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" id="lead_id-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned To</label>
                            @if(auth()->user()->isAdmin())
                                <select name="assigned_user_id" id="assigned_user_id"
                                    class="form-select @error('assigned_user_id') is-invalid @enderror"
                                    data-search-url="{{ route('api.users.search') }}" data-search-type="user"
                                    data-search-placeholder="-- Search User --" required>
                                    <option value="">-- Search User --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                            @selected(old('assigned_user_id') == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="assigned_user_id"
                                    value="{{ old('assigned_user_id', auth()->id()) }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                            @endif
                            <div class="invalid-feedback d-block" id="assigned_user_id-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose </label>
                            <input name="purpose" id="purpose" value="{{ old('purpose') }}" class="form-control" placeholder="Enter purpose of follow up" required>
                            <div class="invalid-feedback d-block" id="purpose-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Comment</label>
                            <textarea name="comment" id="comment" rows="1"
                                class="form-control" placeholder="Enter any comments or notes">{{ old('comment') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority </label>
                            <select name="priority" id="priority" class="form-select" required>
                                <option value="" @selected(old('priority') === null)>Select Priority</option>
                                <option value="low" @selected(old('priority') == 'low')>Low</option>
                                <option value="medium" @selected(old('priority') == 'medium')>Medium</option>
                                <option value="high" @selected(old('priority') == 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status </label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="" @selected(old('status') === null)>Select Status</option>
                                <option value="pending" @selected(old('status') == 'pending')>Pending</option>
                                <option value="resheduled" @selected(old('status') == 'resheduled')>Rescheduled</option>
                                <option value="completed" @selected(old('status') == 'completed')>Completed</option>
                                <option value="cancelled" @selected(old('status') == 'cancelled')>Cancelled</option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Follow Up Date </label>
                            <input type="datetime-local" name="follow_up_at" id="follow_up_at"
                                value="{{ old('follow_up_at') }}" class="form-control" required>
                            <div class="invalid-feedback d-block" id="follow_up_at-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('followups.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.5.2/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/followup.js') }}"></script>
    <script>
        $(document).ready(function() {
            const isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
            const $leadSelect = $('#lead_id');
            const $assignedUserSelect = $('#assigned_user_id');
            const allUsers = @json($users);

            // When lead is selected, filter staff dropdown to show only assigned user
            $leadSelect.on('change', function() {
                const leadId = $(this).val();
                
                if (!leadId || !isAdmin) {
                    return;
                }

                // Fetch the assigned user for this lead
                $.ajax({
                    url: `/api/follow-ups/lead/${leadId}/assigned-user`,
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success && response.data.assigned_user) {
                            const assignedUser = response.data.assigned_user;
                            
                            // Clear and update the assigned user dropdown
                            $assignedUserSelect.empty();
                            $assignedUserSelect.append(
                                $('<option>', {
                                    value: assignedUser.id,
                                    text: assignedUser.name,
                                    'data-email': assignedUser.email,
                                    selected: true
                                })
                            );
                            
                            // Trigger change to update select2 if it's initialized
                            $assignedUserSelect.trigger('change');
                            
                            // Show success message
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('info', `Follow-up will be assigned to ${assignedUser.name} (Lead owner)`, 'Auto-assigned');
                            }
                        } else {
                            // If no assigned user, show all users
                            $assignedUserSelect.empty();
                            $assignedUserSelect.append('<option value="">-- Search User --</option>');
                            allUsers.forEach(function(user) {
                                $assignedUserSelect.append(
                                    $('<option>', {
                                        value: user.id,
                                        text: user.name,
                                        'data-email': user.email
                                    })
                                );
                            });
                            $assignedUserSelect.trigger('change');
                            
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('warning', 'This lead has no assigned staff member', 'No Assignment');
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching lead assigned user:', xhr);
                        // On error, restore all users
                        $assignedUserSelect.empty();
                        $assignedUserSelect.append('<option value="">-- Search User --</option>');
                        allUsers.forEach(function(user) {
                            $assignedUserSelect.append(
                                $('<option>', {
                                    value: user.id,
                                    text: user.name,
                                    'data-email': user.email
                                })
                            );
                        });
                        $assignedUserSelect.trigger('change');
                    }
                });
            });

            // Trigger on page load if lead is pre-selected
            if ($leadSelect.val()) {
                $leadSelect.trigger('change');
            }
        });

        // Add browser timezone offset to form submission
        $(document).on('submit', '.ajax-followup-form', function(e) {
            // Get browser timezone offset in minutes
            const offset = new Date().getTimezoneOffset();
            // Add hidden field with timezone offset
            if (!$('input[name="browser_timezone_offset"]').length) {
                $(this).append('<input type="hidden" name="browser_timezone_offset" value="' + offset + '">');
            } else {
                $('input[name="browser_timezone_offset"]').val(offset);
            }
        });
    </script>
@endpush
