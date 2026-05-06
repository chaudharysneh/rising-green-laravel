@extends('layouts.app')

@section('page_title', 'Purchases - Create')

@section('content')
    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden purchase-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Material IN</h1>
                        <p class="text-muted small mb-0">Create a new material IN entry.</p>
                    </div>
                    <a href="{{ route('purchases.index') }}" class="btn btn-dark-blue back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/v1/purchases" enctype="multipart/form-data"
                    class="needs-validation ajax-purchase-form" novalidate id="purchaseCreateForm">
                    @csrf

                    <div class="row g-3">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-person"></i> Select Vendor </label>
                            <select name="customer_id" id="customer_id"
                                class="form-select @error('customer_id') is-invalid @enderror" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('customer_id') == $vendor->id)>
                                        {{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a vendor!</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-calendar"></i> IN Date </label>
                            <input type="date" name="invoice_date" id="invoice_date"
                                value="{{ old('invoice_date', date('Y-m-d')) }}"
                                class="form-control @error('invoice_date') is-invalid @enderror" required>
                            <div class="invalid-feedback">Please enter invoice date!</div>
                        </div>

                        <!-- Left Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-box"></i> Product Name </label>
                            <select name="product_id" id="product_id"
                                class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                        {{ $product->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a product.</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-bar-chart"></i> Qty </label>
                            <input type="number" min="0" name="quantity" id="quantity" value="{{ old('quantity') }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="0" required>
                            <div class="invalid-feedback">Please enter a valid quantity.</div>
                        </div>

                        <!-- Full Width -->
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-chat-left-text"></i> Comment</label>
                            <textarea name="comment" id="comment"
                                class="form-control @error('comment') is-invalid @enderror" rows="3"
                                placeholder="Add any comments...">{{ old('comment') }}</textarea>
                            <div class="invalid-feedback">@error('comment') {{ $message }} @enderror</div>
                        </div>

                        <!-- Hidden Fields -->
                        <input type="hidden" name="price" id="price" value="0">
                        <input type="hidden" name="gst" id="gst" value="0">
                        <input type="hidden" name="discount" id="discount" value="0">
                        <input type="hidden" name="total" id="total" value="0">
                        <input type="hidden" name="status" id="status" value="pending">
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('purchases.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.getElementById('purchaseCreateForm').addEventListener('submit', function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const formData = new FormData(this);

            const submitBtn = document.getElementById('submitBtn');
            const btnSpinner = document.getElementById('btnSpinner');
            const btnText = document.getElementById('btnText');
            submitBtn.disabled = true;
            btnSpinner.classList.remove('d-none');

            $.ajax({
                url: '/api/v1/purchases',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', response.message || 'Material IN created successfully.', 'Success!', '/purchases');
                        } else {
                            alert(response.message || 'Material IN created successfully.');
                            window.location.href = '/purchases';
                        }
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(field => {
                            const input = document.getElementById(field);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.nextElementSibling;
                                if (feedback && feedback.classList.contains('invalid-feedback')) {
                                    feedback.textContent = xhr.responseJSON.errors[field][0];
                                    feedback.style.display = 'block';
                                }
                            }
                        });
                    } else {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', xhr.responseJSON?.message || 'Something went wrong.');
                        } else {
                            alert(xhr.responseJSON?.message || 'Something went wrong.');
                        }
                    }
                    submitBtn.disabled = false;
                    btnSpinner.classList.add('d-none');
                },
            });
        });
    </script>
@endpush