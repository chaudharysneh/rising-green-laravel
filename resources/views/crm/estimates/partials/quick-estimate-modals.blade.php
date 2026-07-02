@php
    $bomTaxOptions = collect($gstTaxes ?? [])->map(function ($tax) {
        $taxName = strtoupper((string) $tax->name);
        $label = (string) $tax->name;

        if (str_contains($taxName, 'CGST') && str_contains($taxName, 'SGST')) {
            $label = 'CGST + SGST';
        } elseif (str_contains($taxName, 'IGST')) {
            $label = 'IGST';
        }

        return ['label' => $label, 'rate' => (float) $tax->rate];
    })->filter(function ($taxOption) {
        return (float) ($taxOption['rate'] ?? 0) > 0;
    })->values();
@endphp
<div class="modal fade" id="quickEstimateModal" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <form id="quickEstimateForm" novalidate class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-white">Quick Estimate</h5>
                    <p class="small text-white-50 mb-0">Create a basic estimate with default/static settings.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                    <!-- Mobile Step Indicator -->
                    <div class="quick-step-indicator d-md-none">
                        <div class="quick-step-dot active" id="qdot-1"></div>
                        <div class="quick-step-dot" id="qdot-2"></div>
                        <div class="quick-step-dot" id="qdot-3"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 col-md-4 quick-step-1 active-step">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2" style="min-width: 0;">
                                <div class="flex-grow-1 w-100" style="min-width: 0;">
                                    <select class="form-select" name="customer_id" id="quick_estimate_customer_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers ?? [] as $customer)
                                            <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-dark-blue flex-shrink-0" id="quickEstimateAddCustomerBtn" title="Add New Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="quick_customer_id-error">Please select a customer.</div>
                        </div>
                        <div class="col-6 col-md-4 quick-step-1 active-step">
                            <label class="form-label fw-semibold">Estimate Name</label>
                            <input type="text" class="form-control" name="estimate_name" id="quick_estimate_name" placeholder="Auto from customer">
                        </div>
                        <div class="col-6 col-md-4 quick-step-1 active-step">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="quick_estimate_type" required>
                                <option value="" selected>Select Type</option>
                                <option value="residential">Residential</option>
                                <option value="commercial">Commercial</option>
                                <option value="industrial">Industrial</option>
                                <option value="common meter">Common Meter</option>
                            </select>
                            <div class="invalid-feedback" id="quick_type-error">Please select type.</div>
                        </div>
                        <div class="col-6 col-md-4 quick-step-1 active-step">
                            <label class="form-label fw-semibold">Quantity (kW) <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control" name="quantity" id="quick_quantity" placeholder="Enter kW" required>
                            <div class="invalid-feedback" id="quick_quantity-error">Please enter quantity.</div>
                        </div>
                        <div class="col-12 col-md-4 quick-step-1 active-step">
                            <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control" name="price" id="quick_price" placeholder="Enter price" required>
                            <div class="invalid-feedback" id="quick_price-error">Please enter price.</div>
                        </div>
                        <div class="col-12 col-md-4 quick-step-1 active-step" id="quick_template_wrapper">
                            <label class="form-label fw-semibold" data-icon-enhanced="true">Quotation Template <span class="text-danger">*</span></label>
                            <select class="form-select" name="template_id" id="quick_template_id" required>
                                <option value="">Select Template</option>
                                @foreach ($templates ?? [] as $template)
                                    <option value="{{ $template->id }}">{{ $template->template_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="quick_template_id-error">Please select template.</div>
                        </div>
                        <div class="col-12 quick-step-2">
                            <label class="form-label fw-semibold">BOM Details <span class="text-danger">*</span></label>
                            <div class="border rounded-3 bg-light p-3">
                                <div id="quickBomRows" class="d-flex flex-column gap-2">
                                    <div class="quick-bom-row bg-white border rounded-3 p-2">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-12 col-md-3 quick-bom-select-col">
                                                <label class="form-label small fw-semibold">BOM</label>
                                                <div class="d-flex align-items-start gap-2">
                                                    <select class="form-select quick-bom-select" name="quick_bom_id[]">
                                                        <option value="">Select BOM</option>
                                                        @foreach ($bomProducts ?? [] as $bom)
                                                            <option value="{{ $bom->id }}"
                                                                data-name="{{ $bom->product_name }}"
                                                                data-price="{{ $bom->price ?? 0 }}"
                                                                data-categories='{{ json_encode($bom->categories->pluck('name')->toArray()) }}'>
                                                                {{ $bom->product_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-dark-blue flex-shrink-0 quick-estimate-add-bom-btn" title="Add New BOM">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 quick-bom-make-col">
                                                <label class="form-label small fw-semibold">Make</label>
                                                <select class="form-select quick-bom-make-select" name="quick_bom_make[]" disabled>
                                                    <option value="">Select Make</option>
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-1 quick-bom-qty-col">
                                                <label class="form-label small fw-semibold">Qty</label>
                                                <input type="number" min="1" step="1" class="form-control quick-bom-qty" name="quick_bom_qty[]" value="1">
                                            </div>
                                            <div class="col-6 col-md-2 quick-bom-money-col">
                                                <label class="form-label small fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Unit Price</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-price" name="quick_bom_price[]" value="0">
                                            </div>
                                            <div class="col-6 col-md-1 quick-bom-money-col">
                                                <label class="form-label small fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Amount</label>
                                                <input type="number" min="0" step="1" class="form-control quick-bom-amount" value="0" readonly>
                                            </div>
                                            <div class="col-12 col-md-2 quick-bom-tax-col">
                                                <label class="form-label small fw-semibold">Tax</label>
                                                <select name="quick_bom_tax_rate[]" class="form-select quick-bom-tax-rate">
                                                    <option value="0" data-label="">No Tax</option>
                                                    @foreach ($bomTaxOptions as $taxOption)
                                                        <option value="{{ $taxOption['rate'] }}" data-label="{{ $taxOption['label'] }}">
                                                            {{ $taxOption['label'] }} ({{ rtrim(rtrim(number_format($taxOption['rate'], 2), '0'), '.') }}%)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-1">
                                                <button type="button" class="btn btn-outline-danger w-100 quick-remove-bom-row" style="display:none;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-dark-blue btn-sm mt-3" id="quickAddBomRow">
                                    <i class="bi bi-plus-circle me-1"></i>Add More BOM
                                </button>
                                <div class="invalid-feedback d-block" id="quick_bom_id-error" style="display:none;">Please select at least one BOM.</div>
                            </div>
                        </div>
                        <div class="col-12 quick-step-3">
                            <div class="totals-card quick-totals-card rounded-3">
                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subtotal:</span>
                                    <span id="quick_subtotal_display" class="fw-bold text-dark">0.00</span>
                                </div>

                                <div class="totals-row align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="switch mb-0">
                                            <input type="checkbox" id="quick_apply_gst" checked>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="small fw-semibold">Apply GST</span>
                                    </div>
                                </div>

                                <div id="quick_gst_fields_box">
                                    <div class="totals-row">
                                        <span class="small text-muted">Select BOM tax to apply GST.</span>
                                        <span class="small">0.00</span>
                                    </div>
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Discount:</span>
                                    <input type="number" id="quick_discount" value="0" step="1" class="input-small">
                                </div>

                                <div class="totals-row">
                                    <span class="fw-semibold crm-label-with-icon" style="font-size: 15px;"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Subsidy:</span>
                                    <input type="number" id="quick_subsidy_amount" value="0" step="1" class="input-small">
                                </div>

                                <hr class="my-2">

                                <div class="totals-row total-row mb-0">
                                    <span class="h6 mb-0 fw-bold crm-label-with-icon"><i class="fa-solid fa-money-bill crm-label-icon" aria-hidden="true"></i>Total Payable:</span>
                                    <span id="quick_final_total_display" class="h5 mb-0 fw-bold">0.00</span>
                                </div>
                            </div>
                            <input type="hidden" id="quick_subtotal" value="0">
                            <input type="hidden" id="quick_final_total" value="0">
                            <input type="hidden" id="quick_gst" value="0">
                        </div>
                        <div class="col-12 quick-step-3">
                            <label class="form-label fw-semibold">Comment</label>
                            <textarea class="form-control" name="comment" id="quick_estimate_comment" rows="2" placeholder="Optional comment"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top rounded-bottom-4 d-flex justify-content-between pb-3">
                    <button type="button" class="btn btn-outline-dark-blue d-none d-md-block" data-bs-dismiss="modal">Cancel</button>
                    
                    <!-- Mobile Wizard Buttons -->
                    <button type="button" class="btn btn-outline-dark-blue mobile-wizard-btn quick-prev-btn" style="display: none !important;">Back</button>
                    
                    <div class="d-flex ms-auto">
                        <button type="button" class="btn btn-dark-blue mobile-wizard-btn quick-next-btn">Next</button>
                        <button type="submit" class="btn btn-dark-blue quick-submit-btn" id="quickEstimateSubmitBtn">
                            <span class="submit-label">Create Estimate</span>
                        </button>
                    </div>
                </div>
        </form>
    </div>
</div>

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            #quickEstimateModal {
                padding-bottom: 85px !important;
            }
            #quickEstimateModal .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            #quickEstimateModal .quick-totals-card .input-small {
                max-width: 110px;
            }

            /* Responsive Multi-Step Logic */
            #quickEstimateModal .quick-step-1,
            #quickEstimateModal .quick-step-2,
            #quickEstimateModal .quick-step-3 {
                display: none !important;
            }
            #quickEstimateModal .active-step {
                display: block !important;
            }
            
            .quick-bom-row-grid .quick-bom-select-col,
            .quick-bom-row-grid .quick-bom-make-col {
                grid-column: span 2;
            }

            /* Step indicator */
            .quick-step-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 15px;
            }
            .quick-step-dot {
                height: 8px;
                width: 100%;
                background: #e9ecef;
                border-radius: 10px;
                transition: 0.3s;
            }
            .quick-step-dot.active {
                background: #121a33;
            }
        }

        #quickEstimateModal .modal-dialog {
            z-index: 1055;
        }

        #addCustomerModal,
        #quickAddBomModal {
            z-index: 1065 !important;
        }

        body.modal-open .modal-backdrop.show ~ .modal-backdrop.show {
            z-index: 1060;
        }

        #quickEstimateModal .quick-bom-row .quick-bom-select-col {
            min-width: 0;
        }

        #quickEstimateModal .d-flex:has(.is-invalid) ~ .invalid-feedback {
            display: block !important;
        }

        /* Force Quotation Template dropdown to open upwards */
        #quick_template_wrapper {
            position: relative;
        }
        #quick_template_wrapper .select2-container--open:not(.select2) {
            top: auto !important;
            bottom: 40px !important;
            left: 0 !important;
            width: 100% !important;
            height: 0 !important;
        }
        #quick_template_wrapper .select2-dropdown {
            top: auto !important;
            bottom: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-top-left-radius: 0.375rem !important;
            border-top-right-radius: 0.375rem !important;
            border-bottom: none !important;
            border-top: 1px solid #ced4da !important;
            box-shadow: 0 -0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentStep = 1;
            const totalSteps = 3;

            function updateWizardUI() {
                if (window.innerWidth >= 768) {
                    $('#quickEstimateModal .quick-step-1, #quickEstimateModal .quick-step-2, #quickEstimateModal .quick-step-3').removeClass('active-step');
                    $('.quick-step-indicator').addClass('d-none');
                    $('.quick-prev-btn, .quick-next-btn').addClass('d-none');
                    $('.quick-submit-btn').removeClass('d-none');
                    return;
                }

                $('.quick-step-indicator').removeClass('d-none');
                $('#quickEstimateModal .quick-step-1, #quickEstimateModal .quick-step-2, #quickEstimateModal .quick-step-3').removeClass('active-step');
                $('#quickEstimateModal .quick-step-' + currentStep).addClass('active-step');

                $('.quick-step-dot').removeClass('active');
                for (let i = 1; i <= currentStep; i++) {
                    $('#qdot-' + i).addClass('active');
                }

                if (currentStep === 1) {
                    $('.quick-prev-btn').attr('style', 'display: none !important;');
                    $('.quick-next-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-submit-btn').attr('style', 'display: none !important;');
                } else if (currentStep === 2) {
                    $('.quick-prev-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-next-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-submit-btn').attr('style', 'display: none !important;');
                } else {
                    $('.quick-prev-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-next-btn').attr('style', 'display: none !important;');
                    $('.quick-submit-btn').attr('style', 'display: inline-block !important;');
                }
            }

            $('.quick-next-btn').click(function() {
                let isValid = true;
                
                if (currentStep === 1) {
                    $('#quickEstimateModal .quick-step-1 [required]').each(function() {
                        if (!$(this).val() && $(this).is(':visible')) {
                            isValid = false;
                            $(this).addClass('is-invalid');
                        } else {
                            $(this).removeClass('is-invalid');
                        }
                    });
                } else if (currentStep === 2) {
                    let selectedBomCount = 0;
                    $('#quickEstimateModal .quick-bom-row').each(function() {
                        const select = $(this).find('.quick-bom-select');
                        const qtyInput = $(this).find('.quick-bom-qty');
                        const priceInput = $(this).find('.quick-bom-price');
                        
                        if (select.val()) {
                            selectedBomCount++;
                            const qty = parseFloat(qtyInput.val() || 0);
                            const price = parseFloat(priceInput.val() || 0);
                            
                            if (!(qty > 0)) {
                                qtyInput.addClass('is-invalid');
                                isValid = false;
                            } else {
                                qtyInput.removeClass('is-invalid');
                            }
                            
                            if (price < 0) {
                                priceInput.addClass('is-invalid');
                                isValid = false;
                            } else {
                                priceInput.removeClass('is-invalid');
                            }
                        }
                    });
                    
                    if (selectedBomCount === 0) {
                        $('#quick_bom_id-error').show();
                        $('#quickEstimateModal .quick-bom-select').first().addClass('is-invalid');
                        isValid = false;
                    } else {
                        $('#quick_bom_id-error').hide();
                    }
                }
                
                if (isValid && currentStep < totalSteps) {
                    currentStep++;
                    updateWizardUI();
                }
            });

            $('.quick-prev-btn').click(function() {
                if (currentStep > 1) {
                    currentStep--;
                    updateWizardUI();
                }
            });

            // Dynamically clear validation errors using event delegation
            $('#quickEstimateForm').on('change input', '[required], .quick-bom-select, .quick-bom-qty, .quick-bom-price', function() {
                const val = $(this).val();
                if (val || $(this).hasClass('quick-bom-qty') || $(this).hasClass('quick-bom-price')) {
                    $(this).removeClass('is-invalid');
                    if ($(this).hasClass('quick-bom-select')) {
                        $('#quick_bom_id-error').hide();
                    }
                }
            });

            $('#quickEstimateModal').on('hidden.bs.modal', function () {
                currentStep = 1;
                updateWizardUI();
            });

            // Initial setup
            updateWizardUI();
            $(window).resize(updateWizardUI);
        });
    </script>
@endpush

<div class="modal fade" id="quickAddBomModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
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
