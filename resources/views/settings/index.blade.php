@extends('layouts.app')

@section('page_title', 'Settings')

@push('styles')
    <link rel="stylesheet"
        href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/users.css') }}?v={{ filemtime(public_path('css/users.css')) }}">
    <link rel="stylesheet"
        href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/settings.css') }}?v={{ filemtime(public_path('css/settings.css')) }}">
@endpush

@section('content')
    @php
        $logoPath = $settings['company_logo_path']->value ?? null;
        $logoUrl = $logoPath && Storage::disk('public')->exists($logoPath)
            ? asset($logoPath)
            : 'https://crm-demo.fableadtech.com/public/assets/img/logos/fabcrmlogo.png';
    @endphp

    <div class="container-fluid px-0 settings-shell">
        <div class="d-flex justify-content-between align-items-center mb-3 settings-page-head">
            <div>
                <h1 class="h4 mb-1">Settings</h1>
                <p class="text-muted small mb-0">Manage SMTP, keys, WhatsApp and integration preferences.</p>
            </div>
        </div>

        <div class="settings-tabs-wrap">
            <ul class="nav settings-main-tabs flex-wrap" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab"
                        data-bs-target="#smtp" type="button" role="tab">Email SMTP</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#keys" type="button" role="tab">Keys</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#whatsapp-configure" type="button" role="tab">WhatsApp Configure Settings</button>
                </li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#integrations" type="button" role="tab">Integrations</button></li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="smtp" role="tabpanel">
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data"
                    id="smtpSettingsForm">
                    @csrf
                    @method('PUT')
                    <div class="settings-panel">
                        <div class="settings-panel-head">Email SMTP Settings</div>
                        <div class="settings-panel-body">
                            <div class="row g-3 settings-smtp-grid">
                                <div class="col-md-6"><label class="form-label">SMTP Host</label><input type="text"
                                        name="mail_host" class="form-control"
                                        value="{{ $settings['mail_host']->value ?? '' }}" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-6"><label class="form-label">SMTP Port</label><input type="number"
                                        name="mail_port" class="form-control"
                                        value="{{ $settings['mail_port']->value ?? '587' }}" placeholder="587"></div>
                                <div class="col-md-6"><label class="form-label">SMTP Username</label><input type="email"
                                        name="mail_username" class="form-control"
                                        value="{{ $settings['mail_username']->value ?? '' }}" placeholder="you@gmail.com">
                                </div>
                                <div class="col-md-6"><label class="form-label">SMTP Password</label><input type="password"
                                        name="mail_password" class="form-control"
                                        value="{{ $settings['mail_password']->value ?? '' }}" placeholder="Enter password">
                                </div>
                                <div class="col-md-6"><label class="form-label">Encryption</label><select
                                        name="mail_encryption" class="form-select">
                                        <option value="tls" {{ ($settings['mail_encryption']->value ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ ($settings['mail_encryption']->value ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="" {{ ($settings['mail_encryption']->value ?? '') == '' ? 'selected' : '' }}>None</option>
                                    </select></div>
                                <div class="col-md-6"><label class="form-label">From Name</label><input type="text"
                                        name="mail_from_name" class="form-control"
                                        value="{{ $settings['mail_from_name']->value ?? config('app.name', 'CRM') }}"
                                        placeholder="Company Name"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                                <span id="smtpSettingsStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-primary settings-submit-btn">Save SMTP
                                    Settings</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="keys" role="tabpanel">
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data"
                    id="keysSettingsForm">
                    @csrf
                    @method('PUT')
                    <div class="settings-panel">
                        <div class="settings-panel-head">Keys</div>
                        <div class="settings-panel-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Firebase Key</label>
                                    <input type="file" name="firebase_key_file" class="form-control settings-file-input">
                                    <div class="settings-inline-help mt-2">Upload your Firebase service credential file if
                                        required.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Google Client ID</label>
                                    <input type="text" name="google_client_id" class="form-control"
                                        value="{{ $settings['google_client_id']->value ?? '' }}"
                                        placeholder="Google OAuth Client ID">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Google Client Secret</label>
                                    <input type="text" name="google_client_secret" class="form-control"
                                        value="{{ $settings['google_client_secret']->value ?? '' }}"
                                        placeholder="Google OAuth Client Secret">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Google Redirect URI</label>
                                    <input type="text" name="google_redirect_uri" class="form-control"
                                        value="{{ $settings['google_redirect_uri']->value ?? '' }}"
                                        placeholder="{{ route('google.callback') }}">
                                    <div class="settings-inline-help mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Recommended URI for Google Console: <code class="bg-light px-1">{{ route('google.callback') }}</code>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="d-flex justify-content-between align-items-center flex-wrap gap-3 settings-keys-actions">
                                <span id="keysSettingsStatus" class="settings-form-status"></span>
                                <button type="submit" class="btn btn-primary settings-submit-btn"><i
                                        class="bi bi-send-fill me-1"></i> Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="whatsapp-configure" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head">WhatsApp Configure Settings</div>
                    <div class="settings-panel-body">
                        <div class="mb-4">
                            <div>
                                <h4 class="fw-bold mb-1">Configure your WhatsApp API settings <span
                                        class="settings-status-badge settings-inline-status"><span
                                            class="settings-status-dot"></span>Connected</span></h4>
                                <div class="text-muted">Enter your WhatsApp App details and WhatsApp Business credentials.
                                </div>
                            </div>
                        </div>

                        <ul class="nav settings-subtabs mb-4" id="waSettingsTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="wa-config-tab"
                                    data-bs-toggle="pill" data-bs-target="#wa-config-pane" type="button"
                                    role="tab">Configuration</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="wa-templates-tab"
                                    data-bs-toggle="pill" data-bs-target="#wa-templates-pane" type="button"
                                    role="tab">Message Templates</button></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="wa-config-pane" role="tabpanel">
                                <div class="alert alert-primary d-flex align-items-start mb-4">
                                    <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                                    <div><strong>Credentials are stored locally.</strong>
                                        <div>Use backend storage for production and update credentials whenever you rotate
                                            tokens.</div>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-app me-2"></i>WhatsApp App ID
                                            </label>
                                        <input type="text" class="form-control" id="wa_app_id">
                                        <div class="invalid-feedback" id="wa_app_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i
                                                class="bi bi-shield-lock-fill me-2"></i>WhatsApp App Secret <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="wa_app_secret">
                                        <div class="invalid-feedback" id="wa_app_secret_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-telephone-fill me-2"></i>Phone
                                            Number ID </label>
                                        <input type="text" class="form-control" id="wa_phone_number_id">
                                        <div class="invalid-feedback" id="wa_phone_number_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-building-fill me-2"></i>WhatsApp
                                            Business Account ID </label>
                                        <input type="text" class="form-control" id="wa_business_account_id">
                                        <div class="invalid-feedback" id="wa_business_account_id_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-key-fill me-2"></i>Access Token
                                            </label>
                                        <input type="text" class="form-control" id="wa_access_token">
                                        <div class="invalid-feedback" id="wa_access_token_error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="bi bi-link-45deg me-2"></i>Webhook
                                            URL</label>
                                        <input type="text" class="form-control" id="wa_webhook_url">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4">
                                    <span class="small" id="wa_status_msg"></span>
                                    <button type="button" class="btn btn-primary settings-submit-btn" id="wa_save_btn"><i
                                            class="bi bi-floppy-fill me-1"></i> Save Configuration</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="wa-templates-pane" role="tabpanel">
                                <div class="wa-templates-title">WhatsApp Message Templates</div>
                                <div class="wa-templates-subtitle">View and manage your WhatsApp message templates from
                                    WhatsApp. Stored in database; refresh to sync from API.</div>

                                <div class="wa-templates-toolbar">
                                    <div class="wa-templates-toolbar-left">
                                        <button type="button" class="btn btn-primary wa-templates-refresh-btn"
                                            id="wa_templates_refresh"><i class="bi bi-arrow-repeat me-1"></i> Refresh
                                            Templates</button>
                                        <div class="wa-templates-search-wrap"><input type="text" class="form-control"
                                                id="wa_templates_search" placeholder="Search templates..."></div>
                                    </div>
                                    <div class="wa-templates-show"><span>Show</span><select id="wa_templates_show"
                                            class="form-select">
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select><span>entries</span></div>
                                </div>

                                <div class="table-responsive wa-templates-table-wrap">
                                    <table class="table align-middle mb-0" id="wa_templates_table"
                                        data-module-options='@json($whatsappModuleOptions)'>
                                        <thead class="table-light">
                                            <tr>
                                                <th>Templates</th>
                                                <th class="d-none d-md-table-cell" style="width: 30%;">Use For Module</th>
                                                <th class="d-none d-md-table-cell" style="width: 20%;">Status</th>
                                                <th class="d-none d-md-table-cell" style="width: 20%;">Active/Inactive</th>
                                                <th class="text-center d-md-none" style="width: 80px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($whatsappTemplates as $template)
                                                <tr data-template-row="main" data-template-id="{{ $template->id }}">
                                                    <td class="wa-templates-name">{{ $template->name }}</td>
                                                    <td class="d-none d-md-table-cell">
                                                        <select class="form-select form-select-sm wa-template-module-select"
                                                            data-template-id="{{ $template->id }}">
                                                            <option value="">-- Select --</option>
                                                            @foreach($whatsappModuleOptions as $key => $label)
                                                                <option value="{{ $key }}" {{ $template->use_for_module === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><span class="wa-templates-status-badge">{{ $template->status }}</span>
                                                    </td>
                                                    <td class="d-none d-md-table-cell"><select class="form-select form-select-sm wa-template-status-select"
                                                            data-template-id="{{ $template->id }}">
                                                            <option value="1" {{ $template->is_active ? 'selected' : '' }}>
                                                                Active</option>
                                                            <option value="0" {{ !$template->is_active ? 'selected' : '' }}>
                                                                Inactive</option>
                                                        </select></td>
                                                    <td class="text-center d-md-none">
                                                        <button type="button" class="btn-user-expand"
                                                            data-template-id="{{ $template->id }}">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="details-row d-md-none border-0" data-template-row="details"
                                                    id="wa-template-details-{{ $template->id }}" style="display: none;">
                                                    <td colspan="5" class="p-0">
                                                        <div class="details-content">
                                                            <div class="row g-3">
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-puzzle-piece"></i> Use For Module :</div>
                                                                    <div class="expand-value">
                                                                        <select class="form-select form-select-sm wa-template-module-select"
                                                                            data-template-id="{{ $template->id }}">
                                                                            <option value="">-- Select --</option>
                                                                            @foreach($whatsappModuleOptions as $key => $label)
                                                                                <option value="{{ $key }}" {{ $template->use_for_module === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-signal"></i> Status :</div>
                                                                    <div class="expand-value"><span class="wa-templates-status-badge">{{ $template->status }}</span></div>
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                                    <div class="expand-label"><i class="fa-solid fa-toggle-on"></i> Active / Inactive :</div>
                                                                    <div class="expand-value">
                                                                        <select class="form-select form-select-sm wa-template-status-select"
                                                                            data-template-id="{{ $template->id }}">
                                                                            <option value="1" {{ $template->is_active ? 'selected' : '' }}>Active</option>
                                                                            <option value="0" {{ !$template->is_active ? 'selected' : '' }}>Inactive</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">No WhatsApp message
                                                        templates found in database.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3"
                                    id="wa_templates_pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="integrations" role="tabpanel">
                <div class="settings-panel">
                    <div class="settings-panel-head">Integrations</div>
                    <div class="settings-panel-body">
                        <div class="integrations-accordion" id="integrationsAccordion">
                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#facebookAdsPanel" aria-expanded="false"
                                    aria-controls="facebookAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon facebook"><i class="bi bi-facebook"></i></span>
                                        <span>Facebook Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="facebookAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy">This integration lets you connect your Facebook Ads
                                            account to manage campaigns and track conversions.</div>

                                        <div class="integration-inner-card">
                                            <button class="integration-inner-toggle" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#facebookConnectedPages" aria-expanded="false"
                                                aria-controls="facebookConnectedPages">
                                                <span>Connected Pages</span>
                                                <i class="bi bi-chevron-down integration-inner-caret"></i>
                                            </button>
                                            <div id="facebookConnectedPages" class="collapse integration-inner-collapse">
                                                <div class="table-responsive">
                                                    <table class="table integration-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 48px;">#</th>
                                                                <th style="width: 80px;">Image</th>
                                                                <th style="width: 240px;">Page ID</th>
                                                                <th>Page Name</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>1</td>
                                                                <td><span class="integration-mini-icon facebook"><i
                                                                            class="bi bi-facebook"></i></span></td>
                                                                <td>681579935046274</td>
                                                                <td>Hello test bhavvik</td>
                                                            </tr>
                                                            <tr>
                                                                <td>2</td>
                                                                <td><span class="integration-mini-icon whatsapp"><i
                                                                            class="bi bi-megaphone-fill"></i></span></td>
                                                                <td>727332373793917</td>
                                                                <td>Testing page</td>
                                                            </tr>
                                                            <tr>
                                                                <td>3</td>
                                                                <td><span class="integration-mini-icon analytics"><i
                                                                            class="bi bi-bar-chart-fill"></i></span></td>
                                                                <td>909114223345667</td>
                                                                <td>Demo Campaign Hub</td>
                                                            </tr>
                                                            <tr>
                                                                <td>4</td>
                                                                <td><span class="integration-mini-icon audience"><i
                                                                            class="bi bi-people-fill"></i></span></td>
                                                                <td>555666777888999</td>
                                                                <td>Demo Lead Gen Page</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="integration-action">
                                            <a href="https://www.facebook.com/login.php" target="_blank"
                                                rel="noopener noreferrer" class="btn btn-primary settings-submit-btn"><i
                                                    class="bi bi-facebook me-1"></i> Connect with Facebook Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#googleAdsPanel" aria-expanded="false" aria-controls="googleAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon google">G</span>
                                        <span>Google Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="googleAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy mb-3">Connect your Google Ads workspace to sync
                                            campaigns, account IDs and conversion reporting.</div>
                                        <div class="d-flex justify-content-end">
                                            <a href="https://myaccount.google.com/" target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-outline-primary rounded-pill px-4">Connect Google Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="integration-card">
                                <button class="integration-toggle" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#instagramAdsPanel" aria-expanded="false"
                                    aria-controls="instagramAdsPanel">
                                    <span class="integration-toggle-left">
                                        <span class="integration-icon instagram"><i class="bi bi-instagram"></i></span>
                                        <span>Instagram Ads</span>
                                    </span>
                                    <i class="bi bi-chevron-down integration-caret"></i>
                                </button>
                                <div id="instagramAdsPanel" class="collapse integration-panel"
                                    data-bs-parent="#integrationsAccordion">
                                    <div class="integration-body">
                                        <div class="integration-copy mb-3">Connect Instagram Ads to manage campaign sources
                                            and social lead capture from a single place.</div>
                                        <div class="d-flex justify-content-end">
                                            <a href="https://www.instagram.com/accounts/login/" target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-outline-primary rounded-pill px-4">Connect Instagram Ads</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.settingsPageConfig = {
                apiSettingsIndex: @json(route('api.settings.index')),
                apiSettingsUpdate: @json(route('api.settings.update')),
            };
        </script>
        <script
            src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'assets/js/setting.js') }}?v={{ filemtime(public_path('assets/js/setting.js')) }}"></script>
        <script
            src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/settings-page.js') }}?v={{ filemtime(public_path('js/settings-page.js')) }}"></script>
    @endpush
@endsection
