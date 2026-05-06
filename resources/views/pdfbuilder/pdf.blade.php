<!DOCTYPE html>
<html>

<head>
    <title>{{ $template_name ?? 'Solar Proposal' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 130px 0px 80px 0px;
        }

        @page :first {
            margin: 0px;
        }

        /* Hide fixed header on first page using a high-z-index cover */
        @page :first {
            header {
                display: none;
            }
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.5;
        }

        /* Header */
        header {
            position: fixed;
            top: -110px;
            left: 0px;
            right: 0px;
            height: 80px;
            padding: 10px 40px;
            border-bottom: 1px solid #eee;
            background-color: #fff;
            z-index: 1;
            /* Lower than cover page to remain hidden on page 1 */
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo {
            width: 100px;
        }

        .social-icons {
            text-align: right;
        }

        .social-icons img {
            width: 20px;
            margin-left: 10px;
        }

        /* Footer */
        footer {
            position: fixed;
            bottom: -90px;
            left: 0px;
            right: 0px;
            height: 60px;
            background: #5d8b74;
            color: #fff;
            padding: 10px 40px;
            z-index: 1000;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        /* Cover Page Redesign - Maximum Compatibility */
        .cover-page {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #fff;
            page-break-after: always;
            z-index: 1000;
            overflow: hidden;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        .cover-image-container {
            position: absolute;
            top: 130px;
            left: 0;
            width: 52%;
            height: 1000px;
            background-size: cover;
            background-position: center;
            border-top-right-radius: 400px;
            z-index: 1;
        }

        .cover-header-left {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }

        .cover-header-left .cover-logo {
            width: 150px;
        }

        .cover-header-left .tagline {
            font-size: 16px;
            font-style: italic;
            color: #333;
            margin-top: 5px;
            padding-left: 5px;
        }

        .cover-title-section {
            position: absolute;
            top: 30px;
            right: 40px;
            text-align: right;
            z-index: 10;
        }

        .cover-title-section .main-title {
            font-size: 80px;
            font-weight: bold;
            color: #264d3b;
            line-height: 0.85;
            text-transform: uppercase;
            margin: 0;
        }

        .info-card {
            position: absolute;
            top: 240px;
            right: 30px;
            width: 43%;
            background-color: rgba(232, 241, 237, 0.7);
            /* Glass transparency */
            padding: 50px 40px;
            border-radius: 40px;
            z-index: 5;
            border: 1px solid rgba(255, 255, 255, 0.5);
            /* Glass edge */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
        }

        .info-card .company-name {
            font-size: 42px;
            font-weight: bold;
            color: #1a1a1a;
            line-height: 1.1;
            margin-bottom: 30px;
        }

        .info-card .detail-row {
            font-size: 24px;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .info-card .divider {
            height: 1px;
            background-color: #8da399;
            margin: 30px 0;
            width: 100%;
        }

        .contact-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .contact-icon-circle {
            width: 50px;
            height: 50px;
            background-color: #b1315b;
            border-radius: 25px;
            text-align: center;
        }

        .contact-icon-circle img {
            width: 24px;
            height: 24px;
            margin-top: 13px;
        }

        .contact-label {
            font-weight: bold;
            font-size: 22px;
            color: #1a1a1a;
            padding-left: 15px;
        }

        .contact-value {
            font-size: 22px;
            color: #1a1a1a;
            padding-left: 15px;
        }

        .address-label {
            font-weight: bold;
            font-size: 24px;
            display: block;
            margin-bottom: 10px;
        }

        .address-value {
            font-size: 22px;
            color: #333;
            line-height: 1.4;
        }

        .geometric-shape {
            position: absolute;
            z-index: 0;
        }

        .shape-top-right {
            top: 0;
            right: 0;
            width: 450px;
            height: 350px;
            background-color: #e8f1ed;
            /* Using clip-path for triangle effect if supported, or just background */
        }

        .shape-bottom-right {
            bottom: -20px;
            right: -20px;
            width: 400px;
            height: 400px;
            background-color: #264d3b;
        }

        /* Main Content */
        .content {
            padding: 20px 40px 20px;
            margin-top: 0px;
            page-break-before: always;
        }

        .section {
            margin-bottom: 30px;
        }

        h1,
        h2,
        h3 {
            color: #2F3E46;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        table.data-table th {
            background-color: #f9f9f9;
        }

        /* Thank You Page */
        .thank-you-page {
            text-align: center;
            padding: 10px 40px;
            padding-top: 150px;
            page-break-before: always;
        }

        .thank-you-title {
            font-size: 60px;
            font-weight: bold;
            margin-bottom: 40px;
            color: #000;
        }

        .thank-you-text {
            font-size: 18px;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .thank-you-company {
            font-weight: bold;
            color: #5d8b74;
        }
    </style>
</head>

<body>
    @php
        $companySettings = $companySettings ?? collect();
        $profileUser = $profileUser ?? null;
        $logoPath = $companyLogoPath ?? public_path('logo/fableadcrmLogo.png');
        if ($logoPath && !file_exists($logoPath)) {
            $logoPath = null;
        }
        $companyName = $companySettings['company_name'] ?? ($template_name ?? 'Solar-CRM');
        $companyTagline = $companySettings['company_tagline'] ?? 'Explorable Renewable Energy';
        $companyAddress = $companySettings['company_address'] ?? '';
        $companyEmail = $profileUser->email ?? 'solar-crm@gmail.com';
        $companyPhone = $profileUser->phone ?? '9974458840';
        $estimateNumber = $template_id ?? '--';
        $facebookUrl = $companySettings['social_facebook'] ?? null;
        $instagramUrl = $companySettings['social_instagram'] ?? null;
        $linkedinUrl = $companySettings['social_linkedin'] ?? null;
        $coverImage = $header_image ?? public_path('uploads/pdf_headers/pink_tree_bg.png');
    @endphp

    <!-- Fixed Footer (Shows on all pages where margin allows) -->
    <footer>
        <table class="header-table" style="color: white; font-size: 12px;">
            <tr>
                <td style="width: 60%;">
                    <strong>{{ $companyName }}.</strong> <i>{{ $companyTagline }}</i>
                </td>
                <td style="text-align: right; width: 40%;">
                    <strong>Address: {!! nl2br(e($companyAddress)) !!}</strong>
                </td>
            </tr>
        </table>
    </footer>

    <!-- Fixed Header (Shows on all pages where margin allows) -->
    <header>
        <table class="header-table">
            <tr>
                @if($logoPath)
                    <td><img src="{{ $logoPath }}" class="header-logo"></td>
                @else
                    <td></td>
                @endif
                <td class="social-icons">
                    @if ($facebookUrl)
                        <a href="{{ $facebookUrl }}" target="_blank"
                            style="display: inline-block; margin-left: 10px; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="Facebook"
                                style="width: 20px; height: 20px; vertical-align: middle;">
                                <path fill="#1877F2"
                                    d="M24 12a12 12 0 1 0-13.88 11.85v-8.39H7.08V12h3.04V9.36c0-3 1.79-4.67 4.53-4.67 1.31 0 2.68.23 2.68.23v2.95h-1.51c-1.49 0-1.95.92-1.95 1.87V12h3.32l-.53 3.46h-2.79v8.39A12 12 0 0 0 24 12Z" />
                            </svg>
                        </a>
                    @endif
                    @if ($instagramUrl)
                        <a href="{{ $instagramUrl }}" target="_blank"
                            style="display: inline-block; margin-left: 10px; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="Instagram"
                                style="width: 20px; height: 20px; vertical-align: middle;">
                                <defs>
                                    <linearGradient id="ig-gradient" x1="0%" y1="100%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#feda75" />
                                        <stop offset="35%" stop-color="#fa7e1e" />
                                        <stop offset="65%" stop-color="#d62976" />
                                        <stop offset="100%" stop-color="#4f5bd5" />
                                    </linearGradient>
                                </defs>
                                <path fill="url(#ig-gradient)"
                                    d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5a4.25 4.25 0 0 0 4.25 4.25h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5A4.25 4.25 0 0 0 16.25 3.5Zm8.88 1.13a1.12 1.12 0 1 1 0 2.24 1.12 1.12 0 0 1 0-2.24ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5Z" />
                            </svg>
                        </a>
                    @endif
                    @if ($linkedinUrl)
                        <a href="{{ $linkedinUrl }}" target="_blank"
                            style="display: inline-block; margin-left: 10px; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="LinkedIn"
                                style="width: 20px; height: 20px; vertical-align: middle;">
                                <path fill="#0A66C2"
                                    d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.15 1.45-2.15 2.95v5.66H9.33V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.31 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12ZM7.09 20.45H3.53V9h3.56v11.45Z" />
                            </svg>
                        </a>
                    @endif
                </td>
            </tr>
        </table>
    </header>

    <!-- ===================== COVER PAGE ===================== -->
    <div class="cover-page">
        <!-- Geometric Shape Top Right (Triangle) -->
        <div class="geometric-shape shape-top-right">
            <svg width="450" height="350" style="display: block;">
                <polygon points="450,0 0,0 450,300" style="fill:#e8f1ed;" />
                <line x1="450" y1="60" x2="200" y2="0" style="stroke:#264d3b;stroke-width:2" />
                <line x1="450" y1="110" x2="300" y2="0" style="stroke:#264d3b;stroke-width:1.5" />
            </svg>
        </div>

        <!-- Header Left -->
        <div class="cover-header-left">
            @if($logoPath)
                <img src="{{ $logoPath }}" class="cover-logo">
            @endif
            <br>
            <div class="tagline">{{ $companyTagline }}</div>
        </div>

        <!-- Title Section -->
        <div class="cover-title-section">
            <div class="main-title">SOLAR<br>PROPOSAL</div>
        </div>

        <!-- Hero Image -->
        <div class="cover-image-container" style="background-image: url('{{ $coverImage }}');">
        </div>

        <!-- Info Card -->
        <div class="info-card">
            <div class="company-name">{{ $companyName }}</div>

            <div class="detail-row"><strong>Estimate No:</strong> #{{ $estimateNumber }}</div>
            <div class="detail-row"><strong>Generated At:</strong><br>{{ $generated_at ?? date('d M Y H:i A') }}</div>

            <div class="divider"></div>

            <table class="contact-table">
                <tr>
                    <td style="width: 50px;">
                        <div class="contact-icon-circle">
                            <div style="padding-top: 6px; color: white; font-weight: bold; font-size: 20px;">@</div>
                        </div>
                    </td>
                    <td>
                        <div class="contact-label">Email:</div>
                        <div class="contact-value">{{ $companyEmail }}</div>
                    </td>
                </tr>
            </table>

            <table class="contact-table">
                <tr>
                    <td style="width: 50px;">
                        <div class="contact-icon-circle">
                            <div style="padding-top: 7px; color: white; font-weight: bold; font-size: 20px;">P</div>
                        </div>
                    </td>
                    <td>
                        <div class="contact-label">Phone:</div>
                        <div class="contact-value">{{ $companyPhone }}</div>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            <div class="info-address-section">
                <span class="address-label">Address:</span>
                <span class="address-value">{!! nl2br(e($companyAddress)) !!}</span>
            </div>
        </div>

        <!-- Geometric Shape Bottom Right -->
        <div class="geometric-shape shape-bottom-right">
            <svg width="400" height="400">
                <polygon points="400,400 400,0 0,400" style="fill:#264d3b;" />
                <line x1="400" y1="250" x2="150" y2="400" style="stroke:#f47b20;stroke-width:6" />
                <line x1="400" y1="320" x2="280" y2="400" style="stroke:#f47b20;stroke-width:3" />
            </svg>
        </div>

        <!-- Footer for Cover Page -->
        <div
            style="position: absolute; bottom: -20px; left: 0; right: 0; height: 60px; background: #5d8b74; color: #fff; padding: 10px 40px; z-index: 1001;">
            <table class="header-table" style="color: white; font-size: 12px; width: 100%;">
                <tr>
                    <td style="width: 60%;">
                        <strong>{{ $companyName }}.</strong> <i>{{ $companyTagline }}</i>
                    </td>
                    <td style="text-align: right; width: 40%;">
                        <strong>Address: {!! nl2br(e($companyAddress)) !!}</strong>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <!-- ===================== END COVER PAGE ===================== -->


    <!-- Main Content -->
    <div class="content">
        @foreach ($before_blocks ?? [] as $block)
            <div class="section">
                @if (!empty($block['image']) && file_exists($block['image']))
                    <div style="text-align: center;">
                        <img src="{{ $block['image'] }}"
                            style="max-width: 100%; margin-top: 10px;">
                    </div>
                @endif
                @if (!empty($block['title']))
                    <div style="text-align: center;">
                        <h2>{{ $block['title'] }}</h2>
                    </div>
                @endif
                <div class="block-content">{!! $block['content'] !!}</div>
            </div>
        @endforeach

        <div class="section" style="page-break-before: always;">
            {!! $quotation_html ?? '' !!}
        </div>


        @foreach ($after_blocks ?? [] as $block)
            <div class="section">
                @if (!empty($block['image']) && file_exists($block['image']))
                    <div style="text-align: center;">
                        <img src="{{ $block['image'] }}"
                            style="max-width: 100%; margin-top: 10px;">
                    </div>
                @endif
                @if (!empty($block['title']))
                    <div style="text-align: center;">
                        <h3>{{ $block['title'] }}</h3>
                    </div>
                @endif
                <div class="block-content">{!! $block['content'] !!}</div>
            </div>
        @endforeach
    </div>

    <!-- Thank You Page -->
    @if ($footer['active'] ?? 1)
        <div class="thank-you-page">
            <div class="thank-you-title">{{ $footer['title'] ?? 'THANK YOU' }}</div>

            <div class="thank-you-text">
                {!! $footer['sub_title'] ??
            'We sincerely appreciate your trust. Together, we\'re building a brighter, more sustainable future.' !!}
            </div>
        </div>
    @endif
</body>

</html>