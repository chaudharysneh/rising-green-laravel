<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PdfBuilderForm;
use App\Models\BomProduct;
use App\Models\Product;
use App\Models\Technology;
use App\Models\Warranty;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Invoice::class);
        return view('crm.invoices.index');
    }

    public function create()
    {
        $this->authorize('create', Invoice::class);
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $bomProducts = BomProduct::with('categories')->orderBy('product_name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        return view('crm.invoices.create', compact('customers', 'currencies', 'bomProducts', 'templates'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice->load('items');
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $bomProducts = BomProduct::with('categories')->orderBy('product_name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        return view('crm.invoices.edit', compact('invoice', 'customers', 'currencies', 'bomProducts', 'templates'));
    }

    // status update
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $request->validate([
            'status' => 'required|in:paid,unpaid,cancelled'
        ]);

        $invoice->update([
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $invoice->status,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'items', 'creator']);
        
        // Get user/company info to match estimate show pattern
        $user = auth()->user();
        $settings = \App\Models\Setting::pluck('value', 'key');
        
        return view('crm.invoices.show', compact('invoice', 'user', 'settings'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);
        $fileName = 'Invoice_' . date('Y-m-d_H-i-s') . '.csv';
        $query = Invoice::with(['customer', 'currency']);

        $invoices = $query->latest('invoice_date')->get();

        $fileName = 'invoices_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['ID', 'Invoice #', 'Customer', 'Invoice Date', 'Due Date', 'Amount', 'Status', 'Currency'];

        $callback = function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->id,
                    $invoice->invoice_no,
                    $invoice->customer?->name,
                    optional($invoice->invoice_date)->format('Y-m-d'),
                    optional($invoice->due_date)->format('Y-m-d'),
                    $invoice->amount,
                    ucfirst($invoice->status ?? 'unpaid'),
                    $invoice->currency?->code ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'creator', 'items', 'currency']);

        // Get user/company info
        $user = auth()->user();
        $settings = \App\Models\Setting::query()->whereIn('key', [
            'company_name',
            'company_tagline',
            'company_address',
            'company_tax_id',
            'company_logo_path',
            'company_qr_code_path',
            'social_instagram',
            'social_facebook',
            'social_linkedin',
        ])->pluck('value', 'key');

        // Get all products for BOM specifications
        $product_data = Product::all()->toArray();

        // Load technology and warranty maps
        $technologyList = Technology::all();
        $warrantyList = Warranty::all();

        $technology_map = [];
        foreach ($technologyList as $tech) {
            $technology_map[$tech->id] = $tech->title;
        }

        $warranty_map = [];
        foreach ($warrantyList as $war) {
            $warranty_map[$war->id] = $war->title;
        }

        // Render the invoice details HTML (similar to quotation_html)
        $quotation_html = view('crm.invoices.pdf', compact('invoice', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'))->render();

        // Get the selected template or default
        $template = null;
        if ($invoice->template_id) {
            $template = PdfBuilderForm::find($invoice->template_id);
        }
        if (!$template) {
            $template = PdfBuilderForm::latest()->first();
        }

        if ($template) {
            $form_data = $template->form_data ?? [];
            
            $pdfData = [
                'companySettings' => $settings,
                'companyLogoPath' => ($settings['company_logo_path'] ?? null) ? Storage::disk('public')->path($settings['company_logo_path']) : null,
                'companyQrCodePath' => ($settings['company_qr_code_path'] ?? null) ? Storage::disk('public')->path($settings['company_qr_code_path']) : null,
                'profileUser' => $user,
                'template_id' => $template->id,
                'template_name' => $template->template_name,
                'quotation_html' => $quotation_html,
                'header_image' => !empty($template->first_img) ? asset($template->first_img) : 'https://solar-crm.fableadtech.com/public/assets/img/profile/1760436391_b4bc9a00389df8eac539.jpg',
                'footer_image' => asset('/assets/pdfFooter.png'),
                'generated_at' => now()->format('d M Y h:i A'),
                'before_blocks' => $form_data['before_blocks'] ?? [],
                'after_blocks' => $form_data['after_blocks'] ?? [],
                'companyInfo' => $template->company_information ?? [],
                'timeLine' => $template->time_line ?? [],
                'components' => $template->components ?? ($form_data['components'] ?? []),
                'payment_terms' => $template->payment_terms ?? ($form_data['payment_terms'] ?? []),
                'environment_impact' => $template->environment_impact ?? ($form_data['environment_impact'] ?? []),
                'footer' => $template->footer ?? ($form_data['footer'] ?? []),
                'generationSection' => $form_data['generation'] ?? [],
                'ongridRoiSection' => $form_data['ongrid_roi'] ?? [],
            ];

            $pdf = Pdf::loadView('pdfbuilder.pdf', $pdfData);
            $pdf->setPaper('A4', 'portrait');
        } else {
            $pdf = Pdf::loadView('crm.invoices.pdf', compact('invoice', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'));
        }

        $filename = 'invoice-' . ($invoice->invoice_no ?: $invoice->id) . '.pdf';
        return $pdf->download($filename);
    }
}
