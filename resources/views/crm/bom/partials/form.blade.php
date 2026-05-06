<div class="card border-0 shadow-sm rounded-4 overflow-hidden bom-form-card">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h4 mb-1 fw-semibold">{{ $title }}</h1>
                <p class="text-muted small mb-0">{{ $subtitle }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                @if($product)
                    <a href="{{ route('bom-products.show', $product) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                @endif
                <a href="{{ route('bom-products.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                    <i class="fa-solid fa-angle-left pe-1"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="needs-validation ajax-bom-form" novalidate id="bomProductForm">
            @csrf
            @if($method)
                @method($method)
            @endif

            <div class="row g-3">
                <!-- Row 1: Name | Make -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Name <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" id="product_name" value="{{ old('product_name', $product?->product_name) }}" class="form-control" placeholder="BOM name" required>
                    <div class="invalid-feedback d-block" id="product_name-error"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-gear me-2"></i>Make <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2" style="position: relative;">
                        <div class="make-input-wrapper flex-grow-1 border rounded" style="min-height: 40px; background-color: white; cursor: text; position: relative; display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; padding: 0.5rem;">
                            <div id="make-tags-display" class="d-flex flex-wrap gap-2 align-items-center" style="flex: 1;">
                                <!-- Tags will be inserted here -->
                            </div>
                            <input type="text" id="make-search-input" class="form-control border-0 p-0" style="flex: 1; min-width: 150px; outline: none; height: auto;" placeholder="Search makes...">
                        </div>
                        <select name="category_id[]" id="category_id" class="form-control" multiple required style="display: none;">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_id', $product?->categories?->pluck('id')->toArray() ?? [])))>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-dark-blue" id="add-make-btn" data-bs-toggle="modal" data-bs-target="#addMakeModal" style="padding: 0.5rem 0.75rem;" title="Add New Make">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <!-- ✅ Dynamic Make Dropdown with Search -->
                    <div class="make-dropdown-list border rounded mt-2" id="make-dropdown-list" style="display: none; max-height: 250px; overflow-y: auto; background: white; z-index: 1000; position: absolute; width: calc(100% - 80px); top: 100%; left: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <!-- Suggestions will be inserted here -->
                    </div>
                    <div class="invalid-feedback d-block" id="category_id-error"></div>
                </div>

                <!-- Row 2: Price | Tax Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-tag me-2"></i>Price <span class="text-danger">*</span></label>
                    <input type="number" name="price" id="price" value="{{ old('price', $product?->price) }}" class="form-control" placeholder="Enter price" step="0.01" min="0" required>
                    <div class="invalid-feedback d-block" id="price-error"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-percent me-2"></i>Tax Type</label>
                    <select name="tax_type" id="tax_type" class="form-select">
                        <option value="">Select Tax</option>
                        <option value="GST (CGST + SGST)" @selected(old('tax_type', $product?->tax_type) == 'GST (CGST + SGST)')>GST (CGST + SGST)</option>
                        <option value="GST (IGST)" @selected(old('tax_type', $product?->tax_type) == 'GST (IGST)')>GST (IGST)</option>
                        <option value="GST (custom)" @selected(old('tax_type', $product?->tax_type) == 'GST (custom)')>GST (custom)</option>
                    </select>
                </div>

                <!-- Row 3: Tax Rate (%) | Technology -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-percentage me-2"></i>Tax Rate (%)</label>
                    <select name="tax_rate" id="tax_rate" class="form-select">
                        <option value="">Select Rate</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-gear me-2"></i>Technology</label>
                    <select name="technology_id" id="technology_id" class="form-select">
                        <option value="">Select Technology</option>
                        @foreach($technologies as $technology)
                            <option value="{{ $technology->id }}" @selected(old('technology_id', $product?->technology_id) == $technology->id)>{{ $technology->title }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Row 4: Warranty | Capacity -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-gear me-2"></i>Warranty</label>
                    <select name="warranty_id" id="warranty_id" class="form-select">
                        <option value="">Select Warranty</option>
                        @foreach($warranties as $warranty)
                            <option value="{{ $warranty->id }}" @selected(old('warranty_id', $product?->warranty_id) == $warranty->id)>{{ $warranty->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Capacity</label>
                    <input type="text" name="capacity" id="capacity" value="{{ old('capacity', $product?->capacity) }}" class="form-control">
                </div>

                <!-- Row 5: Size of Pipe | Thickness -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Size of Pipe</label>
                    <input type="text" name="size_of_pipe" id="size_of_pipe" value="{{ old('size_of_pipe', $product?->size_of_pipe) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Thickness</label>
                    <input type="text" name="thickness" id="thickness" value="{{ old('thickness', $product?->thickness) }}" class="form-control">
                </div>

                <!-- Row 6: Height | Fitting Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Height</label>
                    <input type="text" name="height" id="height" value="{{ old('height', $product?->height) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Fitting Type</label>
                    <input type="text" name="fitting_type" id="fitting_type" value="{{ old('fitting_type', $product?->fitting_type) }}" class="form-control">
                </div>

                <!-- Row 7: Fitting Material | Meter -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Fitting Material</label>
                    <input type="text" name="fitting_material" id="fitting_material" value="{{ old('fitting_material', $product?->fitting_material) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-ruler me-2"></i>Meter</label>
                    <input type="text" name="meter" id="meter" value="{{ old('meter', $product?->meter) }}" class="form-control">
                </div>

                <!-- Row 8: Nos (Pieces) | Image -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-cubes me-2"></i>Nos (Pieces)</label>
                    <input type="number" name="nos" id="nos" value="{{ old('nos', $product?->nos) }}" class="form-control" step="1" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-image me-2"></i>Image <span class="text-danger">*</span></label>
                    <input type="file" name="image" id="image" class="form-control" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml" @required(!$product)>
                    <div class="invalid-feedback d-block" id="image-error"></div>
                    <div class="mt-2 @if(!$product?->image) d-none @endif" id="bom-image-preview-wrap">
                        <img src="{{ $product?->image ? route('bom-products.image', $product) . '?v=' . (optional($product?->updated_at)->timestamp ?? time()) : '' }}" alt="{{ $product?->product_name }}" class="img-thumbnail" style="max-height: 120px;" id="bom-image-preview">
                    </div>
                </div>

                <!-- Row 9: Description (Full Width) -->
                <div class="col-12">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-pen me-2"></i>Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $product?->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                <a href="{{ route('bom-products.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                    <span id="btnText">{{ $product ? 'Update' : 'Submit' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ Dynamic Tax Rate Dropdown Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== TAX RATE DYNAMIC DROPDOWN =====
    const taxTypeSelect = document.getElementById('tax_type');
    const taxRateSelect = document.getElementById('tax_rate');
    const currentTaxRate = "{{ old('tax_rate', $product?->tax_rate) }}";

    // Tax Rate options based on Tax Type
    const taxRateOptions = {
        'GST (CGST + SGST)': [
            { value: '5', label: 'GST (CGST + SGST) (5.00%)' },
            { value: '12', label: 'GST (CGST + SGST) (12.00%)' },
            { value: '10', label: 'GST (CGST + SGST) (10.00%)' },
            { value: '8', label: 'GST (CGST + SGST) (8.00%)' }
        ],
        'GST (IGST)': [
            { value: '18', label: 'GST (IGST) (18.00%)' }
        ],
        'GST (custom)': [
            { value: '', label: 'Enter custom rate' }
        ]
    };

    function updateTaxRateOptions() {
        const selectedTaxType = taxTypeSelect.value;
        const options = taxRateOptions[selectedTaxType] || [];

        // Clear existing options except the first one
        taxRateSelect.innerHTML = '<option value="">Select Rate</option>';

        // Add new options
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            if (option.value === currentTaxRate) {
                optionElement.selected = true;
            }
            taxRateSelect.appendChild(optionElement);
        });

        // If GST (custom), show input field with % symbol
        if (selectedTaxType === 'GST (custom)') {
            const parentDiv = taxRateSelect.parentElement;
            
            // Create input group wrapper
            const inputGroup = document.createElement('div');
            inputGroup.className = 'input-group';
            inputGroup.id = 'tax_rate_custom_group';
            
            // Create input field
            const customInput = document.createElement('input');
            customInput.type = 'number';
            customInput.name = 'tax_rate';
            customInput.id = 'tax_rate_custom';
            customInput.className = 'form-control';
            customInput.placeholder = 'Enter custom rate';
            customInput.step = '0.01';
            customInput.min = '0';
            customInput.max = '100';
            customInput.value = currentTaxRate || '';
            
            // Create % symbol span
            const percentSpan = document.createElement('span');
            percentSpan.className = 'input-group-text';
            percentSpan.textContent = '%';
            
            // Append to input group
            inputGroup.appendChild(customInput);
            inputGroup.appendChild(percentSpan);
            
            // Replace select with input group
            parentDiv.replaceChild(inputGroup, taxRateSelect);
        } else {
            // If switching back from custom, restore the select
            const customGroup = document.getElementById('tax_rate_custom_group');
            if (customGroup) {
                const parentDiv = customGroup.parentElement;
                const newSelect = document.createElement('select');
                newSelect.name = 'tax_rate';
                newSelect.id = 'tax_rate';
                newSelect.className = 'form-select';
                newSelect.innerHTML = taxRateSelect.innerHTML;
                parentDiv.replaceChild(newSelect, customGroup);
                
                // Re-attach event listener
                document.getElementById('tax_type').removeEventListener('change', updateTaxRateOptions);
                document.getElementById('tax_type').addEventListener('change', updateTaxRateOptions);
            }
        }
    }

    // Update on page load
    updateTaxRateOptions();

    // Update when Tax Type changes
    taxTypeSelect.addEventListener('change', updateTaxRateOptions);

    // ===== MAKE SEARCH WITH API =====
    const makeSearchInput = document.getElementById('make-search-input');
    const makeDropdownList = document.getElementById('make-dropdown-list');
    const categorySelect = document.getElementById('category_id');
    const makeInputWrapper = document.querySelector('.make-input-wrapper');
    let searchTimeout;

    // Show search input on click
    makeInputWrapper.addEventListener('click', function() {
        makeSearchInput.style.display = 'block';
        makeSearchInput.focus();
    });

    // Search makes via API
    makeSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const searchQuery = this.value.trim();

        if (searchQuery.length === 0) {
            makeDropdownList.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            // Call API to search makes
            fetch(`/api/makes/search?q=${encodeURIComponent(searchQuery)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                makeDropdownList.innerHTML = '';

                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(make => {
                        const option = document.createElement('div');
                        option.className = 'make-option p-2';
                        option.style.cssText = 'cursor: pointer; padding: 0.5rem; border-bottom: 1px solid #f0f0f0;';
                        option.textContent = make.name;
                        option.dataset.value = make.id;

                        option.addEventListener('click', function() {
                            selectMake(make.id, make.name);
                        });

                        makeDropdownList.appendChild(option);
                    });
                    makeDropdownList.style.display = 'block';
                } else {
                    makeDropdownList.innerHTML = '<div class="p-2 text-muted">No makes found</div>';
                    makeDropdownList.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error searching makes:', error);
                makeDropdownList.innerHTML = '<div class="p-2 text-danger">Error loading makes</div>';
                makeDropdownList.style.display = 'block';
            });
        }, 300);
    });

    // Select make function
    function selectMake(id, name) {
        // Add to selected makes
        const option = document.createElement('option');
        option.value = id;
        option.textContent = name;
        option.selected = true;

        // Check if already selected
        if (!categorySelect.querySelector(`option[value="${id}"]`)) {
            categorySelect.appendChild(option);
        } else {
            categorySelect.querySelector(`option[value="${id}"]`).selected = true;
        }

        // Add tag to display
        const tag = document.createElement('span');
        tag.className = 'badge bg-dark-blue me-2';
        tag.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem;';
        tag.innerHTML = `${name} <i class="bi bi-x" style="cursor: pointer; font-size: 1.2rem;" data-id="${id}"></i>`;

        // Remove duplicate tags
        const existingTag = document.querySelector(`[data-id="${id}"]`)?.closest('.badge');
        if (existingTag) {
            existingTag.remove();
        }

        document.getElementById('make-tags-display').appendChild(tag);

        // Remove tag on click
        tag.querySelector('i').addEventListener('click', function() {
            categorySelect.querySelector(`option[value="${id}"]`).selected = false;
            tag.remove();
        });

        // Clear search
        makeSearchInput.value = '';
        makeSearchInput.style.display = 'none';
        makeDropdownList.style.display = 'none';
    }

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!makeInputWrapper.contains(e.target) && !makeDropdownList.contains(e.target)) {
            makeDropdownList.style.display = 'none';
            makeSearchInput.style.display = 'none';
        }
    });
});
</script>

<div class="modal fade" id="addMakeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add Make</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-make-form" novalidate enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="new-make-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-make-name" placeholder="Enter make name">
                        <div class="invalid-feedback" id="new-make-name-error"></div>
                    </div>
                    <div class="mb-0">
                        <label for="new-make-image" class="form-label fw-semibold">Image</label>
                        <input type="file" class="form-control" id="new-make-image" accept=".avif,.webp,.jpg,.jpeg,.png,.gif,.bmp,.svg,image/avif,image/webp,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml">
                        <div class="invalid-feedback d-block" id="new-make-image-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue px-4 rounded-3" id="save-make-btn">Add Make</button>
            </div>
        </div>
    </div>
</div>
