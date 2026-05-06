@extends('layouts.app')

@section('page_title', 'Create Invoice')

@section('content')
<div class="container-fluid p-0">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden invoice-form-card">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold text-dark-blue">Create New Invoice</h1>
                    <p class="text-muted small mb-0">Generate a professional invoice for your customer.</p>
                </div>
                <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue back-btn">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
        @php
            $selectedProducts = old('products');
            if (!is_array($selectedProducts) || empty($selectedProducts)) {
                $selectedProducts = [['product_id' => '', 'category_name' => '', 'quantity' => 1]];
            }
        @endphp
        <div class="card-body p-3 p-md-4">
            <form action="/api/invoices" method="POST" class="ajax-invoice-form needs-validation" novalidate enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Select Customer <span class="text-danger">*</span>
                        </label>
                        <select name="customer_id" id="select_customer" class="form-select @error('customer_id') is-invalid @enderror" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Invoice Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="invoice_name" id="invoice_name" value="{{ old('invoice_name') }}" class="form-control @error('invoice_name') is-invalid @enderror" placeholder="Enter invoice name" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Currency <span class="text-danger">*</span>
                        </label>
                        <select name="currency_id" id="currency_id" class="form-select @error('currency_id') is-invalid @enderror" required>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }} ({{ $currency->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Estimate Type <span class="text-danger">*</span>
                        </label>
                        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="industrial">Industrial</option>
                            <option value="common meter">Common Meter</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Quantity (kW) <span class="text-danger">*</span>
                        </label>
                        <input type="number" min="0" step="1" name="quantity" id="quantity" value="{{ old('quantity') }}" class="form-control @error('quantity') is-invalid @enderror" placeholder="Enter kW" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Base Price <span class="text-danger">*</span>
                        </label>
                        <input type="number" min="0" step="0.01" name="price" id="price" value="{{ old('price') }}" class="form-control @error('price') is-invalid @enderror" placeholder="Enter price" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Additional Charges
                        </label>
                        <div class="form-control d-flex align-items-center bg-light border-0" style="min-height: 42px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="solar_structure_charges_check" value="1">
                                <label class="form-check-label fw-medium" for="solar_structure_charges_check">
                                    Solar Structure Charges
                                </label>
                            </div>
                        </div>
                        <div id="structure-charges-input" style="display: none; margin-top: 12px;">
                            <label class="form-label fw-semibold small text-uppercase">Structure Charges Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-solar-panel text-muted"></i></span>
                                <input type="number" min="0" step="0.01" name="solar_structure_charges" id="solar_structure_charges" class="form-control border-start-0" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Solar Meter Charges <span class="text-danger">*</span>
                        </label>
                        <select name="solar_meter_charges" id="solar_meter_select" class="form-select @error('solar_meter_charges') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="as_per_actual">As per Actual</option>
                            <option value="as_per_client_scope">As per client scope</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Quotation Template
                        </label>
                        <select name="template_id" id="template_id" class="form-select @error('template_id') is-invalid @enderror">
                            <option value="">Select Template</option>
                            @if(isset($templates))
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->template_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Invoice Date
                        </label>
                        <input type="date" name="invoice_date" id="invoice_date" value="{{ date('Y-m-d') }}" class="form-control @error('invoice_date') is-invalid @enderror">
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
                                                    @if (isset($bomProducts))
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
                                                    @endif
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
                                                    value="{{ $selectedProduct['quantity'] ?? 1 }}" class="form-control"
                                                    placeholder="0">
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

                    <div class="col-md-6 mt-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-file-arrow-up text-primary me-2"></i>Design File
                        </label>
                        <div class="input-group">
                            <input type="file" name="attach_file" id="attach_file" class="form-control @error('attach_file') is-invalid @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx">
                        </div>

                        <label class="form-label fw-semibold mt-4">
                            <i class="fa-solid fa-comment-dots text-primary me-2"></i>Special Instructions / Comments
                        </label>
                        <textarea name="comment" id="comment" class="form-control @error('comment') is-invalid @enderror" rows="5" placeholder="Add any comments or special notes for this invoice...">{{ old('comment') }}</textarea>
                    </div>

                    <div class="col-md-6 mt-4">
                        <div class="totals-card rounded-4 p-4 shadow-sm border h-100" style="background: #fdfdfd;">
                            <h6 class="fw-bold mb-4 text-dark-blue border-bottom pb-2">Summary & Totals</h6>
                            
                            <div class="totals-row mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-medium">Subtotal:</span>
                                <span class="fw-bold h6 mb-0 text-dark" id="subtotal_display">0.00</span>
                            </div>

                            <div class="totals-row mb-3 d-flex justify-content-between align-items-center bg-light p-2 rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="switch mb-0">
                                        <input type="checkbox" id="apply_gst">
                                        <span class="slider"></span>
                                    </label>
                                    <span class="text-dark fw-medium">Apply GST</span>
                                </div>
                                <div id="gst_fields_box" style="display: none;">
                                    <div class="input-group input-group-sm" style="width: 100px;">
                                        <input type="number" id="gst_percent" value="18" class="form-control rounded-start-pill">
                                        <span class="input-group-text rounded-end-pill">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="totals-row mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-medium">Discount:</span>
                                <div class="input-group input-group-sm" style="width: 140px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-tag text-muted"></i></span>
                                    <input type="number" name="discount" id="discount" value="0" class="form-control border-start-0 rounded-end-pill">
                                </div>
                            </div>

                            <div class="totals-row mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-medium">Subsidy Amount:</span>
                                <div class="input-group input-group-sm" style="width: 140px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-hand-holding-dollar text-muted"></i></span>
                                    <input type="number" name="subsidy_amount" id="subsidy_amount" value="0" class="form-control border-start-0 rounded-end-pill">
                                </div>
                            </div>

                            <div class="border-top pt-3 mt-3">
                                <div class="totals-row d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark-blue fs-5">Final Total:</span>
                                    <span class="fw-bold text-primary fs-4" id="final_total_display">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="total" id="subtotal" value="0">
                    <input type="hidden" name="final_total" id="final_total" value="0">
                    <input type="hidden" name="gst" id="gst" value="0">
                    <input type="hidden" name="status" id="status" value="unpaid">
                </div>

                <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                    <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                        <span id="btnText">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/estimates.css') }}">
<link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/invoice.js') }}"></script>
@endpush
