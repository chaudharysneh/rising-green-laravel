<?php
$summarySubtotal = ($estdata && isset($estdata->price)) ? (float) $estdata->price : 0;
$summaryGstRate = ($estdata && isset($estdata->gst)) ? (float) $estdata->gst : 0;
$summaryDiscount = ($estdata && isset($estdata->discount)) ? (float) $estdata->discount : 0;
$summarySubsidy = ($estdata && isset($estdata->subsidy_amount)) ? (float) $estdata->subsidy_amount : 0;
$summarySolarStructureCharges = ($estdata && isset($estdata->solar_structure_charges)) ? (float) $estdata->solar_structure_charges : 0;
$summaryIsQuotation = ($estdata && isset($estdata->is_quotation)) ? (int) $estdata->is_quotation : 0;
$summaryGstAmount = null;
$summaryGstBreakdown = [];

if ($estdata && isset($estdata->gst_amount) && $estdata->gst_amount !== null && $estdata->gst_amount !== '') {
    $summaryGstAmount = (float) $estdata->gst_amount;
}

if ($estdata && !empty($estdata->gst_breakdown)) {
    $decodedSummaryGst = json_decode($estdata->gst_breakdown, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSummaryGst)) {
        $summaryGstBreakdown = $decodedSummaryGst;
        if ($summaryGstAmount === null && isset($decodedSummaryGst['gst_amount'])) {
            $summaryGstAmount = (float) $decodedSummaryGst['gst_amount'];
        }
    }
}

if ($summaryGstAmount === null && $estdata && !empty($estdata->product_name)) {
    $summaryItems = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
    if (is_array($summaryItems)) {
        $summaryTaxSum = 0.0;
        foreach ($summaryItems as $item) {
            if (isset($item['tax_amount']) && is_numeric($item['tax_amount'])) {
                $summaryTaxSum += (float) $item['tax_amount'];
            }
        }
        if ($summaryTaxSum > 0) {
            $summaryGstAmount = $summaryTaxSum;
        }
    }
}

if ($summaryGstAmount === null) {
    $summaryGstAmount = ($summarySubtotal + $summarySolarStructureCharges) * ($summaryGstRate / 100);
}

$summaryTotalPayable = $summarySubtotal + $summarySolarStructureCharges + $summaryGstAmount - $summaryDiscount;
$summaryLendingCost = $summaryTotalPayable - $summarySubsidy;
$summaryHeaderCellStyle = "background-color:#4b9349;color:#fff;border:1px solid #333;padding:9px 11px;font-size:13px;font-family:'Montserrat',sans-serif;font-weight:bold;line-height:1.4;";
$summaryCellStyle = "border:1px solid #333;padding:9px 11px;font-size:13px;font-family:'Montserrat',sans-serif;color:#000;line-height:1.4;";
$summaryRightCellStyle = $summaryCellStyle . 'text-align:right;';
$summaryHighlightCellStyle = $summaryRightCellStyle . 'background-color:#4b9349;color:#fff;font-weight:bold;';
$summaryHeaderTextStyle = "font-size:13.5px;font-family:'Montserrat',sans-serif;color:#000;line-height:1.45;";
$summaryEstimateNo = $estdata->estimate_no ?? ($estimate_no ?? '--');
$summaryDate = ($estdata && !empty($estdata->estimate_date)) ? date('Y-m-d', strtotime($estdata->estimate_date)) : date('Y-m-d');
$summaryCompanyName = $companySettings['company_name'] ?? $globalCompanyName ?? '--';
$summaryCompanyAddress = $companySettings['company_address'] ?? $user['address'] ?? '';
$summaryCompanyPhone = $companySettings['phone'] ?? $user['phone'] ?? $user['contact'] ?? '';
$summaryEstimateTypeLabel = $estdata && isset($estdata->type) ? ucfirst((string) $estdata->type) : '--';
$summarySolarMeterLabel = ($estdata && !empty($estdata->solar_meter_charges)) ? ucwords(str_replace('_', ' ', (string) $estdata->solar_meter_charges)) : '--';
$summaryEstimateComment = $estdata && isset($estdata->estimate_comment) ? $estdata->estimate_comment : ($estdata->comment ?? '--');
$summaryBankLines = array_filter([
    !empty($companySettings['bank_name']) ? '<strong>Bank:</strong> ' . esc($companySettings['bank_name']) : '',
    !empty($companySettings['account_name']) ? '<strong>Account Name:</strong> ' . esc($companySettings['account_name']) : '',
    !empty($companySettings['account_number']) ? '<strong>Account No.:</strong> ' . esc($companySettings['account_number']) : '',
    !empty($companySettings['ifsc_code']) ? '<strong>IFSC:</strong> ' . esc($companySettings['ifsc_code']) : '',
    !empty($companySettings['branch_name']) ? '<strong>Branch:</strong> ' . esc($companySettings['branch_name']) : '',
]);
$summaryQrImage = !empty($companyQrCodePath) ? normalize_pdf_image($companyQrCodePath) : '';
$summaryGstRateText = is_numeric($summaryGstRate) ? rtrim(rtrim(number_format((float) $summaryGstRate, 2, '.', ''), '0'), '.') : '';
$summaryShowGst = ((float) $summaryGstAmount > 0) || ((float) $summaryGstRate > 0);
$summaryBreakupLines = [];

if (!empty($summaryGstBreakdown['groups']) && is_array($summaryGstBreakdown['groups'])) {
    foreach ($summaryGstBreakdown['groups'] as $group) {
        if ((string) ($group['tax_type'] ?? '') === 'gst_percent') {
            continue;
        }
        $lines = $group['lines'] ?? [];
        if (!is_array($lines)) {
            continue;
        }
        foreach ($lines as $line) {
            $label = trim((string) ($line['label'] ?? ''));
            if ($label === '' || strtoupper($label) === 'GST') {
                continue;
            }
            $summaryBreakupLines[] = [
                'label' => $label,
                'rate' => $line['rate'] ?? null,
                'amount' => (float) ($line['amount'] ?? 0),
            ];
        }
    }
}

if (empty($summaryBreakupLines) && $estdata && !empty($estdata->product_name)) {
    $summaryItems = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
    if (is_array($summaryItems)) {
        $summaryTaxBuckets = [
            'CGST' => ['amount' => 0.0, 'rate' => null],
            'SGST' => ['amount' => 0.0, 'rate' => null],
            'IGST' => ['amount' => 0.0, 'rate' => null],
        ];
        foreach ($summaryItems as $item) {
            foreach (['cgst' => 'CGST', 'sgst' => 'SGST', 'igst' => 'IGST'] as $key => $label) {
                if (isset($item[$key . '_amount']) && is_numeric($item[$key . '_amount'])) {
                    $summaryTaxBuckets[$label]['amount'] += (float) $item[$key . '_amount'];
                    if ($summaryTaxBuckets[$label]['rate'] === null && isset($item[$key . '_rate']) && is_numeric($item[$key . '_rate'])) {
                        $summaryTaxBuckets[$label]['rate'] = (float) $item[$key . '_rate'];
                    }
                }
            }
        }
        foreach ($summaryTaxBuckets as $label => $bucket) {
            if ($bucket['amount'] > 0) {
                $summaryBreakupLines[] = ['label' => $label, 'rate' => $bucket['rate'], 'amount' => $bucket['amount']];
            }
        }
    }
}
?>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:0;margin-bottom:12px;border-collapse:collapse;">
    <tr>
        <td width="45%" valign="top" align="left" style="<?= $summaryHeaderTextStyle ?>padding-bottom:12px;">
            <?php if (!empty($logoBase64)): ?>
                <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width:160px;height:auto;">
            <?php else: ?>
                <span style="color:#666;">Company Logo</span>
            <?php endif; ?>
        </td>
        <td width="55%" valign="top" align="right" style="<?= $summaryHeaderTextStyle ?>padding-bottom:12px;">
            <strong style="font-size:16px;"><?= esc($summaryCompanyName) ?></strong><br>
            <?= esc($summaryCompanyAddress ?: '--') ?><br>
            <?= esc($summaryCompanyPhone ?: '--') ?><br>
            <span style="color:#4b9349;font-weight:bold;">Google Location Map</span>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-top:1px solid #e5e5e5;padding-top:14px;"></td>
    </tr>
</table>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
    <tr>
        <td width="33%" align="left" style="<?= $summaryHeaderTextStyle ?>font-weight:bold;">Estimate no.: #<?= esc($summaryEstimateNo) ?></td>
        <td width="34%" align="center" style="<?= $summaryHeaderTextStyle ?>font-weight:bold;text-decoration:underline;">ESTIMATION</td>
        <td width="33%" align="right" style="<?= $summaryHeaderTextStyle ?>font-weight:bold;">Date: <?= esc($summaryDate) ?></td>
    </tr>
</table>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:10px;border-collapse:collapse;">
    <tr>
        <td colspan="4" style="<?= $summaryHeaderCellStyle ?>">Customer Details</td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong>Customer Name</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->name ?? '--') ?></td>
        <td style="<?= $summaryCellStyle ?>"><strong>Email</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->email ?? '--') ?></td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><strong>Address</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->address ?? '--') ?></td>
        <td style="<?= $summaryCellStyle ?>"><strong>Contact</strong></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->phone ?? '--') ?></td>
    </tr>
</table>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:10px;border-collapse:collapse;">
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>">Estimate Name</td>
        <td style="<?= $summaryHeaderCellStyle ?>">Quantity (kW)</td>
        <td style="<?= $summaryHeaderCellStyle ?>">Price</td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>"><?= esc($estdata->estimate_name ?? '--') ?></td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($quantity) ?></td>
        <td style="<?= $summaryRightCellStyle ?>"><?= number_format((float) ($estdata->price ?? 0), 2) ?></td>
    </tr>
    <tr>
        <td colspan="2" style="<?= $summaryRightCellStyle ?>"><strong>Base Price</strong></td>
        <td style="<?= $summaryRightCellStyle ?>"><strong><?= number_format($summarySubtotal, 2) ?></strong></td>
    </tr>
    <?php if ($summarySolarStructureCharges > 0): ?>
    <tr>
        <td colspan="2" style="<?= $summaryRightCellStyle ?>">Solar Structure Charges</td>
        <td style="<?= $summaryRightCellStyle ?>"><?= number_format($summarySolarStructureCharges, 2) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($summaryIsQuotation === 1 && $summaryShowGst): ?>
    <tr>
        <td colspan="2" style="<?= $summaryRightCellStyle ?>">GST<?= $summaryGstRateText !== '' ? ' (' . esc($summaryGstRateText) . '%)' : '' ?></td>
        <td style="<?= $summaryRightCellStyle ?>"><?= number_format((float) $summaryGstAmount, 2) ?></td>
    </tr>
    <?php elseif ($summaryShowGst && !empty($summaryBreakupLines)): ?>
        <?php foreach ($summaryBreakupLines as $line): ?>
            <?php
            $lineLabel = trim((string) ($line['label'] ?? ''));
            $lineRate = $line['rate'] ?? null;
            $lineAmount = (float) ($line['amount'] ?? 0);
            if ($lineLabel === '' || $lineAmount <= 0) {
                continue;
            }
            $lineRateText = is_numeric($lineRate) ? rtrim(rtrim(number_format((float) $lineRate, 2, '.', ''), '0'), '.') : '';
            ?>
            <tr>
                <td colspan="2" style="<?= $summaryRightCellStyle ?>"><?= esc($lineLabel) ?><?= $lineRateText !== '' ? ' (' . esc($lineRateText) . '%)' : '' ?></td>
                <td style="<?= $summaryRightCellStyle ?>"><?= number_format($lineAmount, 2) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($summaryDiscount > 0): ?>
    <tr>
        <td colspan="2" style="<?= $summaryRightCellStyle ?>">Discount</td>
        <td style="<?= $summaryRightCellStyle ?>">-<?= number_format($summaryDiscount, 2) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <td colspan="2" style="<?= $summaryRightCellStyle ?>"><strong>Customer Payable Amount</strong></td>
        <td style="<?= $summaryHighlightCellStyle ?>"><?= number_format($summaryTotalPayable, 2) ?></td>
    </tr>
    <?php if ($summarySubsidy > 0): ?>
    <tr>
        <td colspan="2" style="<?= $summaryRightCellStyle ?>">Subsidy</td>
        <td style="<?= $summaryRightCellStyle ?>">-<?= number_format($summarySubsidy, 2) ?></td>
    </tr>
    <tr>
        <td colspan="2" style="<?= $summaryRightCellStyle ?>"><strong>Lending Cost Of Customer</strong></td>
        <td style="<?= $summaryHighlightCellStyle ?>"><?= number_format($summaryLendingCost, 2) ?></td>
    </tr>
    <?php endif; ?>
</table>

<?php if ($summarySubsidy > 0): ?>
<div style="width:98%;margin:8px auto 0;font-size:11.5px;font-family:'Montserrat',sans-serif;color:#000;line-height:1.4;">
    <strong>Note:</strong> Subsidy Amount to be credited in clients account.
</div>
<?php endif; ?>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:10px;border-collapse:collapse;">
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>width:38%;">System Capacity</td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($quantity) ?> kW</td>
    </tr>
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>">Estimate Type</td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($summaryEstimateTypeLabel) ?></td>
    </tr>
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>">Solar Meter Charges</td>
        <td style="<?= $summaryCellStyle ?>"><?= esc($summarySolarMeterLabel) ?></td>
    </tr>
</table>

<table width="98%" align="center" cellpadding="0" cellspacing="0" style="margin-top:10px;margin-bottom:20px;border-collapse:collapse;">
    <tr>
        <td style="<?= $summaryHeaderCellStyle ?>width:35%;">Comment</td>
        <td style="<?= $summaryHeaderCellStyle ?>width:40%;">Bank Details</td>
        <td style="<?= $summaryHeaderCellStyle ?>width:25%;">QR Code</td>
    </tr>
    <tr>
        <td style="<?= $summaryCellStyle ?>vertical-align:top;"><?= nl2br(esc($summaryEstimateComment ?: '--')) ?></td>
        <td style="<?= $summaryCellStyle ?>vertical-align:top;">
            <?= !empty($summaryBankLines) ? implode('<br>', $summaryBankLines) : 'No bank details available.' ?>
        </td>
        <td style="<?= $summaryCellStyle ?>vertical-align:top;text-align:center;">
            <?php if (!empty($summaryQrImage)): ?>
                <img src="<?= $summaryQrImage ?>" alt="QR Code" style="max-width:80px;max-height:80px;">
            <?php else: ?>
                No QR code available.
            <?php endif; ?>
        </td>
    </tr>
</table>
