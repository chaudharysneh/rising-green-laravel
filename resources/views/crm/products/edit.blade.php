@extends('layouts.app')

@section('page_title', 'Products - Edit')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Edit Product</h1>
                        <p class="text-muted small mb-0">Update product details.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        @can('products.view')
                        <a href="{{ route('products.show', $product->id) }}" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endcan
                        <a href="{{ route('products.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/products/{{ $product->id }}" enctype="multipart/form-data"
                    class="needs-validation ajax-product-form" novalidate id="productEditForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box"></i> Name </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
                                class="form-control @error('name') is-invalid @enderror" placeholder="Product Name" required>
                            <div class="invalid-feedback" id="name-error">@error('name') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-tag"></i> Category </label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="category_id-error">@error('category_id') {{ $message }} @enderror</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-bar-chart"></i> Quantity </label>
                            <input type="number" min="0" name="quantity" id="quantity" value="{{ old('quantity', $currentStock) }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="0" required>
                            <div class="invalid-feedback" id="quantity-error">@error('quantity') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box-seam"></i> Stock</label>
                            <select name="availability" id="availability" class="form-select @error('availability') is-invalid @enderror">
                                <option value="">Select Stock Status</option>
                                <option value="in_stock" @selected(old('availability', $product->availability) == 'in_stock')>In Stock</option>
                                <option value="out_of_stock" @selected(old('availability', $product->availability) == 'out_of_stock')>Out of Stock</option>
                            </select>
                            <div class="invalid-feedback" id="availability-error">@error('availability') {{ $message }} @enderror</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-hash"></i> Serial No</label>
                            <input type="text" name="serial_no" id="serial_no" value="{{ old('serial_no', $product->serial_no) }}"
                                class="form-control @error('serial_no') is-invalid @enderror" placeholder="Serial Number">
                            <div class="invalid-feedback" id="serial_no-error">@error('serial_no') {{ $message }} @enderror</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-bullseye"></i> Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="">Select Status</option>
                                <option value="active" @selected(old('status', $product->status) == 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $product->status) == 'inactive')>Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="status-error">@error('status') {{ $message }} @enderror</div>
                        </div>

                        <!-- Full Width -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-pencil-square"></i> Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                rows="3" placeholder="Product description">{{ old('description', $product->description) }}</textarea>
                            <div class="invalid-feedback" id="description-error">@error('description') {{ $message }} @enderror</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('products.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
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
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/product.js') }}"></script>
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const icon = document.getElementById(previewId.replace('Preview', 'Icon'));
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    icon.classList.add('d-none');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endpush
