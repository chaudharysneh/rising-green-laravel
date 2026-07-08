@php
    $doc = $estimate ?? $estdata ?? null;
    $customer = $doc->customer ?? null;
    $clientName = trim((string) ($customer->name ?? $doc->name ?? ''));
    $clientAddress = trim((string) ($customer->address ?? $doc->address ?? ''));
    $companySettings = isset($companySettings) && is_iterable($companySettings) ? collect($companySettings) : collect($companySettings ?? []);
    $companyName = trim((string) ($companySettings['company_name'] ?? $user['company_name'] ?? $profileUser->company_name ?? ''));
    $companyName = $companyName !== '' ? $companyName : '[Your Company Name]';
    $clientName = $clientName !== '' && !in_array(strtolower($clientName), ['--', 'client name'], true) ? $clientName : '[Client Name]';
    $clientAddress = $clientAddress !== '' && !in_array(strtolower($clientAddress), ['--', 'address'], true) ? $clientAddress : '[Client Address]';
    $capacity = trim((string) ($doc->quantity ?? ''));
    $capacity = $capacity !== '' && $capacity !== '0' ? rtrim(rtrim(number_format((float) $capacity, 1), '0'), '.') . ' kWp' : '[X] kWp';
    $unitRate = data_get($doc, 'generation_data.unit_rate', '[Tariff]') ?: '[Tariff]';
    $currencyAmount = '&#8377; [Amount]';
    $proposalLabel = 'Solar Power Project Proposal | Confidential';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Basic Template</title>
    <style>
        @page { margin: 14mm 15mm 12mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #1e2f44;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10.8px;
            line-height: 1.48;
            background: #fff;
        }
        .page {
            position: relative;
            width: 100%;
            min-height: 260mm;
            padding-bottom: 11mm;
            page-break-after: always;
            background: #fff;
            overflow: visible;
        }
        .page:last-child { page-break-after: auto; }
        .cover-header {
            margin: -14mm -15mm 9mm;
            padding: 13mm 15mm 10mm;
            background: #183d66;
            color: #fff;
            border-bottom: 4px solid #f2a51c;
        }
        .cover-title {
            font-size: 25px;
            line-height: 1;
            font-weight: 700;
            letter-spacing: .2px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .cover-subtitle {
            font-size: 10.5px;
            font-style: italic;
            color: #eef5ff;
        }
        .section {
            margin: 0 0 14px;
        }
        .section-title {
            color: #14395f;
            font-size: 14.4px;
            line-height: 1.2;
            font-weight: 700;
            border-left: 4px solid #f2a51c;
            padding-left: 8px;
            margin: 0 0 9px;
        }
        p { margin: 0 0 7px; }
        ul { margin: 0 0 9px 15px; padding: 0; }
        li { margin: 0 0 4px; }
        .diagram-box {
            margin: 9px 0 15px;
            padding: 14px 16px;
            border: 1px dashed #b9c8d7;
            background: #f8fbfe;
            color: #58708a;
            font-style: italic;
            text-align: center;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .process-box {
            margin: 9px 0 15px;
            padding: 12px 15px;
            border: 1px solid #9fd5f3;
            border-radius: 4px;
            background: #eef9ff;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 7px 0 15px;
        }
        .data-table th {
            background: #2d73b6;
            color: #fff;
            font-size: 10.2px;
            text-align: left;
            padding: 7px 8px;
            border: 1px solid #2d73b6;
        }
        .data-table td {
            padding: 7px 8px;
            border: 1px solid #d5e0eb;
            vertical-align: top;
        }
        .data-table tbody tr:nth-child(even) td { background: #f4f8fb; }
        .data-table .total-row td {
            background: #fff1d8 !important;
            border-color: #f2a51c;
            color: #c44d00;
            font-weight: 700;
        }
        .quote-box {
            border-left: 3px solid #c5d1de;
            background: #f7f9fb;
            padding: 11px 13px;
            margin-bottom: 10px;
            font-style: italic;
        }
        .quote-author {
            display: block;
            margin-top: 6px;
            font-style: normal;
            font-weight: 700;
            color: #1e2f44;
        }
        .footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            color: #7c8ca0;
            font-size: 8.5px;
        }
        .footer .page-no { float: right; }
        strong { color: #19314c; }
    </style>
</head>
<body>
    <section class="page">
        <div class="cover-header">
            <div class="cover-title">Custom Solar Energy Proposal</div>
            <div class="cover-subtitle">Clean Energy. Guaranteed Savings. Sustainable Future.</div>
        </div>

        <div class="section">
            <h2 class="section-title">1. Introduction Page</h2>
            <p>Dear <strong>{{ $clientName }}</strong>,</p>
            <p>Thank you for giving <strong>{{ $companyName }}</strong> the opportunity to present this customized solar energy proposal for your property located at <strong>{{ $clientAddress }}</strong>.</p>
            <p>With electricity tariffs rising consistently year after year, switching to solar is no longer just an environmental choice-it is one of the smartest and safest financial investments available today. At <strong>{{ $companyName }}</strong>, we combine premium Tier-1 components, precise engineering, and seamless multi-stage execution to ensure your transition to clean energy is entirely effortless and highly profitable.</p>
            <p>Enclosed, you will find a comprehensive breakdown of your custom tailored solar solution, expected annual generation metrics, long-term financial returns, and an end-to-end implementation roadmap.</p>
            <p>Best Regards,</p>
            <p><strong>[Your Name/Title]</strong><br>[Your Company Name] | [Contact Information] | [Website]</p>
        </div>

        <div class="section">
            <h2 class="section-title">2. How the Solar System Works</h2>
            <p>A grid-tied solar power system seamlessly integrates with your existing utility grid infrastructure to cleanly power your property:</p>
            <ul>
                <li><strong>Step 1: Solar Panels (Photovoltaic Modules)</strong> - Positioned optimally on your roof, these modules absorb sunlight ambient photon radiation and convert it directly into Direct Current (DC) electricity.</li>
                <li><strong>Step 2: The Solar Inverter</strong> - Acts as the intelligent brain of the system, converting the DC electricity into stable Alternating Current (AC), standardizing it for all home appliances.</li>
                <li><strong>Step 3: Home Consumption &amp; Net Metering</strong> - Power goes to your appliances first. Any excess surplus electricity generated is instantly directed back into the government utility grid via a specialized bidirectional meter.</li>
                <li><strong>Step 4: Utility Grid Backup</strong> - At night or during heavily overcast days, this system smoothly pulls electricity back from the utility grid, ensuring uninterrupted power.</li>
            </ul>
        </div>

        <div class="section">
            <h2 class="section-title">3. Advantages of Solar Energy</h2>
            <ul>
                <li><strong>Massive Utility Bill Reductions:</strong> Drastically slash your monthly energy spend by up to <strong>80% - 90%</strong>.</li>
                <li><strong>High Return on Investment:</strong> Solar operates as a high-yielding financial asset that typical clears its payback period within <strong>3 - 4 years</strong>, yielding completely free power for the remainder of its 25+ year lifecycle.</li>
                <li><strong>Property Appreciation:</strong> Green-certified residential buildings equipped with fixed solar infrastructure command higher market resale values.</li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 1 of 5</span></div>
    </section>

    <section class="page">
        <ul>
            <li><strong>Extremely Low Maintenance:</strong> With zero moving parts, the entire system requires minimal operational upkeep-restricted primarily to routine automated or manual panel washings.</li>
            <li><strong>Environmental Stewardship:</strong> Directly mitigate carbon footprints and actively combat localized climate change.</li>
        </ul>

        <div class="section">
            <h2 class="section-title">4. Technical Line Diagram Layout</h2>
            <p>The layout below illustrates the seamless logical electrical connection map from production to grid export.</p>
            <div class="diagram-box">[ Engineering single line diagram (SLD) layout: Solar PV Modules &rarr; DC Distribution Box (DCDB) &rarr; Smart Grid-Tied Inverter &rarr; AC Distribution Box (ACDB) &rarr; Bi-Directional Net Meter &rarr; Residential Load / Public Grid ]</div>
        </div>

        <div class="section">
            <h2 class="section-title">5. PM-Surya Ghar: Muft Bijli Yojana Process</h2>
            <p>As a fully authorized and certified empaneled solar vendor, we manage the entire national subsidy workflow framework for your project end-to-end:</p>
            <div class="process-box">
                <p><strong>1. Registration:</strong> We safely onboard your consumer credentials directly onto the central government's PM-Surya Ghar National Portal.</p>
                <p><strong>2. Technical Feasibility:</strong> The regional DISCOM (Electricity Board) reviews local grid capacity and issues a formal structural clearance approval.</p>
                <p><strong>3. Execution &amp; Installation:</strong> Our engineering wing carries out code-compliant installation adhering precisely to MNRE quality guidelines.</p>
                <p><strong>4. Net Metering Inspection:</strong> DISCOM engineers perform physical on-site verification, deploy the smart net meter, and commission the plant.</p>
                <p><strong>5. Subsidy Disbursal:</strong> Post-commissioning, the sanctioned central government subsidy is electronically credited to your linked bank account within 30 business days.</p>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">6. Generation Calculations</h2>
            <p>Projected estimations are generated based on regional irradiance data for a premium proposed <strong>{{ $capacity }}</strong> system:</p>
            <ul>
                <li><strong>Daily Average Generation:</strong> ~4 to 4.5 kWh (Units) per kWp installed - <strong>[X] Units/day</strong></li>
                <li><strong>Estimated Monthly Generation:</strong> <strong>[X] Units/month</strong></li>
                <li><strong>Projected Annual Generation:</strong> <strong>[X] Units/year</strong></li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 2 of 5</span></div>
    </section>

    <section class="page">
        <div class="section">
            <h2 class="section-title">7. Return on Savings (ROI)</h2>
            <p>Financial projections calculated based on a baseline localized utility tariff of <strong>&#8377;{{ $unitRate }}/kWh</strong> per unit:</p>
            <table class="data-table">
                <thead><tr><th>Financial Parameter</th><th>Projected Value</th></tr></thead>
                <tbody>
                    <tr><td>Estimated Monthly Savings Target</td><td>{!! $currencyAmount !!}</td></tr>
                    <tr><td>Projected First-Year Annual Savings</td><td>{!! $currencyAmount !!}</td></tr>
                    <tr><td>Cumulative 25-Year System Lifecycle Savings</td><td>{!! $currencyAmount !!}</td></tr>
                    <tr><td><strong>Calculated System Payback Period (Break-Even)</strong></td><td><strong>[X] Years</strong></td></tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">8. Carbon Emission Offset Calculation</h2>
            <p>By shifting power generation source to solar PV, your property achieves highly significant environmental offset targets across its guaranteed 25-year operational lifecycle:</p>
            <ul>
                <li><strong>CO2 Emissions Prevented:</strong> <strong>[X] Metric Tons</strong> of pure Carbon Dioxide stopped from entering the atmosphere.</li>
                <li><strong>Fossil Fuel Preservation:</strong> Equivalent to preventing the burning of <strong>[X] Tons</strong> of standard coal.</li>
                <li><strong>Reforestation Equivalence:</strong> Equal to the ecological impact of planting <strong>[X] mature trees</strong>.</li>
            </ul>
        </div>

        <div class="section">
            <h2 class="section-title">9. Documents Required</h2>
            <p>To initiate file processing for DISCOM permissions and central subsidy approvals, please provide:</p>
            <ul>
                <li>Latest Official Electricity Utility Bill (All pages included)</li>
                <li>Aadhaar Card of the property owner (Name alignment must match utility billing details)</li>
                <li>PAN Card Copy</li>
                <li>Bank Cancelled Cheque or clear Passbook photocopy (Required for direct electronic subsidy disbursement)</li>
                <li>Two recent passport-size color photographs</li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 3 of 5</span></div>
    </section>

    <section class="page">
        <div class="section">
            <h2 class="section-title">10. Material Make &amp; Specifications</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Component Type</th><th>Approved Brand / Make</th><th>Technical Specification</th><th>Warranty Terms</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><strong>Solar PV Panels</strong></td><td>[Waaree / Adani / Tata]</td><td>Mono PERC - Half Cut Module ([X]Wp)</td><td>10 Yr Product / 25 Yr Performance</td></tr>
                    <tr><td><strong>Grid-Tied Inverter</strong></td><td>[Growatt / Sungrow / Solis]</td><td>High Efficiency String Inverter with App Monitoring</td><td>5 Years Base Warranty</td></tr>
                    <tr><td><strong>Mounting Structure</strong></td><td>[Custom Engineered]</td><td>Hot-Dip Galvanized Structural Steel (HDG) / Al</td><td>5 Years Structural</td></tr>
                    <tr><td><strong>AC / DC Cabling</strong></td><td>[Polycab / KEI Industries]</td><td>Multi-strand Copper, FRLS, XLPE Solar Grade</td><td>Standard OEM Warranty</td></tr>
                    <tr><td><strong>Switchgear / Safety</strong></td><td>[Schneider / Legrand]</td><td>IP65 Enclosed ACDB &amp; DCDB with Type-II SPD &amp; Fuses</td><td>1 Year Comprehensive</td></tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">11. Price Quote &amp; Commercials</h2>
            <p>Commercial quote schedule valid for precisely 15 calendar days from document date of issue:</p>
            <table class="data-table">
                <thead><tr><th>Line Item Description</th><th>Amount (&#8377;)</th></tr></thead>
                <tbody>
                    <tr><td>Base System Supply, Engineering, Liaisoning &amp; Installation Cost</td><td>{!! $currencyAmount !!}</td></tr>
                    <tr><td>Applicable Statutory Goods and Services Tax (GST)</td><td>{!! $currencyAmount !!}</td></tr>
                    <tr><td><strong>Gross Capital Project Value (A)</strong></td><td><strong>{!! $currencyAmount !!}</strong></td></tr>
                    <tr><td><em>Less: Eligible PM-Surya Ghar Portal Subsidy Allocation (B)</em></td><td><em>- {!! $currencyAmount !!}</em></td></tr>
                    <tr class="total-row"><td>Net Out-of-Pocket Customer Investment (A - B)</td><td>{!! $currencyAmount !!}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">12. Payment Terms</h2>
            <ul>
                <li><strong>30% Mobilization Advance:</strong> Booked to initiate legal DISCOM file log, architectural layouts, and factory component ordering.</li>
                <li><strong>60% Component Delivery Milestone:</strong> Payable immediately upon the safe arrival of primary inventory (PV Modules &amp; Inverter) at the installation site.</li>
                <li><strong>10% Commissioning Milestone:</strong> Balance due upon successful grid connection synchronization and hand over of system logins.</li>
            </ul>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 4 of 5</span></div>
    </section>

    <section class="page">
        <div class="section">
            <h2 class="section-title">13. Terms &amp; Conditions</h2>
            <ul>
                <li><strong>Turnaround Timeline:</strong> Project completion spans 3 to 4 weeks conditional upon localized utility board structural approval speed.</li>
                <li><strong>Site Handover Readiness:</strong> The client is required to grant clear rooftop clearance, secure storage space for physical components, and a continuous water line connection for maintenance panels cleaning.</li>
                <li><strong>Civil Variations:</strong> Baseline quotes assume mounting configurations directly onto structurally sound RCC flat roofs. High-raise custom structures or unique modifications will be billed extra as per agreed metrics.</li>
            </ul>
        </div>

        <div class="section">
            <h2 class="section-title">14. Disclaimer</h2>
            <p>Solar generation metrics are derived parameters calculated utilizing historical long-term satellite climate records for your specific latitude/longitude. Actual real-time production yields may fluctuate in accordance with variations in seasonal weather cycles, structural micro-climate shading patterns (such as subsequent newly erected adjacent high-rises), and regular panel dust wash upkeep consistency.</p>
        </div>

        <div class="section">
            <h2 class="section-title">15. Client Testimonials</h2>
            <div class="quote-box">
                "The complete migration process to solar with this team was entirely fluid. Our typical monthly operational electric bills fell right down from near &#8377;8,000 to basic minimal meter standing charges under &#8377;500! Clean installation and outstanding customer portal communication."
                <span class="quote-author">- Rajesh K., Verified Residential Client</span>
            </div>
            <div class="quote-box">
                "Outstanding expertise handling the central PM-Surya Ghar portal compliance parameters. The full subsidy allocation arrived securely into my bank profile within exactly 25 working days post meter calibration."
                <span class="quote-author">- Sunita Sharma, Verified Residential Client</span>
            </div>
        </div>
        <div class="footer">{{ $proposalLabel }}<span class="page-no">Page 5 of 5</span></div>
    </section>
</body>
</html>
