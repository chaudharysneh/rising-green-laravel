@extends('layouts.app')

@section('page_title', 'Dashboard')

@section('content')
<div class="container-fluid p-0 dashboard-page">
    @php
        $dashboardUser = auth()->user();
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
        © {{ date('Y') }} Copyright - Fablead Developers Technolab
    </footer>

</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ ((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ ((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
@endpush
