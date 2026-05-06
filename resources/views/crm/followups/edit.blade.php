@extends('layouts.app')

@section('page_title', 'Follow Ups - Edit')

@section('content')
    <div class="container-fluid p-0">

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden lead-form-card mb-4">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Follow Up</h1>
                        <p class="text-muted small mb-0">Update follow up details.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('followups.view')
                            <a href="{{ route('followups.show', $followUp) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('followups.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/follow-ups/{{ $followUp->id }}"
                    class="needs-validation ajax-followup-form js-status-comment-form" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lead Name </label>
                            <select name="lead_id" id="lead_id" class="form-select select2" required>
                                <option value="">Select Lead</option>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected(old('lead_id', $followUp->lead_id) == $lead->id)>
                                        {{ $lead->name }}
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
                                            @selected(old('assigned_user_id', $followUp->assigned_user_id) == $user->id)>
                                            {{ $user->name }}
                                        </option>
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
                            <input name="purpose" id="purpose" value="{{ old('purpose', $followUp->purpose) }}"
                                class="form-control" required>
                            <div class="invalid-feedback d-block" id="purpose-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Comment</label>
                            <textarea name="comment" id="comment" rows="1"
                                class="form-control">{{ old('comment', $followUp->comment) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority </label>
                            <select name="priority" id="priority" class="form-select" required>
                                <option value="low" @selected(old('priority', $followUp->priority) == 'low')>Low</option>
                                <option value="medium" @selected(old('priority', $followUp->priority) == 'medium')>Medium</option>
                                <option value="high" @selected(old('priority', $followUp->priority) == 'high')>High</option>
                            </select>
                            <div class="invalid-feedback d-block" id="priority-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status </label>
                            <select name="status" id="status" class="form-select js-status-comment-trigger" required>
                                <option value="pending" @selected(old('status', $followUp->status) == 'pending')>Pending
                                </option>
                                <option value="resheduled" @selected(old('status', $followUp->status) == 'resheduled')>
                                    Rescheduled</option>
                                <option value="completed" @selected(old('status', $followUp->status) == 'completed')>Completed
                                </option>
                                <option value="cancelled" @selected(old('status', $followUp->status) == 'cancelled')>Cancelled
                                </option>
                            </select>
                            <div class="invalid-feedback d-block" id="status-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Follow Up Date </label>
                            <input type="datetime-local" name="follow_up_at" id="follow_up_at"
                                value="{{ old('follow_up_at', \Illuminate\Support\Carbon::parse($followUp->follow_up_at)->format('Y-m-d\\TH:i')) }}"
                                class="form-control" required>
                            <div class="invalid-feedback d-block" id="follow_up_at-error"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('followups.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue">Submit</button>
                    </div>
                </form>

                @include('crm.partials.status-history-table', ['histories' => $followUp->statusHistories])
            </div>
        </div>
    </div>

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
            $(document).ready(function () {

                $.ajax({
                    type: "GET",
                    url: "/api/follow-ups/{{ $followUp->id }}",
                    success: function (res) {

                        let data = res.data;

                        // Set input values
                        $('input[name="purpose"]').val(data.purpose);
                        $('textarea[name="comment"]').val(data.comment);

                        // Select dropdowns
                        $('select[name="assigned_user_id"]').val(data.assigned_user_id).trigger('change');
                        $('select[name="lead_id"]').val(data.lead_id).trigger('change');
                        $('select[name="priority"]').val(data.priority).trigger('change');
                        $('select[name="status"]').val(data.status).trigger('change');

                        // Format datetime-local
                        if (data.follow_up_at) {
                            let dt = data.follow_up_at.replace(' ', 'T').slice(0, 16);
                            $('input[name="follow_up_at"]').val(dt);
                        }
                    },
                    error: function (err) {
                        console.log(err);
                    }
                });

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
@endsection
