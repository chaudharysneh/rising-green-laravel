@php
    $company = $settings['company_name'] ?? config('app.name');
    $customer = $deal->customer;
    $estimate = $deal->estimate;
    $currency = $deal->currency?->symbol ?: ($deal->currency?->code ?: ($estimate?->currency ?: 'INR'));
    $money = fn($value) => $currency . ' ' . number_format((float) $value, 2);
    $date = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d M Y') : '—';
    // Keep the upper information card concise; do not print unavailable details.
    $customerFields = array_filter([
        'Name' => $customer?->name,
        'Phone' => $customer?->phone,
        'Address' => $customer?->address,
        'GST / Tax No.' => $customer?->tax_number,
    ], static fn ($value) => filled($value));
    $infoFields = array_filter([
        'Deal Number' => 'DEAL-' . str_pad($deal->id, 5, '0', STR_PAD_LEFT),
        'Deal Date' => $date($deal->created_at),
        'Expected Close' => $deal->expected_close_date ? $date($deal->expected_close_date) : null,
    ], static fn ($value) => filled($value));
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>Deal Details</title>
<style>
@page { margin:28px 30px; }
body { font-family:'DejaVu Sans',sans-serif; font-size:11.5px; color:#27373c; margin:0; line-height:1.45; }
/* Fixed frames do not take document space, so they never create a blank page.
   Dompdf repeats fixed elements when actual content continues onto another page. */
.page-frame { position:fixed; top:0; right:0; bottom:0; left:0; border:2px solid #4b9349; }
.pdf-frame { padding:5px 10px; }
table { width:100%; border-collapse:collapse; } td { vertical-align:top; }
.brand { border-bottom:1px solid #cbdccf; margin-bottom:12px; } .brand td { padding-bottom:14px; vertical-align:middle; }
.company { font-size:18px; color:#24643b; font-weight:bold; } .muted { color:#63756b; font-size:9px; }
h1 { font-size:21px; margin:8px 0 12px; } h2 { font-size:12.5px; color:#286340; margin:0 0 8px; }
.party-info { background:#e5e7e9; table-layout:fixed; page-break-inside:avoid; }
.party-info > tbody > tr > td { width:50%; padding:9px 10px; }
.party-info .info-divider { border-left:1px solid #4b9349; }
.party-info h2 { color:#111; font-size:11.5px; margin:0 0 5px; text-transform:uppercase; }
.party-info .details { table-layout:fixed; }
.party-info .details td { padding:2px 0; color:#111; }
.party-info .label { width:38%; }
.party-info .value { text-align:right; overflow-wrap:break-word; }
.panel { background:#f3f6f5; border:1px solid #d9e2df; padding:10px; }
.details td { padding:2px 0; overflow-wrap:break-word; } .label { width:35%; color:#516259; }
.section { margin:12px 0; } .products th { background:#28633d; color:#fff; padding:7px; text-align:left; font-size:10.5px; }
.products td { padding:5px 7px; border:1px solid #d5dfda; } .products tr { page-break-inside:avoid; }
.products .total-row td { background:#e4efe6; font-weight:bold; color:#245c37; }
.right { text-align:right; } .total { background:#e4efe6; color:#245c37; font-weight:bold; font-size:12px; }
.bottom { margin-top:10px; page-break-inside:avoid; border-collapse:separate; border-spacing:8px 0; margin-left:-8px; width:calc(100% + 8px); }
.payment-card { background:#fbfdfb; border:1px solid #d6e5db; padding:9px 11px; }
.payment-card h2 { color:#1f633c; font-size:13px; margin:0 0 6px; padding-bottom:5px; border-bottom:2px solid #dbece0; }
.payment-card .details td { padding:2px 0; }
.payment-card .details .label { color:#63756b; width:42%; }
.amount-summary td { padding:4px 0; border-bottom:1px solid #e4eee7; }
.amount-summary .deal-total td { background:#e3f2e8; color:#175d35; font-size:13px; font-weight:bold; padding:6px; border-bottom:0; }
.qr-card { text-align:center; background:#f3faf5; border:1px dashed #9fc5aa; padding:12px; }
.qr-card img { display:block; max-width:76px; max-height:76px; margin:0 auto 5px; }
.qr-card .muted { color:#2b6d45; font-weight:bold; }
.signature { text-align:right; margin-top:14px; } .signature-line { margin:35px 0 4px auto; border-top:1px solid #687a70; width:145px; }
</style></head><body><div class="page-frame"></div><div class="pdf-frame">
<table class="brand"><tr><td style="width:42%">@if(!empty($images['company_logo_path']))<img src="{{ $images['company_logo_path'] }}" style="max-width:220px;max-height:65px;">@else<span class="company">{{ $company }}</span>@endif</td><td class="right"><div class="company">{{ mb_strtoupper($company) }}</div><div class="muted">{{ $settings['company_address'] ?? '' }}</div><div class="muted">{{ $settings['phone'] ?? '' }} @if(!empty($settings['email'])) | {{ $settings['email'] }} @endif</div></td></tr></table>
<table class="section party-info"><tbody><tr>
    <td>
        <h2>Customer Details:</h2>
        <table class="details">
            @foreach($customerFields as $label => $value)
                <tr><td class="label">{{ $label }} :</td><td class="value">{{ $value }}</td></tr>
            @endforeach
        </table>
    </td>
    <td class="info-divider">
        <h2>Deal Info:</h2>
        <table class="details">
            @foreach($infoFields as $label => $value)
                <tr><td class="label">{{ $label }} :</td><td class="value">{{ $value }}</td></tr>
            @endforeach
        </table>
    </td>
</tr></tbody></table>
@if($estimate)
@php
    $doc = $estimate;
    $subsidyValue = (float) ($doc->subsidy_amount ?? 0);
    $productsRaw = $doc->product_name ?? [];
    $products = is_array($productsRaw) ? $productsRaw : (json_decode((string) $productsRaw, true) ?: []);
    $bomValue = 0.0;
    foreach ($products as $product) {
        if (is_array($product)) {
            $bomValue += (float) ($product['quantity'] ?? 0) * (float) ($product['price'] ?? 0);
        }
    }
    $baseSystemValue = (float) ($doc->price ?? 0);
    $gstRate = (float) ($doc->gst ?? 0);
    $gstBreakdown = is_array($doc->gst_breakdown ?? null)
        ? $doc->gst_breakdown
        : (json_decode((string) ($doc->gst_breakdown ?? ''), true) ?: []);
    $taxLines = [];
    $usesGlobalTax = false;
    if (!empty($gstBreakdown['groups']) && is_array($gstBreakdown['groups'])) {
        foreach ($gstBreakdown['groups'] as $group) {
            if ((string) ($group['tax_type'] ?? '') === 'global_tax') {
                $usesGlobalTax = true;
            }
            if ((string) ($group['tax_type'] ?? '') === 'gst_percent') {
                continue;
            }
            foreach (($group['lines'] ?? []) as $line) {
                $label = trim((string) ($line['label'] ?? ''));
                $amount = (float) ($line['amount'] ?? 0);
                if ($label !== '' && strtoupper($label) !== 'GST' && $amount > 0) {
                    $taxLines[] = ['label' => $label, 'rate' => $line['rate'] ?? null, 'amount' => $amount];
                }
            }
        }
    }
    if (empty($taxLines)) {
        $taxBuckets = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            $taxable = (float) ($product['quantity'] ?? 0) * (float) ($product['price'] ?? 0);
            $rate = (float) ($product['tax_rate'] ?? 0);
            $label = strtoupper(trim((string) ($product['tax_label'] ?? '')));
            if ($taxable <= 0 || $rate <= 0) {
                continue;
            }
            $parts = str_contains($label, 'CGST') && str_contains($label, 'SGST')
                ? [['CGST', $rate / 2], ['SGST', $rate / 2]]
                : [[str_contains($label, 'IGST') ? 'IGST' : 'GST', $rate]];
            foreach ($parts as [$taxLabel, $taxRate]) {
                $key = $taxLabel . '|' . number_format($taxRate, 4, '.', '');
                if (!isset($taxBuckets[$key])) {
                    $taxBuckets[$key] = ['label' => $taxLabel, 'rate' => $taxRate, 'amount' => 0.0];
                }
                $taxBuckets[$key]['amount'] += ($taxable * $taxRate) / 100;
            }
        }
        $taxLines = array_values($taxBuckets);
    }
    $gstValue = !empty($taxLines)
        ? array_sum(array_map(static fn ($line) => (float) ($line['amount'] ?? 0), $taxLines))
        : (float) ($doc->gst_amount ?? ($bomValue * ($gstRate / 100)));
    if (empty($taxLines) && $gstValue > 0) {
        $taxLines = $gstRate > 0
            ? [
                ['label' => 'CGST', 'rate' => $gstRate / 2, 'amount' => $gstValue / 2],
                ['label' => 'SGST', 'rate' => $gstRate / 2, 'amount' => $gstValue / 2],
            ]
            : [['label' => 'GST', 'rate' => null, 'amount' => $gstValue]];
    }
    $solarStructureValue = (float) ($doc->solar_structure_charges ?? 0);
    $discountValue = (float) ($doc->discount ?? 0);
    $grossValue = $baseSystemValue + $bomValue + $gstValue + $solarStructureValue - $discountValue;
    $netInvestment = max(0, $grossValue - $subsidyValue);
@endphp
            <table class="products">
                <thead><tr><th>Line Item Description</th><th>Amount (&#8377;)</th></tr></thead>
                <tbody>
                    @if (($doc->price_mode ?? '') !== 'bom' && ($usesGlobalTax || $baseSystemValue > 0))
                        <tr><td>Base cost</td><td>{{ $money($baseSystemValue) }}</td></tr>
                    @endif
                    <tr><td>Bill of Materials (BOM)</td><td>{{ $usesGlobalTax ? '--' : $money($bomValue) }}</td></tr>
                    @if ($gstValue > 0)
                        <tr><td><strong>{{ $usesGlobalTax ? 'Global Tax on Base Price' : 'Taxes on Bill of Materials (BOM Only)' }}</strong></td><td></td></tr>
                        @foreach ($taxLines as $taxLine)
                            @php
                                $taxRateText = is_numeric($taxLine['rate'] ?? null)
                                    ? rtrim(rtrim(number_format((float) $taxLine['rate'], 2, '.', ''), '0'), '.')
                                    : '';
                            @endphp
                            <tr>
                                <td>{{ $taxLine['label'] }}{{ $taxRateText !== '' ? ' (' . $taxRateText . '%)' : '' }}</td>
                                <td>{{ $money($taxLine['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr><td><strong>{{ $usesGlobalTax ? 'Total Global Tax' : 'Total Taxes on BOM' }}</strong></td><td><strong>{{ $money($gstValue) }}</strong></td></tr>
                    @endif
                    @if ($solarStructureValue > 0)
                        <tr><td>Solar Structure Charges</td><td>{{ $money($solarStructureValue) }}</td></tr>
                    @endif
                    @if ($discountValue > 0)
                        <tr><td>Discount</td><td>- {{ $money($discountValue) }}</td></tr>
                    @endif
                    <tr><td><strong>Consumer Net Payable</strong></td><td><strong>{{ $money($grossValue) }}</strong></td></tr>
                    @if ($subsidyValue > 0)
                        <tr><td>Subsidy</td><td>- {{ $money($subsidyValue) }}</td></tr>
                    @endif
                    <tr class="total-row"><td>Net Amount Payable</td><td>{{ $money($netInvestment) }}</td></tr>
                </tbody>
            </table>
            @if ($subsidyValue > 0)
                <p style="margin-top: 6px; margin-bottom: 4px; font-size: 11px; color: #555;"><strong>Note:</strong> Subsidy Amount to be credited in clients account.</p>
            @endif
@else<p>No linked estimate available.</p>@endif
<table class="bottom"><tr>
    <td style="width:{{ !empty($images['company_qr_code_path']) ? '43%' : '52%' }}"><div class="payment-card"><h2>Bank Details</h2><table class="details">@foreach(['Bank Name'=>'bank_name', 'Account Name'=>'account_name', 'Branch'=>'branch_name', 'A/C No.'=>'account_number', 'IFSC Code'=>'ifsc_code'] as $label=>$key)<tr><td class="label">{{ $label }}</td><td>{{ $settings[$key] ?? '—' }}</td></tr>@endforeach</table></div></td>
    @if(!empty($images['company_qr_code_path']))<td style="width:16%"><div class="qr-card"><img src="{{ $images['company_qr_code_path'] }}"><div class="muted">Scan &amp; Pay</div></div></td>@endif
    <td style="width:{{ !empty($images['company_qr_code_path']) ? '41%' : '48%' }}"><div class="payment-card"><h2>Value Summary</h2><table class="amount-summary">@if($estimate)<tr><td>Estimate Total</td><td class="right">{{ $money($estimate->amount) }}</td></tr>@endif<tr class="deal-total"><td>Deal Value</td><td class="right">{{ $money($deal->amount) }}</td></tr></table></div></td>
</tr></table>
@if($estimate?->comment)<div class="section"><strong>Remarks:</strong><br>{!! nl2br(e($estimate->comment)) !!}</div>@endif
<div class="signature">For, {{ $company }}<div class="signature-line"></div><span class="muted">Authorized Signatory</span></div>
</div></body></html>
