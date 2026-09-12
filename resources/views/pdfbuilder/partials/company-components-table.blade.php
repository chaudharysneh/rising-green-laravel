<div>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:10px 0 18px; border:0; font-family:'Montserrat', sans-serif; page-break-inside: auto;">
    <thead>
        {{-- Repeat the top gap with the heading on continuation pages. --}}
        <tr aria-hidden="true">
            <td colspan="3" style="height:24px; padding:0; border:0; background:#fff; font-size:0; line-height:0;"></td>
        </tr>
        <tr style="background-color:#4b9349; border-bottom:2px solid #3d7a3b;">
            <th width="22%" style="padding:9px 10px; font-weight:bold; font-size:12px; color:#fff; text-align:left; font-family:'Montserrat',sans-serif; border: 1px solid #3d7a3b;">
                Component Type
            </th>
            <th width="25%" style="padding:9px 10px; font-weight:bold; font-size:12px; color:#fff; text-align:left; font-family:'Montserrat',sans-serif; border: 1px solid #3d7a3b;">
                Approved Brand / Make
            </th>
            <th width="53%" style="padding:9px 10px; font-weight:bold; font-size:12px; color:#fff; text-align:left; font-family:'Montserrat',sans-serif; border: 1px solid #3d7a3b;">
                Technical Specification
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

        // Keep the primary details first, followed by comma-separated specifications.
        $techSpecs = [];
        $additionalSpecs = [];

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
                } elseif (strtolower($k) === 'type') {
                    $techSpecs[] = '<strong>' . htmlspecialchars($k) . ':</strong> ' . $v;
                } else {
                    $additionalSpecs[] = '<strong>' . htmlspecialchars($k) . ':</strong> ' . $v;
                }
            }
        } else {
            $legacy = trim((string) $specs);
            if ($legacy !== '') {
                $techSpecs[] = $legacy;
            }
        }

        if (!empty($additionalSpecs)) {
            $techSpecs[] = '(' . implode(', ', $additionalSpecs) . ')';
        }
        $techSpecsHtml = !empty($techSpecs) ? implode('<br>', $techSpecs) : '—';
        $rowBg = '#ffffff';
        $componentRowIndex++;
    ?>
    <tr style="page-break-inside:avoid; background:<?= $rowBg ?>;">
        <td style="padding:8px 10px; font-size:12px; font-weight:bold; color:#000000; border:1px solid #dfe9df; font-family:'DejaVu Sans',sans-serif; vertical-align:top; text-align:center;">
            <?php if (!empty($productImagePath)): ?>
                <div style="text-align: center; margin-bottom: 4px;">
                    <img src="<?= $productImagePath ?>" alt="<?= esc($component['name'] ?? 'Product') ?>" style="width:48px; height:48px; object-fit:contain; border:1px solid #d4e4d4; padding:4px; background:#fff; display: block; margin: 0 auto;">
                </div>
            <?php endif; ?>
            <?= esc($component['name'] ?? '--') ?>
        </td>
        <td style="padding:8px 10px; font-size:12px; color:#222; border:1px solid #dfe9df; font-family:'DejaVu Sans',sans-serif; vertical-align:top;">
            <?= $make !== '' ? esc($make) : '—' ?>
        </td>
        <td style="padding:8px 10px; font-size:12px; color:#222; line-height:1.3; border:1px solid #dfe9df; font-family:'DejaVu Sans',sans-serif; vertical-align:top;">
            <?= $techSpecsHtml ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
