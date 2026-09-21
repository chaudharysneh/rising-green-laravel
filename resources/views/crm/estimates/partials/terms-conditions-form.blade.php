<div class="mt-3">
            <label for="attach_file" class="form-label fw-semibold">Attach File to Estimate</label>
            <input type="file" name="attach_file" id="attach_file"
                class="form-control @error('attach_file') is-invalid @enderror"
                accept=".pdf,.doc,.docx,.xls,.xlsx">
            <div class="invalid-feedback" id="attach_file-error">
                @error('attach_file')
                    {{ $message }}
                @enderror
            </div>
            <div class="form-text">Upload one PDF, Word, or Excel file.</div>
            @if (!empty($estimate->attach_file))
                <div class="mt-2 small">
                    <a href="{{ Storage::url($estimate->attach_file) }}" target="_blank" rel="noopener"
                        class="text-primary">View existing file</a>
                </div>
            @endif
</div>
<div class="mt-3">
    <label for="terms_conditions" class="form-label fw-semibold">Terms &amp; Conditions</label>
    <textarea name="terms_conditions" id="terms_conditions" rows="9" maxlength="10000"
        class="form-control @error('terms_conditions') is-invalid @enderror"
        style="min-height: 140px;height:175px;"
        placeholder="Enter the terms and conditions for this estimate...">{{ old('terms_conditions', $estimate->terms_conditions ?? '') }}</textarea>
    <div class="invalid-feedback" id="terms_conditions-error">
        @error('terms_conditions')
            {{ $message }}
        @enderror
    </div>
</div>
