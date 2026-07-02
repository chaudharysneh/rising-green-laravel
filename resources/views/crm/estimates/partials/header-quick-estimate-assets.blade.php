@if (!empty($showHeaderQuickEstimate))
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/estimates.css') }}?v={{ filemtime(public_path('css/estimates.css')) }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        #quickEstimateModal .modal-dialog { z-index: 1055; }
        #addCustomerModal, #quickAddBomModal { z-index: 1065 !important; }
        body.modal-open .modal-backdrop.show ~ .modal-backdrop.show { z-index: 1060; }
        #quickEstimateModal .quick-bom-row .quick-bom-select-col { min-width: 0; }
        #quickEstimateModal .d-flex:has(.is-invalid) ~ .invalid-feedback { display: block !important; }
    </style>
@endif
