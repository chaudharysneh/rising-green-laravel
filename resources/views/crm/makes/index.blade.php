@extends('layouts.app')

@section('page_title', 'Make')

@push('styles')
<link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
<link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/product-categories.css') }}?v={{ filemtime(public_path('css/product-categories.css')) }}">
@endpush

@section('content')
    @include('crm.inventory-masters.index', [
        'moduleKey' => 'make',
        'pageTitle' => 'Manage Make',
        'resourceTitle' => 'Make',
        'resourcePlural' => 'Makes',
        'fieldName' => 'name',
        'fieldLabel' => 'Name',
        'hasDescription' => false,
        'hasImage' => true,
        'permissions' => [
            'view' => auth()->user()?->hasMatrixPermission('view_make'),
            'create' => auth()->user()?->hasMatrixPermission('create_make'),
            'edit' => auth()->user()?->hasMatrixPermission('edit_make'),
            'delete' => auth()->user()?->hasMatrixPermission('delete_make'),
        ]
    ])
@endsection

@push('scripts')
<script>
    window.inventoryMasterConfig = {
        moduleKey: 'make',
        resourceTitle: 'Make',
        resourcePlural: 'Makes',
        fieldName: 'name',
        fieldLabel: 'Name',
        hasDescription: false,
        hasImage: true,
        indexUrl: @json(route('api.make.index')),
        storeUrl: @json(route('api.make.store')),
        showUrlTemplate: @json(route('api.make.show', ['make' => '__ID__'])),
        updateUrlTemplate: @json(route('api.make.update', ['make' => '__ID__'])),
        destroyUrlTemplate: @json(route('api.make.destroy', ['make' => '__ID__'])),
        permissions: {
            view: @json(auth()->user()?->hasMatrixPermission('view_make')),
            create: @json(auth()->user()?->hasMatrixPermission('create_make')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_make')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_make')),
        }
    };
</script>
<script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/inventory-master.js') }}?v={{ time() }}"></script>
@endpush
