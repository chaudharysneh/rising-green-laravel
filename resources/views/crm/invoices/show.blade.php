@extends('layouts.app')

@section('page_title', 'Invoice Details #' . $invoice->invoice_no)

@section('content')
    <style>
        .quotation-block {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .quotation-box {
            max-width: 1000px;
            margin: 20px auto;
            padding: 30px;
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
            padding: 6px 10px;
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

        .highlight-bg {
            background-color: #52866A;
            color: #fff !important;
        }

        .qr-code-img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .info-table {
                font-size: 12px;
            }

            .info-table thead {
                display: block;
                width: 100%;
            }

            .info-table tbody {
                display: block;
                width: 100%;
            }

            .info-table tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            .info-table th {
                grid-column: 1 / -1;
                width: 100% !important;
            }

            .info-table td {
                padding: 8px;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .info-table-responsive tr {
                display: flex;
                flex-wrap: wrap;
            }

            .info-table-responsive td {
                flex: 0 0 50%;
                min-width: 0;
            }

            .info-table-responsive thead th {
                flex: 0 0 100%;
            }

            /* Comment Bank Details QR Code Table - Stack on mobile */
            .comment-bank-qr-table {
                border-collapse: separate !important;
                border-spacing: 0;
            }

            .comment-bank-qr-table thead {
                display: none !important;
            }

            .comment-bank-qr-table tbody {
                display: block !important;
                width: 100%;
            }

            .comment-bank-qr-table tr {
                display: flex !important;
                flex-direction: column;
                width: 100%;
                border: none;
                margin-bottom: 15px;
            }

            .comment-bank-qr-table td {
                display: block !important;
                border: 1px solid #ddd;
                padding: 15px !important;
                background: #fafafa !important;
                width: 100%;
                position: relative;
                padding-top: 40px !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .comment-bank-qr-table td:first-child {
                border-top: 3px solid #52866A;
            }

            .comment-bank-qr-table td::before {
                content: attr(data-label);
                display: block;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                background: #52866A;
                color: #fff;
                padding: 10px 15px;
                font-weight: 600;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                /* margin: -15px -15px 0 -15px; */
                border-bottom: 1px solid #52866A;
            }

            .comment-bank-qr-table td div {
                margin-bottom: 8px;
            }

            .comment-bank-qr-table td div:last-child {
                margin-bottom: 0;
            }

            .comment-bank-qr-table td img {
                max-width: 100%;
                height: auto;
                margin: 0 auto;
                display: block;
            }
        }

        @media print {
            .no-print, .card-header, .btn {
                display: none !important;
            }

            .quotation-box {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>

    <div class="container-fluid p-0">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden detail-view-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold text-dark-blue">Invoice Details</h1>
                        <p class="text-muted small mb-0">Complete information about this invoice</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-md-end no-print">
                        @can('invoices.edit')
                            <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endcan
                        @can('invoices.view')
                            <a href="{{ route('invoices.pdf', $invoice->id) }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0" target="_blank">
                                <i class="fa-solid fa-file-pdf me-1"></i>PDF
                            </a>
                        @endcan
                        <a href="{{ route('invoices.index') }}" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0">
                            <i class="fa-solid fa-angle-left pe-1"></i>
                            <span>Back</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="quotation-block">
                    <div class="quotation-box mx-auto">
                        <!-- Header -->
                        <div class="quotation-header">
                            <table>
                                <tr>
                                    <td class="company-logo" style="width: 50%;">
                                        @php
                                            $companyLogoPath = $settings['company_logo_path'] ?? null;
                                            $companyLogoUrl = $companyLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogoPath)
                                                ? route('profile.company_logo.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($companyLogoPath)
                                                : ($user && $user->company_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->company_logo)
                                                    ? asset('storage/' . $user->company_logo)
                                                    : asset('assets/images/logo.png'));
                                        @endphp
                                        <img src="{{ $companyLogoUrl }}" alt="Company Logo" style="width: 300px" onerror="this.onerror=null;this.src='{{ asset('assets/images/logo.png') }}';">
                                    </td>
                                    <td class="quotation-title" style="width: 50%;">
                                        <div style="line-height:22px;color:#000">
                                            <strong style="font-size:18px;color:#000">{{ $settings['company_name'] ?? ($user->company_name ?? 'Company Name') }}</strong><br>
                                            {{ $settings['company_address'] ?? ($user->address ?? '--') }}<br>
                                            @if(isset($settings['company_contact'])) {{ $settings['company_contact'] }} @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <hr>

                        <!-- Invoice Info -->
                        <div class="flex-between border-top mt-3 pt-4">
                            <div style="font-weight:700; font-size:15px;">Invoice no.: #{{ $invoice->invoice_no }}</div>
                            <div class="center-text" style="font-size:16px;">INVOICE</div>
                            <div style="font-weight:700; font-size:15px;">Date:
                                {{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '-' }}</div>
                        </div>

                        <!-- Customer Info Table -->
                        <table class="info-table info-table-responsive">
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
                                    <td>{{ $invoice->customer->phone ?? '--' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Invoice Details Table -->
                        <table class="quotation-table" style="border: 1px solid #333; border-collapse: collapse; width: 100%; font-family: sans-serif; margin-bottom: 20px;">
                            <thead style="background-color: #52866A; color: #fff;">
                                <tr>
                                    <th style="padding: 10px 12px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Invoice Name</th>
                                    <th style="padding: 10px 12px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Quantity (kW)</th>
                                    <th style="padding: 10px 12px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; background-color: #52866A !important; color: #ffffff !important;">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 10px 12px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->invoice_name ?? '--' }}</td>
                                    <td style="padding: 10px 12px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->quantity ?? '0' }}</td>
                                    <td style="padding: 10px 12px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ number_format((float) ($invoice->price ?? 0), 2) }}</td>
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

                        <!-- Extra Info -->
                        <div class="extra-info">
                            <table>
                                <tr>
                                    <th style="width: 40%;">System Capacity</th>
                                    <td>{{ $invoice->quantity ?? '0' }} kW</td>
                                </tr>
                                <tr>
                                    <th>Invoice Type</th>
                                    <td>{{ ucfirst($invoice->type ?? '') }}</td>
                                </tr>
                                @if (!empty($invoice->solar_meter_charges))
                                    <tr>
                                        <th>Solar Meter Charges</th>
                                        <td>{{ ucwords(str_replace('_', ' ', $invoice->solar_meter_charges)) }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                        <!-- Comment + Bank Details Table -->
                        <table class="info-table comment-bank-qr-table" style="margin-top:15px;">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Comment</th>
                                    <th style="width: 40%;">Bank Details</th>
                                    <th style="width: 25%;">QR Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="Comment" style="vertical-align: top; background: #fafafa;">
                                        {!! nl2br(e($invoice->comment ?? '--')) !!}
                                    </td>
                                    <td data-label="Bank Details" style="vertical-align: top; background: #fafafa;">
                                        @if (!empty($settings['bank_name']) || !empty($settings['account_number']))
                                            <div><strong>Bank:</strong> {{ $settings['bank_name'] ?? '--' }}</div>
                                            <div><strong>Account Name:</strong> {{ $settings['account_name'] ?? '--' }}</div>
                                            <div><strong>Account No.:</strong> {{ $settings['account_number'] ?? '--' }}</div>
                                            <div><strong>IFSC:</strong> {{ $settings['ifsc_code'] ?? '--' }}</div>
                                            <div><strong>Branch:</strong> {{ $settings['branch_name'] ?? '--' }}</div>
                                        @else
                                            <div style="color:#666;">No bank details available.</div>
                                        @endif
                                    </td>
                                    <td data-label="QR Code" style="vertical-align: top; background: #fafafa; display: flex; align-items: center; justify-content: center;">
                                        @php
                                            $companyQrCodePath = $settings['company_qr_code_path'] ?? null;
                                            $companyQrCodeUrl = $companyQrCodePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyQrCodePath)
                                                ? route('profile.company_qr_code.image') . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($companyQrCodePath)
                                                : ($user && $user->qr_code && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->qr_code)
                                                    ? asset('storage/' . $user->qr_code)
                                                    : null);
                                        @endphp
                                        @if ($companyQrCodeUrl)
                                            <img src="{{ $companyQrCodeUrl }}" alt="QR Code" class="qr-code-img">
                                        @else
                                            <div style="color:#666;">No QR code available.</div>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- BOM Section -->
                        <div class="page-break"></div>
                        <div style="margin-top: 40px;">
                            <h2 style="text-align: center; color: #52866A; margin-bottom: 30px; text-decoration: underline; font-weight: bold; font-family: sans-serif;">
                                BILL OF MATERIALS (BOM)
                            </h2>
                            <table class="quotation-table table table-bordered" style="border: 1px solid #333; border-collapse: collapse; width: 100%; font-family: sans-serif;">
                                <thead style="background-color: #52866A; color: #fff;">
                                    <tr>
                                        <th style="padding: 12px 10px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; width: 12%; background-color: #52866A !important; color: #ffffff !important;">Image</th>
                                        <th style="padding: 12px 10px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; width: 20%; background-color: #52866A !important; color: #ffffff !important;">Product Name</th>
                                        <th style="padding: 12px 10px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; width: 38%; background-color: #52866A !important; color: #ffffff !important;">Specifications</th>
                                        <th style="padding: 12px 10px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: center; width: 10%; background-color: #52866A !important; color: #ffffff !important;">Quantity</th>
                                        <th style="padding: 12px 10px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; width: 10%; background-color: #52866A !important; color: #ffffff !important;">Price</th>
                                        <th style="padding: 12px 10px; font-weight: bold; font-size: 14px; border: 1px solid #333; text-align: left; width: 10%; background-color: #52866A !important; color: #ffffff !important;">Total(Excl. GST)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $items = is_array($invoice->product_name) ? $invoice->product_name : json_decode($invoice->product_name ?? '[]', true);
                                        $total_quantity = 0;
                                        $grand_total_excluding_gst = 0.0;

                                        // Dynamically fetch dependencies
                                        $product_data = \App\Models\BomProduct::all()->toArray();
                                        $technology_map = \App\Models\Technology::pluck('title', 'id')->toArray();
                                        $warranty_map = \App\Models\Warranty::pluck('title', 'id')->toArray();
                                    @endphp
                                    @if (is_array($items) && !empty($items))
                                        @foreach ($items as $item)
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
                                                    $techArray = array_filter($techArray, fn($v) => trim((string)$v) !== '');
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
                                                    $warArray = array_filter($warArray, fn($v) => trim((string)$v) !== '');
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
                                                <td style="padding: 12px 10px; border: 1px solid #333; text-align: center; vertical-align: middle;">
                                                    @if ($full_product_details && !empty($full_product_details['image']))
                                                        <div style="border: 1px solid #ddd; border-radius: 4px; padding: 4px; background-color: #fff; display: inline-block;">
                                                            @php
                                                                $product_image_display = $full_product_details['image'];
                                                                $productImageUrl = $product_id && \Illuminate\Support\Facades\Storage::disk('public')->exists($product_image_display)
                                                                    ? route('bom-products.image', $product_id)
                                                                    : asset('storage/' . $product_image_display);
                                                            @endphp
                                                            <img src="{{ $productImageUrl }}" alt="{{ $product_name_display }}" style="max-width: 80px; max-height: 80px; object-fit: contain;">
                                                        </div>
                                                    @else
                                                        <div style="width: 80px; height: 80px; background-color: #f5f5f5; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; color: #ccc; font-size: 11px; border: 1px solid #ddd;">No Image</div>
                                                    @endif
                                                </td>
                                                <td style="padding: 12px 10px; border: 1px solid #333; color: #333; font-weight: bold; vertical-align: middle;">{{ $product_name_display }}</td>
                                                <td style="padding: 12px 10px; border: 1px solid #333; font-size: 13px; line-height: 1.5; vertical-align: middle;">{!! $specifications_html !!}</td>
                                                <td style="padding: 12px 10px; border: 1px solid #333; text-align: right; vertical-align: middle; font-weight: bold; color: #333;">{{ $product_quantity }}{{ $qty_unit }}</td>
                                                <td style="padding: 12px 10px; border: 1px solid #333; text-align: right; vertical-align: middle; color: #333;">{{ number_format($price_val, 2) }}</td>
                                                <td style="padding: 12px 10px; border: 1px solid #333; text-align: right; vertical-align: middle; font-weight: bold; color: #333;">{{ number_format($row_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6" style="text-align: center; color: #666; padding: 20px; border: 1px solid #333;">No BOM items found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                                @if (is_array($items) && !empty($items))
                                    <tfoot>
                                        <tr style="font-weight: bold;">
                                            <td style="border: 1px solid #333; background-color: #fff;"></td>
                                            <td style="border: 1px solid #333; background-color: #fff;"></td>
                                            <td style="text-align: right; padding: 10px 15px; border: 1px solid #333; font-size: 14px; background-color: #fff; color: #333;">Total:</td>
                                            <td style="text-align: right; padding: 10px 15px; border: 1px solid #333; font-size: 14px; background-color: #fff; color: #333;">{{ $total_quantity }}</td>
                                            <td style="text-align: center; padding: 10px 15px; border: 1px solid #333; font-size: 14px; background-color: #fff; color: #333;">—</td>
                                            <td style="text-align: right; padding: 10px 15px; border: 1px solid #333; font-size: 14px; background-color: #52866A !important; color: #ffffff !important;">{{ number_format($grand_total_excluding_gst, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-5 no-print">
                            <p class="mb-0 text-muted small">Thank you for your business!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
