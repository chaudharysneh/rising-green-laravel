<?php

namespace App\Http\Controllers;

use App\Models\BomProduct;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\PdfBuilderForm;
use App\Models\Setting;
use App\Models\User;
use App\Models\Product;
use App\Models\Technology;
use App\Models\Warranty;
use Illuminate\Support\Facades\Storage;

class EstimateController extends Controller
{
    public function index()
    {
        return view('crm.estimates.index');
    }

    public function create()
    {
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        $bomProducts = BomProduct::with('categories')->orderBy('product_name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }

        return view('crm.estimates.create', compact('customers', 'users', 'templates', 'bomProducts'));
    }

    public function show(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        $estimate->load(['customer', 'product', 'creator']);

        // Get user/company info
        $user = auth()->user();
        $settings = Setting::pluck('value', 'key');

        // Get all products for BOM specifications
        $product_data = BomProduct::all()->toArray();

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

        return view('crm.estimates.show', compact('estimate', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'));
    }

    public function edit(Estimate $estimate)
    {
        if (($estimate->status ?? '') === 'approved') {
            return redirect()->route('estimates.index')->with('error', 'Approved estimates cannot be edited.');
        }

        $this->authorize('update', $estimate);
        $estimate->load(['customer', 'product', 'creator']);

        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $templates = PdfBuilderForm::orderBy('template_name')->get();
        $bomProducts = \App\Models\BomProduct::with('categories')->orderBy('product_name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }

        return view('crm.estimates.edit', compact('estimate', 'customers', 'users', 'templates', 'bomProducts'));
    }

    public function generate_estimate_pdf(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        $estimate->load(['customer', 'product', 'creator']);

        // Get user/company info
        $user = auth()->user();

        // Replicate profile settings logic for the template
        $settings = Setting::query()->whereIn('key', [
            'company_name',
            'company_tagline',
            'company_address',
            'company_tax_id',
            'company_logo_path',
            'company_qr_code_path',
            'phone',
            'email',
            'social_instagram',
            'social_facebook',
            'social_linkedin',
        ])->pluck('value', 'key');

        // Get all products for BOM specifications
        $product_data = BomProduct::all()->toArray();

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

        // Render the quotation HTML from the original PDF view
        $quotation_html = view('crm.estimates.pdf', compact('estimate', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'))->render();

        // Get the selected template or default to the first one
        $template = null;
        if ($estimate->template_id) {
            $template = PdfBuilderForm::find($estimate->template_id);
        }

        if (!$template) {
            $template = PdfBuilderForm::first();
        }

        if ($template) {
            $form_data = $template->form_data ?? [];

            // Prepare data for the new template wrapper
            $pdfData = [
                'estimate' => $estimate,
                'estimate_no' => $estimate->estimate_no,
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

            $pdf = \PDF::loadView('pdfbuilder.pdf', $pdfData);
            $pdf->setPaper('A4', 'portrait');
        } else {
            // Fallback to original behavior if absolutely no template exists
            $pdf = \PDF::loadView('crm.estimates.pdf', compact('estimate', 'user', 'settings', 'product_data', 'technology_map', 'warranty_map'));
            $pdf->setPaper('A4', 'portrait');
        }

        // Save Dompdf output as temp PDF
        $tmpMain = tempnam(sys_get_temp_dir(), 'main_pdf_');
        file_put_contents($tmpMain, $pdf->output());

        // Now merge with customer uploaded docs
        $mergedPdf = new \setasign\Fpdi\Fpdi();

        // Add Dompdf file
        $pageCount = $mergedPdf->setSourceFile($tmpMain);
        for ($page = 1; $page <= $pageCount; $page++) {
            $tpl = $mergedPdf->importPage($page);
            $size = $mergedPdf->getTemplateSize($tpl);
            $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $mergedPdf->useTemplate($tpl);
        }

        // Add each customer doc (if PDF/Image)
        $customer_docs = is_array($estimate->customer_docs) ? $estimate->customer_docs : [];
        foreach ($customer_docs as $doc) {
            $path = is_array($doc) ? ($doc['path'] ?? null) : $doc;
            if ($path && Storage::disk('public')->exists($path)) {
                $filePath = Storage::disk('public')->path($path);
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                if ($ext === 'pdf') {
                    try {
                        $count = $mergedPdf->setSourceFile($filePath);
                        for ($p = 1; $p <= $count; $p++) {
                            $tpl = $mergedPdf->importPage($p);
                            $size = $mergedPdf->getTemplateSize($tpl);
                            $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $mergedPdf->useTemplate($tpl);
                        }
                    } catch (\Exception $e) {
                        \Log::error("Failed to merge PDF customer doc {$path}: " . $e->getMessage());
                    }
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    try {
                        // Convert image to a page
                        $mergedPdf->AddPage();
                        $mergedPdf->Image($filePath, 10, 10, 190, 270, strtoupper($ext === 'jpg' ? 'jpeg' : $ext));
                    } catch (\Exception $e) {
                        \Log::error("Failed to merge image customer doc {$path}: " . $e->getMessage());
                    }
                }
            }
        }

        // Clean up temp file
        if (file_exists($tmpMain)) {
            @unlink($tmpMain);
        }

        // Output final merged PDF
        $fileName = 'Estimate-' . ($estimate->estimate_no ?: $estimate->estimate_id) . '.pdf';
        return response($mergedPdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function downloadCustomerDocument(Estimate $estimate, int $docIndex)
    {
        abort_unless($this->canAccessEstimateDocuments($estimate), 403);

        $docs = is_array($estimate->customer_docs) ? $estimate->customer_docs : [];
        $doc = $docs[$docIndex] ?? null;

        if (!$doc) {
            abort(404);
        }

        $path = is_array($doc) ? ($doc['path'] ?? null) : null;
        $downloadName = is_array($doc)
            ? ($doc['original_name'] ?? basename((string) $path))
            : basename((string) $doc);

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download($path, $downloadName);
    }

    private function canAccessEstimateDocuments(Estimate $estimate): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return Customer::query()
            ->visibleToUser($user)
            ->whereKey($estimate->customer_id)
            ->exists();
    }
}
