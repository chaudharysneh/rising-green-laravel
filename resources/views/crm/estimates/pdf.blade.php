<?php
if (!function_exists('normalize_pdf_image')) {
    function normalize_pdf_image($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        // If it's a base64 data URI, return as-is
        if (strpos($path, 'data:image') === 0) {
            return $path;
        }

        // If it starts with http/https
        if (preg_match('/^https?:\/\//i', $path)) {
            $urlParts = parse_url($path);
            if (isset($urlParts['path'])) {
                $urlPath = ltrim($urlParts['path'], '/');
                $candidates = [
                    public_path($urlPath),
                    public_path(preg_replace('#^public/#i', '', $urlPath)),
                    base_path($urlPath)
                ];
                foreach ($candidates as $candidate) {
                    if (file_exists($candidate) && is_file($candidate)) {
                        return $candidate;
                    }
                }
            }
            return $path;
        }

        // It is a relative path or filename
        $cleanPath = preg_replace('#^public(?:/|\\\\)#i', '', $path);
        $cleanPath = ltrim($cleanPath, '/\\');

        $candidates = [
            public_path($cleanPath),
            public_path('assets/' . $cleanPath),
            public_path('uploads/' . $cleanPath),
            public_path('uploads/img/product/' . $cleanPath),
            public_path('assets/img/profile/' . $cleanPath),
            public_path('assets/uploads/' . $cleanPath),
            public_path('uploads/products/' . $cleanPath),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return asset($cleanPath);
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        if (empty($path)) {
            return rtrim(url('/'), '/') . '/';
        }
        return normalize_pdf_image($path);
    }
}
?>
<?php
if (!isset($estdata) && isset($estimate)) {
    $estdata = new \stdClass();
    
    $attrs = [];
    if ($estimate instanceof \Illuminate\Database\Eloquent\Model) {
        $attrs = $estimate->getAttributes();
    } elseif (is_array($estimate)) {
        $attrs = $estimate;
    } else {
        $attrs = (array) $estimate;
    }
    
    foreach ($attrs as $key => $val) {
        $estdata->$key = $val;
    }
    
    if (isset($estimate->customer)) {
        $estdata->name = $estimate->customer->name ?? '--';
        $estdata->email = $estimate->customer->email ?? '--';
        $estdata->address = $estimate->customer->address ?? '--';
        $estdata->contact = $estimate->customer->contact ?? '--';
    }
}
?>
<div class="quotation-block">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .quotation-box {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #fff;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }

        .quotation-header table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .quotation-header td {
            vertical-align: top;
            padding: 5px 0;
        }

        .company-logo img {
            max-width: 90px;
            display: block;
        }

        .quotation-title {
            font-size: 16px;
            text-align: right;
            color: #686868;
        }

        .info-table,
        .quotation-table,
        .extra-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .info-table th,
        .info-table td,
        .quotation-table th,
        .quotation-table td,
        .extra-info th,
        .extra-info td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        .info-table th,
        .quotation-table th,
        .extra-info th {
            background-color: #52866A;
            color: #fff;
        }

        .quotation-table tfoot td {
            font-weight: bold;
            text-align: right;
        }

        .quotation-footer {
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
            color: #555;
        }

        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-direction: row;
        }

        .center-text {
            text-align: center;
            font-weight: 700;
            text-decoration: underline;
        }
    </style>

    <main id="main" class="main">
        <div class="quotation-box">

            <!-- Header -->
            <div class="quotation-header">
                <table>
                    <tr>
                        <td class="company-logo">
                            <?php
$company_name = isset($user['company_name']) ? $user['company_name'] : 'NA';
$company_logo = isset($user['company_logo']) ? $user['company_logo'] : 'default_logo.jpg';
                            ?>
                            <img src="<?php echo base_url(); ?>public/assets/img/profile/<?php echo htmlspecialchars($company_logo); ?>"
                                alt="Company Logo" style="max-width: 300px; width: 50%; height: auto;">
                        </td>
                        <td class="quotation-title">
                            <div style="line-height:22px;color:#000">
                                <strong
                                    style="font-size:18px;color:#000"><?php echo htmlspecialchars($company_name); ?></strong><br>
                                <?php echo htmlspecialchars($user['address'] ?? '--'); ?><br>
                                <?php echo htmlspecialchars($user['country'] ?? '--'); ?>,
                                <?php echo htmlspecialchars($user['state'] ?? '--'); ?>,
                                <?php echo htmlspecialchars($user['city'] ?? '--'); ?>
                                <?php echo htmlspecialchars($user['pincode'] ?? '--'); ?><br>
                                <?php echo htmlspecialchars($user['contact'] ?? '--'); ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <hr>
            </div>

            <!-- Quotation Info -->
            <div class="flex-between">
                <div style="font-weight:700; font-size:15px;">
                    Estimate no.: #<?php echo htmlspecialchars($estdata->estimate_no); ?>
                </div>
                <div class="center-text" style="font-size:16px;">ESTIMATION</div>
                <div style="font-weight:700; font-size:15px;">
                    Date: <?php echo htmlspecialchars($estdata->estimate_date); ?>
                </div>
            </div>

            <!-- Customer Info Table -->
            <table class="info-table">
                <thead>
                    <tr>
                        <th colspan="4">Customer Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Customer Name</strong></td>
                        <td><?php echo htmlspecialchars($estdata->name ?? '--'); ?></td>
                        <td><strong>Email</strong></td>
                        <td><?php echo htmlspecialchars($estdata->email ?? '--'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Address</strong></td>
                        <td><?php echo htmlspecialchars($estdata->address ?? '--'); ?></td>
                        <td><strong>Contact</strong></td>
                        <td><?php echo htmlspecialchars($estdata->contact ?? '--'); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Estimate Details Table (no comment column) -->
            <table class="quotation-table">
                <thead>
                    <tr>
                        <th>Estimate Name</th>
                        <th>Quantity (kW)</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($estdata->estimate_name ?? '--'); ?></td>
                        <td><?php echo htmlspecialchars($estdata->quantity ?? '0'); ?></td>
                        <td><?php echo number_format((float) ($estdata->price ?? 0), 2); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <?php
// Use the estimate's main price as base
$subtotal = (float) ($estdata->price ?? 0);
$gstRate = (float) ($estdata->gst ?? 0);
$discount = (float) ($estdata->discount ?? 0);
$subsidy = (float) ($estdata->subsidy_amount ?? 0);
$solarStructureCharges = (float) ($estdata->solar_structure_charges ?? 0);

// Calculate totals
$gstAmount = $subtotal * ($gstRate / 100);
$totalPayable = $subtotal + $solarStructureCharges + $gstAmount - $discount; // exclude subsidy
$lendingCost = $totalPayable - $subsidy;
// $totalBeforeDiscount = $subtotal + $gstAmount + $solarStructureCharges;
// $finalTotal = $totalBeforeDiscount - $discount - $subsidy;
                    ?>
                    <tr>
                        <td colspan="2">Base Price</td>
                        <td><?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php if ($solarStructureCharges > 0): ?>
                    <tr>
                        <td colspan="2">Solar Structure Charges</td>
                        <td><?php    echo number_format($solarStructureCharges, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($gstRate > 0): ?>
                    <tr>
                        <td colspan="2">GST (<?php    echo $gstRate; ?>%)</td>
                        <td><?php    echo number_format($gstAmount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($discount > 0): ?>
                    <tr>
                        <td colspan="2">Discount</td>
                        <td>-<?php    echo number_format($discount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr style="font-weight: bold; border-top: 2px solid #000;">
                        <td colspan="2">Customer Payable Amount</td>
                        <td style="background-color: #52866A; color: #fff;">
                            <?php echo number_format($totalPayable, 2); ?></td>
                    </tr>
                    <?php if ($subsidy > 0): ?>
                    <tr>
                        <td colspan="2">Subsidy</td>
                        <td>-<?php    echo number_format($subsidy, 2); ?></td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td colspan="2">Lending Cost Of Customer</td>
                        <td style="background-color: #52866A; color: #fff;">
                            <?php    echo number_format($lendingCost, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <!-- <tr style="font-weight: bold; border-top: 2px solid #000;">
                        <td colspan="2">Total Amount</td>
                        <td><? // echo number_format($finalTotal, 2); ?></td>
                    </tr> -->
                </tfoot>
            </table>
            <?php if ($subsidy > 0): ?>
            <p style="font-size: 15px; margin-top: 2px; color: #555;"><strong>Note:</strong> Subsidy Amount to be
                credited in clients account.</p>
            <?php endif; ?>
            <!-- Extra Info and Side-by-side Comment / Bank Details -->
            <div class="extra-info">
                <table>
                    <tr>
                        <th>System Capacity</th>
                        <td><?php echo htmlspecialchars($estdata->quantity ?? '0'); ?> kW</td>
                    </tr>
                    <tr>
                        <th>Estimate Type</th>
                        <td><?php echo htmlspecialchars(ucfirst($estdata->type ?? '')); ?></td>
                    </tr>
                    <?php if (!empty($estdata->solar_meter_charges)): ?>
                    <tr>
                        <th>Solar Meter Charges</th>
                        <td><?php    echo htmlspecialchars(ucwords(str_replace('_', ' ', $estdata->solar_meter_charges))); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php
// Fetch bank details for current user
// try {
//     $bankModel = new \App\Models\BankModel();
//     $currentUserId = session()->get('id');
//     $bank = $bankModel->where('user_id', $currentUserId)->orderBy('id', 'DESC')->first();
// } catch (\Throwable $e) {
//     $bank = null;
// }
            ?>

            <!-- Comment + Bank Details Table -->
            {{-- <table class="info-table" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width: 35%;">Comment</th>
                        <th style="width: 40%;">Bank Details</th>
                        <th style="width: 25%;">QR Code</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <!-- Comment Column -->
                        <td style="vertical-align: top; background: #fafafa;">
                            <?php echo nl2br(htmlspecialchars($estdata->estimate_comment ?? ($estdata->comment ?? '--'))); ?>
                        </td>

                        <!-- Bank Details Column -->
                        <td style="vertical-align: top; background: #fafafa;">
                            <?php if ($bank): ?>
                            <div><strong>Bank:</strong> <?php    echo htmlspecialchars($bank['bank_name'] ?? '--'); ?>
                            </div>
                            <div><strong>Account Name:</strong>
                                <?php    echo htmlspecialchars($bank['account_name'] ?? '--'); ?></div>
                            <div><strong>Account No.:</strong>
                                <?php    echo htmlspecialchars($bank['account_number'] ?? '--'); ?></div>
                            <div><strong>IFSC:</strong> <?php    echo htmlspecialchars($bank['ifsc_code'] ?? '--'); ?>
                            </div>
                            <div><strong>Branch:</strong> <?php    echo htmlspecialchars($bank['branch_name'] ?? '--'); ?>
                            </div>
                            <?php else: ?>
                            <div style="color:#666;">No bank details available.</div>
                            <?php endif; ?>
                        </td>
                        <td style="vertical-align: top; background: #fafafa; text-align:center;">
                            <?php if (!empty($user['qr_code'])): ?>
                            <img src="<?php    echo base_url(); ?>public/assets/img/profile/<?php    echo htmlspecialchars($user['qr_code']); ?>"
                                alt="QR Code"
                                style="max-width:120px; max-height:120px; object-fit:contain; border:1px solid #ddd; border-radius:4px;">
                            <?php else: ?>
                            <div style="color:#666;">No QR code available.</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table> --}}




            <!-- Page Break for BOM -->
            <div style="page-break-before: always;"></div>

            <!-- BOM Section -->
            <div style="margin-top: 20px;">
                <h2 style="text-align: center; color: #19547B; margin-bottom: 30px; text-decoration: underline;">
                    BILL OF MATERIALS (BOM)
                </h2>


                <table class="quotation-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Specifications</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$allproduct = json_decode($estdata->product_name, true); // since you used getRow()
if (is_array($allproduct) && !empty($allproduct)) {
    foreach ($allproduct as $item) {
        $product_id = $item['product_id'] ?? null;
        $product_name_display = $item['name'] ?? 'Product name not found';

        // Capitalize first letter of each word
        $product_name_display = ucwords(strtolower($product_name_display));

        $product_quantity = $item['quantity'] ?? 0;
        $product_category_makes = $item['category_name'] ?? '';

        // Find product details from master list
        $full_product_details = null;
        foreach ($product_data as $prod_detail) {
            if ($prod_detail['id'] == $product_id) {
                $full_product_details = $prod_detail;
                break;
            }
        }

        // Build specification list
        $specifications = [];
        if (!empty($product_category_makes)) {
            $specifications[] = '<strong>Make: </strong>' . htmlspecialchars($product_category_makes);
        }
        if (!empty($product_quantity)) {
            $specifications[] = '<strong>Quantity: </strong>' . htmlspecialchars($product_quantity);
        }
        if (!empty($full_product_details['technology'])) {
            // Decode JSON if it's a JSON string
            $techArray = json_decode($full_product_details['technology'], true);

            // If json_decode fails or returns a single value, normalize to array
            if (!is_array($techArray)) {
                $techArray = [$full_product_details['technology']];
            }

            // Filter out empty values
            $techArray = array_filter($techArray, fn($v) => trim((string) $v) !== '');

            // Only show if we have at least one valid value
            if (!empty($techArray)) {
                $techNames = array_map(fn($id) => $technology_map[$id] ?? $id, $techArray);
                $specifications[] = '<strong>Technology: </strong>' . htmlspecialchars(implode(', ', $techNames));
            }
        }


        if (!empty($full_product_details['warranty'])) {
            // Decode JSON if needed
            $warArray = json_decode($full_product_details['warranty'], true);

            // Normalize to array if not an array
            if (!is_array($warArray)) {
                $warArray = [$full_product_details['warranty']];
            }

            // Filter out empty values
            $warArray = array_filter($warArray, fn($v) => trim((string) $v) !== '');

            // Only show if we have valid values
            if (!empty($warArray)) {
                // Map IDs to names
                $warNames = array_map(fn($id) => $warranty_map[$id] ?? $id, $warArray);
                $specifications[] = '<strong>Warranty: </strong>' . htmlspecialchars(implode(', ', $warNames));
            }
        }

        if (!empty($full_product_details['height'])) {
            $specifications[] = '<strong>Height: </strong>' . htmlspecialchars($full_product_details['height']);
        }
        if (!empty($full_product_details['fitting_material'])) {
            $specifications[] = '<strong>Fitting Material: </strong>' . htmlspecialchars($full_product_details['fitting_material']);
        }
        if (!empty($full_product_details['fitting_type'])) {
            $specifications[] = '<strong>Fitting Type: </strong>' . htmlspecialchars($full_product_details['fitting_type']);
        }
        if (!empty($full_product_details['thickness'])) {
            $specifications[] = '<strong>Thickness: </strong>' . htmlspecialchars($full_product_details['thickness']);
        }
        if (!empty($full_product_details['size_of_pipe'])) {
            $specifications[] = '<strong>Size of Pipe: </strong>' . htmlspecialchars($full_product_details['size_of_pipe']);
        }
        if (!empty($full_product_details['capacity'])) {
            $specifications[] = '<strong>Capacity: </strong>' . htmlspecialchars($full_product_details['capacity']);
        }

        $specifications_html = implode('<br>', $specifications);
                ?>
                        <tr>
                            <td><?= htmlspecialchars($product_name_display); ?></td>
                            <td><?= $specifications_html; ?></td>
                        </tr>
                        <?php    }
} else { ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: #666;">No products added to this estimate
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>

            </div>



        </div>
    </main>
</div>