@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
<div class="container-fluid p-0 dashboard-page">
    @php
        $dashboardUser = auth()->user();
        $planName = $currentSubscriptionPlan?->name ?? 'No Plan Assigned';
        $isPremiumPlan = str_contains(strtolower($planName), 'premium');
        $planStaffLimit = (int) ($currentSubscriptionPlan?->staff_limit ?? 0);
        $planRenewalDate = optional($currentSubscriptionAssignment?->updated_at ?? $currentSubscriptionAssignment?->created_at)->format('d M Y') ?? '-';
        $canViewCustomers = $canViewCustomers ?? \App\Models\Customer::query()->visibleToUser($dashboardUser)->exists();
        $canViewFollowUps = $dashboardUser?->hasMatrixPermission('view_followups') ?? false;
        $canViewLeads = $dashboardUser?->hasMatrixPermission('view_leads') ?? false;
        $canViewDeals = $dashboardUser?->hasMatrixPermission('view_deals') ?? false;
        $canViewTasks = $dashboardUser?->hasMatrixPermission('view_tasks') ?? false;
        $canViewBookings = $dashboardUser?->hasMatrixPermission('view_bookings') ?? false;
        $hasDashboardAccess = $dashboardUser?->isAdmin()
            || $canViewCustomers
            || $canViewFollowUps
            || $canViewLeads
            || $canViewDeals
            || $canViewTasks
            || $canViewBookings;
    @endphp

    <div class="row g-3 mb-2" id="dashboardStats">

        <div class="col-6 col-sm-6 col-md-3 col-lg-3">
            <a href="{{ route('masters.customers.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Customers</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricCustomers">{{ $stats['customers'] ?? 0 }}</h3>
                            <span class="metric-icon icon-customers"><i class="bi bi-people-fill"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-lg-3">
            <a href="{{ route('followups.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Follow Up</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricFollowUps">{{ $stats['follow_ups'] ?? 0 }}</h3>
                            <span class="metric-icon icon-followups"><i class="bi bi-chat-dots-fill"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-lg-3">
            <a href="{{ route('leads.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Leads</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricLeads">{{ $stats['leads'] ?? 0 }}</h3>
                            <span class="metric-icon icon-leads"><i class="bi bi-megaphone-fill"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-sm-6 col-md-3 col-lg-3">
            <a href="{{ route('deals.index') }}" class="text-decoration-none">
                <div class="metric-card card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="metric-label mb-1">Deals</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="metric-value mb-0" id="metricDeals">{{ $stats['deals'] ?? 0 }}</h3>
                            <span class="metric-icon icon-deals"><i class="bi bi-award-fill"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <div class="lead-board-wrapper p-0" id="leadBoardWrapper">
        <button type="button" class="lead-board-arrow lead-board-arrow--left" id="leadBoardLeft" title="Scroll Left">
            <i class="fa-solid fa-angle-left fs-5"></i>
        </button>
        <div class="status-board mb-2 px-0" id="leadBoardContainer">
            <div class="card border-0 shadow-sm w-100">
                <div class="card-body text-muted small">Loading lead board...</div>
            </div>
        </div>
        <button type="button" class="lead-board-arrow lead-board-arrow--right" id="leadBoardRight" title="Scroll Right">
            <i class="fa-solid fa-angle-right fs-5"></i>
        </button>
    </div>


    <div class="row g-3">

        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0 fw-bold">All Tasks</h5>
                    <a href="{{ route('tasks.index') }}" class="text-dark badge bg-light px-3 py-2 fw-semibold small"
                        style="color: #0c0c0c !important;">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="dashboardTasksTable">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Task Name</th>
                                    <th style="width: 25%;" class="d-none d-md-table-cell">Assigned To</th>
                                    <th style="width: 15%;" class="text-center">Priority</th>
                                    <th style="width: 15%;" class="text-center d-none d-md-table-cell">Status</th>
                                    <th style="width: 10%;" class="text-center d-md-none">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Loading tasks...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3">
                    <h5 class="mb-0 fw-bold">Module Trends</h5>
                </div>
                <div class="card-body pt-1">
                    <div class="chart-wrap">
                        <canvas id="dashboardTrendChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-1">

        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold text-white">Customer Report</h5>
                    <select id="customerReportYear" class="form-select form-select-sm dashboard-year-select">
                        @php($thisYear = now()->year)
                        <option value="{{ $thisYear }}">{{ $thisYear }}</option>
                        <option value="{{ $thisYear - 1 }}">{{ $thisYear - 1 }}</option>
                        <option value="{{ $thisYear - 2 }}">{{ $thisYear - 2 }}</option>
                    </select>
                </div>
                <div class="card-body pt-2">
                    <div class="chart-wrap">
                        <canvas id="customerReportChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header dashboard-widget-head py-3 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold text-white">All Deals</h5>
                    <a href="{{ route('deals.index') }}" class="text-dark badge bg-light px-3 py-2 fw-semibold small"
                        style="color: #0c0c0c !important;">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="dashboardDealsTable">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Deals Name</th>
                                    <th style="width: 25%;" class="d-none d-md-table-cell">Deal Value</th>
                                    <th style="width: 20%;" class="text-center">Probability(%)</th>
                                    <th style="width: 15%;" class="text-center d-none d-md-table-cell">Status</th>
                                    <th style="width: 10%;" class="text-center d-md-none">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Loading deals...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <footer class="dashboard-footer text-center py-2 mt-4">
        © {{ date('Y') }} Copyright - Rising Green Energy
    </footer>

    <div class="modal fade dashboard-plan-modal" id="dashboardPlanModal" tabindex="-1" aria-labelledby="dashboardPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header dashboard-plan-modal__header {{ $isPremiumPlan ? 'plan-premium' : 'plan-basic' }} border-0">
                    <h5 class="modal-title fw-bold mb-0" id="dashboardPlanModalLabel">
                        <i class="fa-solid {{ $isPremiumPlan ? 'fa-gem' : 'fa-crown' }} me-2"></i>
                        <span id="dashboardPlanModalTitle">Your Subscription Plan</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="text-center mb-4">
                        <div class="dashboard-plan-modal__pill {{ $isPremiumPlan ? 'dashboard-plan-modal__pill--premium' : '' }}" id="dashboardPlanBadge">{{ $planName }}</div>
                    </div>

                    <div class="dashboard-plan-modal__details">
                        <div class="dashboard-plan-modal__row">
                            <span class="dashboard-plan-modal__icon"><i class="fa-solid fa-users"></i></span>
                            <span class="fw-semibold">Staff Limit:</span>
                            <span id="dashboardPlanStaffLimit" class="text-muted">{{ $currentStaffCount ?? 0 }} / {{ $planStaffLimit }} users</span>
                        </div>
                        <div class="dashboard-plan-modal__row">
                            <span class="dashboard-plan-modal__icon"><i class="fa-solid fa-calendar-days"></i></span>
                            <span class="fw-semibold">Renewal Date:</span>
                            <span id="dashboardPlanRenewalDate" class="text-muted">{{ $planRenewalDate }}</span>
                        </div>
                        <div class="dashboard-plan-modal__row">
                            <span class="dashboard-plan-modal__icon dashboard-plan-modal__icon--status"><i class="fa-solid fa-circle-check"></i></span>
                            <span class="fw-semibold">Status:</span>
                            <span id="dashboardPlanStatus" class="text-muted">{{ $currentSubscriptionPlan ? 'Active' : 'Not Assigned' }}</span>
                        </div>
                    </div>

                    <p class="dashboard-plan-modal__message text-center mt-4 mb-3" id="dashboardPlanMessage">
                        @if($currentSubscriptionPlan)
                            Staff accounts are counted under your admin ID. When the plan limit is reached, new staff creation will be blocked automatically.
                        @else
                            No subscription plan is assigned to this admin account yet.
                        @endif
                    </p>

                    <div class="text-center">
                        <button type="button" class="btn dashboard-plan-modal__cta" id="dashboardPlanContactBtn">Contact Us</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ ((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ ((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
@endpush
