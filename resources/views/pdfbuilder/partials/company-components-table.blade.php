<div style="page-break-inside: avoid;">
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:10px 0 18px; border:1px solid #cfe0cf; font-family:'Montserrat', sans-serif; page-break-inside: avoid;">
    <thead>
        <tr style="background-color:#4b9349; border-bottom:2px solid #3d7a3b;">
            <th width="22%" style="padding:14px 16px; font-weight:bold; font-size:14px; color:#fff; text-align:left; font-family:'Montserrat',sans-serif; border: 1px solid #3d7a3b;">
                Component Type
            </th>
            <th width="25%" style="padding:14px 16px; font-weight:bold; font-size:14px; color:#fff; text-align:left; font-family:'Montserrat',sans-serif; border: 1px solid #3d7a3b;">
                Approved Brand / Make
            </th>
            <th width="35%" style="padding:14px 16px; font-weight:bold; font-size:14px; color:#fff; text-align:left; font-family:'Montserrat',sans-serif; border: 1px solid #3d7a3b;">
                Technical Specification
            </th>
            <th width="18%" style="padding:14px 16px; font-weight:bold; font-size:14px; color:#fff; text-align:left; font-family:'Montserrat',sans-serif; border: 1px solid #3d7a3b;">
                Warranty Terms
            </th>
        </tr>
    </thead>
    <tbody>
    <?php $componentRowIndex = 0; ?>
    <?php foreach ($componentsTableRows ?? $componentsData as $componentKey => $component):
        $specs = $component['specifications'] ?? [];
        $make = trim((string) ($component['category'] ?? ''));
        $qty = trim((string) ($component['quantity'] ?? ''));

        // Resolve product image
        $productImage = $component['image'] ?? $component['product_image'] ?? $component['photo'] ?? null;
        $productImagePath = null;
        if (!empty($productImage)) {
            $productImage = trim((string) $productImage);
            if ($productImage !== '') {
                $resolved = normalize_pdf_image($productImage);
                if ($resolved && strpos($resolved, 'data:image') === 0) {
                    $productImagePath = $resolved;
                }
            }
        }

        // Extract Tech Specs (anything except Make and Warranty) and Warranty
        $techSpecs = [];
        $warrantyValue = 'Standard OEM Warranty';

        if (!empty($component['description'])) {
            $techSpecs[] = '<strong>Description:</strong> ' . htmlspecialchars($component['description']);
        }

        if ($qty !== '') {
            $techSpecs[] = '<strong>Qty:</strong> ' . $qty;
        }

        if (is_array($specs)) {
            foreach ($specs as $row) {
                if (!is_array($row) || count($row) < 2) {
                    continue;
                }
                $k = trim((string) ($row[0] ?? ''));
                $v = trim((string) ($row[1] ?? ''));
                if ($k === '' || $v === '') {
                    continue;
                }
                if (strtolower($k) === 'make') {
                    if (empty($make)) {
                        $make = $v;
                    }
                } elseif (strtolower($k) === 'warranty') {
                    $warrantyValue = $v;
                } else {
                    $techSpecs[] = '<strong>' . htmlspecialchars($k) . ':</strong> ' . $v;
                }
            }
        } else {
            $legacy = trim((string) $specs);
            if ($legacy !== '') {
                $techSpecs[] = $legacy;
            }
        }

        $techSpecsHtml = !empty($techSpecs) ? implode('<br>', $techSpecs) : '—';
        $rowBg = '#ffffff';
        $componentRowIndex++;
    ?>
    <tr style="page-break-inside:avoid; background:<?= $rowBg ?>;">
        <td style="padding:12px 14px; font-size:14px; font-weight:bold; color:#000000; border:1px solid #dfe9df; font-family:'DejaVu Sans',sans-serif; vertical-align:top; text-align:center;">
            <?php if (!empty($productImagePath)): ?>
                <div style="text-align: center; margin-bottom: 8px;">
                    <img src="<?= $productImagePath ?>" alt="<?= esc($component['name'] ?? 'Product') ?>" style="width:60px; height:60px; object-fit:contain; border:1px solid #d4e4d4; padding:4px; background:#fff; display: block; margin: 0 auto;">
                </div>
            <?php endif; ?>
            <?= esc($component['name'] ?? '--') ?>
        </td>
        <td style="padding:12px 14px; font-size:14px; color:#222; border:1px solid #dfe9df; font-family:'DejaVu Sans',sans-serif; vertical-align:top;">
            <?= $make !== '' ? esc($make) : '—' ?>
        </td>
        <td style="padding:12px 14px; font-size:14px; color:#222; line-height:1.45; border:1px solid #dfe9df; font-family:'DejaVu Sans',sans-serif; vertical-align:top;">
            <?= $techSpecsHtml ?>
        </td>
        <td style="padding:12px 14px; font-size:14px; color:#222; border:1px solid #dfe9df; font-family:'DejaVu Sans',sans-serif; vertical-align:top;">
            <?= esc($warrantyValue) ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
