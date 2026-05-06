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