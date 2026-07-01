@extends('layouts.app')

@section('page_title', 'Deals')

@push('styles')
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Manage Deals</h4>
                        <p class="text-muted small mb-0">Track sales opportunities and deal progress.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('deals.view')
                            <a href="{{ route('deals.export') }}" class="btn btn-outline-dark-blue">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                        @can('deals.create')
                            <a href="{{ route('deals.create') }}" class="btn btn-dark-blue">
                                <i class="bi bi-plus-lg me-1"></i>Add Deal
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">All Deals</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search deals..." id="dealsSearch" value="{{ request('search') }}">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="dealsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center">Sr.No</th>
                                <th class="text-center">Customer Name</th>
                                <th class="d-none d-md-table-cell text-center">Estimate Name</th>
                                <th class="d-none d-md-table-cell text-center">Created By</th>
                                <th class="d-none d-md-table-cell text-center">Estimate Amount</th>
                                <th class="d-none d-md-table-cell text-center">Status</th>
                                <th class="d-none d-md-table-cell text-center" style="width: 160px;">Action</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div id="paginationContainer" class="px-4 pb-3 pt-0"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <style>
        .crm-filter-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        .crm-filter-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .crm-filter-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }
        .crm-filter-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.crmUserPermissions = {
            deals: {
                view: @json(auth()->user()?->hasMatrixPermission('view_deals')),
                create: @json(auth()->user()?->hasMatrixPermission('create_deals')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_deals')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_deals')),
            }
        };
    </script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/deal.js') }}?v={{ file_exists(public_path('js/deal.js')) ? filemtime(public_path('js/deal.js')) : time() }}"></script>

@endpush
