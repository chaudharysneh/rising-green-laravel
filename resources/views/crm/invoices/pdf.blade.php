<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        .quotation-box,
        .quotation-box * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.4;
        }

        .quotation-box {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
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
            max-width: 300px;
            width: 50%;
            height: auto;
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
            background-color: #3B5BDB;
            color: #fff;
        }

        .quotation-table tfoot td {
            font-weight: bold;
            text-align: right;
            padding: 8px 12px;
        }

        .quotation-table tbody td {
            padding: 10px 12px;
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
            flex: 1;
        }

        .page-break {
            page-break-before: always;
            margin-top: 40px;
        }

        .bom-section h2 {
            text-align: center;
            color: #19547B;
            margin-bottom: 30px;
            text-decoration: underline;
            font-size: 20px;
        }

        .highlight-bg {
            background-color: #3B5BDB;
            color: #fff;
        }

        .note-text {
            font-size: 15px;
            margin-top: 2px;
            color: #555;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }

        .qr-code-img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="quotation-box">
        <!-- Header -->
        <div class="quotation-header">
            <table>
                <tr>
                    <td class="company-logo" style="width: 50%;">
                        @if($user && $user->company_logo)
                            <img src="{{ public_path('storage/' . $user->company_logo) }}" alt="Company Logo">
                        @else
                            <img src="{{ public_path('assets/img/logo.png') }}" alt="Company Logo">
                        @endif
                    </td>
                    <td class="quotation-title" style="width: 50%;">
                        <div style="line-height:22px;color:#000">
                            <strong
                                style="font-size:18px;color:#000">{{ $user->company_name ?? 'Company Name' }}</strong><br>
                            {{ $user->address ?? '--' }}<br>
                            {{ $user->country ?? '--' }}, {{ $user->state ?? '--' }}, {{ $user->city ?? '--' }}
                            {{ $user->pincode ?? '--' }}<br>
                            {{ $user->contact ?? '--' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <hr>

        <!-- Invoice Info -->
        <div class="flex-between">
            <div style="font-weight:700; font-size:15px;">Invoice no.: #{{ $invoice->invoice_no }}</div>
            <div class="center-text" style="font-size:16px;">TAX INVOICE</div>
            <div style="font-weight:700; font-size:15px;">Date: {{ optional($invoice->invoice_date)->format('Y-m-d') }}
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
                    <td>{{ $invoice->customer->name ?? '--' }}</td>
                    <td><strong>Email</strong></td>
                    <td>{{ $invoice->customer->email ?? '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Address</strong></td>
                    <td>{{ $invoice->customer->address ?? '--' }}</td>
                    <td><strong>Contact</strong></td>
                    <td>{{ $invoice->customer->contact ?? '--' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Invoice Details Table -->
        <table class="quotation-table" style="border: 1px solid #333; border-collapse: collapse; width: 100%; font-family: sans-serif; margin-bottom: 20px;">
            <thead style="background-color: #52866A; color: #fff;">
                <tr>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Invoice Name</th>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Quantity (kW)</th>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->invoice_name ?? '--' }}</td>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->quantity ?? '0' }}</td>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ number_format((float) ($invoice->price ?? 0), 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                @php
                    $subtotal = (float) ($invoice->total ?? 0);
                    $gstRate = (float) ($invoice->gst ?? 0);
                    $discount = (float) ($invoice->discount ?? 0);
                    $subsidy = (float) ($invoice->subsidy_amount ?? 0);
                    $solarStructureCharges = (float) ($invoice->solar_structure_charges ?? 0);

                    $gstAmount = ($subtotal + $solarStructureCharges) * ($gstRate / 100);
                    $totalPayable = $subtotal + $solarStructureCharges + $gstAmount - $discount;
                    $lendingCost = $totalPayable - $subsidy;
                @endphp
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Base Price</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Solar Structure Charges</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($solarStructureCharges, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">GST ({{ $gstRate }}%)</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($invoice->gst_amount ?? $gstAmount, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: bold; padding: 8px 12px; color: #333; font-family: sans-serif;">Customer Payable Amount</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; background-color: #52866A !important; color: #ffffff !important; font-weight: bold; font-family: sans-serif;">{{ number_format($totalPayable, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Subsidy</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">-{{ number_format($subsidy, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: bold; padding: 8px 12px; color: #333; font-family: sans-serif;">Lending Cost Of Customer</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; background-color: #52866A !important; color: #ffffff !important; font-weight: bold; font-family: sans-serif;">{{ number_format($lendingCost, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($subsidy > 0)
            <p class="note-text"><strong>Note:</strong> Subsidy Amount to be credited in clients account.</p>
        @endif

        <!-- Extra Info -->
        <div class="extra-info">
            <table>
                <tr>
                    <th style="width: 40%;">System Capacity</th>
                    <td>{{ $invoice->quantity ?? '0' }} kW</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>{{ ucfirst($invoice->type ?? '') }}</td>
                </tr>
                @if(!empty($invoice->solar_meter_charges))
                    <tr>
                        <th>Solar Meter Charges</th>
                        <td>{{ ucwords(str_replace('_', ' ', $invoice->solar_meter_charges)) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Comment + Bank Details Table -->
        <table class="info-table" style="margin-top:20px; border: 1px solid #eee;">
            <thead>
                <tr>
                    <th style="width: 35%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">Comment</th>
                    <th style="width: 40%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">Bank Details
                    </th>
                    <th style="width: 25%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">QR Code</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee;">
                        <div style="font-size: 13px; color: #555;">{!! nl2br(e($invoice->comment ?? '--')) !!}</div>
                    </td>
                    <td style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee;">
                        @if(!empty($settings['bank_name']) || !empty($settings['account_number']))
                            <div style="font-size: 13px; line-height: 1.6;">
                                <strong>Bank:</strong> {{ $settings['bank_name'] ?? '--' }}<br>
                                <strong>A/c Name:</strong> {{ $settings['account_name'] ?? '--' }}<br>
                                <strong>A/c No.:</strong> {{ $settings['account_number'] ?? '--' }}<br>
                                <strong>IFSC:</strong> {{ $settings['ifsc_code'] ?? '--' }}<br>
                                <strong>Branch:</strong> {{ $settings['branch_name'] ?? '--' }}
                            </div>
                        @else
                            <div style="color:#999; font-size: 12px;">No bank details available.</div>
                        @endif
                    </td>
                    <td
                        style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee; text-align:center;">
                        @php
                            $qrCodePath = \App\Models\Setting::where('key', 'company_qr_code_path')->value('value');
                        @endphp
                        @if($qrCodePath && file_exists(public_path('storage/' . $qrCodePath)))
                            <img src="{{ public_path('storage/' . $qrCodePath) }}" alt="QR Code"
                                style="max-width: 100px; height: auto;">
                        @else
                            <div style="color:#999; font-size: 12px;">No QR code available.</div>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Page Break for BOM -->
        <div style="page-break-before: always;"></div>

        <!-- BOM Section -->
        <div style="margin-top: 20px;">
            <h2 style="text-align: center; color: #52866A; margin-bottom: 30px; text-decoration: underline; font-weight: bold; font-family: sans-serif;">
                BILL OF MATERIALS (BOM)
            </h2>
            <table class="quotation-table" style="border: 1px solid #333; border-collapse: collapse; width: 100%; font-family: sans-serif;">
                <thead style="background-color: #52866A; color: #fff;">
                    <tr>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; width: 12%; background-color: #52866A !important; color: #ffffff !important;">Image</th>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; width: 20%; background-color: #52866A !important; color: #ffffff !important;">Product Name</th>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; width: 38%; background-color: #52866A !important; color: #ffffff !important;">Specifications</th>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: center; width: 10%; background-color: #52866A !important; color: #ffffff !important;">Quantity</th>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; width: 10%; background-color: #52866A !important; color: #ffffff !important;">Price</th>
                        <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; width: 10%; background-color: #52866A !important; color: #ffffff !important;">Total Excluding GST</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $allproduct = is_array($invoice->product_name) ? $invoice->product_name : json_decode($invoice->product_name, true);
                        $total_quantity = 0;
                        $grand_total_excluding_gst = 0.0;
                    @endphp
                    @if(is_array($allproduct) && !empty($allproduct))
                        @foreach($allproduct as $item)
                            @php
                                $product_id = $item['product_id'] ?? null;
                                $product_name_display = $item['name'] ?? 'Product name not found';
                                $product_name_display = ucwords(strtolower($product_name_display));
                                $product_quantity = (int)($item['quantity'] ?? 0);
                                $product_category_makes = $item['category_name'] ?? '';

                                $full_product_details = null;
                                foreach ($product_data as $prod_detail) {
                                    if ($prod_detail['id'] == $product_id) {
                                        $full_product_details = $prod_detail;
                                        break;
                                    }
                                }

                                $specifications = [];
                                $make_val = ltrim(trim($product_category_makes), ',');
                                if (!empty($make_val)) {
                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Make:</span> ' . e($make_val);
                                }
                                if ($full_product_details && !empty($full_product_details['technology'])) {
                                    $techArray = json_decode($full_product_details['technology'], true);
                                    if (!is_array($techArray)) {
                                        $techArray = [$full_product_details['technology']];
                                    }
                                    $techArray = array_filter($techArray, fn($v) => trim((string) $v) !== '');
                                    if (!empty($techArray)) {
                                        $techNames = array_map(fn($id) => $technology_map[$id] ?? $id, $techArray);
                                        $specifications[] = '<span style="color: #555; font-weight: bold;">Technology:</span> ' . e(implode(', ', $techNames));
                                    }
                                }
                                if ($full_product_details && !empty($full_product_details['warranty'])) {
                                    $warArray = json_decode($full_product_details['warranty'], true);
                                    if (!is_array($warArray)) {
                                        $warArray = [$full_product_details['warranty']];
                                    }
                                    $warArray = array_filter($warArray, fn($v) => trim((string) $v) !== '');
                                    if (!empty($warArray)) {
                                        $warNames = array_map(fn($id) => $warranty_map[$id] ?? $id, $warArray);
                                        $specifications[] = '<span style="color: #555; font-weight: bold;">Warranty:</span> ' . e(implode(', ', $warNames));
                                    }
                                }
                                if ($full_product_details && !empty($full_product_details['capacity'])) {
                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Capacity:</span> ' . e($full_product_details['capacity']);
                                }
                                if ($full_product_details && !empty($full_product_details['tax_rate']) && (float)$full_product_details['tax_rate'] > 0) {
                                    $tax_rate = (float)$full_product_details['tax_rate'];
                                    $tax_type = $full_product_details['tax_type'] ?? '';
                                    if (strcasecmp($tax_type, 'IGST') === 0 || strcasecmp($tax_type, 'GST') === 0) {
                                        $specifications[] = '<span style="color: #555; font-weight: bold;">GST:</span> ' . e($tax_type) . ' ' . $tax_rate . '%';
                                    } else {
                                        $half_rate = $tax_rate / 2;
                                        $specifications[] = '<span style="color: #555; font-weight: bold;">GST:</span> (CGST ' . $half_rate . '% + SGST ' . $half_rate . '%)';
                                    }
                                }
                                if ($full_product_details && !empty($full_product_details['height'])) {
                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Height:</span> ' . e($full_product_details['height']);
                                }
                                if ($full_product_details && !empty($full_product_details['fitting_material'])) {
                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Fitting Material:</span> ' . e($full_product_details['fitting_material']);
                                }
                                if ($full_product_details && !empty($full_product_details['fitting_type'])) {
                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Fitting Type:</span> ' . e($full_product_details['fitting_type']);
                                }
                                if ($full_product_details && !empty($full_product_details['thickness'])) {
                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Thickness:</span> ' . e($full_product_details['thickness']);
                                }
                                if ($full_product_details && !empty($full_product_details['size_of_pipe'])) {
                                    $specifications[] = '<span style="color: #555; font-weight: bold;">Size of Pipe:</span> ' . e($full_product_details['size_of_pipe']);
                                }
                                $specifications_html = implode('<br>', $specifications);

                                $price_val = $full_product_details ? (float)($full_product_details['price'] ?? 0) : 0.0;
                                $row_total = $price_val * $product_quantity;

                                $total_quantity += $product_quantity;
                                $grand_total_excluding_gst += $row_total;

                                $qty_unit = '';
                                if ($full_product_details && !empty($full_product_details['nos'])) {
                                    $qty_unit = '(nos)';
                                } elseif ($full_product_details && !empty($full_product_details['meter'])) {
                                    $qty_unit = '(mtr)';
                                }
                            @endphp
                            <tr>
                                <td style="padding: 10px 8px; border: 1px solid #333; text-align: center; vertical-align: middle;">
                                    @if ($full_product_details && !empty($full_product_details['image']) && file_exists(public_path('storage/' . $full_product_details['image'])))
                                        <div style="border: 1px solid #ddd; border-radius: 4px; padding: 4px; background-color: #fff; display: inline-block;">
                                            <img src="{{ public_path('storage/' . $full_product_details['image']) }}" alt="{{ $product_name_display }}" style="width: 80px; height: 80px; object-fit: contain;">
                                        </div>
                                    @else
                                        <div style="width: 80px; height: 80px; background-color: #f5f5f5; border: 1px solid #ddd; line-height: 80px; text-align: center; color: #ccc; font-size: 10px;">No Image</div>
                                    @endif
                                </td>
                                <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-weight: bold; vertical-align: middle; font-size: 12px;">{{ $product_name_display }}</td>
                                <td style="padding: 10px 8px; border: 1px solid #333; font-size: 11px; line-height: 1.5; vertical-align: middle;">{!! $specifications_html !!}</td>
                                <td style="padding: 10px 8px; border: 1px solid #333; text-align: right; vertical-align: middle; font-weight: bold; color: #333; font-size: 12px;">{{ $product_quantity }}{{ $qty_unit }}</td>
                                <td style="padding: 10px 8px; border: 1px solid #333; text-align: right; vertical-align: middle; color: #333; font-size: 12px;">{{ number_format($price_val, 2) }}</td>
                                <td style="padding: 10px 8px; border: 1px solid #333; text-align: right; vertical-align: middle; font-weight: bold; color: #333; font-size: 12px;">{{ number_format($row_total, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666; padding: 15px; border: 1px solid #333;">No products added to this invoice</td>
                        </tr>
                    @endif
                </tbody>
                @if(is_array($allproduct) && !empty($allproduct))
                    <tfoot>
                        <tr style="font-weight: bold;">
                            <td style="border: 1px solid #333; background-color: #fff;"></td>
                            <td style="border: 1px solid #333; background-color: #fff;"></td>
                            <td style="text-align: right; padding: 8px 12px; border: 1px solid #333; font-size: 13px; background-color: #fff; color: #333;">Total:</td>
                            <td style="text-align: right; padding: 8px 12px; border: 1px solid #333; font-size: 13px; background-color: #fff; color: #333;">{{ $total_quantity }}</td>
                            <td style="text-align: center; padding: 8px 12px; border: 1px solid #333; font-size: 13px; background-color: #fff; color: #333;">—</td>
                            <td style="text-align: right; padding: 8px 12px; border: 1px solid #333; font-size: 13px; background-color: #52866A !important; color: #ffffff !important;">{{ number_format($grand_total_excluding_gst, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</body>

</html>