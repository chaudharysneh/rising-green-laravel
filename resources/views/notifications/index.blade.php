@extends('layouts.app')

@section('page_title', 'All Notifications')

@push('styles')
    <link rel="stylesheet"
        href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/user-logs.css') }}?v={{ filemtime(public_path('css/user-logs.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-0">Notifications</h4>
                        <p class="text-muted small mb-0">Your recent alerts and system messages.</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="notification-list">
                    @forelse($notifications as $notification)
                        <div class="notification-row align-items-center">
                            <span class="notification-avatar">
                                <i class="bi bi-bell"></i>
                            </span>
                            <div class="d-flex flex-grow-1 justify-content-between align-items-center">
                                <div class="notification-message">
                                    {{ $notification->notification_text }}
                                </div>
                                <div class="d-flex flex-wrap align-items-center">
                                    <div class="notification-time">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <button class="dropdown-item mark-as-read del_notif fw-semibold" type="button"
                                        data-id="{{ $notification->id }}">
                                        <i class="fa-solid fa-check-double me-2"></i>Mark as Read
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            No notifications found.
                        </div>
                    @endforelse
                </div>
            </div>
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
        // document.querySelectorAll('.delete-notification').forEach(button => {
        //     button.addEventListener('click', function () {
        //         const id = this.getAttribute('data-id');
        //         if (confirm('Are you sure you want to delete this notification?')) {
        //             fetch(`/notifications/${id}`, {
        //                 method: 'DELETE',
        //                 headers: {
        //                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
        //                     'Content-Type': 'application/json',
        //                     'Accept': 'application/json'
        //                 }
        //             })
        //                 .then(response => response.json())
        //                 .then(data => {
        //                     if (data.success) {
        //                         location.reload();
        //                     } else {
        //                         alert(data.message || 'Error deleting notification');
        //                     }
        //                 })
        //                 .catch(error => {
        //                     console.error('Error:', error);
        //                     alert('An error occurred while deleting the notification.');
        //                 });
        //         }
        //     });
        // });

        document.querySelectorAll('.mark-as-read').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                fetch(`/notifications/${id}/read`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Error marking notification as read');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the notification.');
                    });
            });
        });
    </script>

    <script
        src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/user-logs.js') }}?v={{ filemtime(public_path('js/user-logs.js')) }}"></script>
@endpush