<?php
// ✅ Start the session here
$session = session();
$userId = $session->get('id');

// Compatibility helper: `esc()` is used throughout this PDF template.
if (!function_exists('esc')) {
    function esc($value = null)
    {
        return e($value);
    }
}

if (!defined('FCPATH')) {
    define('FCPATH', public_path() . DIRECTORY_SEPARATOR);
}

if (!function_exists('normalize_pdf_image')) {
    function normalize_pdf_image($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        // Helper closure to optimize and convert progressive images to Baseline using GD
        $optimizeImage = function($candidate) {
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if (empty($ext)) $ext = 'png';
            elseif ($ext === 'jpg') $ext = 'jpeg';
            
            if (extension_loaded('gd') && ($ext === 'jpg' || $ext === 'jpeg')) {
                try {
                    // Detect Progressive JPEG format using header inspection
                    $handle = @fopen($candidate, 'rb');
                    $isProgressive = false;
                    if ($handle) {
                        $header = @fread($handle, 131072); // Read initial segment to check for SOF2 markers
                        @fclose($handle);
                        if (strpos($header, "\xFF\xC2") !== false) {
                            $isProgressive = true;
                        }
                    }

                    // Run GD ONLY for broken Progressive JPEGs. Baseline JPEGs are left untouched!
                    if ($isProgressive) {
                        $srcImg = @imagecreatefromjpeg($candidate);
                        if ($srcImg) {
                            $width = imagesx($srcImg);
                            $height = imagesy($srcImg);
                            
                            // Keep 100% of original dimensions to prevent tampering with document layout sizes!
                            $newW = $width;
                            $newH = $height;
                            
                            $dstImg = imagecreatetruecolor($newW, $newH);
                            $white = imagecolorallocate($dstImg, 255, 255, 255);
                            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $white);
                            
                            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height);
                            
                            ob_start();
                            imageinterlace($dstImg, 0); // Enforce non-progressive baseline format
                            imagejpeg($dstImg, null, 90); // High quality 90 to preserve crispness
                            $binData = ob_get_clean();
                            
                            imagedestroy($srcImg);
                            imagedestroy($dstImg);
                            
                            if ($binData !== false && strlen($binData) > 0) {
                                return 'data:image/jpeg;base64,' . base64_encode($binData);
                            }
                        }
                    }
                } catch (\Throwable $t) {}
            }
            
            // Fast pathway for all other formats (Baseline JPEG & PNG) to preserve exact original bytes
            $imgData = @file_get_contents($candidate);
            if ($imgData !== false) {
                $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($imgData);
            }
            return null;
        };

        // If it's already a base64 data URI, return as-is
        if (strpos($path, 'data:image') === 0) {
            return $path;
        }

        // Parse URL paths and filenames
        $cleanPath = $path;
        if (preg_match('/^https?:\/\//i', $path)) {
            $urlParts = parse_url($path);
            $cleanPath = isset($urlParts['path']) ? ltrim($urlParts['path'], '/\\') : $path;
        }

        // Clean prefix components (public, storage, public_html, etc)
        $cleanPath = preg_replace('#^(?:public|public_html|storage|app/public|storage/app/public)(?:/|\\\\)+#i', '', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/\\');

        // Generate robust list of potential disk locations
        $candidates = [
            // 0. Raw path itself (may already be an absolute filesystem path)
            $path,
            
            // 1. Standard Storage paths
            storage_path('app/public/' . $cleanPath),
            storage_path('app/' . $cleanPath),
            
            // 2. Standard Public and Public/Storage paths
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
            
            // 3. Raw filesystem path mappings in standard web-serving folders
            base_path('public_html/storage/' . $cleanPath),
            base_path('public_html/' . $cleanPath),
            base_path('public/storage/' . $cleanPath),
            
            // 4. Deeply nested common project assets & uploads
            public_path('uploads/' . $cleanPath),
            public_path('uploads/products/' . $cleanPath),
            public_path('uploads/img/product/' . $cleanPath),
            public_path('assets/' . $cleanPath),
            public_path('assets/img/profile/' . $cleanPath),
        ];

        // Always try matching by filename for bom-products & products folders
        $filename = basename($cleanPath);
        if ($filename !== '') {
            $candidates[] = storage_path('app/public/bom-products/' . $filename);
            $candidates[] = storage_path('app/public/products/' . $filename);
            $candidates[] = public_path('storage/bom-products/' . $filename);
            $candidates[] = public_path('storage/products/' . $filename);
            $candidates[] = base_path('public_html/storage/bom-products/' . $filename);
            $candidates[] = base_path('public_html/storage/products/' . $filename);
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate && @file_exists($candidate) && @is_file($candidate)) {
                $result = $optimizeImage($candidate);
                if ($result) return $result;
                // Fallback if optimize failed but file is accessible
                $imgData = @file_get_contents($candidate);
                if ($imgData !== false) {
                    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                    $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                    return 'data:' . $mime . ';base64,' . base64_encode($imgData);
                }
            }
        }

        // HTTP fallback: try to fetch the image via URL and convert to base64
        // (Dompdf often cannot fetch URLs from the same server due to loopback issues)
        $urlToTry = (preg_match('/^https?:\/\//i', $path)) ? $path : asset('storage/' . $cleanPath);
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $imgData = @file_get_contents($urlToTry, false, $ctx);
            if ($imgData !== false && strlen($imgData) > 0) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($imgData) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($imgData);
            }
        } catch (\Throwable $e) {}

        // Last resort: return the URL directly (may not render in Dompdf)
        return $urlToTry;
    }
}

// Compatibility helper: CodeIgniter-style `base_url()` used in this PDF template.
if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        return normalize_pdf_image($path);
    }
}

// ✅ Load the model here directly
$model = new \App\Models\User(); // Change to your actual model name
// $user = $model->where('id', $userId)->first();
// $user = $model->where('role', 3)->first();

$rupeeHtml = '<span style="font-family: DejaVu Sans; sans-serif;">&#8377;</span>';
$estimate_no = $estimate_no ?? '--';

// Normalize user data for this legacy PDF template.
if (!isset($user) && isset($profileUser)) {
    $user = $profileUser;
}

if (!isset($user) || (!is_array($user) && !($user instanceof \ArrayAccess))) {
    $user = [];
}

// Resolve company logo from settings table (company_logo_path)
$logoBase64 = null;
if (!empty($companyLogoPath) && file_exists($companyLogoPath)) {
    $logoData = file_get_contents($companyLogoPath);
    $logoBase64 = 'data:image/' . pathinfo($companyLogoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
} elseif (!empty($companySettings['company_logo_path'])) {
    $diskPath = storage_path('app/public/' . $companySettings['company_logo_path']);
    if (file_exists($diskPath)) {
        $logoData = file_get_contents($diskPath);
        $logoBase64 = 'data:image/' . pathinfo($diskPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
    }
}

// Fallback to legacy $user['company_logo'] if settings logo is not found
if (!$logoBase64 && !empty($user['company_logo'])) {
    $legacyPath = public_path('assets/img/profile/' . $user['company_logo']);
    if (file_exists($legacyPath)) {
        $logoData = file_get_contents($legacyPath);
        $logoBase64 = 'data:image/' . pathinfo($legacyPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
    } else {
        $logoBase64 = normalize_pdf_image('public/assets/img/profile/' . $user['company_logo']);
    }
}

// Resolve company name from settings table (company_name)
$globalCompanyName = !empty($companySettings['company_name']) ? $companySettings['company_name'] : (!empty($user['company_name']) ? $user['company_name'] : '--');

// Section active helper (if "active" is missing, treat as active)
$_isActive = static function ($section): bool {
    return !is_array($section) || !array_key_exists('active', $section) || (int) $section['active'] === 1;
};

// ---------------- Page-break control ----------------
// Dompdf will add an extra blank page if the LAST rendered page has `page-break-after: always`.
// Since pages are conditionally rendered, compute which pages are active and only apply
// the `page-break` class to pages BEFORE the last active page.
$__companyInfoSection = (isset($companyInfo) && is_array($companyInfo)) ? $companyInfo : [];
$__generationSection0 = (isset($generationSection) && is_array($generationSection)) ? $generationSection : [];
$__ongridRoiSection0 = (isset($ongridRoiSection) && is_array($ongridRoiSection)) ? $ongridRoiSection : [];
$__timeLineSection = (isset($timeLine) && is_array($timeLine)) ? $timeLine : [];
$__componentsSection = (isset($components) && is_array($components)) ? $components : [];
$__environmentImpactSection = (isset($environmentImpact) && is_array($environmentImpact)) ? $environmentImpact : [];
$__footerSection = (isset($footer) && is_array($footer)) ? $footer : [];

$__companyInfoActive0 = $_isActive($__companyInfoSection);
$__generationActive0 = false;
$__ongridRoiActive0 = $_isActive($__ongridRoiSection0);
$__timeLineActive0 = $_isActive($__timeLineSection);
$__componentsActive0 = $_isActive($__componentsSection);
$__environmentImpactActive0 = $_isActive($__environmentImpactSection);
$__footerActive0 = $_isActive($__footerSection);

// Page 7 (Offer & Terms) is controlled by the same "timeline_active" toggle in form.php
$__offerTermsActive0 = $__timeLineActive0;

$__activePages = ['p1']; // first page always renders
if ($__companyInfoActive0)
    $__activePages[] = 'p2';
if ($__generationActive0)
    $__activePages[] = 'p3';
if ($__ongridRoiActive0)
    $__activePages[] = 'p4';
if ($__timeLineActive0)
    $__activePages[] = 'p5';
if ($__componentsActive0)
    $__activePages[] = 'p6';
if ($__offerTermsActive0)
    $__activePages[] = 'p7';
if ($__environmentImpactActive0)
    $__activePages[] = 'p8';
if ($__footerActive0)
    $__activePages[] = 'p9';

$__lastPageKey = $__activePages[count($__activePages) - 1];
$_pageClass = static function (string $key) use ($__lastPageKey): string {
    return $key === $__lastPageKey ? 'page' : 'page page-break';
};

// Load estimate data if estimate_no is available
$estdata = null;
$passedEstimate = isset($estimate) ? $estimate : null;

if (!$passedEstimate && !empty($estimate_no) && $estimate_no !== '--') {
    try {
        $passedEstimate = \App\Models\Estimate::where('estimate_no', $estimate_no)->with('customer')->first();
    } catch (\Throwable $e) {
        $passedEstimate = null;
    }
}

if ($passedEstimate) {
    $estdata = new \stdClass();
    $attrs = ($passedEstimate instanceof \Illuminate\Database\Eloquent\Model) ? $passedEstimate->getAttributes() : (array) $passedEstimate;

    foreach ($attrs as $key => $val) {
        $estdata->$key = $val;
    }

    // Add aliases needed by this template for Customer details
    if (isset($passedEstimate->customer)) {
        $estdata->name = $passedEstimate->customer->name ?? '--';
        $estdata->address = $passedEstimate->customer->address ?? '--';
    }
}

// Determine model type to dynamically switch labels (Invoice vs Estimate)
$isInvoice = ($passedEstimate instanceof \App\Models\Invoice);
$pdfTypeLabelCap = $isInvoice ? 'SOLAR INVOICE' : 'SOLAR PROPOSAL';
$pdfTypeLabelMixed = $isInvoice ? 'Invoice' : 'Proposal';
$pdfTypeLabelMixed2 = $isInvoice ? 'invoice' : 'proposal';

// Get prepared by name (user who created/owns the estimate)
$preparedByName = $user['name'] ?? ($user['company_name'] ?? '--');
$preparedForName = ($estdata && isset($estdata->name)) ? $estdata->name : '--';
$clientAddress = ($estdata && isset($estdata->address)) ? $estdata->address : '--';
// Get quantity from estimate data
// Get quantity from estimate data - ensure it displays correctly
$quantity = '0';
if ($estdata) {
    if (isset($estdata->quantity) && !empty($estdata->quantity)) {
        $qtyValue = (float) $estdata->quantity;
        if ($qtyValue > 0) {
            // Format quantity - remove trailing zeros if decimal
            $quantity = rtrim(rtrim(number_format($qtyValue, 1), '0'), '.');
        }
    }

    // If quantity is still 0, try to calculate from product_name JSON
    if ($quantity == '0' && !empty($estdata->product_name)) {
        $products = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
        if (is_array($products) && !empty($products)) {
            $totalQty = 0;
            foreach ($products as $product) {
                if (isset($product['quantity']) && isset($product['capacity'])) {
                    $totalQty += (float) $product['quantity'] * (float) $product['capacity'];
                } elseif (isset($product['quantity'])) {
                    $totalQty += (float) $product['quantity'];
                }
            }
            if ($totalQty > 0) {
                $quantity = rtrim(rtrim(number_format($totalQty, 1), '0'), '.');
            }
        }
    }

    // If quantity is still 0, auto-calculate kW from bill/rate (proposal-wise sizing)
    // Uses the same style of assumptions as the generation chart:
    // monthly_units ≈ monthly_bill / unit_rate
    // required_kW ≈ monthly_units / (avg_units_per_kw_per_day * 30 * (pr/100))
    if ($quantity == '0' && !empty($estdata->generation_data)) {
        $gd = json_decode($estdata->generation_data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($gd)) {
            $monthlyBill = (isset($gd['monthly_electricity_bill']) && is_numeric($gd['monthly_electricity_bill']))
                ? (float) $gd['monthly_electricity_bill']
                : 0.0;
            $unitRateTmp = (isset($gd['unit_rate']) && is_numeric($gd['unit_rate'])) ? (float) $gd['unit_rate'] : 0.0;

            // Optional override keys if you later store them in generation_data
            $avgUnitsPerKwTmp = (isset($gd['avg_units_per_kw']) && is_numeric($gd['avg_units_per_kw'])) ? (float) $gd['avg_units_per_kw'] : 4.31;
            $prTmp = (isset($gd['pr']) && is_numeric($gd['pr'])) ? (float) $gd['pr'] : 80.1;

            $unitRateTmp = $unitRateTmp > 0 ? $unitRateTmp : 8.0;
            $avgUnitsPerKwTmp = $avgUnitsPerKwTmp > 0 ? $avgUnitsPerKwTmp : 4.32;
            $prFactorTmp = max(0.0, min(1.0, $prTmp / 100.0));

            if ($monthlyBill > 0 && $unitRateTmp > 0 && $avgUnitsPerKwTmp > 0 && $prFactorTmp > 0) {
                $monthlyUnits = $monthlyBill / $unitRateTmp;
                $assumedDays = 30.0;
                $requiredKw = $monthlyUnits / ($avgUnitsPerKwTmp * $assumedDays * $prFactorTmp);
                if ($requiredKw > 0) {
                    $quantity = rtrim(rtrim(number_format($requiredKw, 1), '0'), '.');
                }
            }
        }
    }
}
$estimateDate = ($estdata && isset($estdata->estimate_date)) ? date('j, F Y', strtotime($estdata->estimate_date)) : date('j, F Y');
$generatedDateTime = date('j, F Y | g:iA');

// ================= ROI (Page 4) - Fixed assumptions (proposal-safe) =================
// Inputs:
// - Solar system size (kW): from estimate quantity (no default)
// - Unit rate (₹/unit): generation_data.unit_rate (default 8)
// - System cost (₹): use "Lending Cost Of Customer" = Customer Payable Amount - Subsidy
//
// Fixed assumptions:
// - Average generation: 3.6 units per kW per day
// - Days per year: 365
// - Solar lifetime: 25 years
// - Electricity tariff escalation: 5% per year
// - Self-consumption: 100%

$lifetimeYears = 25;
$daysPerYear = 365;
$avgUnitsPerKwPerDay_Roi = 3.6;
$tariffEscalation = 0.05;
$panelDegradation = 0.025; // 0.7% per year

// Use estimate quantity (NO default 10kW; if missing it stays 0 unless auto-calculated earlier)
$systemCapacity = (float) $quantity;
if ($systemCapacity <= 0) {
    $systemCapacity = 0.0;
}

// Unit rate (₹/unit) from generation_data, default ₹8
$unitRate = 8.0;
if ($estdata && !empty($estdata->generation_data)) {
    $gd = json_decode($estdata->generation_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($gd)) {
        if (isset($gd['unit_rate']) && is_numeric($gd['unit_rate'])) {
            $unitRate = (float) $gd['unit_rate'];
        }
    }
}
if ($unitRate <= 0) {
    $unitRate = 8.0;
}

// System cost = Customer Payable Amount - Subsidy (your "Lending Cost Of Customer")
$subtotalForCost = ($estdata && isset($estdata->price)) ? (float) $estdata->price : 0.0;
$solarStructureChargesForCost = ($estdata && isset($estdata->solar_structure_charges)) ? (float) $estdata->solar_structure_charges : 0.0;
$discountForCost = ($estdata && isset($estdata->discount)) ? (float) $estdata->discount : 0.0;
$subsidyForCost = ($estdata && isset($estdata->subsidy_amount)) ? (float) $estdata->subsidy_amount : 0.0;
$gstRateForCost = ($estdata && isset($estdata->gst)) ? (float) $estdata->gst : 0.0;

$gstAmountForCost = null;
if ($estdata && isset($estdata->gst_amount) && $estdata->gst_amount !== null && $estdata->gst_amount !== '') {
    $gstAmountForCost = (float) $estdata->gst_amount;
}
if ($gstAmountForCost === null && $estdata && !empty($estdata->gst_breakdown)) {
    $decoded = json_decode($estdata->gst_breakdown, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['gst_amount'])) {
        $gstAmountForCost = (float) $decoded['gst_amount'];
    }
}
if ($gstAmountForCost === null && $estdata && !empty($estdata->product_name)) {
    $items = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
    if (is_array($items)) {
        $sum = 0.0;
        foreach ($items as $it) {
            if (isset($it['tax_amount']) && is_numeric($it['tax_amount'])) {
                $sum += (float) $it['tax_amount'];
            }
        }
        if ($sum > 0) {
            $gstAmountForCost = $sum;
        }
    }
}
if ($gstAmountForCost === null) {
    $gstAmountForCost = ($subtotalForCost + $solarStructureChargesForCost) * ($gstRateForCost / 100.0);
}

$customerPayableForCost = $subtotalForCost + $solarStructureChargesForCost + (float) $gstAmountForCost - $discountForCost;
$lendingCostForCost = $customerPayableForCost - $subsidyForCost;

// Keep consistent naming with COST section later (PAGE 7)
// Customer Payable Amount (incl. taxes) and Lending Cost Of Customer (after subsidy)
$totalPayable = $customerPayableForCost;
$lendingCost = $lendingCostForCost;

// ROI investment: prefer Lending Cost Of Customer when available
$systemCost = $lendingCost > 0 ? $lendingCost : ($totalPayable > 0 ? $totalPayable : $subtotalForCost);

// Base generation & savings (Year 1)
$yearlyUnits = $systemCapacity * $avgUnitsPerKwPerDay_Roi * $daysPerYear;
$year1Savings = $yearlyUnits * $unitRate;

// ROI outputs
$roiData = [];
$yearlySavings = $year1Savings;
$paybackPeriod = 0.0;
$totalLifetimeSavings = 0.0;
$netLifetimeProfit = 0.0;

// Payback (as requested): Investment ÷ Year-1 savings (rounded to nearest whole year)
$paybackExact = ($year1Savings > 0) ? ($systemCost / $year1Savings) : 0.0;
$paybackRoundedYears = ($paybackExact > 0) ? (int) round($paybackExact) : 0;

// Build Year 1..25 cumulative savings with:
// - 5% tariff escalation per year
// - 0.7% panel degradation per year
$cumulative = 0.0;
for ($y = 1; $y <= $lifetimeYears; $y++) {
    $yearSaving = $year1Savings
        * pow(1.0 + $tariffEscalation, $y - 1)
        * pow(1.0 - $panelDegradation, $y - 1);
    $cumulative += $yearSaving;

    $roiData[] = [
        'year' => $y,
        'cumulative' => $cumulative,
        'hasData' => true,
    ];
}

$totalLifetimeSavings = $cumulative;
$netLifetimeProfit = $totalLifetimeSavings - $systemCost;

// Payback display value (whole years)
$paybackPeriod = $paybackExact;
// If payback can't be computed (0/empty), show "1" as requested (display-only)
$paybackPeriodDisplay = (string) (max(1, (int) $paybackRoundedYears));

// This template now always uses the fixed-assumptions ROI (Year 1–25).
// Keep the flag for compatibility with existing chart rendering code below.
$useSimpleRoi = true;

// For axis fallback in chart code later (kept for backward compatibility)
$estimateAmountMin = null;
$estimateAmountMax = null;

// Format outputs (₹)
$yearlySavingsFormatted = number_format($yearlySavings, 0);
$paybackPeriodFormatted = (string) $paybackRoundedYears;
$totalLifetimeSavingsFormatted = number_format($totalLifetimeSavings, 0);
$netLifetimeProfitFormatted = number_format($netLifetimeProfit, 0);
// Lakhs display (like 35.0L)
$totalLifetimeSavingsLakhs = $totalLifetimeSavings / 100000;
$totalLifetimeSavingsLakhsFormatted = number_format($totalLifetimeSavingsLakhs, 1) . 'L';
$netLifetimeProfitLakhs = $netLifetimeProfit / 100000;
$netLifetimeProfitLakhsFormatted = number_format($netLifetimeProfitLakhs, 1) . 'L';

// Chart scaling helpers
$allCumulativeValues = !empty($roiData) ? array_column($roiData, 'cumulative') : [0];
$maxRoiValue = max($allCumulativeValues);
$minRoiValue = min($allCumulativeValues);
$absMax = max(abs($maxRoiValue), abs($minRoiValue));
$maxChartRoi = ceil($absMax / 500000) * 500000;
if ($maxChartRoi < 500000)
    $maxChartRoi = 3000000;

// Fetch generation data from all current year estimates
$monthlyData = [];
// Default values - will be calculated dynamically from estimates table
$maxChartValue = 2000;
$pr = 80;
$monsoonDip = 13.8;
$avgUnitsPerKw = 4.31;

// Get current year
$currentYear = date('Y');
$currentYearStart = $currentYear . '-01-01';
$currentYearEnd = $currentYear . '-12-31';

// Initialize monthly data arrays for aggregation
$monthlyPrimary = array_fill(0, 12, 0);
$monthlySecondary = array_fill(0, 12, 0);
$totalQuantity = 0;
$estimateCount = 0;
$hasGenerationData = false;
try {
    $estimates = \App\Models\Estimate::whereYear('created_at', $currentYear)->get()->toArray();

    // Initialize month-wise quantity aggregation
    $monthlyQuantity = array_fill(0, 12, 0);
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Base generation per kW per month (varies by season) - units per kW
    $baseGenerationPerKw = [
        110,
        110,
        140,
        150,
        150,
        120,
        90,
        80,
        100,
        110,
        110,
        100  // Jan-Dec
    ];

    // Aggregate estimates by month based on created_at
    foreach ($estimates as $estimate) {
        $estimateCount++;
        $estQuantity = isset($estimate['quantity']) ? (float) $estimate['quantity'] : 0;
        if ($estQuantity > 0) {
            $totalQuantity += $estQuantity;

            // Get month from created_at
            if (isset($estimate['created_at']) && !empty($estimate['created_at'])) {
                $createdDate = $estimate['created_at'];
                $monthNum = (int) date('n', strtotime($createdDate)); // 1-12
                $monthIndex = $monthNum - 1; // 0-11

                if ($monthIndex >= 0 && $monthIndex < 12) {
                    // Add quantity to the corresponding month
                    $monthlyQuantity[$monthIndex] += $estQuantity;
                }
            }
        }

        // Check if generation_data field exists and has data
        $generationDataJson = null;
        if (isset($estimate['generation_data']) && !empty($estimate['generation_data'])) {
            $generationDataJson = $estimate['generation_data'];
        } elseif (isset($estimate['comment']) && !empty($estimate['comment'])) {
            // Try parsing comment field as JSON (fallback)
            $decoded = json_decode($estimate['comment'], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['generation_data'])) {
                $generationDataJson = json_encode($decoded['generation_data']);
            }
        }

        // Parse generation data if available
        if ($generationDataJson) {
            $genData = json_decode($generationDataJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($genData)) {
                $hasGenerationData = true;
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                foreach ($months as $index => $month) {
                    $monthKey = strtolower($month);
                    $monthNum = $index + 1;

                    // Try multiple key formats
                    $primary = 0;
                    $secondary = 0;

                    if (isset($genData[$monthKey])) {
                        $monthData = $genData[$monthKey];
                        $primary = isset($monthData['primary']) ? (float) $monthData['primary'] : (isset($monthData[0]) ? (float) $monthData[0] : 0);
                        $secondary = isset($monthData['secondary']) ? (float) $monthData['secondary'] : (isset($monthData[1]) ? (float) $monthData[1] : ($primary * 0.9));
                    } elseif (isset($genData[$monthNum])) {
                        $monthData = $genData[$monthNum];
                        $primary = isset($monthData['primary']) ? (float) $monthData['primary'] : (isset($monthData[0]) ? (float) $monthData[0] : 0);
                        $secondary = isset($monthData['secondary']) ? (float) $monthData['secondary'] : (isset($monthData[1]) ? (float) $monthData[1] : ($primary * 0.9));
                    } elseif (isset($genData[$index])) {
                        $monthData = $genData[$index];
                        $primary = isset($monthData['primary']) ? (float) $monthData['primary'] : (isset($monthData[0]) ? (float) $monthData[0] : 0);
                        $secondary = isset($monthData['secondary']) ? (float) $monthData['secondary'] : (isset($monthData[1]) ? (float) $monthData[1] : ($primary * 0.9));
                    }

                    // Aggregate data
                    $monthlyPrimary[$index] += $primary;
                    $monthlySecondary[$index] += $secondary;
                }

                // Get statistics from estimate data if available (will be overridden by calculated values later)
                if (isset($genData['pr']) && $genData['pr'] > 0) {
                    $pr = (float) $genData['pr'];
                }
                if (isset($genData['monsoon_dip']) && $genData['monsoon_dip'] > 0) {
                    $monsoonDip = (float) $genData['monsoon_dip'];
                }
                if (isset($genData['avg_units_per_kw']) && $genData['avg_units_per_kw'] > 0) {
                    $avgUnitsPerKw = (float) $genData['avg_units_per_kw'];
                }
            }
        }
    }

    // Calculate generation data based on month-wise quantity aggregation
    // For each month, calculate generation based on quantity created in that month
    foreach ($monthNames as $index => $month) {
        $monthQty = $monthlyQuantity[$index];

        if ($monthQty > 0) {
            // Calculate generation for this month based on quantity created
            // Use base generation scaled by quantity
            $baseGen = $baseGenerationPerKw[$index];
            $primaryGen = round($baseGen * $monthQty);
            $secondaryGen = round($primaryGen * 0.9);

            $monthlyPrimary[$index] += $primaryGen;
            $monthlySecondary[$index] += $secondaryGen;
        }
    }

} catch (\Throwable $e) {
    // If error, fall back to default
    $estimateCount = 0;
}

// Build monthly data array
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
foreach ($months as $index => $month) {
    $monthlyData[] = [
        'month' => $month,
        'primary' => round($monthlyPrimary[$index]),
        'secondary' => round($monthlySecondary[$index])
    ];
}

// If no data found, use default calculated values based on current estimate quantity
if (empty($monthlyData) || array_sum($monthlyPrimary) == 0) {
    $baseMonthlyData = [
        ['month' => 'Jan', 'primary' => 1100, 'secondary' => 1000],
        ['month' => 'Feb', 'primary' => 1100, 'secondary' => 950],
        ['month' => 'Mar', 'primary' => 1400, 'secondary' => 1200],
        ['month' => 'Apr', 'primary' => 1500, 'secondary' => 1300],
        ['month' => 'May', 'primary' => 1500, 'secondary' => 1350],
        ['month' => 'Jun', 'primary' => 1200, 'secondary' => 1000],
        ['month' => 'Jul', 'primary' => 900, 'secondary' => 750],
        ['month' => 'Aug', 'primary' => 800, 'secondary' => 650],
        ['month' => 'Sep', 'primary' => 1000, 'secondary' => 850],
        ['month' => 'Oct', 'primary' => 1100, 'secondary' => 950],
        ['month' => 'Nov', 'primary' => 1100, 'secondary' => 950],
        ['month' => 'Dec', 'primary' => 1000, 'secondary' => 850],
    ];

    // Scale data based on quantity (baseMonthlyData is defined for 10kW, so scale only when quantity is provided)
    $scaleFactor = ((float) $quantity > 0) ? ((float) $quantity / 10.0) : 0.0;

    $monthlyData = [];
    foreach ($baseMonthlyData as $data) {
        $monthlyData[] = [
            'month' => $data['month'],
            'primary' => round($data['primary'] * $scaleFactor),
            'secondary' => round($data['secondary'] * $scaleFactor)
        ];
    }
}

// Calculate dynamic statistics from actual estimates data
if (!empty($monthlyData)) {
    $totalPrimary = array_sum(array_column($monthlyData, 'primary'));
    $totalSecondary = array_sum(array_column($monthlyData, 'secondary'));

    // Calculate Performance Ratio (PR) dynamically from estimates data
    $ratios = [];
    foreach ($monthlyData as $data) {
        if ($data['primary'] > 0) {
            $ratios[] = ($data['secondary'] / $data['primary']) * 100;
        }
    }
    if (!empty($ratios) && count($ratios) > 0) {
        $pr = round(array_sum($ratios) / count($ratios), 1);
    }

    // Calculate Monsoon Dip dynamically from estimates data
    $peakMonths = ['Mar', 'Apr', 'May'];
    $monsoonMonths = ['Jul', 'Aug'];
    $peakAvg = 0;
    $monsoonAvg = 0;
    $peakCount = 0;
    $monsoonCount = 0;

    foreach ($monthlyData as $data) {
        if (in_array($data['month'], $peakMonths)) {
            $peakAvg += $data['primary'];
            $peakCount++;
        }
        if (in_array($data['month'], $monsoonMonths)) {
            $monsoonAvg += $data['primary'];
            $monsoonCount++;
        }
    }

    if ($peakCount > 0 && $monsoonCount > 0 && $peakAvg > 0) {
        $peakAvg = $peakAvg / $peakCount;
        $monsoonAvg = $monsoonAvg / $monsoonCount;
        if ($peakAvg > 0) {
            $monsoonDip = round((($peakAvg - $monsoonAvg) / $peakAvg) * 100, 1);
        }
    }

    // Calculate Average units per kW per day dynamically from estimates data
    $calcQuantity = $totalQuantity > 0 ? $totalQuantity : (float) $quantity;
    if ($calcQuantity > 0 && $totalPrimary > 0) {
        $totalUnits = $totalPrimary;
        $daysInYear = 365;
        if ($daysInYear > 0) {
            $avgUnitsPerKw = round(($totalUnits / $calcQuantity) / $daysInYear, 2);
        }
    }

    // Calculate maxChartValue dynamically from estimates data
    $maxPrimary = max(array_column($monthlyData, 'primary'));
    if ($maxPrimary > 0) {
        // Round up to nearest 200
        $maxChartValue = ceil($maxPrimary / 200) * 200;
        if ($maxChartValue < 1000) {
            $maxChartValue = 1000;
        } elseif ($maxChartValue > 2000) {
            $maxChartValue = 2000;
        }
    }
} else {
    // If no monthly data, try to get defaults from estimates table statistics
    try {
        $userId = $estdata ? ($estdata->user_id ?? null) : null;
        $query = \App\Models\Estimate::whereYear('created_at', $currentYear);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $estimates = $query->get()->toArray();

        // Calculate average quantity from estimates
        $quantities = [];
        foreach ($estimates as $est) {
            if (isset($est['quantity']) && (float) $est['quantity'] > 0) {
                $quantities[] = (float) $est['quantity'];
            }
        }

        if (!empty($quantities)) {
            $avgQuantity = array_sum($quantities) / count($quantities);
            // Use average quantity to calculate default avgUnitsPerKw
            if ($avgQuantity > 0) {
                $avgUnitsPerKw = round(4.31 * ($avgQuantity / 10), 2);
            }
        }
    } catch (\Throwable $e) {
        // Keep default values
    }
}
?>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
            height: 100%;
            width: 100%;
        }

        .header,
        .footer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            object-fit: cover;
        }

        .brand-logo {
            display: block;
            width: 120px;
            margin: 0px 0 10px 10px;
        }

        .page {
            height: 100%;
            width: 100%;
        }

        /* ✅ apply break only where needed */
        .page-break {
            page-break-after: always;
        }

        .with-logo {
            padding-top: 10px;
        }

        .image-container {
            height: 60%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .content-container {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            text-align: center;
        }

        .content-container h4 {
            margin: 0;
            font-size: 24px !important;
            font-weight: bold;
        }

        .content-container div {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .content-container div h2 {
            color: #52866A;
        }

        .content-container div p {
            color: #000;
            font-size: 15px
        }

        .content-container div ul li,
        .content-container div ol li {
            color: #000;
            font-size: 15px
        }

        /* ✅ Quotation styles (NO page-break) */
        .quotation-page {
            padding: 0px !important;
        }

        .quotation-container {
            width: 100%;
            font-size: 14px;
            line-height: 1.6;
            text-align: left;
        }

        /* Footer repeated on every page */
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 35px;
            font-size: 15px;
            color: #333;
            background: white;
            border-top: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 20px;
            z-index: 999;
        }

        .footer-left {
            font-weight: bold;
        }

        .footer-center {
            text-align: center;
            flex: 1;
        }


        .footer-right a {
            margin-left: 8px;
            text-decoration: none;
            color: #333;
            /* margin-top: -15px; */
        }

        /* Header Row */
        /* Header Row - only for first page */
        .header-container {
            display: flex;
            align-items: center;
            /* keep both aligned vertically */
            padding: 0;
            /* remove big padding */
            height: 120px;
            /* give more height */
        }

        .header-left {
            width: 50%;
            height: 100%;
        }

        .header-left img {
            width: 410px;
            /* stretch across left side */
            height: 1075px;
            /* fill container height */
            object-fit: cover;
            /* make it look like a banner */
        }

        .header-right {
            width: 100%;
            text-align: right;
            font-size: 29px;
            line-height: 1.4;
            padding-right: 0px;
            /* add spacing from edge */
        }


        .header-right img {
            width: 160px;
            margin-top: -120px;
        }

        .header-right .company-name {
            font-weight: bold;
            font-size: 30px;
            margin-top: 0px;
            font-family: 'Montserrat', sans-serif;
        }

        .header-right .company-address {
            font-weight: 600;
            font-size: 15px;
            margin-top: 100px;
            font-family: 'Montserrat', sans-serif;
        }
    </style>
    <?php
$img3 = !empty($companyInfo['image3']) ? normalize_pdf_image($companyInfo['image3']) : normalize_pdf_image('public/assets/img/secondpage_3.png');
$normalized_header = !empty($header_image) ? normalize_pdf_image($header_image) : '';
$header_image = (strpos($normalized_header, 'data:image') === 0) ? $normalized_header : normalize_pdf_image('public/assets/img/header_Image.jpg');
    ?>
</head>

<body>

    <!-- ✅ FIRST PAGE -->
    <div class="<?= $_pageClass('p1') ?>"
        style="position: relative; height: 100%; min-height: 842px; overflow: hidden;">
        <!-- Top Half: Header Image -->
        <div style="height: 62%; width: 100%; overflow: hidden; position: relative;">
            <img src="<?= $header_image ?>" alt="Header Image"
                style="width: 100%; height: 100%; object-fit: cover; display: block;">
        </div>

        <!-- Bottom Half: Content Section with Red Border -->
        <table width="100%" cellpadding="0" cellspacing="0"
            style="background:#fff; border-collapse:collapse; font-family:'Montserrat', sans-serif; margin-top:20px; padding-left: 20px; padding-right:15px;">
            <tr>
                <!-- LEFT SECTION -->
                <td width="40%" valign="top" style="padding:10px 15px; border-right:2px solid #4b9349;">

                    <!-- Logo + Company Info -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <?php if (!empty($logoBase64)): ?>
                            <td width="40%" valign="top">
                                <img src="<?= $logoBase64 ?>" style="max-width:200px; height:auto; object-fit:contain;">
                            </td>
                            <?php endif; ?>
                        </tr>
                    </table>

                    <!-- Company Full Name -->
                    <div
                        style="font-size:20px; color:#000; margin:25px 0; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <?php 
                        $fullCompanyName = esc($globalCompanyName);
if (stripos($fullCompanyName, 'technologies') === false && stripos($fullCompanyName, 'pvt') === false) {
    $fullCompanyName .= ' Technologies Pvt. Ltd.';
}
echo $fullCompanyName;
                        ?>
                    </div>

                    <!-- Proposal No -->
                    <div style="font-size:20px; color:#000; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <span style="font-weight:400;"><?= $pdfTypeLabelMixed ?> no : </span> #<?= esc($estimate_no) ?? '--' ?>
                    </div>

                </td>

                <!-- RIGHT SECTION -->
                <td width="60%" valign="top" style="padding:10px 10px; padding-top: 25px;">

                    <!-- Title -->
                    <div
                        style="margin-top: -25px; font-size:35px; font-weight:700; margin-bottom:10px; font-family: 'Montserrat', sans-serif; color:#000;">
                        <?= $pdfTypeLabelCap ?>
                    </div>

                    <!-- ONGRID + Date -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                        <tr>
                            <td
                                style="font-size:22px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#000;">
                                ONGRID <?= $quantity ?>KW
                            </td>
                            <td align="right"
                                style="font-size:22px; font-weight:400; font-family: 'Montserrat', sans-serif;">
                                <?= $estimateDate ?>
                            </td>
                        </tr>
                    </table>

                    <!-- Client Name -->
                    <div
                        style="font-size:20px; color:#000; margin-bottom:10px; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <span style="font-size:20px;">Client name :</span> <?= esc($preparedForName) ?>
                    </div>

                    <!-- Client Address -->
                    <div style="font-size:22px; font-weight:400; font-family: 'Montserrat', sans-serif;">
                        <?= esc($clientAddress) ?>
                    </div>

                    <!-- Red Line -->
                    <!-- <div style="border-top:1px solid #ff0000; margin-top:15px;"></div> -->

                </td>
            </tr>
        </table>


        <!-- Footer -->
        <div style="position:fixed; bottom:0; left:0; right:0; background:#4b9349; height:60px; padding:15px; ">
            <table width="100%" cellpadding="0" cellspacing="0"
                style="height:60px; color:#fff; font-size:13px; font-family:'Montserrat', sans-serif;">
                <tr>

                    <!-- Prepared By -->
                    <td width="20%" valign="middle" style="padding-left:30px;">
                        <div
                            style="opacity:0.8; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            Prepared by:</div>
                        <div
                            style="margin-top:0px; font-size:20px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            <?= esc($preparedByName) ?>
                        </div>
                    </td>

                    <!-- Prepared For -->
                    <td width="20%" valign="middle">
                        <div
                            style="opacity:0.8; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            Prepared for:</div>
                        <div
                            style="margin-top:0px; font-size:20px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            <?= esc($preparedForName) ?>
                        </div>
                    </td>

                    <!-- Logo + Divider -->
                    <td width="10%" valign="middle" align="right" style="border-right:3px solid #fff;">
                        <?php if (!empty($companySettings['company_logo_path'])): ?>
                        <img src="<?= normalize_pdf_image('public/assets/img/logos/favicon.jpeg') ?>" style="height:32px; width:32px; object-fit:contain;
                                        border-radius:6px; opacity:0.9; margin-right:5px;">
                        <?php endif; ?>
                    </td>

                    <!-- Generated On -->
                    <td width="30%" valign="middle" style="padding-left:20px;">
                        <div
                            style="opacity:0.8; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            Generated on</div>
                        <div
                            style="margin-top:0px; white-space:nowrap; font-size:18px; font-weight:400; font-family: 'Montserrat', sans-serif; color:#fff;">
                            <?= $generatedDateTime ?>
                        </div>
                    </td>

                </tr>
            </table>
        </div>

    </div>

    <!-- ✅ SECOND PAGE: Company Information & Gallery -->
    <?php
$__companyInfo = (isset($companyInfo) && is_array($companyInfo)) ? $companyInfo : [];
$__companyInfoActive = $_isActive($__companyInfo);
    ?>
    <?php if ($__companyInfoActive): ?>
    <div class="<?= $_pageClass('p2') ?>" style="position: relative; min-height: 842px; background: white;">
        <!-- Header -->
        <div style="padding: 40px;">
            <?php
    // Template-specific company information (saved in pdfbuilder_forms.company_information JSON)
    $companyInfo = isset($companyInfo) && is_array($companyInfo) ? $companyInfo : [];

    // CKEditor stores HTML (<p>, <br>, etc). For PDF we want to render HTML (not show tags),
    // but still keep it safe and avoid strange line breaks from stored newlines.
    $companyDescriptionRaw = (string) ($companyInfo['company_description'] ?? '');
    $companyDescriptionRaw = preg_replace("/\R+/", ' ', $companyDescriptionRaw); // remove hard newlines
    $companyDescription = trim(strip_tags($companyDescriptionRaw, '<p><br><b><strong><i><em><u>'));
    // Prevent long unbroken text from overflowing outside the PDF page
    // (insert zero‑width break opportunities into long runs of non-space chars, but avoid breaking HTML tags)
    $companyDescription = preg_replace('/([^\s<]{30})/', '$1&#8203;', $companyDescription);
    $cap = trim((string) ($companyInfo['company_capacity_installed'] ?? ''));
    $happy = trim((string) ($companyInfo['happy_customers'] ?? ''));
    $cities = trim((string) ($companyInfo['cities'] ?? ''));

    $capDisplay = $cap !== '' ? esc($cap) . '+' : '100+';
    $happyDisplay = $happy !== '' ? esc($happy) . '+' : '30+';
    $citiesDisplay = $cities !== '' ? esc($cities) . '+' : '20+';

    $img1 = !empty($companyInfo['image1']) ? normalize_pdf_image($companyInfo['image1']) : normalize_pdf_image('public/assets/img/seconpage_1.png');
    $img2 = !empty($companyInfo['image2']) ? normalize_pdf_image($companyInfo['image2']) : normalize_pdf_image('public/assets/img/secondpage_2.png');
    $img3 = !empty($companyInfo['image3']) ? normalize_pdf_image($companyInfo['image3']) : normalize_pdf_image('public/assets/img/secondpage_3.png');
            ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <div style="display: inline-block;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width: 160px; height: auto;">
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Company Name (Centered) -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="left">
                        <div
                            style="font-size: 45px; color: #000; font-family: 'Montserrat', sans-serif; text-align: left;">
                            <?php
    $companyName = esc($globalCompanyName);
    echo $companyName;
                        ?>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Mission Statement -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td>
                        <div
                            style="font-size: 21px; text-align: left; font-family: 'Montserrat', sans-serif; word-wrap: break-word; word-break: break-word;">
                            <?= $companyDescription !== '' ? $companyDescription : esc("We are on a mission to deliver 10,000 world-class solar installations ensuring maximum performance, durability, and ROI for every project.") ?>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Statistics Boxes -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
                <tr>
                    <td width="33.33%" valign="top" style="padding-right: 10px;">
                        <div style="background-color: #4b9349; color: #fff; padding: 25px 10px; text-align: center;">
                            <div style="font-size: 30px; margin-bottom: 0px; font-family: 'Montserrat', sans-serif;">
                                <?= $capDisplay ?>
                            </div>
                            <div style="font-size: 14px; font-family: 'Montserrat', sans-serif;">Total capacity
                                installed</div>
                        </div>
                    </td>
                    <td width="33.33%" valign="top" style="padding: 0 5px;">
                        <div style="background-color: #4b9349; color: #fff; padding: 25px 20px; text-align: center;">
                            <div style="font-size: 30px; margin-bottom: 0px; font-family: 'Montserrat', sans-serif;">
                                <?= $happyDisplay ?>
                            </div>
                            <div style="font-size: 14px; font-family: 'Montserrat', sans-serif;">Happy customers</div>
                        </div>
                    </td>
                    <td width="33.33%" valign="top" style="padding-left: 10px;">
                        <div style="background-color: #4b9349; color: #fff; padding: 25px 20px; text-align: center;">
                            <div style="font-size: 30px; margin-bottom: 0px; font-family: 'Montserrat', sans-serif;">
                                <?= $citiesDisplay ?>
                            </div>
                            <div style="font-size: 14px; font-family: 'Montserrat', sans-serif;">Cities</div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Image Gallery (Left: Vertical, Right: Two Horizontal) -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;">
                <tr>
                    <!-- Left Panel: Large Vertical Image -->
                    <td width="50%" valign="top" style="padding-right: 10px;" rowspan="2">
                        <div style="width: 100%; height: 560px; box-sizing: border-box; overflow: hidden;">
                            <img src="<?= $img1 ?>" alt="Solar Installation"
                                style="width: 100%; height: 100%; object-fit: contain; display: block;">
                        </div>
                    </td>
                    <!-- Top-Right Panel: Horizontal Image -->
                    <td width="50%" valign="top" style="padding-left: 10px; padding-bottom: 10px;">
                        <div style="width: 100%; height: 273px; box-sizing: border-box; overflow: hidden;">
                            <img src="<?= $img2 ?>" alt="Solar Installation"
                                style="width: 100%; height: 100%; object-fit: contain; display: block;">
                        </div>
                    </td>
                </tr>
                <tr>
                    <!-- Bottom-Right Panel: Horizontal Image -->
                    <td width="50%" valign="top" style="padding-left: 10px; padding-top: 5px;">
                        <div style="width: 100%; height: 273px;box-sizing: border-box; overflow: hidden;">
                            <img src="<?= $img3 ?>" alt="Solar Installation"
                                style="width: 100%; height: 100%; object-fit: contain; display: block;">
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Image Caption -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <div
                            style="font-size: 15px; text-align: center; font-style: normal; font-family: 'Montserrat', sans-serif;">
                            Each site is installed end to end with 5 years of AMC & monitoring
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->

        <table width="100%" cellpadding="0" cellspacing="0" style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px; font-family: 'Montserrat', sans-serif;">
                    PAGE 2
                </td>

                <td width="33.33%" align="right" style="padding:10px; font-family: 'Montserrat', sans-serif; white-space:nowrap;">
                    <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                        ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>

    </div>
    <?php endif; ?>

    <?php
$__generationSection = (isset($generationSection) && is_array($generationSection)) ? $generationSection : [];
$__generationActive = false;

$genTitle = trim((string) ($__generationSection['title'] ?? ''));
$genTitle = $genTitle !== '' ? $genTitle : 'GENERATION';

$genSubTitle = trim((string) ($__generationSection['sub_title'] ?? ''));
$genSubTitle = $genSubTitle !== '' ? $genSubTitle : 'ROUND THE YEAR GENERATION';

$genNote = trim((string) ($__generationSection['note'] ?? ''));
$genNote = $genNote !== '' ? $genNote : 'Generation figures are indicative and may vary with site conditions and weather patterns.';
    ?>
    <?php if ($__generationActive): ?>
    <!-- ================= PAGE 3 : GENERATION ================= -->
    <div class="<?= $_pageClass('p3') ?>" style="position:relative; min-height:842px; background:#fff;
                   
                    font-family:'Montserrat', sans-serif;">
        <!-- Slightly tighter padding so chart + summary fit on one Dompdf page -->
        <div style="padding: 35px 45px 45px 45px;">
            <!-- ================= HEADER ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr>
                    <td align="left" valign="top">
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" style="max-width: 160px; height: auto;">
                        <?php    endif; ?>

                    </td>
                </tr>
            </table>

            <!-- ================= TITLE ================= -->
            <div style="font-size:45px; font-weight:700; margin-bottom:4px; font-family: 'Montserrat', sans-serif;">
                <?= esc($genTitle) ?>
            </div>
            <div style="font-size:22px; margin-bottom:2px; font-family: 'Montserrat', sans-serif;">
                <?= date('F Y') ?>
            </div>
            <div style="font-size:20px; margin-bottom:12px; font-family: 'Montserrat', sans-serif;">
                <?= esc($genSubTitle) ?>
            </div>

            <!-- ================= BAR CHART ================= -->
            <?php
    // --------- Generation (Jan–Dec) from inputs ---------

    $systemCapacity = max(0.0, (float) $quantity);

    // Initialize
    $avgUnitsPerKw = 0.0;   // kWh / kW / day
    $pr = 0.0;   // %
    $monsoonDip = 0.0;   // %

    $monthlyBill = 0.0;
    $unitRate = 0.0;

    if (!empty($estdata->generation_data)) {
        $gd = json_decode($estdata->generation_data, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($gd)) {

            // Raw inputs
            if (isset($gd['monthly_electricity_bill']) && is_numeric($gd['monthly_electricity_bill'])) {
                $monthlyBill = (float) $gd['monthly_electricity_bill'];
            }

            if (isset($gd['unit_rate']) && is_numeric($gd['unit_rate'])) {
                $unitRate = (float) $gd['unit_rate'];
            }

            if (isset($gd['avg_units_per_kw']) && is_numeric($gd['avg_units_per_kw'])) {
                $avgUnitsPerKw = (float) $gd['avg_units_per_kw'];
            }

            if (isset($gd['pr']) && is_numeric($gd['pr'])) {
                $pr = (float) $gd['pr'];
            }

            if (isset($gd['monsoon_dip']) && is_numeric($gd['monsoon_dip'])) {
                $monsoonDip = (float) $gd['monsoon_dip'];
            }

            // ---------- Derivation Logic ----------
            // monthly_units = monthly_bill / unit_rate
            $monthlyUnits = ($monthlyBill > 0 && $unitRate > 0)
                ? ($monthlyBill / $unitRate)
                : 0.0;

            $days = 30.0;

            if ($systemCapacity > 0 && $monthlyUnits > 0) {

                // Case 1: avg_units_per_kw present, PR missing
                if ($avgUnitsPerKw > 0 && $pr <= 0) {
                    $pr = ($monthlyUnits / ($systemCapacity * $avgUnitsPerKw * $days)) * 100.0;
                }

                // Case 2: PR present, avg_units_per_kw missing
                elseif ($pr > 0 && $avgUnitsPerKw <= 0) {
                    $avgUnitsPerKw = $monthlyUnits / ($systemCapacity * $days * ($pr / 100.0));
                }

                // Case 3: Both missing → assume PR = 100 ONLY for derivation
                elseif ($pr <= 0 && $avgUnitsPerKw <= 0) {
                    $pr = 100.0; // derivation-only
                    $avgUnitsPerKw = $monthlyUnits / ($systemCapacity * $days);
                }
            }
        }
    }

    // ---------- Final normalization ----------
    $avgUnitsPerKw = max(0.0, $avgUnitsPerKw);
    $pr = max(0.0, min(100.0, $pr));
    $monsoonDip = max(0.0, min(100.0, $monsoonDip));


    $prDisp = rtrim(rtrim(number_format($pr, 1, '.', ''), '0'), '.');          // ex: 80 / 80.5
    $monsoonDipDisp = rtrim(rtrim(number_format($monsoonDip, 1, '.', ''), '0'), '.'); // ex: 13.8
    $avgUnitsPerKwDisp = rtrim(rtrim(number_format($avgUnitsPerKw, 2, '.', ''), '0'), '.'); // ex: 4.31

    // Seasonal adjustments:
    // Feb–May: +12%
    // Jun–Aug: -monsoonDip%
    // Nov–Jan: -6%
    // Sep–Oct: 0%
    $peakBoost = 0.12;
    $winterDip = 0.06;
    $monsoonFactor = max(0, (float) $monsoonDip) / 100.0;

    $daysByMonth = [
        'Jan' => 31,
        'Feb' => 28,
        'Mar' => 31,
        'Apr' => 30,
        'May' => 31,
        'Jun' => 30,
        'Jul' => 31,
        'Aug' => 31,
        'Sep' => 30,
        'Oct' => 31,
        'Nov' => 30,
        'Dec' => 31,
    ];
    $seasonFactor = [
        'Jan' => -$winterDip,
        'Feb' => $peakBoost,
        'Mar' => $peakBoost,
        'Apr' => $peakBoost,
        'May' => $peakBoost,
        'Jun' => -$monsoonFactor,
        'Jul' => -$monsoonFactor,
        'Aug' => -$monsoonFactor,
        'Sep' => 0.0,
        'Oct' => 0.0,
        'Nov' => -$winterDip,
        'Dec' => -$winterDip,
    ];

    // Monthly generation (kWh):
    // - primary: after seasonal adjustment (before PR)
    // - secondary: after PR (final usable output)
    $monthlyData = [];
    foreach ($daysByMonth as $m => $days) {
        $dailyGen = $systemCapacity * (float) $avgUnitsPerKw;
        $baseMonth = $dailyGen * (int) $days;
        $adj = $baseMonth * (1.0 + (float) ($seasonFactor[$m] ?? 0.0));
        $finalKwh = $adj * ((float) $pr / 100.0);
        $monthlyData[] = [
            'month' => $m,
            'primary' => round(max(0, $adj)),
            'secondary' => round(max(0, $finalKwh)),
        ];
    }

    // Dompdf is sensitive to total vertical height; increase chart height a bit to reduce blank space,
    // while still fitting chart + summary on a single page.
    $chartHeight = 650;
    // Use the dynamically calculated maxChartValue, or calculate from data if available
    $maxPrimaryFromData = !empty($monthlyData) ? max(array_column($monthlyData, 'primary')) : 0;
    $maxSecondaryFromData = !empty($monthlyData) ? max(array_column($monthlyData, 'secondary')) : 0;
    $maxChartValue = (float) max($maxPrimaryFromData, $maxSecondaryFromData);
    // Dynamic Y-axis labels (no hardcoded 2000/1600/...)
    // Build a "nice" scale with 6 ticks (max..0) based on actual data.
    $ticks = 6; // number of labels on Y-axis

    $maxFromData = max((float) $maxPrimaryFromData, (float) $maxSecondaryFromData, 0);

    // Choose a "nice" step (1/2/5 × 10^n)
    $rawMax = max(1, $maxFromData);
    $rawStep = $rawMax / ($ticks - 1);
    $pow10 = pow(10, floor(log10($rawStep)));
    $norm = $rawStep / $pow10;
    if ($norm <= 1) {
        $niceNorm = 1;
    } elseif ($norm <= 2) {
        $niceNorm = 2;
    } elseif ($norm <= 5) {
        $niceNorm = 5;
    } else {
        $niceNorm = 10;
    }
    $step = $niceNorm * $pow10;
    $niceMax = $step * ($ticks - 1);

    // If everything is 0, keep a small but readable axis
    if ($maxFromData <= 0) {
        $step = 1;
        $niceMax = 5;
    }

    $yAxisLabels = [];
    for ($i = 0; $i < $ticks; $i++) {
        $yAxisLabels[] = (int) round($niceMax - ($step * $i));
    }

    // Use the axis max for scaling bars
    $maxChartValue = (float) ($yAxisLabels[0] ?? $niceMax);

    // Fix: row height must add up to chartHeight (previously it exceeded and pushed content to next page)
    $rowHeight = $chartHeight / count($yAxisLabels);
            ?>

            <table width="100%" cellpadding="0" cellspacing="0"
                style="margin-bottom:18px; border-bottom:1px solid #ccc; page-break-inside: avoid;">
                <tr>

                    <!-- LEFT Y AXIS -->
                    <td width="45" valign="top">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <?php    foreach ($yAxisLabels as $label): ?>
                            <tr>
                                <td style="height:<?= $rowHeight ?>px;
                                            font-size:10px; color:#666;
                                            text-align:right; padding-right:6px;
                                            border-bottom:1px solid #eee;
                                            font-family: 'Montserrat', sans-serif;">
                                    <?= $label ?>
                                </td>
                            </tr>
                            <?php    endforeach; ?>
                        </table>
                    </td>

                    <!-- BARS -->
                    <td valign="bottom">
                        <table width="100%" height="<?= $chartHeight ?>" cellpadding="0" cellspacing="0">
                            <tr>
                                <?php 
                                // Ensure all 12 months are displayed
    $allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthlyDataMap = [];
    foreach ($monthlyData as $data) {
        $monthlyDataMap[$data['month']] = $data;
    }

    // Build complete monthly data array with all 12 months
    $completeMonthlyData = [];
    foreach ($allMonths as $month) {
        if (isset($monthlyDataMap[$month])) {
            $completeMonthlyData[] = $monthlyDataMap[$month];
        } else {
            // Add month with zero values if missing
            $completeMonthlyData[] = [
                'month' => $month,
                'primary' => 0,
                'secondary' => 0
            ];
        }
    }

    foreach ($completeMonthlyData as $data):
        // Prevent division by zero
        $maxValue = $maxChartValue > 0 ? $maxChartValue : 1;
        $primaryValue = max(0, (float) ($data['primary'] ?? 0));
        $secondaryValue = max(0, (float) ($data['secondary'] ?? 0));

        $primaryHeight = ($primaryValue / $maxValue) * $chartHeight;
        $secondaryHeight = ($secondaryValue / $maxValue) * $chartHeight;

        if ($primaryHeight < 1 && $primaryValue > 0)
            $primaryHeight = 1;
        if ($secondaryHeight < 1 && $secondaryValue > 0)
            $secondaryHeight = 1;

        $mLabel = (string) ($data['month'] ?? '');
        $mLabel = strtoupper(substr($mLabel, 0, 3));
                                ?>
                                <td width="8.33%" valign="bottom" align="center">
                                    <table width="100%" height="<?= $chartHeight ?>" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td valign="bottom">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <!-- Green (after PR) -->
                                                        <td width="40%" valign="bottom">
                                                            <div style="height:<?= $secondaryHeight ?>px;
                                                                        background:#00c389;
                                                                        border-radius:8px 8px 0 0;"></div>
                                                        </td>
                                                        <td width="20%"></td>
                                                        <!-- Black (before PR) -->
                                                        <td width="40%" valign="bottom">
                                                            <div style="height:<?= $primaryHeight ?>px;
                                                                        background:#000;
                                                                        border-radius:8px 8px 0 0;"></div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <div style="margin-top:6px; height:26px; font-family:'Montserrat', sans-serif;">
                                        <span style="display:inline-block; font-size:7px; line-height:1; color:#000;
                                                     transform: rotate(-90deg); transform-origin:center;">
                                            <?= $mLabel ?>
                                        </span>
                                    </div>
                                </td>
                                <?php    endforeach; ?>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT LABEL -->
                    <td width="60" valign="middle" align="center"
                        style="font-size:11px; color:#666;   font-family: 'Montserrat', sans-serif;">
                        units produced per month
                    </td>

                </tr>
            </table>

            <!-- ================= SUMMARY ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px; page-break-inside: avoid;">
                <tr>
                    <td width="33.33%" align="center">
                        <div style="font-size:22px; font-weight:600; font-family: 'Montserrat', sans-serif;">80%</div>
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">PR: Performance Ratio</div>
                    </td>
                    <td width="33.33%" align="center">
                        <div style="font-size:22px; font-weight:600; font-family: 'Montserrat', sans-serif;">13.8%</div>
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">Monsoon Dip</div>
                    </td>
                    <td width="33.33%" align="center">
                        <div style="font-size:22px; font-weight:600; font-family: 'Montserrat', sans-serif;">4.31</div>
                        <div style="font-size:18px; font-family: 'Montserrat', sans-serif;">Average units per<br>kW/Day
                        </div>
                    </td>
                </tr>
            </table>

            <!-- ================= DISCLAIMER ================= -->
            <div style="font-size:15px; text-align:center; font-family: 'Montserrat', sans-serif;">
                <?= esc($genNote) ?>
            </div>
        </div>
        <!-- ================= FOOTER ================= -->
        <table width="100%" cellpadding="0" cellspacing="0" style="position:fixed; bottom:10; left:0; right:0;
                        background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE 3
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                            ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>

    </div>
    <!-- ================= END PAGE 3 ================= -->
    <?php endif; ?>

    <?php
$__ongridRoiSection = (isset($ongridRoiSection) && is_array($ongridRoiSection)) ? $ongridRoiSection : [];
$__ongridRoiActive = $_isActive($__ongridRoiSection);

$roiTitle = trim((string) ($__ongridRoiSection['title'] ?? ''));
$roiTitle = $roiTitle !== '' ? $roiTitle : 'ROI';

$roiSubTitle = trim((string) ($__ongridRoiSection['sub_title'] ?? ''));
$roiSubTitle = $roiSubTitle !== '' ? $roiSubTitle : 'Ongrid ROI';

$roiStarts = $__ongridRoiSection['residential_starts_percent'] ?? '';
$roiStarts = (is_numeric($roiStarts) && (float) $roiStarts > 0) ? rtrim(rtrim(number_format((float) $roiStarts, 2, '.', ''), '0'), '.') : '';

$roiNote = trim((string) ($__ongridRoiSection['note'] ?? ''));
$roiNote = $roiNote !== '' ? $roiNote : 'SOLAR IS ONE OF THE BEST INVESTMENT YOU WILL EVER MAKE';
   ?>
    <?php if ($__ongridRoiActive): ?>
    <!-- ================= PAGE 4 : ROI ================= -->
    <div class="<?= $_pageClass('p4') ?>" style="position:relative; min-height:842px;
                background:#4b9349 !important;  /* image-like green */
                font-family:'Montserrat', sans-serif;">
        <div style="padding: 50px;">
            <!-- ================= HEADER ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:30px;">
                <tr>
                    <td align="left" valign="top">
                        <div
                            style="font-size:18px; color:#e8f6f4; margin-bottom:14px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                        <div
                            style="font-size:45px; font-weight:700; color:#fff; margin-bottom:8px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>KW <?= esc($roiTitle) ?>
                        </div>
                        <div style="font-size:18px; color:#e0f2ef; font-family: 'Montserrat', sans-serif;">
                            <?= esc($roiSubTitle) ?>
                        </div>

                    </td>
                </tr>
            </table>

            <!-- ================= ROI BAR CHART ================= -->
            <?php
    // ROI chart:
    // - If simple ROI inputs are present -> show Year 1..25
    // - Else -> keep legacy "last 10 calendar years" behavior
    $roiYears = $useSimpleRoi ? 25 : 10;

    // Chart height (you changed this): keep as-is, but ensure it’s numeric
    // $chartHeight = (int) ($chartHeight ?? 360);
    $chartHeight = 550;
    if ($chartHeight <= 0) {
        $chartHeight = 360;
    }

    $roiDataSafe = is_array($roiData ?? null) ? $roiData : [];
    $completeRoiData = [];

    if ($useSimpleRoi) {
        // Already prepared as Year 1..25 in the calculation block above
        $completeRoiData = $roiDataSafe;
    } else {
        $roiMap = [];
        foreach ($roiDataSafe as $d) {
            $y = (int) ($d['year'] ?? 0); // calendar year
            if ($y > 0) {
                $roiMap[$y] = $d;
            }
        }
        $currentYearRoi = (int) date('Y');
        $startYearRoi = $currentYearRoi - ($roiYears - 1);
        for ($y = $startYearRoi; $y <= $currentYearRoi; $y++) {
            if (isset($roiMap[$y])) {
                $completeRoiData[] = $roiMap[$y];
            } else {
                $completeRoiData[] = [
                    'year' => $y,
                    'cumulative' => 0,
                ];
            }
        }
    }

    // Dynamic Y-axis (Lakhs) + dynamic scaling
    // Build a "nice" scale with 7 ticks (max..0) based on actual ROI data.
    $ticks = 7;

    $roiMaxFromData = 0;
    foreach ($completeRoiData as $d) {
        $roiMaxFromData = max($roiMaxFromData, (float) ($d['cumulative'] ?? 0));
    }

    $maxFromData = max($roiMaxFromData, (float) ($maxChartRoi ?? 0), 0);

    // Choose a "nice" step (1/2/5 × 10^n)
    $rawMax = max(1, $maxFromData);
    $rawStep = $rawMax / ($ticks - 1);
    $pow10 = pow(10, floor(log10($rawStep)));
    $norm = $rawStep / $pow10;
    if ($norm <= 1) {
        $niceNorm = 1;
    } elseif ($norm <= 2) {
        $niceNorm = 2;
    } elseif ($norm <= 5) {
        $niceNorm = 5;
    } else {
        $niceNorm = 10;
    }
    $step = $niceNorm * $pow10;
    $niceMax = $step * ($ticks - 1);

    // If everything is 0, fall back to estimates-table min/max instead of fixed 30L.
    // (If estimates are also empty, then use the old 30L default.)

    if ($maxFromData <= 0) {
        $fallbackMax = (float) ($estimateAmountMax ?? 0);
        if ($fallbackMax > 0) {
            $rawMax = max(1, $fallbackMax);
            $rawStep = $rawMax / ($ticks - 1);
            $pow10 = pow(10, floor(log10($rawStep)));
            $norm = $rawStep / $pow10;
            if ($norm <= 1) {
                $niceNorm = 1;
            } elseif ($norm <= 2) {
                $niceNorm = 2;
            } elseif ($norm <= 5) {
                $niceNorm = 5;
            } else {
                $niceNorm = 10;
            }
            $step = $niceNorm * $pow10;
            $niceMax = $step * ($ticks - 1);
        } else {
            $step = 500000;    // 5L
            $niceMax = 3000000; // 30L
        }
    }

    $yAxis = [];
    for ($i = 0; $i < $ticks; $i++) {
        $val = $niceMax - ($step * $i);
        if ($val <= 0) {
            $yAxis[] = $rupeeHtml . '0';
            continue;
        }
        $yAxis[] = $rupeeHtml . number_format($val / 100000, 1) . 'L';
    }

    // Use axis max for bar scaling
    $maxValue = (float) $niceMax;

    // Keep axis labels within chart height (Dompdf can overflow otherwise)
    $rowHeight = $chartHeight / count($yAxis);
        ?>

            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:40px;">
                <tr>

                    <!-- CHART -->
                    <td valign="bottom">
                        <table width="100%" height="<?= $chartHeight ?>" cellpadding="0" cellspacing="0">
                            <tr>
                                <?php    foreach ($completeRoiData as $data):
        $cumulative = (float) ($data['cumulative'] ?? 0);
        if ($cumulative < 0) {
            $cumulative = 0;
        }
        $height = 0;
        if ($maxValue > 0 && $cumulative > 0) {
            $height = ($cumulative / $maxValue) * $chartHeight;
            // Minimum height only when there is real data
            if ($height > 0 && $height < 6) {
                $height = 6;
            }
        }
                            ?>
                                <td width="<?= round(100 / max(count($completeRoiData), 1), 2) ?>%" align="center"
                                    valign="bottom">
                                    <div style="height:<?= $height ?>px;
                                            width:20px;
                                            margin:0 auto;
                                            background:#ffffff;
                                            border-radius:10px 10px 0 0;"></div>
                                    <!-- Dompdf doesn't reliably support writing-mode; keep labels horizontal & compact -->
                                    <div
                                        style="font-size:7px; color:#e8f6f4; margin-top:4px; line-height:1; white-space:nowrap; font-family: 'Montserrat', sans-serif;">
                                        <?php        if ($useSimpleRoi): ?>
                                        Y<br><?= (int) ($data['year'] ?? 0) ?>
                                        <?php        else: ?>
                                        <?= (int) ($data['year'] ?? 0) ?>
                                        <?php        endif; ?>
                                    </div>
                                </td>
                                <?php    endforeach; ?>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT Y AXIS -->
                    <td width="65" valign="top">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <?php    foreach ($yAxis as $label): ?>
                            <tr>
                                <td style="height:<?= $rowHeight ?>px;
                                        font-size:10px;
                                        color:#e8f6f4;
                                        text-align:right;
                                        padding-right:6px;
                                        border-bottom:1px solid rgba(255,255,255,0.15);
                                        font-family: 'Montserrat', sans-serif;">
                                    <?= $label ?>
                                </td>
                            </tr>
                            <?php    endforeach; ?>
                        </table>
                    </td>

                </tr>
            </table>

            <!-- ================= SUMMARY BOXES (like screenshot) ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
                <tr>
                    <td width="33.33%" align="center">
                        <table width="92%" cellpadding="0" cellspacing="0"
                            style="border:1px solid rgba(255,255,255,0.6);">
                            <tr>
                                <td align="center" style="padding:16px 10px;">
                                    <div
                                        style="font-size:20px; font-weight:700; color:#fff; font-family: 'Montserrat', sans-serif;">
                                        <?= $rupeeHtml ?><?= $yearlySavingsFormatted ?>
                                    </div>
                                    <div
                                        style="font-size:11px; font-weight:600; color:#e8f6f4; margin-top:6px; letter-spacing:0.5px; font-family: 'Montserrat', sans-serif;">
                                        YEARLY SAVINGS
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="33.33%" align="center">
                        <table width="92%" cellpadding="0" cellspacing="0"
                            style="border:1px solid rgba(255,255,255,0.6);">
                            <tr>
                                <td align="center" style="padding:16px 10px;">
                                    <div
                                        style="font-size:20px; font-weight:700; color:#fff; font-family: 'Montserrat', sans-serif;">
                                        <?= esc($paybackPeriodDisplay) ?> Years
                                    </div>
                                    <div
                                        style="font-size:11px; font-weight:600; color:#e8f6f4; margin-top:6px; letter-spacing:0.5px; font-family: 'Montserrat', sans-serif;">
                                        PAYBACK PERIOD
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="33.33%" align="center">
                        <table width="92%" cellpadding="0" cellspacing="0"
                            style="border:1px solid rgba(255,255,255,0.6);">
                            <tr>
                                <td align="center" style="padding:16px 10px;">
                                    <div
                                        style="font-size:20px; font-weight:700; color:#fff; font-family: 'Montserrat', sans-serif;">
                                        <?= $rupeeHtml ?><?= $totalLifetimeSavingsLakhsFormatted ?>
                                    </div>
                                    <div
                                        style="font-size:11px; font-weight:600; color:#e8f6f4; margin-top:6px; letter-spacing:0.5px; font-family: 'Montserrat', sans-serif;">
                                        TOTAL LIFETIME SAVING
                                    </div>
                                    <?php    //if ($useSimpleRoi): ?>
                                    <!-- <div style="font-size:9px; color:#e8f6f4; margin-top:6px; font-family:'Montserrat', sans-serif;">
                                        Net Profit: <?= $rupeeHtml ?><?=  $netLifetimeProfitLakhsFormatted ?>
                                    </div> -->
                                    <?php    //endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- ================= FINANCING ================= -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <div
                            style="font-size:16px; color:#e8f6f4; margin-bottom:10px; font-family: 'Montserrat', sans-serif;">
                            <?= esc($roiNote) ?>
                        </div>
                        <?php if ($roiStarts !== ''): ?>
                        <div style="background:#fff; color:#4b9349;
                                padding:10px 30px;
                                display:inline-block;                                font-size:13px;
                                font-size:18px;
                                font-family: 'Montserrat', sans-serif;">
                            Residential starts at <?= esc($roiStarts) ?> % only
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <!-- ================= FOOTER ================= -->
        <div style="position:fixed; bottom:10; left:0; right:0;
                    background:#4b9349; color:#fff; height:40px; border-top: 1px solid #fff;">
            <table width="100%" height="36" cellpadding="0" cellspacing="0" style="font-size:11px; color:#fff;">
                <tr>
                    <td width="33.33%" style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px;">
                        <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                    </td>

                    <td width="33.33%" align="center"
                        style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px;">
                        PAGE 4
                    </td>

                    <td width="33.33%" align="right"
                        style="padding:10px; font-family: 'Montserrat', sans-serif; font-size:16px; white-space:nowrap;">
                        <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                                ?>
                        Generated by <?= esc($companyName) ?>
                    </td>
                </tr>
            </table>
        </div>

    </div>
    <!-- ================= END PAGE 4 ================= -->
    <?php endif; ?>
    <!-- ================= PAGE 5 : TIMELINE & SYSTEM SPECIFICATION ================= -->
    <?php
$__timeLine = (isset($timeLine) && is_array($timeLine)) ? $timeLine : [];
$__timeLineActive = $_isActive($__timeLine);
?>
    <?php if ($__timeLineActive): ?>
    <div class="<?= $_pageClass('p5') ?>" style="position: relative;
            min-height: 842px;
            background: #ffffff;
           
            font-family: 'Montserrat', sans-serif;">
        <div style="padding: 50px;">
            <!-- ================= HEADER ================= -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size:20px;font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>

                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" style="max-width:160px; margin-bottom:5px;">
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>

            <?php
    // Template-specific timeline (saved in pdfbuilder_forms.time_line JSON)
    $timeLine = isset($timeLine) && is_array($timeLine) ? $timeLine : [];
    $timelineMainTitle = trim((string) ($timeLine['main_title'] ?? ''));
    $timelineTitle = trim((string) ($timeLine['title'] ?? ''));
    $timelineTitle2 = trim((string) ($timeLine['title2'] ?? ''));
    $timelineNote = trim((string) ($timeLine['note'] ?? ''));

    $timelineImg1 = !empty($timeLine['image1']) ? normalize_pdf_image($timeLine['image1']) : '';
    $timelineImg2 = !empty($timeLine['image2']) ? normalize_pdf_image($timeLine['image2']) : '';
    ?>
            <!-- ================= TIMELINE ================= -->
            <div
                style="font-size:35px; font-weight:bold; color:#000; margin-bottom:5px; font-family: 'Montserrat', sans-serif;">
                <?= $timelineMainTitle !== '' ? esc($timelineMainTitle) : 'TIMELINE' ?>
            </div>
            <div style="font-size:20px; font-weight:bold; margin-bottom:28px; font-family: 'Montserrat', sans-serif;">
                <?= $timelineTitle !== '' ? esc($timelineTitle) : 'TIMELINE AND MILESTONES' ?>
            </div>

            <?php if (!empty($timelineImg1)): ?>
            <img src="<?= $timelineImg1 ?>" style="width:100%; max-width:650px; display:block; margin:0 auto 40px;">
            <?php else: ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 auto 40px; border-collapse:collapse;">
                <tr>
                    <td width="25%" align="center" style="padding:10px;">
                        <div style="border:3px solid #4b9349; color:#4b9349; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">1</div>
                        <div style="font-size:15px; font-weight:bold;">10%</div>
                        <div style="font-size:14px;">Advance</div>
                    </td>
                    <td width="25%" align="center" style="padding:10px;">
                        <div style="border:3px solid #000; color:#000; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">2</div>
                        <div style="font-size:15px; font-weight:bold;">60%</div>
                        <div style="font-size:14px;">Procurement</div>
                    </td>
                    <td width="25%" align="center" style="padding:10px;">
                        <div style="border:3px solid #4b9349; color:#4b9349; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">3</div>
                        <div style="font-size:15px; font-weight:bold;">20%</div>
                        <div style="font-size:14px;">Installation</div>
                    </td>
                    <td width="25%" align="center" style="padding:10px;">
                        <div style="border:3px solid #000; color:#000; border-radius:50%; width:55px; height:55px; line-height:55px; font-size:24px; font-weight:bold; margin:0 auto 8px;">4</div>
                        <div style="font-size:15px; font-weight:bold;">10%</div>
                        <div style="font-size:14px;">Net metering</div>
                    </td>
                </tr>
            </table>
            <?php endif; ?>

            <!-- ================= SYSTEM SPECIFICATION ================= -->
            <div style="font-size:20px; font-weight:bold; margin-bottom:18px; font-family: 'Montserrat', sans-serif;">
                <?= $timelineTitle2 !== '' ? esc($timelineTitle2) : 'SYSTEM SPECIFICATION' ?>
            </div>

            <?php if (!empty($timelineImg2)): ?>
            <img src="<?= $timelineImg2 ?>" style="width:100%; max-width:650px; display:block; margin:0 auto 20px;">
            <?php else: ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:0 auto 20px;">
                <tr>
                    <td style="background:#4b9349; color:#fff; padding:9px; border:1px solid #333; font-weight:bold;">System Specification</td>
                    <td style="background:#4b9349; color:#fff; padding:9px; border:1px solid #333; font-weight:bold;">Details</td>
                </tr>
                <tr>
                    <td style="padding:9px; border:1px solid #333; font-weight:bold;">System Capacity</td>
                    <td style="padding:9px; border:1px solid #333;"><?= esc($quantity) ?> kW</td>
                </tr>
                <tr>
                    <td style="padding:9px; border:1px solid #333; font-weight:bold;">System Type</td>
                    <td style="padding:9px; border:1px solid #333;">Ongrid</td>
                </tr>
                <tr>
                    <td style="padding:9px; border:1px solid #333; font-weight:bold;">Application</td>
                    <td style="padding:9px; border:1px solid #333;"><?= esc(ucfirst((string) ($estdata->type ?? 'Residential'))) ?></td>
                </tr>
            </table>
            <?php endif; ?>

            <!-- ================= NOTE ================= -->
            <div style="text-align:center; font-size:16px; font-family: 'Montserrat', sans-serif;">
                <?= $timelineNote !== '' ? esc($timelineNote) : "Net metering is entirely dependent on DISCOM, we don't control that process" ?>
            </div>

        </div>
        <!-- ================= FOOTER ================= -->
        <table width="100%" cellpadding="0" cellspacing="0" style="position:fixed; bottom:10; left:0; right:0;
                        background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE 5
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                            ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>

        <!-- </div> -->
    </div>
    <?php endif; ?>
    <!-- ================= FIXED FOOTER (DOMPDF SAFE) ================= -->



    <!-- ================= END PAGE 5 ================= -->

    <?php
$__components = (isset($components) && is_array($components)) ? $components : [];
$__componentsActive = $_isActive($__components);
?>
    <?php if ($__componentsActive): ?>
    <!-- ================= PAGE 6 : SOLAR COMPONENTS ================= -->
    <div class="<?= $_pageClass('p6') ?>" style="position: relative; min-height: 842px; background: white;">
        <!-- Header -->
        <div style="padding: 50px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <div style="display: inline-block; text-align: right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width: 160px; height: auto; margin-bottom: 5px;">
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>

            @include('pdfbuilder.partials.estimate-invoice-summary')

            <div style="page-break-before: always; height: 1px;"></div>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 50px; margin-bottom: 34px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <div style="display: inline-block; text-align: right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width: 160px; height: auto; margin-bottom: 5px;">
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Main Title (Template-driven) -->
            <?php
    $components = isset($components) && is_array($components) ? $components : [];
    $componentsActive = (int) ($components['active'] ?? 1);
    $componentsTitle = trim((string) ($components['title'] ?? ''));
    $componentsDescRaw = (string) ($components['description'] ?? '');
    // CKEditor stores HTML; allow basic tags
    $componentsDescRaw = preg_replace("/\\R+/", ' ', $componentsDescRaw);
    $componentsDesc = trim(strip_tags($componentsDescRaw, '<p><br><b><strong><i><em><u>'));
        ?>
            <div style="page-break-inside: avoid; margin-top: 26px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
                <tr>
                    <td align="left">
                        <div
                            style="font-size: 34px; font-weight: bold; margin-bottom: 8px; line-height:1.1; font-family: 'Montserrat', sans-serif; color:#000; border-left:6px solid #4b9349; padding-left:14px;">
                            <?= esc($componentsTitle !== '' ? $componentsTitle : 'SOLAR COMPONENTS') ?>
                        </div>
                        <div style="font-size: 14px; line-height: 1.45; font-family: 'Montserrat', sans-serif; color:#333; background:#f7fbf7; border:1px solid #dfe9df; padding:10px 12px;">
                            <?php    if ($componentsActive === 1 && $componentsDesc !== ''): ?>
                            <?= $componentsDesc ?>
                            <?php    else: ?>
                            <b>High-quality</b> components from trusted <b>Tier-1</b> OEMs, selected for performance,
                            safety, and long-term ROI.
                            <?php    endif; ?>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Components Table -->
            <?php
    // Load product data, technology map, warranty map, and categories
    $product_data = [];
    $technology_map = [];
    $warranty_map = [];
    $categories_data = [];
    $category_image_map = []; // Map category name to image

    try {
        $product_data = \App\Models\BomProduct::with('categories')->get()->toArray();

        $technologyList = \App\Models\Technology::all();
        foreach ($technologyList as $tech) {
            $technology_map[$tech->id] = $tech->title;
        }

        $warrantyList = \App\Models\Warranty::all();
        foreach ($warrantyList as $war) {
            $warranty_map[$war->id] = $war->title;
        }

        // Load categories to get brand images
        $categories_data = \App\Models\Category::all();
        foreach ($categories_data as $cat) {
            if (!empty($cat->name) && !empty($cat->image)) {
                $category_image_map[$cat->name] = $cat->image;
            }
        }
    } catch (\Throwable $e) {
        // If models don't exist, continue with empty arrays
    }

    // Get product data from estimate
    $componentsData = [];
    if ($estdata && !empty($estdata->product_name)) {
        $allproduct = $estdata->product_name;
        while (!is_array($allproduct) && !empty($allproduct) && is_string($allproduct)) {
            $decoded = json_decode($allproduct, true);
            if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) {
                break;
            }
            $allproduct = $decoded;
        }

        if (is_array($allproduct) && !empty($allproduct)) {
            foreach ($allproduct as $item) {
                $product_id = $item['product_id'] ?? null;
                $product_name_display = $item['name'] ?? '';
                $product_category_makes = $item['category_name'] ?? '';
                $product_description = $item['description'] ?? '';

                // Find product details from master list
                $full_product_details = null;
                if ($product_id) {
                    foreach ($product_data as $prod_detail) {
                        $prod_detail_arr = (array) $prod_detail;
                        if (isset($prod_detail_arr['id']) && $prod_detail_arr['id'] == $product_id) {
                            $full_product_details = $prod_detail_arr;
                            break;
                        }
                    }
                }

                // Robust product name fallback
                if (empty(trim($product_name_display)) && $full_product_details) {
                    $product_name_display = $full_product_details['product_name'] ?? '';
                }
                if (empty(trim($product_name_display))) {
                    $product_name_display = 'Product name not found';
                }
                $product_name_display = ucfirst(strtolower($product_name_display));

                // Robust Make (category) fallback
                if (empty(trim($product_category_makes)) && $full_product_details && !empty($full_product_details['categories'])) {
                    $firstCat = reset($full_product_details['categories']);
                    $product_category_makes = $firstCat['name'] ?? '';
                }

                // Build specifications rows as: [Label, Value]
                $specifications = [];

                // Capacity
                if ($full_product_details && !empty($full_product_details['capacity'])) {
                    $specifications[] = ['Capacity', htmlspecialchars((string) $full_product_details['capacity'])];
                }

                // Watt Peak
                if ($full_product_details && !empty($full_product_details['watt_peak'])) {
                    $specifications[] = ['Watt Peak', htmlspecialchars((string) $full_product_details['watt_peak'])];
                }

                // Technology with fallback to legacy JSON and ID lookups
                $techVal = null;
                if ($full_product_details && !empty($full_product_details['technology_id'])) {
                    $techVal = $technology_map[$full_product_details['technology_id']] ?? null;
                } elseif ($full_product_details && !empty($full_product_details['technology'])) {
                    $techArray = json_decode($full_product_details['technology'], true);
                    if (!is_array($techArray)) {
                        $techArray = [$full_product_details['technology']];
                    }
                    $techArray = array_filter($techArray, fn($v) => trim((string) $v) !== '');
                    if (!empty($techArray)) {
                        $techNames = array_map(fn($id) => $technology_map[$id] ?? $id, $techArray);
                        $techVal = implode(', ', $techNames);
                    }
                }
                if ($techVal) {
                    $specifications[] = ['Type', htmlspecialchars($techVal)];
                }

                // Warranty with fallback to legacy JSON and ID lookups
                $warVal = null;
                if ($full_product_details && !empty($full_product_details['warranty_id'])) {
                    $warVal = $warranty_map[$full_product_details['warranty_id']] ?? null;
                } elseif ($full_product_details && !empty($full_product_details['warranty'])) {
                    $warArray = json_decode($full_product_details['warranty'], true);
                    if (!is_array($warArray)) {
                        $warArray = [$full_product_details['warranty']];
                    }
                    $warArray = array_filter($warArray, fn($v) => trim((string) $v) !== '');
                    if (!empty($warArray)) {
                        $warNames = array_map(fn($id) => $warranty_map[$id] ?? $id, $warArray);
                        $warVal = implode(', ', $warNames);
                    }
                }
                if ($warVal) {
                    $specifications[] = ['Warranty', htmlspecialchars($warVal)];
                }

                // Height
                if ($full_product_details && !empty($full_product_details['height'])) {
                    $specifications[] = ['Height', htmlspecialchars((string) $full_product_details['height'])];
                }

                // Thickness
                if ($full_product_details && !empty($full_product_details['thickness'])) {
                    $specifications[] = ['Thickness', htmlspecialchars((string) $full_product_details['thickness'])];
                }

                // Fitting Material
                if ($full_product_details && !empty($full_product_details['fitting_material'])) {
                    $specifications[] = ['Fitting Material', htmlspecialchars((string) $full_product_details['fitting_material'])];
                }

                // Fitting Type
                if ($full_product_details && !empty($full_product_details['fitting_type'])) {
                    $specifications[] = ['Fitting Type', htmlspecialchars((string) $full_product_details['fitting_type'])];
                }

                // Size of Pipe
                if ($full_product_details && !empty($full_product_details['size_of_pipe'])) {
                    $specifications[] = ['Size of Pipe', htmlspecialchars((string) $full_product_details['size_of_pipe'])];
                }

                // Get product image
                $product_image = $full_product_details['image'] ?? $full_product_details['product_image'] ?? $full_product_details['photo'] ?? null;

                // Get category/brand image
                $category_image = null;
                if (!empty($product_category_makes) && isset($category_image_map[$product_category_makes])) {
                    $category_image = $category_image_map[$product_category_makes];
                }

                // Get description from product table
                $product_table_description = '';
                if ($full_product_details) {
                    if (isset($full_product_details['description'])) {
                        $desc_value = $full_product_details['description'];
                        if ($desc_value !== null && $desc_value !== '' && trim($desc_value) !== '') {
                            $product_table_description = trim($desc_value);
                        }
                    }
                }

                // Use product table description first, then fallback to estimate item description
                $final_description = '';
                if (!empty($product_table_description) && trim($product_table_description) !== '') {
                    $final_description = $product_table_description;
                } elseif (!empty($product_description) && trim($product_description) !== '') {
                    $final_description = trim($product_description);
                }

                // Store ALL products
                $uniqueKey = $product_id . '_' . $product_name_display;
                $componentsData[$uniqueKey] = [
                    'name' => $product_name_display,
                    'category' => $product_category_makes, // Make = Brand
                    'category_image' => $category_image,
                    'description' => $final_description,
                    'specifications' => $specifications,
                    'image' => $product_image,
                    'quantity' => $item['quantity'] ?? 0
                ];
            }
        }
    }

    // Default components if no data
    if (empty($componentsData)) {
        $componentsData = [
            'Panel' => [
                'name' => 'Panel',
                'category' => 'Waaree Group',
                'description' => 'Bifacial High Wattage solar panels, with 25 years of warranty.',
                'specifications' => '<strong>Watt Peak:</strong> 585-615<br><strong>Type:</strong> Bifacial<br><strong>Warranty:</strong> 10 years (product), 25 years (performance)',
                'image' => null,
                'category_image' => null
            ],
            'Inverter' => [
                'name' => 'Inverter',
                'category' => 'Sungrow',
                'description' => 'Sungrow 15kw inverter, with all protection features, and anti-islanding support.',
                'specifications' => '<strong>Capacity:</strong> 15 KW design<br><strong>Type:</strong> On-Grid<br><strong>Warranty:</strong> 10 years',
                'image' => null,
                'category_image' => null
            ],
            'Cable' => [
                'name' => 'Cable',
                'category' => 'Polycab',
                'description' => 'Polycab U/V resistant, long lasting cables.',
                'specifications' => '<strong>Type:</strong> DC/AC Cables<br>double insulated cables<br><strong>Warranty:</strong> 30 years',
                'image' => null,
                'category_image' => null
            ],
            'Electrical Component' => [
                'name' => 'Electrical Component',
                'category' => 'True Power',
                'description' => 'TruePower electrical system.',
                'specifications' => 'MC4 Connectors<br>HDGI Earthing',
                'image' => null,
                'category_image' => null
            ],

        ];
    }
        ?>

            <!-- Two-column table like screenshot: Product Name + Specifications -->
            <table width="94%" align="center" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 12px auto 30px; border:1px solid #333;">
                <tr style="background-color:#4b9349; color:#fff;">
                    <td
                        style="padding: 9px 12px; font-weight: bold; font-size: 12px; border: 1px solid #333; font-family: 'Montserrat', sans-serif; width: 32%;">
                        Product Name
                    </td>
                    <td
                        style="padding: 9px 12px; font-weight: bold; font-size: 12px; border: 1px solid #333; font-family: 'Montserrat', sans-serif; width: 68%;">
                        Specifications
                    </td>
                </tr>

                <?php    foreach ($componentsData as $componentKey => $component):
        $specs = $component['specifications'] ?? [];
        $make = trim((string) ($component['category'] ?? ''));
        $qty = trim((string) ($component['quantity'] ?? ''));

        // Product image (best-effort). In this project images can be stored as:
        // - full URL
        // - relative path (uploads/... or public/...)
        // - only filename (stored in different folders)
        $productImage = $component['image'] ?? $component['product_image'] ?? $component['photo'] ?? null;
        $productImagePath = null;
        if (!empty($productImage)) {
            $productImage = trim((string) $productImage);
            if ($productImage !== '') {
                $resolved = normalize_pdf_image($productImage);
                // Only use if it resolved to an actual base64 image (file was found)
                if ($resolved && strpos($resolved, 'data:image') === 0) {
                    $productImagePath = $resolved;
                }
            }
        }

        // Build a mini table (Label | Value) inside the Specifications column
        $specRows = [];
        if ($make !== '')
            $specRows[] = ['Make', htmlspecialchars($make)];
        if ($qty !== '')
            $specRows[] = ['Quantity', htmlspecialchars($qty)];

        if (is_array($specs)) {
            foreach ($specs as $row) {
                if (!is_array($row) || count($row) < 2)
                    continue;
                $k = trim((string) ($row[0] ?? ''));
                $v = trim((string) ($row[1] ?? ''));
                if ($k === '' || $v === '')
                    continue;
                $specRows[] = [htmlspecialchars($k), $v]; // $v is already escaped above
            }
        } else {
            // Backward compatibility (if specs were stored as "<br>" string)
            $legacy = trim((string) $specs);
            if ($legacy !== '') {
                $legacy = strip_tags($legacy);
                $specRows[] = ['Specs', htmlspecialchars($legacy)];
            }
        }

        $specHtml = '--';
        if (!empty($specRows)) {
            // Compact layout: remove cellpadding + reduce line-height to avoid extra gaps
            $specHtml = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; line-height:1.15;">';
            foreach ($specRows as $r) {
                $k = (string) ($r[0] ?? '');
                $v = (string) ($r[1] ?? '');
                $specHtml .= '<tr>'
                    . '<td style="font-weight:bold; width:20%; padding:0 8px 2px 0; vertical-align:top;">' . $k . ':</td>'
                    . '<td style="width:80%; padding:0 0 2px 0; vertical-align:top;">' . $v . '</td>'
                    . '</tr>';
            }
            $specHtml .= '</table>';
        }
            ?>
                <tr style="page-break-inside: avoid;">
                    <td
                        style="padding: 14px 12px; font-size: 12px; border: 1px solid #333; font-family: 'Montserrat', sans-serif; vertical-align: middle; background:#fbfbfb;">
                        <!-- Center image + product name (Dompdf-safe) -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <?php        if (!empty($productImagePath)): ?>
                            <tr>
                                <td align="center" valign="middle" style="padding-bottom:6px;">
                                    <img src="<?= $productImagePath ?>"
                                        alt="<?= esc($component['name'] ?? 'Product') ?>"
                                        style="width:92px; height:92px; object-fit:contain; display:inline-block; border:1px solid #c8d8c8; padding:6px; background:#fff;">
                                </td>
                            </tr>
                            <?php        endif; ?>
                            <tr>
                                <td align="center" valign="middle"
                                    style="font-size:11px; font-family:'Montserrat', sans-serif; font-weight:700; color:#000;">
                                    <?= esc($component['name'] ?? '--') ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td
                        style="padding: 14px 16px; font-size: 12px; border: 1px solid #333; font-family: 'Montserrat', sans-serif; vertical-align: top; line-height:1.35;">
                        <?= $specHtml ?>
                    </td>
                </tr>
                <?php    endforeach; ?>
            </table>
            </div>
        </div>
        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0" style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE 6
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                        ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <!-- ================= END PAGE 6 ================= -->
    <?php endif; ?>

    <?php if ($__offerTermsActive0): ?>
    <!-- ================= PAGE 7 : OFFER & TERMS ================= -->
    <div class="<?= $_pageClass('p7') ?>" style="position: relative; min-height: 842px; background: white;">
        <!-- Header -->
        <div style="padding: 50px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <div style="display: inline-block; text-align: right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width: 160px; height: auto; margin-bottom: 5px;">
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Main Title -->
            <table width="100%" cellpadding="0" cellspacing="0" style="">
                <tr>
                    <td align="left">
                        <div style="font-size: 35px; font-weight: bold; font-family: 'Montserrat', sans-serif;">OFFER &
                            TERMS</div>
                    </td>
                </tr>
            </table>

            <?php
    // Calculate cost breakdown from estimate data
    // Use the estimate's main price as base (this already represents products total)
    $subtotal = ($estdata && isset($estdata->price)) ? (float) $estdata->price : 0;
    $gstRate = ($estdata && isset($estdata->gst)) ? (float) $estdata->gst : 0;
    $discount = ($estdata && isset($estdata->discount)) ? (float) $estdata->discount : 0;
    $subsidy = ($estdata && isset($estdata->subsidy_amount)) ? (float) $estdata->subsidy_amount : 0;
    $solarStructureCharges = ($estdata && isset($estdata->solar_structure_charges)) ? (float) $estdata->solar_structure_charges : 0;
    $isQuotation = ($estdata && isset($estdata->is_quotation)) ? (int) $estdata->is_quotation : 0;

    // GST Amount (prefer stored gst_amount / gst_breakdown, fallback to per-line JSON, then fallback to % calc)
    $gstAmount = null;
    $gstBreakdown = [];

    if ($estdata && isset($estdata->gst_amount) && $estdata->gst_amount !== null && $estdata->gst_amount !== '') {
        $gstAmount = (float) $estdata->gst_amount;
    }

    if ($estdata && !empty($estdata->gst_breakdown)) {
        $decoded = json_decode($estdata->gst_breakdown, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $gstBreakdown = $decoded;
            if ($gstAmount === null && isset($decoded['gst_amount'])) {
                $gstAmount = (float) $decoded['gst_amount'];
            }
        }
    }

    if ($gstAmount === null && $estdata && !empty($estdata->product_name)) {
        $items = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
        if (is_array($items)) {
            $sum = 0.0;
            foreach ($items as $it) {
                if (isset($it['tax_amount']) && is_numeric($it['tax_amount'])) {
                    $sum += (float) $it['tax_amount'];
                }
            }
            if ($sum > 0) {
                $gstAmount = $sum;
            }
        }
    }

    // Calculate totals (excluding subsidy in intermediate totals)
    if ($gstAmount === null) {
        $gstAmount = ($subtotal + $solarStructureCharges) * ($gstRate / 100);
    }
    $totalPayable = $subtotal + $solarStructureCharges + $gstAmount - $discount; // Base Price + Solar Structure Charges + GST - Discount
    $lendingCost = $totalPayable - $subsidy; // Customer Payable Amount minus Subsidy
        ?>

            <?php
    $headerCellStyle = "background-color:#52866A;color:#fff;border:1px solid #333;padding:6px 8px;font-size:11px;font-family:'Montserrat',sans-serif;font-weight:bold;";
    $cellStyle = "border:1px solid #333;padding:6px 8px;font-size:11px;font-family:'Montserrat',sans-serif;color:#000;";
    $rightCellStyle = $cellStyle . 'text-align:right;';
    $highlightCellStyle = $rightCellStyle . 'background-color:#52866A;color:#fff;font-weight:bold;';
    $invoiceHeaderTextStyle = "font-size:12px;font-family:'Montserrat',sans-serif;color:#000;";
    $invoiceEstimateNo = $estdata->estimate_no ?? ($estimate_no ?? '--');
    $invoiceDate = ($estdata && !empty($estdata->estimate_date)) ? date('Y-m-d', strtotime($estdata->estimate_date)) : date('Y-m-d');
    $invoiceCompanyName = $companySettings['company_name'] ?? $globalCompanyName ?? '--';
    $invoiceCompanyAddress = $companySettings['company_address'] ?? $user['address'] ?? '';
    $invoiceCompanyPhone = $companySettings['phone'] ?? $user['phone'] ?? $user['contact'] ?? '';
    $estimateTypeLabel = $estdata && isset($estdata->type) ? ucfirst((string) $estdata->type) : '--';
    $solarMeterLabel = ($estdata && !empty($estdata->solar_meter_charges)) ? ucwords(str_replace('_', ' ', (string) $estdata->solar_meter_charges)) : '--';
    $estimateComment = $estdata && isset($estdata->estimate_comment) ? $estdata->estimate_comment : ($estdata->comment ?? '--');
    $bankLines = array_filter([
        !empty($companySettings['bank_name']) ? '<strong>Bank:</strong> ' . esc($companySettings['bank_name']) : '',
        !empty($companySettings['account_name']) ? '<strong>Account Name:</strong> ' . esc($companySettings['account_name']) : '',
        !empty($companySettings['account_number']) ? '<strong>Account No.:</strong> ' . esc($companySettings['account_number']) : '',
        !empty($companySettings['ifsc_code']) ? '<strong>IFSC:</strong> ' . esc($companySettings['ifsc_code']) : '',
        !empty($companySettings['branch_name']) ? '<strong>Branch:</strong> ' . esc($companySettings['branch_name']) : '',
    ]);
    $qrImage = !empty($companyQrCodePath) ? normalize_pdf_image($companyQrCodePath) : '';
    $gstRateTxt = is_numeric($gstRate) ? rtrim(rtrim(number_format((float) $gstRate, 2, '.', ''), '0'), '.') : '';
    $showGst = ((float) $gstAmount > 0) || ((float) $gstRate > 0);
    $breakupLines = [];

    if (!empty($gstBreakdown['groups']) && is_array($gstBreakdown['groups'])) {
        foreach ($gstBreakdown['groups'] as $g) {
            if ((string) ($g['tax_type'] ?? '') === 'gst_percent') {
                continue;
            }
            $lines = $g['lines'] ?? [];
            if (!is_array($lines)) {
                continue;
            }
            foreach ($lines as $ln) {
                $lnLabel = trim((string) ($ln['label'] ?? ''));
                if ($lnLabel === '' || strtoupper($lnLabel) === 'GST') {
                    continue;
                }
                $breakupLines[] = [
                    'label' => $lnLabel,
                    'rate' => $ln['rate'] ?? null,
                    'amount' => (float) ($ln['amount'] ?? 0),
                ];
            }
        }
    }

    if (empty($breakupLines) && $estdata && !empty($estdata->product_name)) {
        $items = is_array($estdata->product_name) ? $estdata->product_name : json_decode($estdata->product_name, true);
        if (is_array($items)) {
            $taxBuckets = [
                'CGST' => ['amount' => 0.0, 'rate' => null],
                'SGST' => ['amount' => 0.0, 'rate' => null],
                'IGST' => ['amount' => 0.0, 'rate' => null],
            ];
            foreach ($items as $it) {
                foreach (['cgst' => 'CGST', 'sgst' => 'SGST', 'igst' => 'IGST'] as $key => $label) {
                    if (isset($it[$key . '_amount']) && is_numeric($it[$key . '_amount'])) {
                        $taxBuckets[$label]['amount'] += (float) $it[$key . '_amount'];
                        if ($taxBuckets[$label]['rate'] === null && isset($it[$key . '_rate']) && is_numeric($it[$key . '_rate'])) {
                            $taxBuckets[$label]['rate'] = (float) $it[$key . '_rate'];
                        }
                    }
                }
            }
            foreach ($taxBuckets as $label => $bucket) {
                if ($bucket['amount'] > 0) {
                    $breakupLines[] = ['label' => $label, 'rate' => $bucket['rate'], 'amount' => $bucket['amount']];
                }
            }
        }
    }
            ?>

            <?php if (false): ?>
            <!-- Invoice-style estimate header -->
            <table width="96%" align="center" cellpadding="0" cellspacing="0" style="margin-top:12px;margin-bottom:10px;border-collapse:collapse;">
                <tr>
                    <td width="45%" valign="top" align="left" style="<?= $invoiceHeaderTextStyle ?>padding-bottom:12px;">
                        <?php if (!empty($logoBase64)): ?>
                            <img src="<?= $logoBase64 ?>" alt="Company Logo" style="max-width:160px;height:auto;">
                        <?php else: ?>
                            <span style="color:#666;">Company Logo</span>
                        <?php endif; ?>
                    </td>
                    <td width="55%" valign="top" align="right" style="<?= $invoiceHeaderTextStyle ?>line-height:1.45;padding-bottom:12px;">
                        <strong style="font-size:14px;"><?= esc($invoiceCompanyName) ?></strong><br>
                        <?= esc($invoiceCompanyAddress ?: '--') ?><br>
                        <?= esc($invoiceCompanyPhone ?: '--') ?><br>
                        <span style="color:#52866A;font-weight:bold;">Google Location Map</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border-top:1px solid #e5e5e5;padding-top:16px;"></td>
                </tr>
            </table>

            <table width="96%" align="center" cellpadding="0" cellspacing="0" style="margin-bottom:14px;border-collapse:collapse;">
                <tr>
                    <td width="33%" align="left" style="<?= $invoiceHeaderTextStyle ?>font-weight:bold;">Estimate no.: #<?= esc($invoiceEstimateNo) ?></td>
                    <td width="34%" align="center" style="<?= $invoiceHeaderTextStyle ?>font-weight:bold;text-decoration:underline;">ESTIMATION</td>
                    <td width="33%" align="right" style="<?= $invoiceHeaderTextStyle ?>font-weight:bold;">Date: <?= esc($invoiceDate) ?></td>
                </tr>
            </table>

            <!-- Invoice-style estimate table -->
            <table width="96%" align="center" cellpadding="0" cellspacing="0" style="margin-top:8px;border-collapse:collapse;">
                <tr>
                    <td colspan="4" style="<?= $headerCellStyle ?>">Customer Details</td>
                </tr>
                <tr>
                    <td style="<?= $cellStyle ?>"><strong>Customer Name</strong></td>
                    <td style="<?= $cellStyle ?>"><?= esc($estdata->name ?? '--') ?></td>
                    <td style="<?= $cellStyle ?>"><strong>Email</strong></td>
                    <td style="<?= $cellStyle ?>"><?= esc($estdata->email ?? '--') ?></td>
                </tr>
                <tr>
                    <td style="<?= $cellStyle ?>"><strong>Address</strong></td>
                    <td style="<?= $cellStyle ?>"><?= esc($estdata->address ?? '--') ?></td>
                    <td style="<?= $cellStyle ?>"><strong>Contact</strong></td>
                    <td style="<?= $cellStyle ?>"><?= esc($estdata->phone ?? '--') ?></td>
                </tr>
            </table>

            <table width="96%" align="center" cellpadding="0" cellspacing="0" style="margin-top:10px;border-collapse:collapse;">
                <tr>
                    <td style="<?= $headerCellStyle ?>">Estimate Name</td>
                    <td style="<?= $headerCellStyle ?>">Quantity (kW)</td>
                    <td style="<?= $headerCellStyle ?>">Price</td>
                </tr>
                <tr>
                    <td style="<?= $cellStyle ?>"><?= esc($estdata->estimate_name ?? '--') ?></td>
                    <td style="<?= $cellStyle ?>"><?= esc($quantity) ?></td>
                    <td style="<?= $rightCellStyle ?>"><?= number_format((float) ($estdata->price ?? 0), 2) ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>"><strong>Base Price</strong></td>
                    <td style="<?= $rightCellStyle ?>"><strong><?= number_format($subtotal, 2) ?></strong></td>
                </tr>
                <?php if ($solarStructureCharges > 0): ?>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>">Solar Structure Charges</td>
                    <td style="<?= $rightCellStyle ?>"><?= number_format($solarStructureCharges, 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($isQuotation === 1 && $showGst): ?>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>">GST<?= $gstRateTxt !== '' ? ' (' . esc($gstRateTxt) . '%)' : '' ?></td>
                    <td style="<?= $rightCellStyle ?>"><?= number_format((float) $gstAmount, 2) ?></td>
                </tr>
                <?php elseif ($showGst && !empty($breakupLines)): ?>
                    <?php foreach ($breakupLines as $ln): ?>
                        <?php
            $lnLabel = trim((string) ($ln['label'] ?? ''));
            $lnRate = $ln['rate'] ?? null;
            $lnAmt = (float) ($ln['amount'] ?? 0);
            if ($lnLabel === '' || $lnAmt <= 0) {
                continue;
            }
            $rateTxt = is_numeric($lnRate) ? rtrim(rtrim(number_format((float) $lnRate, 2, '.', ''), '0'), '.') : '';
                        ?>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>"><?= esc($lnLabel) ?><?= $rateTxt !== '' ? ' (' . esc($rateTxt) . '%)' : '' ?></td>
                    <td style="<?= $rightCellStyle ?>"><?= number_format($lnAmt, 2) ?></td>
                </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($discount > 0): ?>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>">Discount</td>
                    <td style="<?= $rightCellStyle ?>">-<?= number_format($discount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>"><strong>Customer Payable Amount</strong></td>
                    <td style="<?= $highlightCellStyle ?>"><?= number_format($totalPayable, 2) ?></td>
                </tr>
                <?php if ($subsidy > 0): ?>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>">Subsidy</td>
                    <td style="<?= $rightCellStyle ?>">-<?= number_format($subsidy, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="<?= $rightCellStyle ?>"><strong>Lending Cost Of Customer</strong></td>
                    <td style="<?= $highlightCellStyle ?>"><?= number_format($lendingCost, 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <?php if ($subsidy > 0): ?>
            <div style="width:96%;margin:8px auto 0;font-size:10px;font-family:'Montserrat',sans-serif;color:#000;">
                <strong>Note:</strong> Subsidy Amount to be credited in clients account.
            </div>
            <?php endif; ?>

            <table width="96%" align="center" cellpadding="0" cellspacing="0" style="margin-top:12px;border-collapse:collapse;">
                <tr>
                    <td style="<?= $headerCellStyle ?>width:38%;">System Capacity</td>
                    <td style="<?= $cellStyle ?>"><?= esc($quantity) ?> kW</td>
                </tr>
                <tr>
                    <td style="<?= $headerCellStyle ?>">Estimate Type</td>
                    <td style="<?= $cellStyle ?>"><?= esc($estimateTypeLabel) ?></td>
                </tr>
                <tr>
                    <td style="<?= $headerCellStyle ?>">Solar Meter Charges</td>
                    <td style="<?= $cellStyle ?>"><?= esc($solarMeterLabel) ?></td>
                </tr>
            </table>

            <table width="96%" align="center" cellpadding="0" cellspacing="0" style="margin-top:12px;margin-bottom:12px;border-collapse:collapse;">
                <tr>
                    <td style="<?= $headerCellStyle ?>width:35%;">Comment</td>
                    <td style="<?= $headerCellStyle ?>width:40%;">Bank Details</td>
                    <td style="<?= $headerCellStyle ?>width:25%;">QR Code</td>
                </tr>
                <tr>
                    <td style="<?= $cellStyle ?>vertical-align:top;"><?= nl2br(esc($estimateComment ?: '--')) ?></td>
                    <td style="<?= $cellStyle ?>vertical-align:top;">
                        <?= !empty($bankLines) ? implode('<br>', $bankLines) : 'No bank details available.' ?>
                    </td>
                    <td style="<?= $cellStyle ?>vertical-align:top;text-align:center;">
                        <?php if (!empty($qrImage)): ?>
                            <img src="<?= $qrImage ?>" alt="QR Code" style="max-width:90px;max-height:90px;">
                        <?php else: ?>
                            No QR code available.
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php endif; ?>

            <!-- SCOPE Section (Template-driven) -->
            <?php
    $paymentTermsScope = trim((string) ($paymentTerms['scope'] ?? ''));
    $paymentTermsNote = trim((string) ($paymentTerms['note'] ?? ''));
    $services = $paymentTerms['services'] ?? [];
    if (!is_array($services))
        $services = [];

    // Normalize services into rows and remove empty rows
    $rows = [];
    foreach ($services as $s) {
        if (is_array($s)) {
            $rows[] = [
                'left' => trim((string) ($s['left'] ?? '')),
                'right' => trim((string) ($s['right'] ?? '')),
            ];
        } else {
            $rows[] = ['left' => trim((string) $s), 'right' => ''];
        }
    }
    $rows = array_values(array_filter($rows, static fn($r) => ($r['left'] ?? '') !== '' || ($r['right'] ?? '') !== ''));

    // Show SCOPE block only if something is actually saved
    $showScopeBlock = ($paymentTermsScope !== '') || ($paymentTermsNote !== '') || !empty($rows);
        ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="">
                <tr>
                    <td>
                        <?php    if ($paymentTermsScope !== ''): ?>
                        <div
                            style="font-size: 20px; font-weight: bold; margin-bottom: 12px; font-family: 'Montserrat', sans-serif;">
                            <?= $paymentTermsScope !== '' ? esc($paymentTermsScope) : 'SCOPE' ?>
                        </div>
                        <?php    endif; ?>
                        <!-- Scope Table -->
                        <?php    if (!empty($rows)): ?>
                        <table width="100%" cellpadding="0" cellspacing="0"
                            style="border-collapse: collapse; margin-bottom: 15px;">
                            <tr>
                                <td
                                    style="padding: 5px; border: 1px solid #ddd; font-weight: bold; background-color: #f5f5f5; width: 50%; font-family: 'Montserrat', sans-serif;">
                                    What we cover under our services
                                </td>
                                <td
                                    style="padding: 5px; border: 1px solid #ddd; font-weight: bold; background-color: #f5f5f5; width: 50%; font-family: 'Montserrat', sans-serif;">
                                    &nbsp;
                                </td>
                            </tr>

                            <?php        foreach ($rows as $r): ?>
                            <tr>
                                <td
                                    style="padding: 5px; border: 1px solid #ddd; color: #333; font-family: 'Montserrat', sans-serif;">
                                    <?= esc($r['left'] ?? '') ?>
                                </td>
                                <td
                                    style="padding: 5px; border: 1px solid #ddd; color: #333; font-family: 'Montserrat', sans-serif;">
                                    <?= esc($r['right'] ?? '') ?>
                                </td>
                            </tr>
                            <?php        endforeach; ?>
                        </table>
                        <?php    endif; ?>
                        <?php    if ($paymentTermsNote !== ''): ?>
                        <div
                            style="font-size: 14px; font-style: italic; margin-top: 10px; font-family: 'Montserrat', sans-serif;">
                            <?= esc($paymentTermsNote) ?>
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>


            <!-- PAYMENT TERMS Section -->
            <?php
    // Template-specific payment terms (saved in form_data.payment_terms)
    $paymentTerms = isset($paymentTerms) && is_array($paymentTerms) ? $paymentTerms : [];
    $paymentTermsActive = $_isActive($paymentTerms);
    $paymentTermsTitle = trim((string) ($paymentTerms['title'] ?? ''));
    $paymentTermsImg = !empty($paymentTerms['image'])
        ? normalize_pdf_image($paymentTerms['image'])
        : normalize_pdf_image('public/assets/img/page_7.png');
        ?>
            <?php    if ($paymentTermsActive): ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:10px;">
                <tr>
                    <td>
                        <div style="font-size: 20px; font-weight: bold; font-family: 'Montserrat', sans-serif;">
                            <?= $paymentTermsTitle !== '' ? esc($paymentTermsTitle) : 'PAYMENT TERMS' ?>
                        </div>

                        <!-- Payment Timeline Image -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                            <tr>
                                <td align="center" valign="middle">
                                    <img src="<?= $paymentTermsImg ?>" alt="Payment Terms Timeline"
                                        style="max-width: 91%; height: auto; display: block; margin: 0 auto;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <?php    endif; ?>
        </div>
        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0" style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE 7
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                        ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <!-- ================= END PAGE 7 ================= -->
    <?php endif; ?>

    <!-- ================= PAGE 8 : ENVIRONMENT IMPACT ================= -->
    <?php
$__environmentImpact = (isset($environmentImpact) && is_array($environmentImpact)) ? $environmentImpact : [];
$__environmentImpactActive = $_isActive($__environmentImpact);
    ?>
    <?php if ($__environmentImpactActive): ?>
    <div class="<?= $_pageClass('p8') ?>" style="position: relative; min-height: 842px; background: white;">
        <!-- Header -->
        <div style="padding: 50px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 50px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 20px; color: #000; font-family: 'Montserrat', sans-serif;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <div style="display: inline-block; text-align: right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width: 160px; height: auto; margin-bottom: 5px;">
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>

            <?php
    // Calculate environment impact metrics based on system capacity
    $systemCapacity = (float) $quantity;
    $lifetimeYears = 25; // Typical solar system lifetime

    // CO2 offset: ~1.01 metric tons per kW per year (average for India)
    $co2PerKwPerYear = 1.01;
    $totalCo2Offset = $systemCapacity * $co2PerKwPerYear * $lifetimeYears;

    // Equivalent acres of forest: ~1.187 acres per kW per year
    $acresPerKwPerYear = 1.187;
    $equivalentAcres = $systemCapacity * $acresPerKwPerYear;

    // Coal burn avoided: ~1.259 metric tons per kW per year
    $coalPerKwPerYear = 1.259;
    $coalAvoided = $systemCapacity * $coalPerKwPerYear;
        ?>
            <?php
    // Template-specific environment impact (saved in DB as JSON)
    $environmentImpact = isset($environmentImpact) && is_array($environmentImpact) ? $environmentImpact : [];
    $envTitle = trim((string) ($environmentImpact['title'] ?? ''));
    $envContent = trim((string) ($environmentImpact['content'] ?? ''));
    $envImg = !empty($environmentImpact['image'])
        ? normalize_pdf_image($environmentImpact['image'])
        : normalize_pdf_image('public/assets/img/page_8.png');
        ?>

            <!-- Main Title -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td align="left">
                        <div
                            style="font-size: 30px; font-weight: bold; margin-bottom: 20px; letter-spacing: 2px; font-family: 'Montserrat', sans-serif;">
                            <?= $envTitle !== '' ? esc($envTitle) : 'ENVIRONMENT IMPACT' ?>
                        </div>
                        <div style="font-size: 25px; color: #000; font-family: 'Montserrat', sans-serif;">
                            <?= $envContent !== '' ? $envContent : "You are contributing to solve earth's biggest problem- <b>Climate Change.</b>" ?>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Environment Impact Image -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 60px;">
                <tr>
                    <td align="left" valign="middle">
                        <img src="<?= $envImg ?>" alt="Environment Impact"
                            style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
                    </td>
                </tr>
            </table>
        </div>
        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0" style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE 8
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                        ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>
    <!-- ================= END PAGE 8 ================= -->

    <!-- ================= PAGE 9 : THANK YOU ================= -->
    <?php
$__footer = (isset($footer) && is_array($footer)) ? $footer : [];
$__footerActive = $_isActive($__footer);
    ?>
    <?php if ($__footerActive): ?>
    <div class="page" style="position: relative; min-height: 842px; background: white;">
        <!-- Header -->
        <div style="padding: 50px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td width="50%" align="left" valign="top">
                        <div style="font-size: 18px;">
                            <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php    if (!empty($logoBase64)): ?>
                        <div style="display: inline-block; text-align: right;">
                            <img src="<?= $logoBase64 ?>" alt="Company Logo"
                                style="max-width: 160px; height: auto; margin-bottom: 5px;">
                        </div>
                        <?php    endif; ?>
                    </td>
                </tr>
            </table>



            <!-- Solar Panel Image -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10px;">
                <tr>
                    <td align="center">
                        <?php
    // Template-specific footer (saved in DB as JSON)
    $footer = isset($footer) && is_array($footer) ? $footer : [];
    $footerActive = (int) ($footer['active'] ?? 1);
    $footerTitle = trim((string) ($footer['title'] ?? ''));
    $footerSubTitle = trim((string) ($footer['sub_title'] ?? ''));
    $footerImg = !empty($footer['image'])
        ? normalize_pdf_image($footer['image'])
        : normalize_pdf_image('public/assets/img/footer.png');
                    ?>
                        <img src="<?= $footerImg ?>" alt="Solar Panels"
                            style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
                    </td>
                </tr>
            </table>

            <!-- Thank You Section -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                <tr>
                    <td align="left"
                        style="font-size: 80px; color: #000; padding-bottom: 20px; letter-spacing: 3px; font-family: 'Montserrat', sans-serif;">
                        <?= $footerTitle !== '' ? esc($footerTitle) : 'THANK YOU' ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: -20px;">
                            <tr>
                                <!-- Text -->
                                <td align="left" style="font-size: 20px; font-family: 'Montserrat', sans-serif;">
                                    <?php
    $defaultSub = 'Looking forward to work and add value';
    echo esc(($footerActive === 1 && $footerSubTitle !== '') ? $footerSubTitle : $defaultSub);
                        ?>
                                </td>

                                <!-- Image -->
                                <td align="right">
                                    <img src="<?= normalize_pdf_image('public/assets/img/footer_arrow.png') ?>"
                                        alt="Arrow" width="80" height="55">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Contact Information Footer (Black Box) -->
            <?php
    $companySettings = $companySettings ?? [];
    $userPhone = $companySettings['phone'] ?? $user['phone'] ?? $user['whatsapp'] ?? $user['contact'] ?? $user['whatsapp_no'] ?? $user['contact_phone'] ?? '';
    $userEmail = $companySettings['email'] ?? $user['email'] ?? $user['contact_email'] ?? '';
    $userWebsite = $companySettings['company_name'] ?? $user['website'] ?? '';
    $userAddress = $companySettings['company_address'] ?? $user['address'] ?? '';
    $company_name = $companySettings['company_name'] ?? $user['company_name'] ?? '';
        ?>
            <table width="100%" cellpadding="0" cellspacing="0"
                style="background-color: #4b9349; color: #fff; padding: 20px; margin-top: 20px;">
                <tr>
                    <td width="50%" valign="top" style="padding-right: 20px;">
                        <div style="margin-bottom: 25px;">
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Phone:</div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <?= !empty($userPhone) ? esc($userPhone) : '--' ?>
                            </div>
                        </div>
                        <div>
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Email:</div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <?= !empty($userEmail) ? esc($userEmail) : '--' ?>
                            </div>
                        </div>
                    </td>
                    <td width="50%" valign="top" align="right" style="padding-left: 20px; text-align: right;">
                        <div style="margin-bottom: 25px;">
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Website:
                            </div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <a href="<?= base_url() ?>"
                                    style="color: #fff; text-decoration: none; font-family: 'Montserrat', sans-serif;"
                                    target="_blank">
                                    <?= !empty($company_name) ? esc($company_name) : '--' ?>
                                </a>
                            </div>
                        </div>

                        <div>
                            <div
                                style="font-size: 20px; font-weight: bold; color: #fff; margin-bottom: 8px; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                Address:
                            </div>
                            <div
                                style="font-size: 16px; color: #fff; font-family: 'Montserrat', sans-serif; line-height: 1.4;">
                                <?= !empty($userAddress) ? esc($userAddress) : '--' ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <!-- Footer -->
        <table width="100%" cellpadding="0" cellspacing="0" style="position:fixed; bottom:10; left:0; right:0;
                    background:#fff; color:#4b9349; height:40px; border-top: 1px solid #4b9349;">
            <tr>
                <td width="33.33%" style="padding:10px;">
                    <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
                </td>

                <td width="33.33%" align="center" style="padding:10px;">
                    PAGE 9
                </td>

                <td width="33.33%" align="right" style="padding:10px; white-space:nowrap;">
                    <?php
    $companyName = esc($globalCompanyName);
    $companyParts = explode(' ', $companyName);
    $mainName = $companyParts[0] ?? $companyName;
                        ?>
                    Generated by <?= esc($companyName) ?>
                </td>
            </tr>
        </table>

    </div>
    <!-- ================= END PAGE 9 ================= -->
    <?php endif; ?>

</body>

</html>