@extends('layouts.app')

@section('page_title', 'BOM')

@push('styles')
<link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm overflow-hidden rounded-4">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">Manage BOM</h1>
                    <p class="text-muted small mb-0">Manage BOM inventory records in Solar CRM.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('bom.create')
                        <a href="{{ route('bom-products.create') }}" class="btn btn-dark-blue">
                            <i class="bi bi-plus-lg me-1"></i>Add BOM
                        </a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="px-3 px-md-4 py-3 bg-light border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h6 class="fw-bold mb-0">All BOM</h6>
                    <div class="input-group input-group-sm" style="max-width: 300px; width: 100%;">
                        <span class="input-group-text crm-search-icon border-0 bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control crm-search-input border-0" placeholder="Search BOM..." id="bomProductsSearch">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="bomProductsTable">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 80px;">Sr.No</th>
                            <th>Name</th>
                            <th class="d-none d-md-table-cell">Make</th>
                            <th class="d-none d-md-table-cell">Technology</th>
                            <th class="d-none d-md-table-cell">Warranty</th>
                            <th class="d-none d-md-table-cell" style="width: 180px;">Created At</th>
                            <th class="text-center d-none d-md-table-cell" style="width: 140px;">Actions</th>
                            <th class="text-center d-md-none" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="card-footer border-top-0 py-4 px-4" id="bomProductsPagination"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.bomProductsConfig = {
        indexUrl: @json(route('api.bom-products.index')),
        destroyUrlTemplate: @json(route('api.bom-products.destroy', ['bomProduct' => '__ID__'])),
        editUrlTemplate: @json(route('bom-products.edit', ['bomProduct' => '__ID__'])),
        showUrlTemplate: @json(route('bom-products.show', ['bomProduct' => '__ID__']))
    };
    window.crmUserPermissions = {
        ...(window.crmUserPermissions || {}),
        bom: {
            view: @json(auth()->user()?->hasMatrixPermission('view_bom')),
            create: @json(auth()->user()?->hasMatrixPermission('create_bom')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_bom')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_bom')),
        }
    };
</script>
<script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/bom-products.js') }}?v={{ time() }}"></script>
@endpush
