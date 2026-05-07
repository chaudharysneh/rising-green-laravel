@extends('layouts.app')

@section('page_title', 'Invoices')

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Invoices</h4>
                        <p class="text-muted small mb-0">Manage customer billing and payment records.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('invoices.view')
                            <a href="{{ route('invoices.export') }}" class="btn btn-outline-dark-blue">
                                <i class="fa-solid fa-download me-1"></i>Export
                            </a>
                        @endcan
                        @can('invoices.create')
                            <a href="{{ route('invoices.create') }}" class="btn btn-dark-blue">
                                <i class="bi bi-plus-lg me-1"></i>Create Invoice
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">Active Invoices</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search invoices..." id="invoiceSearch" value="{{ request('search') }}">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if(!auth()->user()->isAdmin())
                <div class="px-4 pt-3">
                    <ul class="nav nav-tabs crm-filter-tabs" id="invoiceFilterTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="created-by-me-tab" data-bs-toggle="tab" data-filter="created_by_me" type="button" role="tab">
                                Created By Me
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assigned-to-me-tab" data-bs-toggle="tab" data-filter="assigned_to_me" type="button" role="tab">
                                Assigned To Me
                            </button>
                        </li>
                    </ul>
                </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="invoicesTableMain">
                        <thead>
                            <tr>
                                <th class="text-center">Sr.No</th>
                                <th class="text-start">Customer Name</th>
                                <th class="text-center d-none d-md-table-cell">Invoice No</th>
                                <th class="text-center d-none d-md-table-cell">Invoice Date</th>
                                <th class="text-center d-none d-md-table-cell">Due Date</th>
                                <th class="text-center d-none d-md-table-cell">Status</th>
                                <th class="text-center d-none d-md-table-cell" style="width: 150px;">Actions</th>
                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="invoicesTable"></tbody>
                    </table>
                </div>

                <div id="invoicePaginationContainer" class="px-4 pb-3 pt-0"></div>
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
            invoices: {
                view: @json(auth()->user()?->hasMatrixPermission('view_invoices')),
                create: @json(auth()->user()?->hasMatrixPermission('create_invoices')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_invoices')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_invoices')),
            }
        };
    </script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/invoice.js') }}?v={{ filemtime(public_path('js/invoice.js')) }}"></script>
@endpush