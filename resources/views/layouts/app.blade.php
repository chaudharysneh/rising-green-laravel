<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Fablead CRM</title>

    <!-- Google Fonts (Outfit)     -->
    <link rel="icon" type="image/png" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'images/template/crmfavicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/crm-layout.css') }}?v={{ filemtime(public_path('css/crm-layout.css')) }}">
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/buttons.css') }}?v={{ filemtime(public_path('css/buttons.css')) }}">
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/main.css') }}?v={{ filemtime(public_path('css/main.css')) }}">
    <link rel="stylesheet" href="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'css/chatbot.css') }}?v={{ filemtime(public_path('css/chatbot.css')) }}">
    <style>
        .dashboard-plan-switcher {
            align-items: center;
            gap: .65rem;
            margin-right: .25rem;
        }

        .dashboard-plan-btn {
            border: 1px solid #f4c4a6;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7f0 0%, #ffe7d8 100%);
            color: #d4631b;
            font-weight: 700;
            font-size: .88rem;
            min-height: 42px;
            padding: .65rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            white-space: nowrap;
            box-shadow: 0 10px 20px rgba(234, 118, 45, .12);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
        }

        .dashboard-plan-btn span {
            white-space: nowrap;
        }

        .dashboard-plan-btn:hover,
        .dashboard-plan-btn:focus {
            color: #b95516;
            border-color: #f39a63;
            background: linear-gradient(135deg, #fff1e6 0%, #ffd9c0 100%);
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(234, 118, 45, .18);
        }

        .dashboard-plan-btn.active {
            background: linear-gradient(135deg, #ff8c47 0%, #ff6a3d 100%);
            border-color: #ff7d3e;
            color: #fff;
            box-shadow: 0 14px 28px rgba(255, 106, 61, .28);
        }

        .dashboard-plan-btn--premium {
            border-color: #cdd6f7;
            background: linear-gradient(135deg, #f6f8ff 0%, #e7edff 100%);
            color: #3551b6;
            box-shadow: 0 10px 20px rgba(53, 81, 182, .12);
        }

        .dashboard-plan-btn--premium:hover,
        .dashboard-plan-btn--premium:focus {
            color: #2443aa;
            border-color: #9eb1f6;
            background: linear-gradient(135deg, #eef2ff 0%, #dce6ff 100%);
            box-shadow: 0 14px 24px rgba(53, 81, 182, .18);
        }

        .dashboard-plan-btn--premium.active {
            background: linear-gradient(135deg, #3e63dd 0%, #2846ad 100%);
            border-color: #3154cc;
            color: #fff;
            box-shadow: 0 14px 28px rgba(40, 70, 173, .28);
        }

        .dashboard-plan-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }

        .dashboard-plan-modal__header {
            background: linear-gradient(135deg, #ff8a45 0%, #ff6b42 100%);
            color: #fff;
            padding: 1.1rem 1.35rem;
        }

        .dashboard-plan-modal__header.plan-basic {
            background: linear-gradient(135deg, #ff8a45 0%, #ff6b42 100%);
        }

        .dashboard-plan-modal__header.plan-premium {
            background: linear-gradient(135deg, #355fdf 0%, #243e9d 100%);
        }

        .dashboard-plan-modal__header .btn-close {
            filter: invert(1);
            opacity: .8;
        }

        .dashboard-plan-modal__pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 170px;
            border-radius: 999px;
            background: #fff3ea;
            color: #f06529;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: .7rem 1.2rem;
        }

        .dashboard-plan-modal__pill--premium {
            background: #edf2ff;
            color: #3154cc;
        }

        .dashboard-plan-modal__details {
            display: grid;
            gap: 1rem;
        }

        .dashboard-plan-modal__row {
            display: flex;
            align-items: center;
            gap: .7rem;
            color: #334155;
            font-size: 1rem;
        }

        .dashboard-plan-modal__icon {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff1e8;
            color: #f97316;
            flex-shrink: 0;
        }

        .dashboard-plan-modal__icon--status {
            background: #eaf8ef;
            color: #16a34a;
        }

        .dashboard-plan-modal__message {
            color: #475569;
            font-size: .98rem;
        }

        .dashboard-plan-modal__cta {
            min-width: 138px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #ff8a45 0%, #ff6b42 100%);
            color: #fff;
            font-weight: 700;
            padding: .7rem 1.1rem;
            box-shadow: 0 12px 24px rgba(255, 107, 66, .22);
        }

        .dashboard-plan-modal__cta:hover,
        .dashboard-plan-modal__cta:focus {
            color: #fff;
            transform: translateY(-1px);
        }
    </style>
    @stack('styles')

    <!-- Theme Management -->
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/theme.js') }}?v={{ filemtime(public_path('js/theme.js')) }}"></script>
</head>

<body class="{{ request()->routeIs('*.create', '*.edit') ? 'crm-form-page' : '' }}">
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;"></div>
    <div id="app" class="d-flex min-vh-100">
        @auth
            @php
                $authUser = auth()->user();
                $userRoleLabel = $authUser?->isAdmin()
                    ? 'Administrator'
                    : ($authUser?->roles->first()?->name
                        ? \Illuminate\Support\Str::headline($authUser->roles->first()->name)
                        : ($authUser?->job_title ?: 'Staff'));
                $planOwner = null;
                if ($authUser) {
                    if ($authUser->isAdmin()) {
                        $planOwner = $authUser;
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users', 'parent_id') && !empty($authUser->parent_id)) {
                        $planOwner = \App\Models\User::find($authUser->parent_id) ?: $authUser;
                    } else {
                        $planOwner = $authUser;
                    }
                }

                $currentSubscriptionPlan = null;
                $currentSubscriptionAssignment = null;
                $currentStaffCount = 0;
                if ($planOwner && \Illuminate\Support\Facades\Schema::hasTable('subscription_user_plan')) {
                    $currentSubscriptionAssignment = \Illuminate\Support\Facades\DB::table('subscription_user_plan')
                        ->where('user_id', $planOwner->id)
                        ->orderByDesc('id')
                        ->first();

                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'parent_id')) {
                        $currentStaffCount = \App\Models\User::query()->nonAdmin()->where('parent_id', $planOwner->id)->count();
                    }

                    if ($currentSubscriptionAssignment && class_exists(\App\Models\SubscriptionPlan::class)) {
                        $currentSubscriptionPlan = \App\Models\SubscriptionPlan::find($currentSubscriptionAssignment->subscription_id);
                    }
                }
            @endphp
            <!-- Sidebar -->
            <aside class="crm-sidebar shadow-sm" id="sidenav-main" style="min-width: 260px">
                @php
                    $mainLogoPath = \App\Models\Setting::where('key', 'company_logo_path')->value('value');
                    $mainLogoUrl = $mainLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($mainLogoPath)
                        ? route('profile.company_logo.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($mainLogoPath)
                        : url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'logo/fableadcrmLogo.png');
                @endphp
                <div class="sidenav-header">
                    <a class="navbar-brand m-0 d-flex flex-row align-items-center" href="{{ route('dashboard') }}">
                        <img src="{{ $mainLogoUrl }}" class="navbar-brand-img h-100" alt="main_logo">
                    </a>
                </div>

                <div class="sidenav-header" style="padding: 0px 8px;">
                    <div class="profile-card brand-card">
                        <img src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'images/template/crmfavicon.png') }}"
                            class="brand-logo-icon" alt="Fablead Logo">
                        <span class="brand-logo-text capitalize">{{ strtoupper($authUser?->name ?? 'FableadCRM') }}</span>
                    </div>
                </div>

                <nav class="navbar-nav overflow-x-hidden" id="sidebarMenu" style="padding: 0px 8px; padding-bottom:25px;">
                    <!-- Dashboard -->
                    <li class="nav-item mt-2">
                        <a class="nav-link ccc ddd @if(request()->routeIs('dashboard')) active @endif"
                            href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-tv me-2 text-primary"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Manage Staff -->
                    @if(auth()->user()?->isAdmin())
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('users.*')) active @endif"
                                href="{{ route('users.index') }}">
                                <i class="fa fa-users me-2 text-info"></i>
                                <span>Manage Staff</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()?->hasMatrixPermission('view_customers') || auth()->user()?->hasMatrixPermission('create_customers'))
                        <!-- Manage Customers -->
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('masters.customers.*')) active @endif"
                                href="{{ route('masters.customers.index') }}">
                                <i class="fa fa-users me-2 text-success"></i>
                                <span>Manage Customers</span>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->hasMatrixPermission('view_leads'))
                        <!-- Manage Leads -->
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('leads.*')) active @endif"
                                href="{{ route('leads.index') }}">
                                <i class="fa fa-bullhorn me-2 text-warning"></i>
                                <span>Manage Leads</span>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->hasMatrixPermission('view_followups'))
                        <!-- Manage Follow up -->
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('followups.*')) active @endif"
                                href="{{ route('followups.index') }}">
                                <i class="fa fa-user-tie me-2 text-secondary"></i>
                                <span>Manage Follow up</span>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->hasMatrixPermission('view_meetings'))
                        <!-- Manage Meetings -->
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('meetings.*')) active @endif"
                                href="{{ route('meetings.index') }}">
                                <i class="fa-solid fa-handshake me-2 text-success"></i>
                                <span>Manage Meetings</span>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->hasMatrixPermission('view_deals'))
                        <!-- Manage Deals -->
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('deals.*')) active @endif"
                                href="{{ route('deals.index') }}">
                                <i class="fa-solid fa-medal me-2 text-warning"></i>
                                <span>Manage Deals</span>
                            </a>
                        </li>
                    @endif

                    @if(
                        auth()->user()?->hasMatrixPermission('view_estimates') || auth()->user()?->hasMatrixPermission('create_estimates') ||
                        auth()->user()?->hasMatrixPermission('view_invoices') || auth()->user()?->hasMatrixPermission('create_invoices') ||
                        auth()->user()?->hasMatrixPermission('view_templates') || auth()->user()?->hasMatrixPermission('create_templates')
                    )
                        <li class="nav-item mt-2">
                            <a class="nav-link nav-link-collapse" data-bs-toggle="collapse"
                                href="#estimatesMenu" role="button"
                                aria-expanded="{{ request()->is('estimate') || request()->is('estimates') || request()->is('estimates/*') || request()->is('invoices*') || request()->is('pdfbuilder*') ? 'true' : 'false' }}">
                                <i class="fa-solid fa-file-invoice-dollar me-2"></i>
                                <span>Manage Estimates</span>

                                <i class="fa fa-chevron-down small sidebar-chevron"></i>
                            </a>

                            <div id="estimatesMenu"
                                class="collapse {{ request()->is('estimate') || request()->is('estimates') || request()->is('estimates/*') || request()->is('invoices*') || request()->is('pdfbuilder*') ? 'show' : '' }}"
                                data-bs-parent="#sidebarMenu">
                                <ul class="nav flex-column ms-3 mt-2">
                                    @if(auth()->user()?->hasMatrixPermission('view_estimates') || auth()->user()?->hasMatrixPermission('create_estimates'))
                                        <li><a class="nav-link {{ request()->is('estimate') || request()->is('estimates') || request()->is('estimates/*') ? 'active' : '' }}"
                                                href="{{ url('estimate') }}"><i class="fa-solid fa-file-lines me-2"></i>All Estimates</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_invoices') || auth()->user()?->hasMatrixPermission('create_invoices'))
                                        <li><a class="nav-link {{ request()->is('invoices*') ? 'active' : '' }}"
                                                href="{{ route('invoices.index') }}"><i class="fa-solid fa-file-lines me-2"></i>All Invoices</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_templates') || auth()->user()?->hasMatrixPermission('create_templates'))
                                        <li><a class="nav-link {{ request()->is('pdfbuilder*') ? 'active' : '' }}"
                                                href="{{ url('pdfbuilder') }}"><i class="fa-solid fa-file-lines me-2"></i>Templates</a></li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if(
                        auth()->user()?->hasMatrixPermission('view_sales') || auth()->user()?->hasMatrixPermission('create_sales') ||
                        auth()->user()?->hasMatrixPermission('view_purchases') || auth()->user()?->hasMatrixPermission('create_purchases') ||
                        auth()->user()?->hasMatrixPermission('view_inventory') || auth()->user()?->hasMatrixPermission('edit_inventory') ||
                        auth()->user()?->hasMatrixPermission('view_products') || auth()->user()?->hasMatrixPermission('create_products') ||
                        auth()->user()?->hasMatrixPermission('view_categories') || auth()->user()?->hasMatrixPermission('create_categories') ||
                        auth()->user()?->hasMatrixPermission('view_vendors') || auth()->user()?->hasMatrixPermission('create_vendors') ||
                        auth()->user()?->hasMatrixPermission('view_handover_persons') || auth()->user()?->hasMatrixPermission('create_handover_persons')
                    )
                        <li class="nav-item mt-2">
                            <a class="nav-link nav-link-collapse" data-bs-toggle="collapse"
                                href="#inventoryMenu" role="button"
                                aria-expanded="{{ request()->is('sales') || request()->is('sales/*') || request()->is('purchase') || request()->is('purchases') || request()->is('purchases/*') || request()->is('inventory') || request()->is('inventory/*') || request()->is('products') || request()->is('products/*') || request()->is('all-categories') || request()->is('all-categories/*') || request()->is('all-vendor') || request()->is('all-vendor/*') || request()->is('add-vendor') || request()->is('add-vendor/*') || request()->is('add-handover-person') ? 'true' : 'false' }}">
                                <i class="fa-solid fa-boxes-stacked me-2"></i>
                                <span>Manage Inventory</span>

                                <i class="fa fa-chevron-down small sidebar-chevron"></i>
                            </a>

                            <div id="inventoryMenu"
                                class="collapse {{ request()->is('sales') || request()->is('sales/*') || request()->is('purchase') || request()->is('purchases') || request()->is('purchases/*') || request()->is('inventory') || request()->is('inventory/*') || request()->is('products') || request()->is('products/*') || request()->is('all-categories') || request()->is('all-categories/*') || request()->is('all-vendor') || request()->is('all-vendor/*') || request()->is('add-vendor') || request()->is('add-vendor/*') || request()->is('add-handover-person') ? 'show' : '' }}"
                                data-bs-parent="#sidebarMenu">
                                <ul class="nav flex-column ms-3 mt-2">
                                    @if(auth()->user()?->hasMatrixPermission('view_sales') || auth()->user()?->hasMatrixPermission('create_sales'))
                                        <li><a class="nav-link {{ request()->is('sales') || request()->is('sales/*') ? 'active' : '' }}"
                                                href="{{ url('sales') }}"><i class="fa-solid fa-file-lines me-2"></i>All Material OUT</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_purchases') || auth()->user()?->hasMatrixPermission('create_purchases'))
                                        <li><a class="nav-link {{ request()->is('purchase') || request()->is('purchases') || request()->is('purchases/*') ? 'active' : '' }}"
                                                href="{{ url('purchase') }}"><i class="fa-solid fa-file-lines me-2"></i>All Material IN</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_inventory') || auth()->user()?->hasMatrixPermission('edit_inventory'))
                                        <li><a class="nav-link {{ request()->is('inventory') || request()->is('inventory/*') ? 'active' : '' }}"
                                                href="{{ url('inventory') }}"><i class="fa-solid fa-file-lines me-2"></i>Inventory</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_products') || auth()->user()?->hasMatrixPermission('create_products'))
                                        <li><a class="nav-link {{ request()->is('products') || request()->is('products/*') ? 'active' : '' }}"
                                                href="{{ route('products.index') }}"><i class="fa-solid fa-file-lines me-2"></i>All Products</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_categories') || auth()->user()?->hasMatrixPermission('create_categories'))
                                        <li><a class="nav-link {{ request()->is('all-categories') || request()->is('all-categories/*') ? 'active' : '' }}"
                                                href="{{ route('categories.index') }}"><i class="fa-solid fa-file-lines me-2"></i>All Categories</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_vendors') || auth()->user()?->hasMatrixPermission('create_vendors'))
                                        <li><a class="nav-link {{ request()->is('all-vendor') || request()->is('all-vendor/*') || request()->is('add-vendor') || request()->is('add-vendor/*') ? 'active' : '' }}"
                                                href="{{ url('all-vendor') }}"><i class="fa-solid fa-file-lines me-2"></i>Vendors</a></li>
                                    @endif
                                    @if(auth()->user()?->hasMatrixPermission('view_handover_persons') || auth()->user()?->hasMatrixPermission('create_handover_persons'))
                                        <li><a class="nav-link {{ request()->is('add-handover-person') ? 'active' : '' }}"
                                                href="{{ url('add-handover-person') }}"><i class="fa-solid fa-file-lines me-2"></i>Handover Person</a></li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif

                    <!-- Manage BOM -->
                    @if(
                        auth()->user()?->hasMatrixPermission('view_bom') || auth()->user()?->hasMatrixPermission('create_bom') ||
                        auth()->user()?->hasMatrixPermission('view_make') || auth()->user()?->hasMatrixPermission('create_make') ||
                        auth()->user()?->hasMatrixPermission('view_warranty') || auth()->user()?->hasMatrixPermission('create_warranty') ||
                        auth()->user()?->hasMatrixPermission('view_technology') || auth()->user()?->hasMatrixPermission('create_technology')
                    )
                        <li class="nav-item mt-2">

                            <a class="nav-link nav-link-collapse" data-bs-toggle="collapse"
                                href="#bomMenu" role="button"
                                aria-expanded="{{ request()->is('all_product') || request()->is('all_product/*') || request()->is('add-product') || request()->is('make') || request()->is('make/*') || request()->is('warranty') || request()->is('warranty/*') || request()->is('technology') || request()->is('technology/*') ? 'true' : 'false' }}">

                                <i class="fa-solid fa-layer-group me-2"></i>
                                <span>Manage BOM</span>

                                <i class="fa fa-chevron-down small sidebar-chevron"></i>
                            </a>

                            <div id="bomMenu"
                                class="collapse {{ request()->is('all_product') || request()->is('all_product/*') || request()->is('add-product') || request()->is('make') || request()->is('make/*') || request()->is('warranty') || request()->is('warranty/*') || request()->is('technology') || request()->is('technology/*') ? 'show' : '' }}"
                                data-bs-parent="#sidebarMenu">

                                <ul class="nav flex-column ms-3 mt-2">

                                    @if(auth()->user()?->hasMatrixPermission('view_bom') || auth()->user()?->hasMatrixPermission('create_bom'))
                                        <li><a class="nav-link {{ request()->is('all_product') || request()->is('all_product/*') || request()->is('add-product') ? 'active' : '' }}"
                                                href="{{ url('all_product') }}"><i class="fa-solid fa-file-lines me-2"></i>All BOM</a></li>
                                    @endif

                                    @if(auth()->user()?->hasMatrixPermission('view_make') || auth()->user()?->hasMatrixPermission('create_make'))
                                        <li><a class="nav-link {{ request()->is('make') || request()->is('make/*') ? 'active' : '' }}"
                                                href="{{ url('make') }}"><i class="fa-solid fa-file-lines me-2"></i>All Make</a></li>
                                    @endif

                                    @if(auth()->user()?->hasMatrixPermission('view_warranty') || auth()->user()?->hasMatrixPermission('create_warranty'))
                                        <li><a class="nav-link {{ request()->is('warranty') || request()->is('warranty/*') ? 'active' : '' }}"
                                                href="{{ url('warranty') }}"><i class="fa-solid fa-file-lines me-2"></i>All Warranty</a></li>
                                    @endif

                                    @if(auth()->user()?->hasMatrixPermission('view_technology') || auth()->user()?->hasMatrixPermission('create_technology'))
                                        <li><a class="nav-link {{ request()->is('technology') || request()->is('technology/*') ? 'active' : '' }}"
                                                href="{{ url('technology') }}"><i class="fa-solid fa-file-lines me-2"></i>All Technology</a></li>
                                    @endif

                                </ul>
                            </div>
                        </li>
                    @endif

                    @if(auth()->user()?->hasMatrixPermission('view_tasks'))
                        <!-- Manage Tasks -->
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('tasks.*')) active @endif"
                                href="{{ route('tasks.index') }}">
                                <i class="fa fa-tasks me-2 text-info"></i>
                                <span>Manage Tasks</span>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->hasMatrixPermission('view_tickets'))
                        <!-- Manage Tickets -->
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('tickets.*')) active @endif"
                                href="{{ route('tickets.index') }}">
                                <i class="fa fa-ticket me-2 text-info"></i>
                                <span>Manage Tickets</span>
                            </a>
                        </li>
                    @endif

                    <!-- Manage Reports -->
                    @if(auth()->user()?->hasMatrixPermission('view_reports'))
                        <li class="nav-item mt-2">

                            <a class="nav-link nav-link-collapse" data-bs-toggle="collapse"
                                href="#reportsMenu" role="button"
                                aria-expanded="{{ request()->is('customers_report*') || request()->is('leads_report*') || request()->is('deals_report*') || request()->is('tasks_report*') || request()->is('followups_report*') ? 'true' : 'false' }}">

                                <i class="fa-solid fa-chart-column me-2"></i>
                                <span>Manage Reports</span>

                                <i class="fa fa-chevron-down small sidebar-chevron"></i>
                            </a>

                            <div id="reportsMenu" class="collapse {{ request()->is('customers_report*') || request()->is('leads_report*') || request()->is('deals_report*') || request()->is('tasks_report*') || request()->is('followups_report*') ? 'show' : '' }}"
                                data-bs-parent="#sidebarMenu">

                                <ul class="nav flex-column ms-3 mt-2">

                                    <li><a class="nav-link {{ request()->is('customers_report*') ? 'active' : '' }}"
                                            href="{{ route('customers_report_old') }}"><i class="fa-solid fa-file-lines me-2"></i>Customer Reports</a></li>

                                    <li><a class="nav-link {{ request()->is('leads_report*') ? 'active' : '' }}"
                                            href="{{ route('leads_report_old') }}"><i class="fa-solid fa-file-lines me-2"></i>Lead Reports</a></li>

                                    <li><a class="nav-link {{ request()->is('deals_report*') ? 'active' : '' }}"
                                            href="{{ route('deals_report_old') }}"><i class="fa-solid fa-file-lines me-2"></i>Deal Reports</a></li>

                                    <!-- <li><a class="nav-link {{ request()->is('projects_report*') ? 'active' : '' }}"
                                            href="{{ route('projects_report_old') }}"><i class="fa-solid fa-file-lines me-2"></i>Project Reports</a></li> -->

                                    <li><a class="nav-link {{ request()->is('tasks_report*') ? 'active' : '' }}"
                                            href="{{ route('tasks_report_old') }}"><i class="fa-solid fa-file-lines me-2"></i>Task Reports</a></li>

                                    <li><a class="nav-link {{ request()->is('followups_report*') ? 'active' : '' }}"
                                            href="{{ route('followups_report_old') }}"><i class="fa-solid fa-file-lines me-2"></i>Followup Reports</a></li>

                                </ul>
                            </div>
                        </li>
                    @endif

                    @if(auth()->user()?->isAdmin())
                        <li class="nav-item mt-2">
                            <a class="nav-link ccc ddd @if(request()->routeIs('settings.index')) active @endif"
                                href="{{ route('settings.index') }}">
                                <i class="fa fa-gear me-2 text-secondary"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                    @endif  

                </nav>
            </aside>
            <div class="crm-sidebar-backdrop" id="crmSidebarBackdrop"></div>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-grow-1 overflow-auto">
                <!-- Topbar -->
                <header class="crm-topbar">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="d-flex align-items-center gap-4">
                            <button id="sidebarToggle" class="crm-sidebar-toggle" type="button" aria-label="Toggle sidebar"
                                aria-controls="sidenav-main" aria-expanded="true">
                                <i class="bi bi-list"></i>
                            </button>
                            <div class="search-wrapper d-none d-lg-block">
                                <i class="bi bi-search text-muted"></i>
                                <input type="text" class="form-control bg-light border-0" placeholder="Search anything...">
                            </div>
                        </div>

                        @php
                            $notificationCount = \App\Models\Notification::where('user_id', auth()->id())
                                ->where('is_read', 0)
                                ->count();
                            $recentNotifications = \App\Models\Notification::where('user_id', auth()->id())
                                ->where('is_read', 0)
                                ->latest()
                                ->take(3)
                                ->get()
                                ->map(function ($notification) {
                                    return [
                                        'id' => $notification->id,
                                        'message' => $notification->notification_text,
                                        'time' => optional($notification->created_at)->format('Y-m-d H:i:s'),
                                    ];
                                });
                        @endphp
                        @php
                            $topbarAvatar = Auth::user()?->avatar_path
                                ? route('users.image', Auth::user()) . '?v=' . optional(Auth::user()->updated_at)->timestamp
                                : (Auth::user()?->avatar_url ??
                                    'https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=ffffff&size=128');
                        @endphp
                        <div class="crm-top-actions">
                            @if (!empty($currentSubscriptionPlan))
                                @php
                                    $isPremiumPlan = str_contains(strtolower($currentSubscriptionPlan->name ?? ''), 'premium');
                                @endphp
                                <div class="dashboard-plan-switcher d-none d-lg-flex" role="group" aria-label="Subscription plan">
                                    <button type="button"
                                        class="btn top-action-btn dashboard-plan-btn {{ $isPremiumPlan ? 'dashboard-plan-btn--premium' : 'dashboard-plan-btn--basic' }} active"
                                        data-plan-trigger="{{ $isPremiumPlan ? 'premium' : 'basic' }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#dashboardPlanModal">
                                        <i class="fa-solid {{ $isPremiumPlan ? 'fa-gem' : 'fa-crown' }}"></i>
                                        <span>{{ $currentSubscriptionPlan->name }}</span>
                                    </button>
                                </div>
                            @endif

                            @php
                                $showTopInventoryMenu =
                                    auth()->user()?->hasMatrixPermission('view_sales') ||
                                    auth()->user()?->hasMatrixPermission('create_sales') ||
                                    auth()->user()?->hasMatrixPermission('view_purchases') ||
                                    auth()->user()?->hasMatrixPermission('create_purchases') ||
                                    auth()->user()?->hasMatrixPermission('view_inventory') ||
                                    auth()->user()?->hasMatrixPermission('edit_inventory') ||
                                    auth()->user()?->hasMatrixPermission('view_products') ||
                                    auth()->user()?->hasMatrixPermission('create_products') ||
                                    auth()->user()?->hasMatrixPermission('view_categories') ||
                                    auth()->user()?->hasMatrixPermission('create_categories') ||
                                    auth()->user()?->hasMatrixPermission('view_vendors') ||
                                    auth()->user()?->hasMatrixPermission('create_vendors');

                                $showTopCrmMenu =
                                    auth()->user()?->hasMatrixPermission('view_customers') ||
                                    auth()->user()?->hasMatrixPermission('create_customers') ||
                                    auth()->user()?->hasMatrixPermission('view_leads') ||
                                    auth()->user()?->hasMatrixPermission('create_leads') ||
                                    auth()->user()?->hasMatrixPermission('view_tasks') ||
                                    auth()->user()?->hasMatrixPermission('create_tasks') ||
                                    auth()->user()?->hasMatrixPermission('view_meetings') ||
                                    auth()->user()?->hasMatrixPermission('create_meetings');

                                $showTopEstimatesButton =
                                    auth()->user()?->hasMatrixPermission('view_estimates') ||
                                    auth()->user()?->hasMatrixPermission('create_estimates');
                            @endphp

                            @if ($showTopInventoryMenu)
                                <div class="dropdown d-none d-lg-block">
                                    <button class="btn top-action-btn old-crm-nav-btn" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                        <span>Inventory</span>
                                        <i class="bi bi-chevron-down small"></i>
                                    </button>
                                    <ul class="dropdown-menu old-crm-top-menu">
                                        @if(auth()->user()?->hasMatrixPermission('view_sales') || auth()->user()?->hasMatrixPermission('create_sales'))
                                            <li><a class="dropdown-item {{ request()->is('sales') || request()->is('sales/*') ? 'active' : '' }}"
                                                    href="{{ url('sales') }}"><i class="fa-solid fa-chart-line"></i><span>All Material OUT</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_purchases') || auth()->user()?->hasMatrixPermission('create_purchases'))
                                            <li><a class="dropdown-item {{ request()->is('purchase') || request()->is('purchases') || request()->is('purchases/*') ? 'active' : '' }}"
                                                    href="{{ url('purchase') }}"><i class="fa-solid fa-cart-shopping"></i><span>All Material IN</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_inventory') || auth()->user()?->hasMatrixPermission('edit_inventory'))
                                            <li><a class="dropdown-item {{ request()->is('inventory') || request()->is('inventory/*') ? 'active' : '' }}"
                                                    href="{{ url('inventory') }}"><i class="fa-solid fa-warehouse"></i><span>Inventory</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_products') || auth()->user()?->hasMatrixPermission('create_products'))
                                            <li><a class="dropdown-item {{ request()->is('products') || request()->is('products/*') ? 'active' : '' }}"
                                                    href="{{ route('products.index') }}"><i class="fa-solid fa-box-open"></i><span>All Products</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_categories') || auth()->user()?->hasMatrixPermission('create_categories'))
                                            <li><a class="dropdown-item {{ request()->is('all-categories') || request()->is('all-categories/*') ? 'active' : '' }}"
                                                    href="{{ route('categories.index') }}"><i class="fa-solid fa-tags"></i><span>All Categories</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_vendors') || auth()->user()?->hasMatrixPermission('create_vendors'))
                                            <li><a class="dropdown-item {{ request()->is('all-vendor') || request()->is('all-vendor/*') || request()->is('add-vendor') || request()->is('add-vendor/*') ? 'active' : '' }}"
                                                    href="{{ url('all-vendor') }}"><i class="fa-solid fa-handshake"></i><span>Vendors</span></a></li>
                                        @endif
                                    </ul>
                                </div>
                            @endif

                            @if ($showTopCrmMenu)
                                <div class="dropdown d-none d-lg-block">
                                    <button class="btn top-action-btn old-crm-nav-btn" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-handshake-angle"></i>
                                        <span>CRM</span>
                                        <i class="bi bi-chevron-down small"></i>
                                    </button>
                                    <ul class="dropdown-menu old-crm-top-menu">
                                        @if(auth()->user()?->hasMatrixPermission('view_customers') || auth()->user()?->hasMatrixPermission('create_customers'))
                                            <li><a class="dropdown-item {{ request()->routeIs('masters.customers.*') ? 'active' : '' }}"
                                                    href="{{ route('masters.customers.index') }}"><i class="fa-solid fa-users"></i><span>Customers</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_leads') || auth()->user()?->hasMatrixPermission('create_leads'))
                                            <li><a class="dropdown-item {{ request()->routeIs('leads.*') ? 'active' : '' }}"
                                                    href="{{ route('leads.index') }}"><i class="fa-solid fa-user-plus"></i><span>Leads</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_tasks') || auth()->user()?->hasMatrixPermission('create_tasks'))
                                            <li><a class="dropdown-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}"
                                                    href="{{ route('tasks.index') }}"><i class="fa-solid fa-list-check"></i><span>Tasks</span></a></li>
                                        @endif
                                        @if(auth()->user()?->hasMatrixPermission('view_meetings') || auth()->user()?->hasMatrixPermission('create_meetings'))
                                            <li><a class="dropdown-item {{ request()->routeIs('meetings.*') ? 'active' : '' }}"
                                                    href="{{ route('meetings.index') }}"><i class="fa-solid fa-handshake-simple"></i><span>Meetings</span></a></li>
                                        @endif
                                    </ul>
                                </div>
                            @endif

                            @if ($showTopEstimatesButton)
                                <a href="{{ route('estimates.index') }}"
                                    class="btn top-action-btn old-crm-nav-btn d-none d-lg-inline-flex {{ request()->is('estimate') || request()->is('estimates') || request()->is('estimates/*') || request()->is('invoices*') || request()->is('pdfbuilder*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-file-lines"></i>
                                    <span>Estimates</span>
                                </a>
                            @endif

                            <div class="dropdown">
                                <button class="notification-btn {{ request()->routeIs('notifications.index') ? 'bg-light' : '' }}"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell"></i>
                                    @if($notificationCount > 0)
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                            style="font-size: 0.6rem;">
                                            {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                                        </span>
                                    @endif
                                </button>
                                <div class="dropdown-menu dropdown-menu-end notifications-dropdown mt-2">
                                    @forelse($recentNotifications as $item)
                                        <div class="notification-row">
                                            <span class="notification-avatar">
                                                <i class="bi bi-bell"></i>
                                            </span>
                                            <div class="flex-grow-1">
                                                <div class="notification-message">{{ $item['message'] }}</div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="bi bi-clock"></i>
                                                    <div class="notification-time mt-0">{{ $item['time'] }}</div>
                                                </div>
                                            </div>
                                            <div class="ms-2">
                                                <i class="fa fa-times float-right mark-as-read" data-id="{{ $item['id'] }}" style="cursor: pointer; color: #94a3b8;"></i>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="notification-row">
                                            <span class="notification-avatar"><i class="bi bi-bell"></i></span>
                                            <div class="notification-message">No notifications yet.</div>
                                        </div>
                                    @endforelse
                                    <a class="view-all-notifications" href="{{ route('notifications.list') }}">View all notifications</a>
                                </div>
                            </div>

                            <!-- Dark Mode Toggle -->
                            <button id="darkModeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                                <i class="bi bi-moon-stars-fill" id="darkModeIcon"></i>
                            </button>

                            <div class="vr mx-2 text-muted opacity-25"></div>

                            <div class="dropdown">
                                <button class="btn text-decoration-none crm-user-trigger" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="{{ $topbarAvatar ?? (Auth::user()?->avatar_url ?? 'https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=ffffff&size=128') }}"
                                        onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=ffffff&size=128';"
                                        alt="{{ Auth::user()?->name ?? 'Administrator' }}" class="crm-user-avatar">
                                    <div class="crm-user-meta">
                                        <span class="crm-user-name">{{ $authUser?->name ?? 'Administrator' }}</span>
                                        <span class="crm-user-role">{{ $userRoleLabel }}</span>
                                    </div>
                                    <i class="bi bi-chevron-down crm-user-caret"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end mt-2 old-crm-user-menu">
                                    <li><a class="dropdown-item {{ request()->routeIs('profile.show') ? 'active' : '' }}"
                                            href="{{ route('profile.show') }}"><i
                                                class="fa fa-user"></i><span>Profile</span></a></li>
                                    @if(auth()->user()?->isAdmin())
                                        <li><a class="dropdown-item {{ request()->routeIs('notifications.index') || request()->routeIs('user-logs.*') ? 'active' : '' }}" href="{{ route('user-logs.index') }}">
                                                <i aria-autocomplete=""class="fa fa-history"></i><span>User Logs</span></a></li>
                                        <li><a class="dropdown-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                                                <i class="fa fa-gear"></i><span>Settings</span></a></li>
                                        <li>
                                    @endif
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fa fa-sign-out"></i><span>Logout</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="p-4 p-lg-4 mb-5">
                    @unless(request()->routeIs('dashboard'))
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"
                                            class="text-muted text-decoration-none">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">@yield('page_title', 'Dashboard')
                                    </li>
                                </ol>
                            </nav>
                            <div class="d-flex align-items-center gap-3">
                                @yield('page_actions')
                            </div>
                        </div>
                    @else
                        @yield('page_actions')
                    @endunless
                    @if(session('success'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: "{{ session('success') }}",
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                    customClass: {
                                        popup: 'rounded-4 shadow'
                                    }
                                });
                            });
                        </script>
                    @endif

                    @if(session('error'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: "{{ session('error') }}",
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 5000,
                                    timerProgressBar: true,
                                    customClass: {
                                        popup: 'rounded-4 shadow'
                                    }
                                });
                            });
                        </script>
                    @endif

                    @yield('content')
                </main>

                <!-- Mobile Bottom Navigation -->
                <div class="crm-mobile-nav d-lg-none">
                    <a href="{{ route('dashboard') }}" class="mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-house"></i>
                        <span>Home</span>
                    </a>

                    @if (auth()->user()?->hasMatrixPermission('view_leads'))
                        <a href="{{ route('leads.index') }}" class="mobile-nav-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span>Leads</span>
                        </a>
                    @endif

                    @if (auth()->user()?->hasMatrixPermission('view_customers'))
                        <a href="{{ route('masters.customers.index') }}" class="mobile-nav-item {{ request()->routeIs('masters.customers.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i>
                            <span>Customers</span>
                        </a>
                    @endif

                    @if (auth()->user()?->hasMatrixPermission('view_deals'))
                        <a href="{{ route('deals.index') }}" class="mobile-nav-item {{ request()->routeIs('deals.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-handshake"></i>
                            <span>Deals</span>
                        </a>
                    @endif

                    <a href="{{ route('profile.show') }}" class="mobile-nav-item {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                        <i class="fa-solid fa-user"></i>
                        <span>Profile</span>
                    </a>
                </div>
                
            </div>
    @endauth

    @guest
        <main class="w-100">
            @yield('content')
        </main>
    @endguest
    </div>

    @auth
        @if (!empty($currentSubscriptionPlan) && !request()->routeIs('dashboard'))
            @php
                $isPremiumPlan = str_contains(strtolower($currentSubscriptionPlan->name ?? ''), 'premium');
                $planName = $currentSubscriptionPlan->name ?? 'No Plan Assigned';
                $planStaffLimit = (int) ($currentSubscriptionPlan->staff_limit ?? 0);
                $planRenewalDate = optional($currentSubscriptionAssignment?->updated_at ?? $currentSubscriptionAssignment?->created_at)->format('d M Y') ?? '-';
            @endphp
            <div class="modal fade dashboard-plan-modal" id="dashboardPlanModal" tabindex="-1" aria-labelledby="dashboardPlanModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header dashboard-plan-modal__header {{ $isPremiumPlan ? 'plan-premium' : 'plan-basic' }} border-0">
                            <h5 class="modal-title fw-bold mb-0" id="dashboardPlanModalLabel">
                                <i class="fa-solid {{ $isPremiumPlan ? 'fa-gem' : 'fa-crown' }} me-2"></i>
                                <span>Your Subscription Plan</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4 py-4">
                            <div class="text-center mb-4">
                                <div class="dashboard-plan-modal__pill {{ $isPremiumPlan ? 'dashboard-plan-modal__pill--premium' : '' }}">{{ $planName }}</div>
                            </div>

                            <div class="dashboard-plan-modal__details">
                                <div class="dashboard-plan-modal__row">
                                    <span class="dashboard-plan-modal__icon"><i class="fa-solid fa-users"></i></span>
                                    <span class="fw-semibold">Staff Limit:</span>
                                    <span class="text-muted">{{ $currentStaffCount }} / {{ $planStaffLimit }} users</span>
                                </div>
                                <div class="dashboard-plan-modal__row">
                                    <span class="dashboard-plan-modal__icon"><i class="fa-solid fa-calendar-days"></i></span>
                                    <span class="fw-semibold">Renewal Date:</span>
                                    <span class="text-muted">{{ $planRenewalDate }}</span>
                                </div>
                                <div class="dashboard-plan-modal__row">
                                    <span class="dashboard-plan-modal__icon dashboard-plan-modal__icon--status"><i class="fa-solid fa-circle-check"></i></span>
                                    <span class="fw-semibold">Status:</span>
                                    <span class="text-muted">Active</span>
                                </div>
                            </div>

                            <p class="dashboard-plan-modal__message text-center mt-4 mb-3">
                                Need more team members? <strong>Contact us for upgrades!</strong>
                            </p>

                            <div class="text-center">
                                <a href="{{ route('settings.index') }}" class="btn dashboard-plan-modal__cta">Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <div class="modal fade status-comment-modal" id="statusCommentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title mb-0">Add Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="statusCommentInput" class="form-control" rows="4" maxlength="2000"
                        placeholder="Enter your message"></textarea>
                    <div class="invalid-feedback d-block d-none" id="statusCommentError">Comment is required.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-dark-blue" id="statusCommentSaveBtn">Submit</button>
                </div>
            </div>
        </div>
    </div>

    @auth        
    <div class="chatbot-float-btn chatbot-toggle-btn" id="chatbotToggleBtn" role="button" aria-label="Open chatbot">
        <i class="fa-solid fa-headset"></i>
    </div>
    {{-- @include('crm.chatbot.chatbot-modal') --}}
    <!-- Chatbot Card -->
    <div class="chatbot-card" id="chatbotCard" role="dialog" aria-labelledby="chatbotCardLabel" aria-hidden="true">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <span class="chatbot-header-icon">
                        <i class="fa-solid fa-headset"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 text-white fw-semibold" id="chatbotCardLabel">Bot</h5>
                        <p class="text-light small mb-0">Always here to help</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" id="chatbotCloseBtn"
                    aria-label="Close"></button>
            </div>


            <div class="card-body p-0">
                <div id="chatbotMessages" class="chatbot-messages">
                    <div class="chatbot-empty-state pt-0">
                        👋 Hi there!<br>
                        Ask me anything about <strong>leads, customers, tickets, reports,</strong> or CRM settings.
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div id="chatbotInputWrapper" class="d-none">
                    <label for="chatbotInput" class="form-label mb-1 ps-3">What's your question?</label>
                    <div class="input-group">
                        <input id="chatbotInput" type="text" class="form-control"
                            placeholder="Type your message here..." aria-label="Chat message">
                        <button type="button" class="btn btn-dark-blue" id="chatbotSendBtn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endauth


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    @stack('scripts')
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/crm-layout.js') }}?v={{ filemtime(PUBLIC_PATH('js/crm-layout.js')) }}"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/status-comment-box.js') }}?v={{ filemtime(PUBLIC_PATH('js/status-comment-box.js')) }}"></script>
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/global-search.js') }}?v={{ filemtime(PUBLIC_PATH('js/global-search.js')) }}"></script>
    <!-- Bootstrap JS -->
    <!-- SweetAlert2 -->
    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . '/js/main.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'js/chatbot.js') }}?v={{ filemtime(PUBLIC_PATH('js/chatbot.js')) }}"></script>
    
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mark as Read Handler
            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('mark-as-read')) {
                    const button = event.target;
                    const id = button.getAttribute('data-id');
                    
                    if (!id) return;

                    fetch(`/notifications/${id}/read`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Find the notification row and remove it or hide it
                            const row = button.closest('.notification-row');
                            if (row) {
                                row.classList.add('fade-out');
                                setTimeout(() => {
                                    row.remove();
                                    
                                    // Update count
                                    const badge = document.querySelector('.notification-btn .badge');
                                    if (badge) {
                                        let count = parseInt(badge.textContent.replace('99+', '100'));
                                        count = Math.max(0, count - 1);
                                        if (count === 0) {
                                            badge.remove();
                                        } else {
                                            badge.textContent = count > 99 ? '99+' : count;
                                        }
                                    }

                                    // If no notifications left in dropdown, show "No notifications yet"
                                    const dropdown = document.querySelector('.notifications-dropdown');
                                    if (dropdown && dropdown.querySelectorAll('.notification-row').length === 0) {
                                        const emptyState = document.createElement('div');
                                        emptyState.className = 'notification-row';
                                        emptyState.innerHTML = '<span class="notification-avatar"><i class="bi bi-bell"></i></span><div class="notification-message">No notifications yet.</div>';
                                        dropdown.insertBefore(emptyState, dropdown.querySelector('.view-all-notifications'));
                                    }
                                }, 300);
                            }
                        } else {
                            console.error('Error marking notification as read:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        });
    </script>
</body>

</html>
