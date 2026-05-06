@extends('layouts.app')

@section('page_title', 'Estimates - Edit')

@push('styles')
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/estimates.css') }}">
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
                            <label class="form-label fw-semibold">Select Customer </label>
                            <select name="customer_id" id="select_customer"
                                class="form-select @error('customer_id') is-invalid @enderror" required>
                                <option value="">Select Customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id', $estimate->customer_id) == $customer->id)>
                                        {{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="customer_id-error">Please select a customer</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estimate Name </label>
                            <input type="text" name="estimate_name" id="estimate_name"
                                value="{{ old('estimate_name', $estimate->estimate_name) }}"
                                class="form-control @error('estimate_name') is-invalid @enderror"
                                placeholder="Enter estimate name" required>
                            <div class="invalid-feedback" id="estimate_name-error">Please enter estimate name</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estimate Type </label>
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
                            <label class="form-label fw-semibold">Quantity (kW) </label>
                            <input type="number" min="0" step="0.01" name="quantity" id="quantity"
                                value="{{ old('quantity', $estimate->quantity) }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="Enter kW"
                                required>
                            <div class="invalid-feedback" id="quantity-error">Please enter valid quantity (kW)</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price </label>
                            <input type="number" min="0" step="0.01" name="price" id="price"
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
                                <input type="number" min="0" step="0.01" name="solar_structure_charges"
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
                            <label class="form-label fw-semibold">Quotation Template</label>
                            <select name="template_id" id="template_id"
                                class="form-select @error('template_id') is-invalid @enderror">
                                <option value="">Select Template</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" @selected(old('template_id', $estimate->template_id) == $template->id)>
                                        {{ $template->template_name }}</option>
                                @endforeach
                            </select>
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
                                        <div class="bom-row mb-3 p-3 bg-white border rounded shadow-sm">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-md-5">
                                                    <label class="form-label small fw-semibold">BOM <span
                                                            class="text-danger">*</span></label>
                                                    <select name="service[]" class="form-select product-select" required>
                                                        <option value="">Select BOM</option>
                                                        @foreach ($bomProducts as $bom)
                                                            <option value="{{ $bom->id }}"
                                                                data-name="{{ $bom->product_name }}"
                                                                data-desc="{{ $bom->description ?? '' }}"
                                                                data-categories="{{ json_encode($bom->categories->pluck('name')->toArray()) }}"
                                                                data-price="{{ $bom->price ?? 0 }}"
                                                                data-meter="{{ $bom->meter ?? '' }}"
                                                                data-nos="{{ $bom->nos ?? '' }}"
                                                                @selected((string) ($selectedProduct['product_id'] ?? '') === (string) $bom->id)>
                                                                {{ $bom->product_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-semibold">Make</label>
                                                    <select name="product_make[]" class="form-select product-make"
                                                        data-selected="{{ $selectedProduct['category_name'] ?? '' }}"
                                                        @disabled(empty($selectedProduct['product_id']))>
                                                        <option value="">Select Make</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold">Qty</label>
                                                    <input type="number" min="0" step="1" name="product_qty[]"
                                                        value="{{ $selectedProduct['quantity'] ?? 0 }}"
                                                        class="form-control" placeholder="0">
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
                                    <div id="gst_fields_box" style="display: none;">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="small">GST %:</span>
                                            <input type="number" id="gst_percent"
                                                value="{{ old('gst', $estimate->gst) }}" class="input-small">
                                        </div>
                                    </div>
                                </div>

                                <div class="totals-row">
                                    <span class="small">Discount:</span>
                                    <input type="number" name="discount" id="discount"
                                        value="{{ old('discount', $estimate->discount) }}" class="input-small">
                                </div>

                                <div class="totals-row">
                                    <span class="small">Subsidy:</span>
                                    <input type="number" name="subsidy_amount" id="subsidy_amount"
                                        value="{{ old('subsidy_amount', $estimate->subsidy_amount) }}"
                                        class="input-small">
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
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/estimates.js') }}"></script>
@endpush
