<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Estimate - {{ $estimate->estimate_no }}</title>
    <style>
        .quotation-box, .quotation-box * {
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

        .info-table, .quotation-table, .extra-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .info-table th, .info-table td, .quotation-table th, .quotation-table td, .extra-info th, .extra-info td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        .info-table th, .quotation-table th, .extra-info th {
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
                            <strong style="font-size:18px;color:#000">{{ $user->company_name ?? 'Company Name' }}</strong><br>
                            {{ $user->address ?? '--' }}<br>
                            {{ $user->country ?? '--' }}, {{ $user->state ?? '--' }}, {{ $user->city ?? '--' }} {{ $user->pincode ?? '--' }}<br>
                            {{ $user->contact ?? '--' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <hr>

        <!-- Quotation Info -->
        <div class="flex-between">
            <div style="font-weight:700; font-size:15px;">Estimate no.: #{{ $estimate->estimate_no }}</div>
            <div class="center-text" style="font-size:16px;">ESTIMATION</div>
            <div style="font-weight:700; font-size:15px;">Date: {{ $estimate->estimate_date->format('Y-m-d') }}</div>
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
                    <td>{{ $estimate->customer->name ?? '--' }}</td>
                    <td><strong>Email</strong></td>
                    <td>{{ $estimate->customer->email ?? '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Address</strong></td>
                    <td>{{ $estimate->customer->address ?? '--' }}</td>
                    <td><strong>Contact</strong></td>
                    <td>{{ $estimate->customer->contact ?? '--' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Estimate Details Table -->
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
                    <td>{{ $estimate->estimate_name ?? '--' }}</td>
                    <td>{{ $estimate->quantity ?? '0' }}</td>
                    <td>{{ number_format((float)($estimate->price ?? 0), 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                @php
                    $subtotal = (float)($estimate->price ?? 0);
                    $gstRate = (float)($estimate->gst ?? 0);
                    $discount = (float)($estimate->discount ?? 0);
                    $subsidy = (float)($estimate->subsidy_amount ?? 0);
                    $solarStructureCharges = (float)($estimate->solar_structure_charges ?? 0);
                    
                    $gstAmount = $subtotal * ($gstRate / 100);
                    $totalPayable = $subtotal + $solarStructureCharges + $gstAmount - $discount;
                    $lendingCost = $totalPayable - $subsidy;
                @endphp
                <tr>
                    <td colspan="2">Base Price</td>
                    <td>{{ number_format($subtotal, 2) }}</td>
                </tr>
                @if($solarStructureCharges > 0)
                    <tr>
                        <td colspan="2">Solar Structure Charges</td>
                        <td>{{ number_format($solarStructureCharges, 2) }}</td>
                    </tr>
                @endif
                @if($gstRate > 0)
                    <tr>
                        <td colspan="2">GST ({{ $gstRate }}%)</td>
                        <td>{{ number_format($gstAmount, 2) }}</td>
                    </tr>
                @endif
                @if($discount > 0)
                    <tr>
                        <td colspan="2">Discount</td>
                        <td>-{{ number_format($discount, 2) }}</td>
                    </tr>
                @endif
                <tr style="font-weight: bold; border-top: 2px solid #000;">
                    <td colspan="2">Customer Payable Amount</td>
                    <td class="highlight-bg">{{ number_format($totalPayable, 2) }}</td>
                </tr>
                @if($subsidy > 0)
                    <tr>
                        <td colspan="2">Subsidy</td>
                        <td>-{{ number_format($subsidy, 2) }}</td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td colspan="2">Lending Cost Of Customer</td>
                        <td class="highlight-bg">{{ number_format($lendingCost, 2) }}</td>
                    </tr>
                @endif
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
                    <td>{{ $estimate->quantity ?? '0' }} kW</td>
                </tr>
                <tr>
                    <th>Estimate Type</th>
                    <td>{{ ucfirst($estimate->type ?? '') }}</td>
                </tr>
                @if(!empty($estimate->solar_meter_charges))
                    <tr>
                        <th>Solar Meter Charges</th>
                        <td>{{ ucwords(str_replace('_', ' ', $estimate->solar_meter_charges)) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Comment + Bank Details Table -->
        <table class="info-table" style="margin-top:20px; border: 1px solid #eee;">
            <thead>
                <tr>
                    <th style="width: 35%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">Comment</th>
                    <th style="width: 40%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">Bank Details</th>
                    <th style="width: 25%; background-color: #f8f9fa; color: #333; border: 1px solid #ddd;">QR Code</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee;">
                        <div style="font-size: 13px; color: #555;">{!! nl2br(e($estimate->comment ?? '--')) !!}</div>
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
                    <td style="vertical-align: top; padding: 15px; background: #fff; border: 1px solid #eee; text-align:center;">
                        @if($user && $user->qr_code)
                            <img src="{{ public_path('storage/' . $user->qr_code) }}" alt="QR Code" style="max-width: 100px; height: auto;">
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
                    @php
                        $allproduct = is_array($estimate->product_name) ? $estimate->product_name : json_decode($estimate->product_name, true);
                    @endphp
                    @if(is_array($allproduct) && !empty($allproduct))
                        @foreach($allproduct as $item)
                            @php
                                $product_id = $item['product_id'] ?? null;
                                $product_name_display = $item['name'] ?? 'Product name not found';
                                $product_name_display = ucwords(strtolower($product_name_display));
                                $product_quantity = $item['quantity'] ?? 0;
                                $product_category_makes = $item['category_name'] ?? '';
                                
                                $full_product_details = null;
                                foreach ($product_data as $prod_detail) {
                                    if ($prod_detail['id'] == $product_id) {
                                        $full_product_details = $prod_detail;
                                        break;
                                    }
                                }
                                
                                $specifications = [];
                                if (!empty($product_category_makes)) {
                                    $specifications[] = '<strong>Make: </strong>' . e($product_category_makes);
                                }
                                if (!empty($product_quantity)) {
                                    $specifications[] = '<strong>Quantity: </strong>' . e($product_quantity);
                                }
                                if ($full_product_details && !empty($full_product_details['technology'])) {
                                    $techArray = json_decode($full_product_details['technology'], true);
                                    if (!is_array($techArray)) {
                                        $techArray = [$full_product_details['technology']];
                                    }
                                    $techArray = array_filter($techArray, fn($v) => trim((string)$v) !== '');
                                    if (!empty($techArray)) {
                                        $techNames = array_map(fn($id) => $technology_map[$id] ?? $id, $techArray);
                                        $specifications[] = '<strong>Technology: </strong>' . e(implode(', ', $techNames));
                                    }
                                }
                                if ($full_product_details && !empty($full_product_details['warranty'])) {
                                    $warArray = json_decode($full_product_details['warranty'], true);
                                    if (!is_array($warArray)) {
                                        $warArray = [$full_product_details['warranty']];
                                    }
                                    $warArray = array_filter($warArray, fn($v) => trim((string)$v) !== '');
                                    if (!empty($warArray)) {
                                        $warNames = array_map(fn($id) => $warranty_map[$id] ?? $id, $warArray);
                                        $specifications[] = '<strong>Warranty: </strong>' . e(implode(', ', $warNames));
                                    }
                                }
                                if ($full_product_details && !empty($full_product_details['height'])) {
                                    $specifications[] = '<strong>Height: </strong>' . e($full_product_details['height']);
                                }
                                if ($full_product_details && !empty($full_product_details['fitting_material'])) {
                                    $specifications[] = '<strong>Fitting Material: </strong>' . e($full_product_details['fitting_material']);
                                }
                                if ($full_product_details && !empty($full_product_details['fitting_type'])) {
                                    $specifications[] = '<strong>Fitting Type: </strong>' . e($full_product_details['fitting_type']);
                                }
                                if ($full_product_details && !empty($full_product_details['thickness'])) {
                                    $specifications[] = '<strong>Thickness: </strong>' . e($full_product_details['thickness']);
                                }
                                if ($full_product_details && !empty($full_product_details['size_of_pipe'])) {
                                    $specifications[] = '<strong>Size of Pipe: </strong>' . e($full_product_details['size_of_pipe']);
                                }
                                if ($full_product_details && !empty($full_product_details['capacity'])) {
                                    $specifications[] = '<strong>Capacity: </strong>' . e($full_product_details['capacity']);
                                }
                                $specifications_html = implode('<br>', $specifications);
                            @endphp
                            <tr>
                                <td>{{ $product_name_display }}</td>
                                <td>{!! $specifications_html !!}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" style="text-align: center; color: #666;">No products added to this estimate</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
