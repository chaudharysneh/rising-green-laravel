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
                    <div class="position-relative">
                        <div class="d-flex align-items-center gap-2">
                            <div class="make-select-wrapper flex-grow-1 position-relative">
                                <select name="category_id[]" id="category_id" class="form-select make-multiselect" multiple required style="display: none;">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_id', $product?->categories?->pluck('id')->toArray() ?? [])))>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                
                                <!-- Custom Multi-Select Display -->
                                <div class="make-display-wrapper form-control d-flex flex-wrap align-items-center gap-1 p-1" style="height: auto; min-height: calc(1.5em + 0.75rem + 2px); background-color: white; cursor: text;" id="make-display-wrapper">
                                    <div id="make-selected-tags" class="d-flex flex-wrap gap-2 align-items-center">
                                        <!-- Selected make tags will appear here -->
                                    </div>
                                    <input type="text" id="make-search-input" class="border-0 outline-0 flex-grow-1" style="min-width: 120px; outline: none; box-shadow: none;" placeholder="Select Make" autocomplete="off">
                                </div>
                                
                                <!-- Dropdown List -->
                                <div class="make-dropdown-list border rounded shadow-sm" id="make-dropdown-list" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: white; z-index: 1050; margin-top: 2px;">
                                    <!-- Make options will be populated here -->
                                </div>
                            </div>
                            <button type="button" class="btn btn-dark-blue" id="add-make-btn" data-bs-toggle="modal" data-bs-target="#addMakeModal" style="padding: 0.5rem 0.75rem;" title="Add New Make">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block" id="category_id-error"></div>
                    </div>
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
                        @foreach($taxes->groupBy('name') as $taxName => $taxGroup)
                            <option value="{{ $taxName }}" @selected(old('tax_type', $product?->tax_type) == $taxName)>{{ $taxName }}</option>
                        @endforeach
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
                    <div class="d-flex align-items-center gap-2">
                        <select name="technology_id" id="technology_id" class="form-select flex-grow-1">
                            <option value="">Select Technology</option>
                            @foreach($technologies as $technology)
                                <option value="{{ $technology->id }}" @selected(old('technology_id', $product?->technology_id) == $technology->id)>{{ $technology->title }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-dark-blue" id="add-technology-btn" data-bs-toggle="modal" data-bs-target="#addTechnologyModal" style="padding: 0.5rem 0.75rem;" title="Add New Technology">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block" id="technology_id-error"></div>
                </div>

                <!-- Row 4: Warranty | Capacity -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-gear me-2"></i>Warranty</label>
                    <div class="d-flex align-items-center gap-2">
                        <select name="warranty_id" id="warranty_id" class="form-select flex-grow-1">
                            <option value="">Select Warranty</option>
                            @foreach($warranties as $warranty)
                                <option value="{{ $warranty->id }}" @selected(old('warranty_id', $product?->warranty_id) == $warranty->id)>{{ $warranty->title }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-dark-blue" id="add-warranty-btn" data-bs-toggle="modal" data-bs-target="#addWarrantyModal" style="padding: 0.5rem 0.75rem;" title="Add New Warranty">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block" id="warranty_id-error"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Capacity</label>
                    <input type="text" name="capacity" id="capacity" value="{{ old('capacity', $product?->capacity) }}" class="form-control" placeholder="Enter capacity">
                </div>

                <!-- Row 5: Size of Pipe | Thickness -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Size of Pipe</label>
                    <input type="text" name="size_of_pipe" id="size_of_pipe" value="{{ old('size_of_pipe', $product?->size_of_pipe) }}" class="form-control" placeholder="Enter size of pipe">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Thickness</label>
                    <input type="text" name="thickness" id="thickness" value="{{ old('thickness', $product?->thickness) }}" class="form-control" placeholder="Enter thickness">
                </div>

                <!-- Row 6: Height | Fitting Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Height</label>
                    <input type="text" name="height" id="height" value="{{ old('height', $product?->height) }}" class="form-control" placeholder="Enter height">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Fitting Type</label>
                    <input type="text" name="fitting_type" id="fitting_type" value="{{ old('fitting_type', $product?->fitting_type) }}" class="form-control" placeholder="Enter fitting type">
                </div>

                <!-- Row 7: Fitting Material | Meter -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-briefcase me-2"></i>Fitting Material</label>
                    <input type="text" name="fitting_material" id="fitting_material" value="{{ old('fitting_material', $product?->fitting_material) }}" class="form-control" placeholder="Enter fitting material">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-ruler me-2"></i>Meter</label>
                    <input type="text" name="meter" id="meter" value="{{ old('meter', $product?->meter) }}" class="form-control" placeholder="Enter meter">
                </div>

                <!-- Row 8: Nos (Pieces) | Image -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold bom-label"><i class="fa-solid fa-cubes me-2"></i>Nos (Pieces)</label>
                    <input type="number" name="nos" id="nos" value="{{ old('nos', $product?->nos) }}" class="form-control" placeholder="Enter pieces count" step="1" min="0">
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
                    <textarea name="description" id="description" class="form-control" rows="2" placeholder="Enter BOM description">{{ old('description', $product?->description) }}</textarea>
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
    // Add custom CSS for make multi-select
    const style = document.createElement('style');
    style.textContent = `
        .make-display-wrapper {
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            padding: 0.25rem 0.5rem !important;
        }
        .make-display-wrapper:focus-within {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .make-dropdown-list {
            border: 1px solid #dee2e6;
        }
        .make-option {
            border-bottom: 1px solid #f0f0f0 !important;
        }
        .make-option:hover {
            background-color: #e3f2fd;
        }
        .make-option:last-child {
            border-bottom: none !important;
        }
        #make-search-input {
            border: none !important;
            box-shadow: none !important;
            background: transparent;
        }
        #make-search-input:focus {
            outline: none !important;
            box-shadow: none !important;
        }
        .make-selected-tags .badge {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            border: 1px solid #6c757d !important;
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            line-height: 1.2;
        }
        .make-selected-tags .badge i {
            margin-left: 3px;
            color: #495057 !important;
            font-size: 0.8rem;
        }
        .make-selected-tags .badge:hover {
            background-color: #e9ecef !important;
            border-color: #495057 !important;
        }
        .make-display-wrapper.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }
        .make-display-wrapper.is-invalid:focus-within {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }
        #image.form-control {
            height: calc(1.5em + 0.75rem + 2px) !important;
            padding: 0.375rem 0.75rem !important;
            line-height: 1.5 !important;
            border: 1px solid #dee2e6 !important;
            background-color: #fff !important;
        }
        #image.form-control::-webkit-file-upload-button {
            padding: 0.375rem 0.75rem;
            margin: -0.375rem -0.75rem -0.375rem -0.75rem;
            margin-inline-end: 0.75rem;
            color: #212529;
            background-color: #e9ecef;
            border: 0;
            border-inline-end: 1px solid #dee2e6;
            border-radius: 0.375rem 0 0 0.375rem;
        }
    `;
    document.head.appendChild(style);

    // ===== TAX RATE DYNAMIC DROPDOWN =====
    const taxTypeSelect = document.getElementById('tax_type');
    const taxRateSelect = document.getElementById('tax_rate');
    const currentTaxRate = "{{ old('tax_rate', $product?->tax_rate) }}";

    // Tax data from backend
    const taxData = @json($taxes->groupBy('name'));

    function updateTaxRateOptions() {
        const selectedTaxType = taxTypeSelect.value;
        const taxes = taxData[selectedTaxType] || [];

        // Clear existing options except the first one
        taxRateSelect.innerHTML = '<option value="">Select Rate</option>';

        // Add new options from database
        taxes.forEach(tax => {
            const optionElement = document.createElement('option');
            optionElement.value = tax.rate;
            optionElement.textContent = `${tax.name} (${tax.rate}%)`;
            // Check if this option should be selected
            if (tax.rate == currentTaxRate) {
                optionElement.selected = true;
            }
            taxRateSelect.appendChild(optionElement);
        });

        // If current tax rate doesn't match any predefined option, add it as a custom option
        if (currentTaxRate && !taxes.some(tax => tax.rate == currentTaxRate)) {
            const customOption = document.createElement('option');
            customOption.value = currentTaxRate;
            customOption.textContent = `${selectedTaxType} (${currentTaxRate}%)`;
            customOption.selected = true;
            taxRateSelect.appendChild(customOption);
        }
    }

    // Update on page load
    updateTaxRateOptions();

    // Update when Tax Type changes
    taxTypeSelect.addEventListener('change', updateTaxRateOptions);

    // ===== MAKE MULTI-SELECT WITH SEARCH =====
    const makeSearchInput = document.getElementById('make-search-input');
    const makeDropdownList = document.getElementById('make-dropdown-list');
    const categorySelect = document.getElementById('category_id');
    const makeDisplayWrapper = document.getElementById('make-display-wrapper');
    const makeSelectedTags = document.getElementById('make-selected-tags');
    let searchTimeout;
    let allMakes = [];
    let selectedMakes = [];

    // Initialize with existing selected makes
    function initializeSelectedMakes() {
        const selectedOptions = Array.from(categorySelect.selectedOptions);
        selectedMakes = []; // Reset array
        selectedOptions.forEach(option => {
            if (option.value) {
                selectedMakes.push({
                    id: option.value,
                    name: option.textContent.trim()
                });
            }
        });
        renderSelectedTags();
    }

    // Render selected make tags
    function renderSelectedTags() {
        makeSelectedTags.innerHTML = '';
        selectedMakes.forEach(make => {
            const tag = document.createElement('span');
            tag.className = 'badge d-flex align-items-center gap-1';
            tag.style.cssText = 'font-size: 0.75rem; padding: 0.125rem 0.375rem; background-color: #f8f9fa; color: #495057; border: 1px solid #6c757d; font-weight: 500; line-height: 1.2;';
            tag.innerHTML = `
                ${make.name}
                <i class="bi bi-x" style="cursor: pointer; font-size: 0.8rem; margin-left: 3px; color: #495057;" data-make-id="${make.id}"></i>
            `;
            
            // Remove tag on click
            tag.querySelector('i').addEventListener('click', function(e) {
                e.stopPropagation();
                removeMake(make.id);
            });
            
            makeSelectedTags.appendChild(tag);
        });
        
        // Update placeholder
        makeSearchInput.placeholder = selectedMakes.length > 0 ? 'Add more makes...' : 'Select Make';
    }

    // Load all makes initially (but don't show dropdown)
    function loadAllMakes() {
        fetch('/api/makes/search?limit=100', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                allMakes = data;
                // Don't show dropdown automatically - only when clicked
            }
        })
        .catch(error => {
            console.error('Error loading makes:', error);
        });
    }

    // Show dropdown with makes
    function showDropdown(makes) {
        makeDropdownList.innerHTML = '';
        
        if (makes.length === 0) {
            makeDropdownList.innerHTML = '<div class="p-2 text-muted">No makes found</div>';
        } else {
            makes.forEach(make => {
                // Skip if already selected
                if (selectedMakes.some(selected => selected.id == make.id)) {
                    return;
                }
                
                const option = document.createElement('div');
                option.className = 'make-option px-3 py-2 border-bottom';
                option.style.cssText = 'cursor: pointer; transition: background-color 0.2s; font-size: 0.875rem;';
                option.textContent = make.name;
                option.dataset.makeId = make.id;
                
                // Hover effects
                option.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                option.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
                
                option.addEventListener('click', function() {
                    selectMake(make.id, make.name);
                });
                
                makeDropdownList.appendChild(option);
            });
        }
        
        makeDropdownList.style.display = 'block';
    }

    // Select a make
    function selectMake(id, name) {
        // Check if already selected
        if (selectedMakes.some(make => make.id == id)) {
            return;
        }
        
        // Clear error state when make is selected
        makeDisplayWrapper.classList.remove('is-invalid');
        const errorElement = document.getElementById('category_id-error');
        if (errorElement) {
            errorElement.textContent = '';
        }
        
        // Add to selected makes
        selectedMakes.push({ id, name });
        
        // Update the hidden select
        const option = categorySelect.querySelector(`option[value="${id}"]`);
        if (option) {
            option.selected = true;
        } else {
            // Create new option if it doesn't exist
            const newOption = document.createElement('option');
            newOption.value = id;
            newOption.textContent = name;
            newOption.selected = true;
            categorySelect.appendChild(newOption);
        }
        
        // Re-render tags and clear search
        renderSelectedTags();
        makeSearchInput.value = '';
        makeDropdownList.style.display = 'none';
        
        // Refresh dropdown to hide selected item
        const currentSearch = makeSearchInput.value.trim();
        if (currentSearch) {
            searchMakes(currentSearch);
        } else {
            showDropdown(allMakes);
        }
    }

    // Remove a make
    function removeMake(id) {
        selectedMakes = selectedMakes.filter(make => make.id != id);
        
        // Update the hidden select
        const option = categorySelect.querySelector(`option[value="${id}"]`);
        if (option) {
            option.selected = false;
        }
        
        renderSelectedTags();
        
        // Refresh dropdown
        const currentSearch = makeSearchInput.value.trim();
        if (makeDropdownList.style.display === 'block') {
            if (currentSearch) {
                searchMakes(currentSearch);
            } else {
                showDropdown(allMakes);
            }
        }
    }

    // Search makes
    function searchMakes(query) {
        if (query.length === 0) {
            showDropdown(allMakes);
            return;
        }
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetch(`/api/makes/search?q=${encodeURIComponent(query)}&limit=50`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    showDropdown(data);
                }
            })
            .catch(error => {
                console.error('Error searching makes:', error);
                makeDropdownList.innerHTML = '<div class="p-2 text-danger">Error loading makes</div>';
                makeDropdownList.style.display = 'block';
            });
        }, 300);
    }

    // Event listeners
    makeDisplayWrapper.addEventListener('click', function() {
        // Clear error state when user clicks
        this.classList.remove('is-invalid');
        const errorElement = document.getElementById('category_id-error');
        if (errorElement) {
            errorElement.textContent = '';
        }
        
        makeSearchInput.focus();
        // Only show dropdown when clicked and makes are loaded
        if (allMakes.length > 0 && makeDropdownList.style.display === 'none') {
            if (makeSearchInput.value.trim()) {
                searchMakes(makeSearchInput.value.trim());
            } else {
                showDropdown(allMakes);
            }
        } else if (allMakes.length === 0) {
            // Load makes if not loaded yet
            loadAllMakes();
        }
    });

    makeSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        searchMakes(query);
    });

    makeSearchInput.addEventListener('focus', function() {
        // Only show dropdown when user actually focuses the input
        if (allMakes.length > 0) {
            if (this.value.trim()) {
                searchMakes(this.value.trim());
            } else {
                showDropdown(allMakes);
            }
        } else {
            // Load makes if not loaded yet
            loadAllMakes();
        }
    });

    makeSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value === '' && selectedMakes.length > 0) {
            // Remove last selected make on backspace
            const lastMake = selectedMakes[selectedMakes.length - 1];
            removeMake(lastMake.id);
        } else if (e.key === 'Escape') {
            makeDropdownList.style.display = 'none';
        }
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!makeDisplayWrapper.contains(e.target) && !makeDropdownList.contains(e.target)) {
            makeDropdownList.style.display = 'none';
        }
    });

    // Initialize
    initializeSelectedMakes();
    loadAllMakes();

    // ===== CLEAR VALIDATION ERRORS ON INPUT =====
    function clearFieldError(fieldId, inputElement) {
        const errorElement = document.getElementById(`${fieldId}-error`);
        if (errorElement) {
            errorElement.textContent = '';
        }
        if (inputElement) {
            inputElement.classList.remove('is-invalid');
        }
    }

    // Clear errors for all form fields when user starts typing/selecting
    const formFields = [
        { id: 'product_name', element: document.getElementById('product_name') },
        { id: 'price', element: document.getElementById('price') },
        { id: 'image', element: document.getElementById('image') },
        { id: 'tax_type', element: document.getElementById('tax_type') },
        { id: 'tax_rate', element: document.getElementById('tax_rate') },
        { id: 'technology_id', element: document.getElementById('technology_id') },
        { id: 'warranty_id', element: document.getElementById('warranty_id') }
    ];

    formFields.forEach(field => {
        if (field.element) {
            // For input fields
            if (field.element.type === 'text' || field.element.type === 'number' || field.element.type === 'file') {
                field.element.addEventListener('input', function() {
                    clearFieldError(field.id, this);
                });
            }
            // For select fields
            else if (field.element.tagName === 'SELECT') {
                field.element.addEventListener('change', function() {
                    clearFieldError(field.id, this);
                });
            }
        }
    });

    // Special handling for tax_rate custom input (when GST custom is selected)
    document.addEventListener('input', function(e) {
        if (e.target.id === 'tax_rate_custom') {
            clearFieldError('tax_rate', e.target);
        }
    });

    // ===== IMAGE PREVIEW FUNCTIONALITY =====
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('bom-image-preview');
    const imagePreviewWrap = document.getElementById('bom-image-preview-wrap');

    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/avif', 'image/svg+xml'];
                if (!validTypes.includes(file.type)) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Please select a valid image file (JPEG, PNG, GIF, BMP, WEBP, AVIF, SVG).');
                    } else {
                        alert('Please select a valid image file.');
                    }
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Please select an image smaller than 2MB.');
                    } else {
                        alert('Please select an image smaller than 2MB.');
                    }
                    this.value = '';
                    return;
                }
                
                // Create preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) {
                        imagePreview.src = e.target.result;
                        imagePreview.alt = file.name;
                    } else {
                        // Create new preview image if it doesn't exist
                        const newPreview = document.createElement('img');
                        newPreview.src = e.target.result;
                        newPreview.alt = file.name;
                        newPreview.className = 'img-thumbnail';
                        newPreview.style.maxHeight = '120px';
                        newPreview.id = 'bom-image-preview';
                        imagePreviewWrap.appendChild(newPreview);
                    }
                    
                    // Show preview wrapper
                    if (imagePreviewWrap) {
                        imagePreviewWrap.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(file);
            } else {
                // Hide preview if no file selected
                if (imagePreviewWrap) {
                    imagePreviewWrap.classList.add('d-none');
                }
            }
        });
    }

    // ===== ADD MAKE MODAL FUNCTIONALITY =====
    const addMakeModal = document.getElementById('addMakeModal');
    const addMakeForm = document.getElementById('add-make-form');
    const saveMakeBtn = document.getElementById('save-make-btn');
    const newMakeNameInput = document.getElementById('new-make-name');
    const newMakeImageInput = document.getElementById('new-make-image');

    saveMakeBtn.addEventListener('click', function() {
        const formData = new FormData();
        formData.append('name', newMakeNameInput.value.trim());
        
        if (newMakeImageInput.files[0]) {
            formData.append('image', newMakeImageInput.files[0]);
        }

        // Clear previous errors
        document.querySelectorAll('#addMakeModal .invalid-feedback').forEach(el => {
            el.textContent = '';
            el.previousElementSibling?.classList.remove('is-invalid');
        });

        // Show loading state
        saveMakeBtn.disabled = true;
        saveMakeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';

        fetch('/api/make', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new make to the list
                allMakes.unshift(data.data);
                
                // Auto-select the new make
                selectMake(data.data.id, data.data.name);
                
                // Reset form and close modal
                addMakeForm.reset();
                bootstrap.Modal.getInstance(addMakeModal).hide();
                
                // Show success message (optional)
                // You can add a toast notification here if needed
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorElement = document.getElementById(`new-make-${field}-error`);
                        const inputElement = document.getElementById(`new-make-${field}`);
                        
                        if (errorElement && inputElement) {
                            errorElement.textContent = data.errors[field][0];
                            inputElement.classList.add('is-invalid');
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error adding make:', error);
            alert('Error adding make. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveMakeBtn.disabled = false;
            saveMakeBtn.innerHTML = 'Add Make';
        });
    });

    // Reset form when modal is closed
    addMakeModal.addEventListener('hidden.bs.modal', function() {
        addMakeForm.reset();
        document.querySelectorAll('#addMakeModal .invalid-feedback').forEach(el => {
            el.textContent = '';
            el.previousElementSibling?.classList.remove('is-invalid');
        });
    });

    // ===== ADD TECHNOLOGY MODAL FUNCTIONALITY =====
    const addTechnologyModal = document.getElementById('addTechnologyModal');
    const addTechnologyForm = document.getElementById('add-technology-form');
    const saveTechnologyBtn = document.getElementById('save-technology-btn');
    const newTechnologyNameInput = document.getElementById('new-technology-name');
    const newTechnologyDescriptionInput = document.getElementById('new-technology-description');
    const technologySelect = document.getElementById('technology_id');
    const newTechnologyNameError = document.getElementById('new-technology-name-error');
    const newTechnologyDescriptionError = document.getElementById('new-technology-description-error');

    saveTechnologyBtn.addEventListener('click', function() {
        // Clear previous errors
        newTechnologyNameInput.classList.remove('is-invalid');
        newTechnologyDescriptionInput.classList.remove('is-invalid');
        newTechnologyNameError.textContent = '';
        newTechnologyDescriptionError.textContent = '';
        
        // Basic client-side validation
        let hasError = false;
        
        if (!newTechnologyNameInput.value.trim()) {
            newTechnologyNameInput.classList.add('is-invalid');
            newTechnologyNameError.textContent = 'Please enter technology name.';
            hasError = true;
        }
        
        if (hasError) {
            return;
        }

        const formData = new FormData();
        formData.append('title', newTechnologyNameInput.value.trim());
        formData.append('description', newTechnologyDescriptionInput.value.trim());

        // Show loading state
        saveTechnologyBtn.disabled = true;
        saveTechnologyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';

        fetch('/api/technology', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new technology to the dropdown
                const newOption = document.createElement('option');
                newOption.value = data.data.id;
                newOption.textContent = data.data.title;
                newOption.selected = true;
                technologySelect.appendChild(newOption);
                
                // Reset form and close modal
                addTechnologyForm.reset();
                newTechnologyNameInput.classList.remove('is-invalid');
                newTechnologyDescriptionInput.classList.remove('is-invalid');
                newTechnologyNameError.textContent = '';
                newTechnologyDescriptionError.textContent = '';
                bootstrap.Modal.getInstance(addTechnologyModal).hide();
                
                // Clear error state
                technologySelect.classList.remove('is-invalid');
                const errorElement = document.getElementById('technology_id-error');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        if (field === 'title') {
                            newTechnologyNameInput.classList.add('is-invalid');
                            newTechnologyNameError.textContent = data.errors[field][0];
                        } else if (field === 'description') {
                            newTechnologyDescriptionInput.classList.add('is-invalid');
                            newTechnologyDescriptionError.textContent = data.errors[field][0];
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error adding technology:', error);
            alert('Error adding technology. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveTechnologyBtn.disabled = false;
            saveTechnologyBtn.innerHTML = 'Add Technology';
        });
    });

    // Reset form when modal is closed
    addTechnologyModal.addEventListener('hidden.bs.modal', function() {
        addTechnologyForm.reset();
        newTechnologyNameInput.classList.remove('is-invalid');
        newTechnologyDescriptionInput.classList.remove('is-invalid');
        newTechnologyNameError.textContent = '';
        newTechnologyDescriptionError.textContent = '';
    });

    // ===== ADD WARRANTY MODAL FUNCTIONALITY =====
    const addWarrantyModal = document.getElementById('addWarrantyModal');
    const addWarrantyForm = document.getElementById('add-warranty-form');
    const saveWarrantyBtn = document.getElementById('save-warranty-btn');
    const newWarrantyNameInput = document.getElementById('new-warranty-name');
    const newWarrantyDescriptionInput = document.getElementById('new-warranty-description');
    const warrantySelect = document.getElementById('warranty_id');
    const newWarrantyNameError = document.getElementById('new-warranty-name-error');
    const newWarrantyDescriptionError = document.getElementById('new-warranty-description-error');

    saveWarrantyBtn.addEventListener('click', function() {
        // Clear previous errors
        newWarrantyNameInput.classList.remove('is-invalid');
        newWarrantyDescriptionInput.classList.remove('is-invalid');
        newWarrantyNameError.textContent = '';
        newWarrantyDescriptionError.textContent = '';
        
        // Basic client-side validation
        let hasError = false;
        
        if (!newWarrantyNameInput.value.trim()) {
            newWarrantyNameInput.classList.add('is-invalid');
            newWarrantyNameError.textContent = 'Please enter warranty name.';
            hasError = true;
        }
        
        if (hasError) {
            return;
        }

        const formData = new FormData();
        formData.append('title', newWarrantyNameInput.value.trim());
        formData.append('description', newWarrantyDescriptionInput.value.trim());

        // Show loading state
        saveWarrantyBtn.disabled = true;
        saveWarrantyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';

        fetch('/api/warranty', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new warranty to the dropdown
                const newOption = document.createElement('option');
                newOption.value = data.data.id;
                newOption.textContent = data.data.title;
                newOption.selected = true;
                warrantySelect.appendChild(newOption);
                
                // Reset form and close modal
                addWarrantyForm.reset();
                newWarrantyNameInput.classList.remove('is-invalid');
                newWarrantyDescriptionInput.classList.remove('is-invalid');
                newWarrantyNameError.textContent = '';
                newWarrantyDescriptionError.textContent = '';
                bootstrap.Modal.getInstance(addWarrantyModal).hide();
                
                // Clear error state
                warrantySelect.classList.remove('is-invalid');
                const errorElement = document.getElementById('warranty_id-error');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        if (field === 'title') {
                            newWarrantyNameInput.classList.add('is-invalid');
                            newWarrantyNameError.textContent = data.errors[field][0];
                        } else if (field === 'description') {
                            newWarrantyDescriptionInput.classList.add('is-invalid');
                            newWarrantyDescriptionError.textContent = data.errors[field][0];
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error adding warranty:', error);
            alert('Error adding warranty. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveWarrantyBtn.disabled = false;
            saveWarrantyBtn.innerHTML = 'Add Warranty';
        });
    });

    // Reset form when modal is closed
    addWarrantyModal.addEventListener('hidden.bs.modal', function() {
        addWarrantyForm.reset();
        newWarrantyNameInput.classList.remove('is-invalid');
        newWarrantyDescriptionInput.classList.remove('is-invalid');
        newWarrantyNameError.textContent = '';
        newWarrantyDescriptionError.textContent = '';
    });

    // ===== BOM FORM AJAX SUBMISSION =====
    const bomForm = document.getElementById('bomProductForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');

    bomForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
            el.previousElementSibling?.classList.remove('is-invalid');
        });

        // Show loading state
        submitBtn.disabled = true;
        btnSpinner.classList.remove('d-none');
        btnText.textContent = 'Processing...';

        // Prepare form data
        const formData = new FormData(bomForm);
        
        // Submit form
        fetch(bomForm.action, {
            method: bomForm.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                if (typeof window.showAlert === 'function') {
                    window.showAlert('success', data.message || 'BOM product saved successfully.', 'Success!', data.redirect || '/all_product');
                } else {
                    alert(data.message || 'BOM product saved successfully.');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorElement = document.getElementById(`${field}-error`);
                        let inputElement = document.getElementById(field) || document.querySelector(`[name="${field}"]`) || document.querySelector(`[name="${field}[]"]`);
                        
                        // Special handling for Make field (category_id)
                        if (field === 'category_id') {
                            inputElement = document.getElementById('make-display-wrapper');
                        }
                        
                        if (errorElement) {
                            errorElement.textContent = data.errors[field][0];
                        }
                        if (inputElement) {
                            inputElement.classList.add('is-invalid');
                        }
                    });
                }
                
                // Don't show popup error message, just scroll to first error
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            if (typeof window.showAlert === 'function') {
                window.showAlert('error', 'Something went wrong. Please try again.');
            } else {
                alert('Something went wrong. Please try again.');
            }
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnSpinner.classList.add('d-none');
            btnText.textContent = bomForm.querySelector('[name="_method"][value="PATCH"]') ? 'Update' : 'Submit';
        });
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

<!-- Add Technology Modal -->
<div class="modal fade" id="addTechnologyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add Technology</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-technology-form" novalidate>
                    <div class="mb-3">
                        <label for="new-technology-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-technology-name" placeholder="Enter technology name">
                        <div class="invalid-feedback" id="new-technology-name-error"></div>
                    </div>
                    <div class="mb-0">
                        <label for="new-technology-description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="new-technology-description" placeholder="Enter description" rows="2"></textarea>
                        <div class="invalid-feedback" id="new-technology-description-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue px-4 rounded-3" id="save-technology-btn">Add Technology</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Warranty Modal -->
<div class="modal fade" id="addWarrantyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4" style="background-color: #121a33;">
                <h5 class="modal-title fw-bold text-white">Add Warranty</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-warranty-form" novalidate>
                    <div class="mb-3">
                        <label for="new-warranty-name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-warranty-name" placeholder="Enter warranty name">
                        <div class="invalid-feedback" id="new-warranty-name-error"></div>
                    </div>
                    <div class="mb-0">
                        <label for="new-warranty-description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="new-warranty-description" placeholder="Enter description" rows="2"></textarea>
                        <div class="invalid-feedback" id="new-warranty-description-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-outline-dark-blue px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark-blue px-4 rounded-3" id="save-warranty-btn">Add Warranty</button>
            </div>
        </div>
    </div>
</div>
