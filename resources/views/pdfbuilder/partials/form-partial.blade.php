<div class="card shadow-sm border-0 followup-form-card overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h4 mb-1 fw-semibold">Manage PDF Template</h1>
                <p class="text-muted small mb-0">Create or edit your custom PDF template record.</p>
            </div>
            <a href="{{ route('pdfbuilder.index') }}" class="btn btn-dark-blue">
                <i class="fa-solid fa-angle-left pe-2"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    <div class="card-body p-3 p-md-4">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="pdf-builder-form" class="needs-validation pdf-builder-form" novalidate>
            @csrf
            @if(isset($edit_mode))
                <input type="hidden" name="id" value="{{ $template->id }}">
            @endif

            <div class="row g-3 g-md-4">
                <!-- Template Name -->
                <div class="col-12 col-md-12 mb-2">
                    <label class="form-label fw-medium d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-signature text-primary"></i> Template Name 
                    </label>
                    <input type="text" name="template_name" class="form-control" 
                           value="{{ $template->template_name ?? old('template_name') }}" required placeholder="Enter template name">
                    <div class="invalid-feedback" id="template_name-error">Template name is required.</div>
                </div>

                <!-- Header Image -->
                <div class="col-12 col-md-12 mb-2">
                    <label class="form-label fw-medium d-flex align-items-center gap-2">
                        <i class="fa-solid fa-image text-primary"></i> Header Image (First Page)
                    </label>
                    <div class="input-group">
                        <input type="file" name="first_img" id="header_img_input" class="form-control" accept="image/*">
                    </div>
                    <p class="text-muted small mt-1 mb-0">If empty, default header will be used.</p>
                    <div class="mt-2" id="header_img_preview_container">
                        @if(isset($template) && $template->first_img)
                            @php
                                $headerImagePath = $template->first_img;
                                // Ensure path starts with /
                                if (!str_starts_with($headerImagePath, '/')) {
                                    $headerImagePath = '/' . $headerImagePath;
                                }
                            @endphp
                            <img id="header_img_preview" src="{{ asset($headerImagePath) }}" class="img-thumbnail rounded-3" style="max-height: 80px;">
                        @else
                            <img id="header_img_preview" src="#" class="img-thumbnail rounded-3" style="max-height: 80px; display: none;">
                        @endif
                    </div>
                </div>
            </div>

            <hr class="my-4 opacity-50">

            <!-- Before Quotation Section -->
            <div class="mb-5">
                <h5 class="text-primary fw-bold mb-4 d-flex align-items-center gap-2">Before Quotation :</h5>
                <div id="before-blocks-container">
                    @foreach($before_blocks ?? [] as $index => $block)
                        @php
                            $beforeImagePreview = $block['image_url'] ?? '';
                        @endphp
                        <div class="card mb-4 border shadow-none block-item position-relative rounded-4 bg-light bg-opacity-50">
                            <div class="card-body p-4 pt-5">
                                <button type="button" class="btn btn-danger btn-sm remove-block position-absolute rounded-pill px-3" style="top: 15px; right: 15px;">
                                    <i class="fa-solid fa-trash-can me-1"></i> Remove
                                </button>
                                
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-image text-primary"></i> Image
                                        </label>
                                        <div class="upload-drag-area p-4 border border-dashed rounded-4 text-center bg-white cursor-pointer position-relative">
                                            <input type="file" name="before_image[{{ $block['id'] ?? $index }}]" class="form-control d-none file-input-capture" accept="image/*">
                                            <input type="hidden" name="before_id[]" value="{{ $block['id'] ?? $index }}">
                                            @if(!empty($beforeImagePreview))
                                                <input type="hidden" name="before_image_old[{{ $block['id'] ?? $index }}]" value="{{ $beforeImagePreview }}">
                                            @endif
                                            
                                            <div class="upload-placeholder {{ !empty($beforeImagePreview) ? 'd-none' : '' }}">
                                                <i class="fa-solid fa-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                                <p class="mb-0 text-muted small">Drag & drop files or <span class="text-primary fw-bold">Browse</span></p>
                                            </div>
                                            <div class="preview-area {{ empty($beforeImagePreview) ? 'd-none' : '' }}">
                                                <img src="{{ $beforeImagePreview }}" class="img-fluid rounded-3 mb-2" style="max-height: 120px;">
                                                <p class="filename mb-0 small text-muted text-truncate">{{ basename($beforeImagePreview) }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-heading text-primary"></i> Title
                                        </label>
                                        <input type="text" name="before_title[]" class="form-control" value="{{ $block['title'] ?? '' }}" placeholder="Enter title">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-align-left text-primary"></i> Content
                                        </label>
                                        <textarea name="before_content[]" id="editor_before_{{ $index }}" class="form-control ckeditor-textarea">{{ $block['content'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-before-block" class="btn btn-outline-dark-blue">
                    <i class="fa-solid fa-plus me-1"></i> Add Before Quotation Block
                </button>
            </div>

            <hr class="my-4 opacity-50">

            <!-- After Quotation Section -->
            <div class="mb-5">
                <h5 class="text-primary fw-bold mb-4 d-flex align-items-center gap-2">After Quotation :</h5>
                <div id="after-blocks-container">
                    @foreach($after_blocks ?? [] as $index => $block)
                        @php
                            $afterImagePreview = $block['image_url'] ?? '';
                        @endphp
                        <div class="card mb-4 border shadow-none block-item position-relative rounded-4 bg-light bg-opacity-50">
                            <div class="card-body p-4 pt-5">
                                <button type="button" class="btn btn-danger btn-sm remove-block position-absolute rounded-pill px-3" style="top: 15px; right: 15px;">
                                    <i class="fa-solid fa-trash-can me-1"></i> Remove
                                </button>
                                
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-image text-primary"></i> Image
                                        </label>
                                        <div class="upload-drag-area p-4 border border-dashed rounded-4 text-center bg-white cursor-pointer position-relative">
                                            <input type="file" name="after_image[{{ $block['id'] ?? $index }}]" class="form-control d-none file-input-capture" accept="image/*">
                                            <input type="hidden" name="after_id[]" value="{{ $block['id'] ?? $index }}">
                                            @if(!empty($afterImagePreview))
                                                <input type="hidden" name="after_image_old[{{ $block['id'] ?? $index }}]" value="{{ $afterImagePreview }}">
                                            @endif

                                            <div class="upload-placeholder {{ !empty($afterImagePreview) ? 'd-none' : '' }}">
                                                <i class="fa-solid fa-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                                <p class="mb-0 text-muted small">Drag & drop files or <span class="text-primary fw-bold">Browse</span></p>
                                            </div>
                                            <div class="preview-area {{ empty($afterImagePreview) ? 'd-none' : '' }}">
                                                <img src="{{ $afterImagePreview }}" class="img-fluid rounded-3 mb-2" style="max-height: 120px;">
                                                <p class="filename mb-0 small text-muted text-truncate">{{ basename($afterImagePreview) }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-heading text-primary"></i> Title
                                        </label>
                                        <input type="text" name="after_title[]" class="form-control" value="{{ $block['title'] ?? '' }}" placeholder="Enter title">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-align-left text-primary"></i> Content
                                        </label>
                                        <textarea name="after_content[]" id="editor_after_{{ $index }}" class="form-control ckeditor-textarea">{{ $block['content'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-after-block" class="btn btn-outline-dark-blue">
                    <i class="fa-solid fa-plus me-2"></i> Add After Quotation Block
                </button>
            </div>

            <hr class="my-5 opacity-50">

            <!-- Thank You Page Section -->
            <div class="mb-5">
                <h5 class="text-primary fw-bold mb-4 d-flex align-items-center gap-2">
                    Thank You Page Section :
                </h5>
                <div class="card border shadow-none rounded-4 bg-light bg-opacity-50">
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-12 col-md-6 mb-2">
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input" type="checkbox" name="footer_active" id="footer_active" value="1" {{ ($footer['active'] ?? 1) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="footer_active">Show Thank You Page</label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-solid fa-heading text-primary"></i> Thank You Title
                                </label>
                                <input type="text" name="footer_title" class="form-control" value="{{ $footer['title'] ?? 'THANK YOU' }}" placeholder="Enter title (e.g. THANK YOU)">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-medium d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-solid fa-align-left text-primary"></i> Thank You Message
                                </label>
                                <textarea name="footer_sub_title" id="editor_footer" class="form-control ckeditor-textarea">{{ $footer['sub_title'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Sections -->
            {{-- <div class="accordion mb-5" id="moreSectionsAccordion">
                <div class="accordion-item border rounded-4 overflow-hidden mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold text-dark-blue p-4 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMore">
                            <i class="fa-solid fa-circle-plus me-2"></i> Additional Configuration (Company Info, Timeline, etc.)
                        </button>
                    </h2>
                    <div id="collapseMore" class="accordion-collapse collapse" data-bs-parent="#moreSectionsAccordion">
                        <div class="accordion-body p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Form Title</label>
                                    <input type="text" name="form_title" class="form-control" value="{{ $template->form_title ?? old('form_title') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

            <!-- Submit -->
            <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                <a href="{{ route('pdfbuilder.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btnSpinner"></span>
                    <span id="btnText">Submit</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . '/js/pdfbuilder.js') }}"></script>
@endpush
