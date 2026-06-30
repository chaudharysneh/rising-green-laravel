@extends('layouts.app')

@section('page_title', 'All Notifications')

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-0">Notifications</h4>
                        <p class="text-muted small mb-0">Your recent alerts and system messages.</p>
                    </div>
                    <div class="d-flex gap-3">
                        <button type="button" class="btn d-flex align-items-center gap-2 rounded-pill px-3 py-1 border-0"
                                id="btnMarkAllRead"
                                style="color: #4f46e5; font-weight: 600; font-size: 0.95rem; background-color: #f0f5ff;">
                            <span class="d-flex align-items-center justify-content-center rounded-circle" style="background-color: #4f46e5; width: 26px; height: 26px;">
                                <i class="fa-solid fa-check-double text-white" style="font-size: 12px;"></i>
                            </span>
                            Mark All Read
                        </button>
                        <button type="button" class="btn d-flex align-items-center gap-2 rounded-pill px-3 py-1 border-0"
                                id="btnDeleteAll"
                                style="color: #ef4444; font-weight: 600; font-size: 0.95rem; background-color: #fef2f2;">
                            <span class="d-flex align-items-center justify-content-center rounded-circle" style="background-color: #ef4444; width: 26px; height: 26px;">
                                <i class="fa-solid fa-trash text-white" style="font-size: 12px;"></i>
                            </span>
                            Delete All
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="notification-list" id="notificationList">
                    {{-- Notifications will be loaded here via AJAX --}}
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white py-4 px-4" id="notificationsPagination"></div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .notification-list {
            display: flex;
            flex-direction: column;
        }

        .notification-list .notification-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--crm-border);
            transition: background .15s ease;
        }

        .notification-list .notification-row:hover {
            background: #F8FAFC;
        }

        .notification-list .notification-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(59, 91, 219, .1);
            color: var(--crm-accent);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
        }

        .notification-list .notification-message {
            font-size: .95rem;
            line-height: 1.4;
            color: var(--crm-text-body);
        }

        .notification-list .notification-time {
            font-size: .8rem;
            color: var(--crm-text-muted);
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.crmNotificationsListUrl = "{{ route('notifications.list') }}";
        window.crmCsrfToken = "{{ csrf_token() }}";
    </script>
    <script
        src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/notification.js') }}"></script>
@endpush