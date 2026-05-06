@extends('layouts.app')

@section('page_title', 'Invoice Details #' . $invoice->invoice_no)

@section('content')
    <style>
        .quotation-block {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .quotation-box {
            max-width: 900px;
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
                                        @if ($user && $user->company_logo)
                                            <img src="{{ asset('storage/' . $user->company_logo) }}" alt="Company Logo">
                                        @elseif(isset($settings['company_logo_path']))
                                            <img src="{{ asset('storage/' . $settings['company_logo_path']) }}" alt="Company Logo">
                                        @else
                                            <img src="{{ asset('assets/images/logo.png') }}" alt="Company Logo" onerror="this.src='https://via.placeholder.com/150x60?text=Logo'">
                                        @endif
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
                        <div class="flex-between">
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
                        <table class="quotation-table">
                            <thead>
                                <tr>
                                    <th>Invoice Name</th>
                                    <th>Quantity (kW)</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $invoice->invoice_name ?? '--' }}</td>
                                    <td>{{ $invoice->quantity ?? '0' }}</td>
                                    <td>{{ number_format((float) ($invoice->price ?? 0), 2) }}</td>
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
                                    <td colspan="2">Base Price</td>
                                    <td>{{ number_format($invoice->total, 2) }}</td>
                                </tr>
                                @if ($solarStructureCharges > 0)
                                    <tr>
                                        <td colspan="2">Solar Structure Charges</td>
                                        <td>{{ number_format($solarStructureCharges, 2) }}</td>
                                    </tr>
                                @endif
                                @if ($gstRate > 0)
                                    <tr>
                                        <td colspan="2">GST ({{ $gstRate }}%)</td>
                                        <td>{{ number_format($invoice->gst_amount ?? $gstAmount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ($discount > 0)
                                    <tr>
                                        <td colspan="2">Discount</td>
                                        <td>-{{ number_format($discount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr style="font-weight: bold; border-top: 2px solid #000;">
                                    <td colspan="2">Final Amount</td>
                                    <td class="highlight-bg">{{ number_format($invoice->amount, 2) }}</td>
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
                                    <td data-label="QR Code" style="vertical-align: top; background:">
                                        @if (isset($settings['company_qr_code_path']))
                                            <img src="{{ asset('storage/' . $settings['company_qr_code_path']) }}" alt="QR Code" class="qr-code-img">
                                        @elseif($user && $user->qr_code)
                                            <img src="{{ asset('storage/' . $user->qr_code) }}" alt="QR Code" class="qr-code-img">
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
                                        $items = is_array($invoice->product_name) ? $invoice->product_name : json_decode($invoice->product_name ?? '[]', true);
                                    @endphp
                                    @forelse($items as $item)
                                    <tr>
                                        <td class="fw-bold align-middle">{{ $item['name'] ?? 'Product' }}</td>
                                        <td>
                                            <div class="small">
                                                <span class="fw-bold">Make:</span> {{ $item['category_name'] ?? 'N/A' }}<br>
                                                <span class="fw-bold">Quantity:</span> {{ $item['quantity'] ?? 1 }}
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-3 text-muted">No BOM items found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
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
