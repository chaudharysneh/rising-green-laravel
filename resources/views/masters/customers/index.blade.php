@extends('layouts.masters')

@section('page_title', 'Masters - Customers')

@section('masters_content')

    <div class="card-header border-bottom-0 py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-0">Manage Customers</h4>
                <p class="text-muted small mb-0">View and manage your customer database and communication history.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('customers.create')
                    <button type="button" class="btn btn-outline-dark-blue" onclick="showImportDialog()">
                        <i class="fa-solid fa-upload me-1"></i>Import CSV
                    </button>
                @endcan
                @can('customers.view')
                    <a href="{{ route('masters.customers.export') }}" class="btn btn-outline-dark-blue">
                        <i class="fa-solid fa-download me-1"></i>Export
                    </a>
                @endcan
                @can('customers.create')
                    <a href="{{ route('masters.customers.create') }}" class="btn btn-dark-blue">
                        <i class="fa-solid fa-plus me-1"></i>Add Customer
                    </a>
                @endcan
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h6 class="fw-bold mb-0">Active Customers</h6>
            <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                <span class="input-group-text crm-search-icon border-0"><i class="fa-solid fa-search"></i></span>
                <input type="text" id="customerSearch" class="form-control crm-search-input border-0"
                    placeholder="Search customers..." name="search" value="{{ request('search') }}">
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="customerTable" class="table table-hover align-middle mb-0 responsive-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 80px;">Sr.No</th>
                        <th>Customer Name</th>
                        <th class="d-none d-md-table-cell">Email</th>
                        <th class="d-none d-md-table-cell">Phone</th>
                        <th class="d-none d-md-table-cell">Created At</th>
                        <th class="text-end pe-4 d-none d-md-table-cell" style="width: 140px;">Actions</th>
                        <th class="text-center d-md-none" style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody id="customersTable"></tbody>
            </table>
        </div>

        <!-- Pagination Container -->
        <div id="customerPaginationContainer" class="card-footer border-top-0 py-4 px-4"></div>
    </div>
    <form id="customersImportForm" class="d-none" enctype="multipart/form-data">
        @csrf
        <input type="file" name="import_file" id="customersImportFile" accept=".csv,text/csv">
    </form>
@endsection

@push('styles')
    <style>
        .customer-action-disabled,
        .customer-action-disabled:hover,
        .customer-action-disabled:focus {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            opacity: 1;
            box-shadow: none;
        }

        [data-theme="dark"] .customer-action-disabled,
        [data-theme="dark"] .customer-action-disabled:hover,
        [data-theme="dark"] .customer-action-disabled:focus {
            background-color: #1e293b;
            border-color: rgba(255, 255, 255, .08);
            color: #64748b;
        }

        /* Pagination Styles */
        .crm-pagination-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            padding-top: 0.5rem;
        }

        @media (min-width: 768px) {
            .crm-pagination-container {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        .crm-pagination .page-item {
            margin: 0 3px;
        }

        .crm-pagination .page-link {
            border-radius: 10px !important;
            padding: 0.6rem 1.1rem;
            color: #475569;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .crm-pagination .page-link:hover {
            background-color: #f8fafc;
            color: #0f172a;
            border-color: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .crm-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            border-color: #0f172a !important;
            color: #fff !important;
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.2);
        }

        .crm-pagination .page-item.disabled .page-link {
            background-color: #f8fafc;
            color: #cbd5e1;
            border-color: #f1f5f9;
            box-shadow: none;
            cursor: not-allowed;
        }

        [data-theme="dark"] .crm-pagination .page-link {
            background-color: #1e293b;
            border-color: rgba(255,255,255,0.06);
            color: #94a3b8;
        }

        [data-theme="dark"] .crm-pagination .page-link:hover {
            background-color: #243146;
            color: #3b82f6;
            border-color: #3b82f6;
        }

        [data-theme="dark"] .crm-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            border-color: #3b82f6 !important;
            color: #0f172a !important;
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.crmUserPermissions = {
            ...(window.crmUserPermissions || {}),
            customers: {
                view: @json(auth()->user()?->hasMatrixPermission('view_customers')),
                create: @json(auth()->user()?->hasMatrixPermission('create_customers')),
                edit: @json(auth()->user()?->hasMatrixPermission('edit_customers')),
                delete: @json(auth()->user()?->hasMatrixPermission('delete_customers')),
            }
        };
    </script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/customer.js') }}"></script>
@endpush