@extends('layouts.app')

@section('page_title', 'Estimates - Edit')

@push('styles')
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/estimates.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
    @php
        $selectedProducts = old('products');
        if (!is_array($selectedProducts)) {
            $selectedProducts = is_array($estimate->product_name) ? $estimate->product_name : json_decode($estimate->product_name ?? '[]', true);
        }
        if (!is_array($selectedProducts) || empty($selectedProducts)) {
            $selectedProducts = [['product_id' => '', 'category_name' => '', 'quantity' => 0]];
        }
        $estimateTaxRows = collect($gstTaxes ?? [])->flatMap(function ($tax) {
            $name = (string) $tax->name;
            $upperName = strtoupper($name);
            $rate = (float) $tax->rate;

            if (str_contains($upperName, 'CGST') && str_contains($upperName, 'SGST')) {
                return [
                    ['label' => 'CGST', 'rate' => $rate / 2],
                    ['label' => 'SGST', 'rate' => $rate / 2],
                ];
            }

            if (str_contains($upperName, 'IGST')) {
                return [['label' => 'IGST', 'rate' => $rate]];
            }

            return [['label' => $name, 'rate' => $rate]];
        })->values();
    @endphp

    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Estimate</h1>
                        <p class="text-muted small mb-0">Estimate No: {{ $estimate->estimate_no }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('estimates.view')
                            <a href="{{ route('estimates.show', $estimate) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('estimates.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" enctype="multipart/form-data" class="needs-validation ajax-estimate-form" novalidate
                    id="estimateEditForm" action="/api/estimates/{{ $estimate->estimate_id }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold mb-1">Select Customer <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2">
                                <select name="customer_id" id="select_customer"
                                    class="form-select @error('customer_id') is-invalid @enderror" required>
                                    <option value="">Select Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id', $estimate->customer_id) == $customer->id)>
                                            {{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="customer_id-error">Please select a customer</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estimate Name <span class="text-danger">*</span></label>
                            <input type="text" name="estimate_name" id="estimate_name"
                                value="{{ old('estimate_name', $estimate->estimate_name) }}"
                                class="form-control @error('estimate_name') is-invalid @enderror"
                                placeholder="Enter estimate name" required>
                            <div class="invalid-feedback" id="estimate_name-error">Please enter estimate name</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estimate Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror"
                                required>
                                <option value="">Select Type</option>
                                <option value="residential" @selected(old('type', $estimate->type) == 'residential')>Residential</option>
                                <option value="commercial" @selected(old('type', $estimate->type) == 'commercial')>Commercial</option>
                                <option value="industrial" @selected(old('type', $estimate->type) == 'industrial')>Industrial</option>
                                <option value="common meter" @selected(old('type', $estimate->type) == 'common meter')>Common Meter</option>
                            </select>
                            <div class="invalid-feedback" id="type-error">Please select estimate type</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity (kW) <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" name="quantity" id="quantity"
                                value="{{ old('quantity', $estimate->quantity) }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="Enter kW"
                                required>
                            <div class="invalid-feedback" id="quantity-error">Please enter valid quantity (kW)</div>
            </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" name="price" id="price"
                                value="{{ old('price', $estimate->price) }}"
                                class="form-control @error('price') is-invalid @enderror" placeholder="Enter price"
                                required>
                            <div class="invalid-feedback" id="price-error">Please enter valid price</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Solar Meter Charges <span
                                    class="text-danger">*</span></label>
                            <select name="solar_meter_charges" id="solar_meter_select"
                                class="form-select @error('solar_meter_charges') is-invalid @enderror" required>
                                <option value="">Select</option>
                                <option value="as_per_actual" @selected(old('solar_meter_charges', $estimate->solar_meter_charges) == 'as_per_actual')>As per Actual</option>
                                <option value="as_per_client_scope" @selected(old('solar_meter_charges', $estimate->solar_meter_charges) == 'as_per_client_scope')>As per client scope</option>
                                <option value="included" @selected(old('solar_meter_charges', $estimate->solar_meter_charges) == 'included')>Included</option>
                            </select>
                            <div class="invalid-feedback" id="solar_meter_charges-error">Please select solar meter charges
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Charges</label>
                            <div class="form-control d-flex align-items-center bg-light"
                                style="min-height: 38px; padding: 0.375rem 0.75rem;">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="solar_structure_charges_check"
                                        value="1" @checked((float) old('solar_structure_charges', $estimate->solar_structure_charges) > 0)>
                                    <label class="form-check-label small" for="solar_structure_charges_check">
                                        Solar Structure Charges
                                    </label>
                                </div>
                            </div>
                            <div id="structure-charges-input" style="display: none; margin-top: 12px;">
                                <label class="form-label fw-semibold small">Enter Structure Charges</label>
                                <input type="number" min="0" step="1" name="solar_structure_charges"
                                    id="solar_structure_charges"
                                    value="{{ old('solar_structure_charges', $estimate->solar_structure_charges) }}"
                                    class="form-control @error('solar_structure_charges') is-invalid @enderror"
                                    placeholder="0.00">
                                <div class="invalid-feedback" id="solar_structure_charges-error">
                                    @error('solar_structure_charges')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quotation Template <span class="text-danger">*</span></label>
                            <select name="template_id" id="template_id"
                                class="form-select @error('template_id') is-invalid @enderror" required>
                                <option value="">Select Template</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" @selected(old('template_id', $estimate->template_id) == $template->id)>
                                        {{ $template->template_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="template_id-error">Please select quotation template</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Created Date</label>
                            <input type="date" name="estimate_date" id="estimate_date"
                                value="{{ old('estimate_date', optional($estimate->estimate_date)->format('Y-m-d')) }}"
                                class="form-control @error('estimate_date') is-invalid @enderror">
                            <div class="invalid-feedback" id="estimate_date-error">
                                @error('estimate_date')
                                    {{ $message }}
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">BOM (Bill Of Material)</label>
                            <div class="bom-section bg-light rounded-3 p-3 border">
                                <div id="bomContainer">
                                    @foreach ($selectedProducts as $index => $selectedProduct)
                                        @php
                                            $selectedBom = $bomProducts->firstWhere('id', $selectedProduct['product_id'] ?? null);
                                            $selectedUnitPrice = $selectedProduct['price'] ?? ($selectedBom->price ?? 0);
                                            $selectedQuantity = $selectedProduct['quantity'] ?? 0;
                                        @endphp
                                        <div class="bom-row mb-3 p-3 bg-white border rounded shadow-sm">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">BOM <span
                                                            class="text-danger">*</span></label>
                                                    <div class="d-flex align-items-start gap-2">
                                                        <select name="service[]" class="form-select product-select" required>
                                                            <option value="">Select BOM</option>
                                                            @foreach ($bomProducts as $bom)
                                                                <option value="{{ $bom->id }}"
                                                                    data-name="{{ $bom->product_name }}"
                                                                    data-desc="{{ $bom->description ?? '' }}"
                                                                    data-categories='{{ json_encode($bom->categories->pluck('name')->toArray()) }}'
                                                                    data-price="{{ $bom->price ?? 0 }}"
                                                                    data-meter="{{ $bom->meter ?? '' }}"
                                                                    data-nos="{{ $bom->nos ?? '' }}"
                                                                    @selected((string) ($selectedProduct['product_id'] ?? '') === (string) $bom->id)>
                                                                    {{ $bom->product_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" class="btn btn-dark-blue flex-shrink-0 quick-add-bom-row" data-bs-toggle="modal" data-bs-target="#quickAddBomModal" title="Add New BOM">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Make <span class="text-danger">*</span></label>
                                                    <select name="product_make[]" class="form-select product-make"
                                                        data-selected="{{ $selectedProduct['category_name'] ?? '' }}"
                                                        @disabled(empty($selectedProduct['product_id']))>
                                                        <option value="">Select Make</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold product-qty-label">Qty <span class="text-danger">*</span></label>
                                                    <input type="number" min="0" step="1" name="product_qty[]"
                                                        value="{{ (float) $selectedQuantity > 0 ? $selectedQuantity : '' }}"
                                                        class="form-control" placeholder="Add Quantity">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold">Unit Price <span class="text-danger">*</span></label>
                                                    <input type="number" min="0" step="1" name="product_price[]"
                                                        value="{{ round((float) $selectedUnitPrice) }}"
                                                        class="form-control product-price" placeholder="0">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small fw-semibold">Amount</label>
                                                    <input type="number" min="0" step="1"
                                                        value="{{ round((float) $selectedQuantity * (float) $selectedUnitPrice) }}"
                                                        class="form-control product-total" placeholder="0" readonly>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-outline-danger w-100 delete-bom-row"
                                                        style="display: {{ $index === 0 ? 'none' : 'block' }};">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-outline-dark-blue btn-sm" id="add_more_bom">
                                    <i class="bi bi-plus-circle me-2"></i>Add More BOM
                                </button>
                            </div>
                            <div class="invalid-feedback" id="products-error" style="display:none;">Please select at least
                                one BOM</div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Design File</label>
                                <input type="file" name="attach_file" id="attach_file"
                                    class="form-control @error('attach_file') is-invalid @enderror"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx">
                                <div class="invalid-feedback" id="attach_file-error">
                                    @error('attach_file')
                                        {{ $message }}
                                    @enderror
                                </div>

                                @if ($estimate->attach_file)
                                    <div class="mt-2 small">
                                        <a href="{{ Storage::url($estimate->attach_file) }}" target="_blank"
                                            class="text-primary fw-medium"><i class="bi bi-file-earmark-check me-1"></i>View
                                            existing file</a>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="form-label fw-semibold">Comment</label>
                                <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" rows="5"
                                    placeholder="Add any comments...">{{ old('comment', $estimate->comment) }}</textarea>
                                <div class="invalid-feedback" id="comment-error">
                                    @error('comment')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="totals-card rounded-3 h-100 d-flex flex-column justify-content-center">
                                <div class="totals-row">
                                    <span class="fw-semibold">Subtotal:</span>
                                    <span id="subtotal_display" class="fw-bold text-dark">0.00</span>
                                </div>

                                <div class="totals-row align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="switch mb-0">
                                            <input type="checkbox" id="apply_gst" @checked((float) old('gst', $estimate->gst) > 0)>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="small fw-semibold">Apply GST</span>
                                    </div>
                                </div>

                                <div id="gst_fields_box" style="display: none;">
                                    @forelse ($estimateTaxRows as $index => $taxRow)
                                        <div class="totals-row gst-tax-row" data-tax-rate="{{ $taxRow['rate'] }}">
                                            <span class="small">{{ $taxRow['label'] }} ({{ rtrim(rtrim(number_format($taxRow['rate'], 2), '0'), '.') }}%):</span>
                                            <span class="small gst-tax-amount" id="tax_display_{{ $index }}">0.00</span>
                                        </div>
                                    @empty
                                        <div class="totals-row">
                                            <span class="small text-muted">No active taxes configured.</span>
                                            <span class="small">0.00</span>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="totals-row">
                                    <span class="small">Discount:</span>
                                    <input type="number" name="discount" id="discount"
                                        value="{{ old('discount', $estimate->discount) }}" step="1" class="input-small">
                                </div>

                                <div class="totals-row">
                                    <span class="small">Subsidy:</span>
                                    <input type="number" name="subsidy_amount" id="subsidy_amount"
                                        value="{{ old('subsidy_amount', $estimate->subsidy_amount) }}"
                                        step="1" class="input-small">
                                </div>

                                <hr class="my-2">

                                <div class="totals-row total-row mb-0">
                                    <span class="h5 mb-0 fw-bold">Total Payable:</span>
                                    <span id="final_total_display" class="h5 mb-0 fw-bold">0.00</span>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="total" id="subtotal"
                            value="{{ old('total', $estimate->total) }}">
                        <input type="hidden" name="final_total" id="final_total"
                            value="{{ old('amount', $estimate->amount) }}">
                        <input type="hidden" name="gst" id="gst" value="{{ old('gst', $estimate->gst) }}">
                        <input type="hidden" name="status" id="status"
                            value="{{ old('status', $estimate->status) }}">
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('estimates.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add BOM Modal -->
    <div class="modal fade" id="quickAddBomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Add New BOM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="quickAddBomForm" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">BOM Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_bom_name" required>
                            <div class="invalid-feedback" id="quick_bom_name-error">Please enter BOM name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Make <span class="text-danger">*</span></label>
                            <select class="form-select quick-bom-make-select" id="quick_bom_category_id" required>
                                <option value="">Select Make</option>
                                @foreach ($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" data-name="{{ $category->name }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="quick_bom_category_id-error">Please select make</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Unit Price <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="quick_bom_price" required>
                            <div class="invalid-feedback" id="quick_bom_price-error">Please enter unit price</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="saveQuickBomBtn">Save BOM</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="addCustomerQuickForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_customer_name" required>
                            <div class="invalid-feedback">Please enter customer name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_customer_number" required>
                            <div class="invalid-feedback">Please enter mobile number</div>
                        </div>
                        <div class="mb-3">
                            <a href="#" class="small text-decoration-none" onclick="$('#quick_address_container').toggleClass('d-none'); return false;">+ Add Address (Optional)</a>
                        </div>
                        <div class="mb-3 d-none" id="quick_address_container">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control" id="quick_customer_address" rows="2" placeholder="Address"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="saveQuickCustomerBtn">Save Customer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        @php
            $templateComments = ($templates ?? collect())->mapWithKeys(function ($template) {
                $formData = is_array($template->form_data) ? $template->form_data : (json_decode($template->form_data ?? '[]', true) ?: []);
                $comment = is_array($formData['estimate_comment'] ?? null) ? $formData['estimate_comment'] : [];
                return [
                    (string) $template->id => [
                        'active' => (int) ($comment['active'] ?? 0),
                        'content' => (string) ($comment['content'] ?? ''),
                    ],
                ];
            });
        @endphp
        window.estimateTemplateComments = @json($templateComments);
        window.subsidiesData = @json($subsidies ?? []);
        window.estimateTaxes = @json($estimateTaxRows);
        window.estimateBomQuickAddConfig = {
            storeUrl: @json(route('api.bom-products.store')),
            makeStoreUrl: @json(route('api.make.store'))
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/estimates.js') }}?v={{ filemtime(public_path('js/estimates.js')) }}"></script>
    <script>
        $(document).ready(function() {
            function initSelect2(context = document) {
                $(context).find('#select_customer, #template_id, .product-select, .product-make').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }
            initSelect2();

            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && $(node).hasClass('bom-row')) {
                            $(node).find('.select2-container').remove();
                            $(node).find('.product-select, .product-make').removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');
                            $(node).find('option').removeAttr('data-select2-id');
                            initSelect2(node);
                        }
                    });
                });
            });
            const bomContainer = document.getElementById('bomContainer');
            if (bomContainer) observer.observe(bomContainer, { childList: true });

            $('#saveQuickCustomerBtn').click(function() {
                let name = $('#quick_customer_name').val().trim();
                let number = $('#quick_customer_number').val().trim();
                let address = $('#quick_customer_address').val().trim();
                
                $('#quick_customer_name, #quick_customer_number').removeClass('is-invalid');
                
                if (!name || !number) {
                    if(!name) $('#quick_customer_name').addClass('is-invalid');
                    if(!number) $('#quick_customer_number').addClass('is-invalid');
                    return;
                }
                
                if (!address) {
                    address = 'Address';
                }
                
                let btn = $(this);
                let originalText = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.ajax({
                    url: '/api/customers',
                    type: 'POST',
                    data: {
                        name: name,
                        phone: number,
                        address: address,
                        status: 'active',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success && res.data) {
                            let newOption = new Option(res.data.name, res.data.id, true, true);
                            $('#select_customer').append(newOption).trigger('change');
                            $('#addCustomerModal').modal('hide');
                            $('#addCustomerQuickForm')[0].reset();
                            if (window.showAlert) window.showAlert('success', 'Customer added successfully');
                        }
                    },
                    error: function(xhr) {
                        if (window.showAlert) window.showAlert('error', xhr.responseJSON?.message || 'Failed to add customer');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            $(document).on('select2:select', '.product-select', function (e) {
                $(this).trigger('change');
            });
        });
    </script>
@endpush
