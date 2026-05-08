<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\BomProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends ApiBaseController
{
    public function __construct()
    {
        $this->middleware('can:viewAny,' . Invoice::class)->only('index');
        $this->middleware('can:create,' . Invoice::class)->only('store');
        $this->middleware('can:view,invoice')->only('show');
        $this->middleware('can:update,invoice')->only(['update', 'updateStatus']);
        $this->middleware('can:delete,invoice')->only('destroy');
    }

    public function index(Request $request)
    {
        $filter = $request->get('filter'); // 'created_by_me' or 'assigned_to_me'
        $user = auth()->user();

        $query = Invoice::with(['customer', 'creator', 'items']);

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // ✅ ADVANCED SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('invoice_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
        }

        // Apply filter for staff users only
        if (!$user->isAdmin() && $filter === 'created_by_me') {
            // All records I created (regardless of assignment)
            $query->where('created_by', $user->id);
        } elseif (!$user->isAdmin() && $filter === 'assigned_to_me') {
            // Records assigned to me but NOT created by me
            $query->where('assigned_user_id', $user->id)
                ->where('created_by', '!=', $user->id);
        }

        $invoices = $query
            ->orderByRaw("
                CASE
                    WHEN invoice_no REGEXP '^INV-[0-9]+$'
                    THEN CAST(SUBSTRING_INDEX(invoice_no, '-', -1) AS UNSIGNED)
                    ELSE 0
                END DESC
            ")
            ->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Invoices retrieved successfully',
            'data' => $invoices,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'invoice_name' => 'required|string|min:1',
            'type' => 'required|in:residential,commercial,industrial,common meter',
            'quantity' => 'required|numeric|gt:0',
            'price' => 'required|numeric|gt:0',
            'template_id' => 'nullable|exists:pdf_builder_forms,id',
            'solar_meter_charges' => 'required|in:as_per_actual,as_per_client_scope',
            'invoice_date' => 'nullable|date',
            'products' => 'nullable|json',
            'attach_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'customer_id.required' => 'Please select a customer',
            'invoice_name.required' => 'Please enter invoice name',
            'type.required' => 'Please select invoice type',
            'quantity.required' => 'Please enter valid quantity (kW)',
            'quantity.gt' => 'Please enter valid quantity (kW)',
            'price.required' => 'Please enter valid price',
            'price.gt' => 'Please enter valid price',
            'solar_meter_charges.required' => 'Please select solar meter charges',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $userId = auth()->id();
            $customerId = $request->input('customer_id');
            $invoiceName = $request->input('invoice_name');
            $type = $request->input('type');
            $quantity = (float) $request->input('quantity', 0);
            $price = (float) $request->input('price', 0);
            $templateId = (int) $request->input('template_id', 0);
            $solarMeterCharges = $request->input('solar_meter_charges', '');
            $solarStructureCharges = (float) ($request->input('solar_structure_charges') ?? 0);
            $invoiceDate = $request->input('invoice_date', now()->format('Y-m-d'));
            $comment = $request->input('comment', '');
            $discount = (float) ($request->input('discount') ?? 0);
            $subsidyAmount = (float) ($request->input('subsidy_amount') ?? 0);
            $applyCharges = (int) ($request->input('apply_gst') ?? 0);
            $gstPercent = $applyCharges ? (float) ($request->input('gst') ?? 0) : 0;
            $currencyId = $request->input('currency_id');

            // Handle file upload
            $attachFile = '';
            if ($request->hasFile('attach_file')) {
                $file = $request->file('attach_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('invoices', $filename, 'public');
                $attachFile = $filename;
            }

            // Parse products JSON (from BOM), with fallback to raw form arrays.
            $products = $this->normalizeInvoiceProducts($request);

            // Calculate subtotal and GST
            $basePrice = $price * $quantity;
            $subtotal = $basePrice + $solarStructureCharges;

            $gstAmount = 0;
            if ($applyCharges && $gstPercent > 0) {
                $gstAmount = ($basePrice * $gstPercent) / 100;
            }

            $finalAmount = $subtotal + $gstAmount - $discount - $subsidyAmount;

            // Generate invoice number
            $invoiceNo = $this->generateNextInvoiceNumber();

            // Generation data (ROI info from reference code)
            $monthlyBill = $request->input('monthly_electricity_bill', 3000);
            $unitRate = $request->input('unit_rate', 8);
            $generationData = [
                'monthly_electricity_bill' => (float) $monthlyBill,
                'unit_rate' => (float) $unitRate,
            ];

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $customerId,
                'user_id' => $userId,
                'invoice_no' => $invoiceNo,
                'invoice_date' => $invoiceDate,
                'invoice_name' => $invoiceName,
                'type' => $type,
                'currency_id' => $currencyId,
                'attach_file' => $attachFile,
                'quantity' => $quantity,
                'price' => $price,
                'solar_structure_charges' => $solarStructureCharges,
                'solar_meter_charges' => $solarMeterCharges,
                'template_id' => $templateId ?: null,
                'product_name' => json_encode($products),
                'status' => 'pending',
                'comment' => $comment,
                'total' => $subtotal,
                'gst' => $gstPercent,
                'gst_amount' => $gstAmount,
                'discount' => $discount,
                'subsidy_amount' => $subsidyAmount,
                'amount' => $finalAmount,
                'generation_data' => $generationData,
                'is_quotation' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            app(\App\Services\UserLogService::class)->created($invoice, 'Created an Invoice ' . $invoice->invoice_no);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'redirect' => route('invoices.index')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Invoice retrieved successfully'
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'invoice_name' => 'required|string|min:1',
            'type' => 'required|in:residential,commercial,industrial,common meter',
            'quantity' => 'required|numeric|gt:0',
            'price' => 'required|numeric|gt:0',
            'template_id' => 'nullable|exists:pdf_builder_forms,id',
            'solar_meter_charges' => 'required|in:as_per_actual,as_per_client_scope',
            'invoice_date' => 'nullable|date',
            'products' => 'nullable|json',
            'attach_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'customer_id.required' => 'Please select a customer',
            'invoice_name.required' => 'Please enter invoice name',
            'type.required' => 'Please select invoice type',
            'quantity.required' => 'Please enter valid quantity (kW)',
            'quantity.gt' => 'Please enter valid quantity (kW)',
            'price.required' => 'Please enter valid price',
            'price.gt' => 'Please enter valid price',
            'solar_meter_charges.required' => 'Please select solar meter charges',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $customerId = $request->input('customer_id');
            $invoiceName = $request->input('invoice_name');
            $type = $request->input('type');
            $quantity = (float) $request->input('quantity', 0);
            $price = (float) $request->input('price', 0);
            $templateId = (int) $request->input('template_id', 0);
            $solarMeterCharges = $request->input('solar_meter_charges', '');
            $solarStructureCharges = (float) ($request->input('solar_structure_charges') ?? 0);
            $invoiceDate = $request->input('invoice_date', now()->format('Y-m-d'));
            $comment = $request->input('comment', '');
            $discount = (float) ($request->input('discount') ?? 0);
            $subsidyAmount = (float) ($request->input('subsidy_amount') ?? 0);
            $applyCharges = (int) ($request->input('apply_gst') ?? 0);
            $gstPercent = $applyCharges ? (float) ($request->input('gst') ?? 0) : 0;
            $currencyId = $request->input('currency_id');

            // Handle file upload
            $attachFile = '';
            if ($request->hasFile('attach_file')) {
                $file = $request->file('attach_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('invoices', $filename, 'public');
                $attachFile = $filename;
            }

            // Parse products JSON, with fallback to raw form arrays.
            $products = $this->normalizeInvoiceProducts($request);

            // Calculate subtotal and GST
            $basePrice = $price * $quantity;
            $subtotal = $basePrice + $solarStructureCharges;

            $gstAmount = 0;
            if ($applyCharges && $gstPercent > 0) {
                $gstAmount = ($basePrice * $gstPercent) / 100;
            }

            $finalAmount = $subtotal + $gstAmount - $discount - $subsidyAmount;

            // Generation data
            $monthlyBill = $request->input('monthly_electricity_bill', 3000);
            $unitRate = $request->input('unit_rate', 8);
            $generationData = [
                'monthly_electricity_bill' => (float) $monthlyBill,
                'unit_rate' => (float) $unitRate,
            ];

            // Update invoice
            $updateData = [
                'customer_id' => $customerId,
                'invoice_date' => $invoiceDate,
                'invoice_name' => $invoiceName,
                'type' => $type,
                'quantity' => $quantity,
                'price' => $price,
                'solar_structure_charges' => $solarStructureCharges,
                'solar_meter_charges' => $solarMeterCharges,
                'template_id' => $templateId ?: null,
                'currency_id' => $currencyId,
                'product_name' => json_encode($products),
                'comment' => $comment,
                'total' => $subtotal,
                'gst' => $gstPercent,
                'gst_amount' => $gstAmount,
                'discount' => $discount,
                'subsidy_amount' => $subsidyAmount,
                'amount' => $finalAmount,
                'generation_data' => $generationData,
                'updated_by' => auth()->id(),
            ];

            if (!empty($attachFile)) {
                $updateData['attach_file'] = $attachFile;
            }

            $invoice->update($updateData);

            app(\App\Services\UserLogService::class)->updated($invoice, 'Updated an Invoice ' . ($invoice->invoice_no ?: ('ID ' . $invoice->id)));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'redirect' => route('invoices.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->update(['deleted_by' => auth()->id()]);
        app(\App\Services\UserLogService::class)->deleted($invoice, 'Deleted an Invoice ' . ($invoice->number ?: ('ID ' . $invoice->id)));
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:paid,unpaid'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $newStatus = $request->status;

        if ($newStatus === 'paid') {
            // When marking as paid, link to estimate if provided
            if ($request->has('estimate_id')) {
                $invoice->estimate_id = $request->estimate_id;
            }
        } elseif ($newStatus === 'unpaid') {
            // When setting to unpaid, remove the estimate_id
            $invoice->estimate_id = null;
        }

        $invoice->update([
            'status' => $newStatus,
            'estimate_id' => $invoice->estimate_id,
            'updated_by' => auth()->id(),
        ]);
        app(\App\Services\UserLogService::class)->updated($invoice, 'Updated an Invoice ' . ($invoice->number ?: ('ID ' . $invoice->id)));

        return response()->json([
            'success' => true,
            'message' => 'Invoice status updated successfully.',
            'status' => $invoice->status,
        ]);
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }

    private function generateNextInvoiceNumber(): string
    {
        $numbers = Invoice::withTrashed()
            ->lockForUpdate()
            ->whereNotNull('invoice_no')
            ->pluck('invoice_no');

        $nextSequence = $this->extractHighestInvoiceSequence($numbers) + 1;

        return 'INV-' . str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    private function extractHighestInvoiceSequence(Collection $numbers): int
    {
        return $numbers
            ->map(function ($number) {
                if (!is_string($number)) {
                    return null;
                }

                if (!preg_match('/^INV-(\d+)$/i', trim($number), $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter(static fn($value) => $value !== null)
            ->max() ?? 0;
    }

    private function normalizeInvoiceProducts(Request $request): array
    {
        $productsJson = $request->input('products', '[]');
        $products = is_string($productsJson) ? json_decode($productsJson, true) : $productsJson;

        if (is_array($products) && !empty($products)) {
            return array_values(array_filter(array_map(function ($product) {
                $productId = (string) ($product['product_id'] ?? '');
                if ($productId === '') {
                    return null;
                }

                return [
                    'product_id' => $productId,
                    'name' => (string) ($product['name'] ?? ''),
                    'description' => (string) ($product['description'] ?? ''),
                    'category_name' => (string) ($product['category_name'] ?? ''),
                    'quantity' => (float) ($product['quantity'] ?? 0),
                    'price' => (float) ($product['price'] ?? 0),
                ];
            }, $products)));
        }

        $serviceIds = (array) $request->input('service', []);
        $makes = (array) $request->input('product_make', []);
        $quantities = (array) $request->input('product_qty', []);

        $productIds = array_values(array_unique(array_filter($serviceIds, function ($value) {
            return (string) $value !== '';
        })));

        // Check BomProduct first as that is what's used in the Create/Edit view
        $productsById = BomProduct::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $normalized = [];
        foreach ($serviceIds as $index => $serviceId) {
            if ((string) $serviceId === '') {
                continue;
            }

            $product = $productsById->get((int) $serviceId);

            $normalized[] = [
                'product_id' => (string) $serviceId,
                'name' => (string) ($product->product_name ?? ''),
                'description' => (string) ($product->description ?? ''),
                'category_name' => (string) ($makes[$index] ?? ''),
                'quantity' => (float) ($quantities[$index] ?? 0),
                'price' => (float) ($product->price ?? 0),
            ];
        }

        return $normalized;
    }
}
