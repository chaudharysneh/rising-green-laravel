@if (!empty($showHeaderQuickEstimate))
    @include('crm.estimates.partials.quick-estimate-scripts', [
        'templates' => $headerQuickEstimateTemplates,
        'subsidies' => $headerQuickEstimateSubsidies,
    ])

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/estimates.js') }}?v={{ filemtime(public_path('js/estimates.js')) }}"></script>
@endif
