<div style="page-break-inside: avoid;">
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:10px 0 18px; border:1px solid #cfe0cf; font-family:'Montserrat', sans-serif; page-break-inside: avoid;">
    <tr>
        <td colspan="2" style="background-color:#4b9349; padding:0; border-bottom:2px solid #3d7a3b;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td width="30%" style="padding:14px 16px; font-weight:bold; font-size:15px; color:#fff; letter-spacing:0.3px; font-family:'Montserrat',sans-serif;">
                        Product Name
                    </td>
                    <td width="70%" style="padding:14px 16px; font-weight:bold; font-size:15px; color:#fff; letter-spacing:0.3px; font-family:'Montserrat',sans-serif;">
                        Specifications
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <?php $componentRowIndex = 0; ?>
    <?php foreach ($componentsTableRows ?? $componentsData as $componentKey => $component):
        $specs = $component['specifications'] ?? [];
        $make = trim((string) ($component['category'] ?? ''));
        $qty = trim((string) ($component['quantity'] ?? ''));

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

        $specRows = [];
        if ($make !== '') {
            $specRows[] = ['Make', htmlspecialchars($make)];
        }
        if ($qty !== '') {
            $specRows[] = ['Quantity', htmlspecialchars($qty)];
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
                $specRows[] = [htmlspecialchars($k), $v];
            }
        } else {
            $legacy = trim((string) $specs);
            if ($legacy !== '') {
                $legacy = strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $legacy));
                $specRows[] = ['Specs', htmlspecialchars($legacy)];
            }
        }

        $specHtml = '<span style="color:#888;font-size:14px;font-family:\'DejaVu Sans\',sans-serif;">—</span>';
        if (!empty($specRows)) {
            $specHtml = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-family:\'DejaVu Sans\',sans-serif;">';
            foreach ($specRows as $specIndex => $r) {
                $k = (string) ($r[0] ?? '');
                $v = (string) ($r[1] ?? '');
                $specBorder = ($specIndex < count($specRows) - 1) ? 'border-bottom:1px solid #edf2ed;' : '';
                $specHtml .= '<tr>'
                    . '<td style="width:30%; padding:7px 14px 7px 0; vertical-align:top; font-size:14px; font-weight:bold; color:#5a6f5a; font-family:\'DejaVu Sans\',sans-serif; ' . $specBorder . '">' . $k . '</td>'
                    . '<td style="width:70%; padding:7px 0; vertical-align:top; font-size:14px; color:#222; line-height:1.45; font-family:\'DejaVu Sans\',sans-serif; ' . $specBorder . '">' . $v . '</td>'
                    . '</tr>';
            }
            $specHtml .= '</table>';
        }

        $rowBg = ($componentRowIndex % 2 === 0) ? '#ffffff' : '#f8fbf8';
        $productBg = ($componentRowIndex % 2 === 0) ? '#f3f8f3' : '#ebf3eb';
        $componentRowIndex++;
    ?>
    <tr style="page-break-inside:avoid;">
        <td width="30%" style="padding:0; vertical-align:middle; background:<?= $productBg ?>; border-bottom:1px solid #dfe9df;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td width="4" style="background-color:#4b9349; font-size:0; line-height:0;">&nbsp;</td>
                    <td style="padding:18px 14px; text-align:center; vertical-align:middle;">
                        <?php if (!empty($productImagePath)): ?>
                        <div style="margin-bottom:10px;">
                            <img src="<?= $productImagePath ?>"
                                alt="<?= esc($component['name'] ?? 'Product') ?>"
                                style="width:88px; height:88px; object-fit:contain; display:inline-block; border:1px solid #d4e4d4; padding:8px; background:#fff;">
                        </div>
                        <?php endif; ?>
                        <div style="font-size:15px; font-weight:bold; color:#2d5a2d; line-height:1.35; font-family:'DejaVu Sans',sans-serif;">
                            <?= esc($component['name'] ?? '--') ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td width="70%" style="padding:16px 20px; vertical-align:top; background:<?= $rowBg ?>; border-bottom:1px solid #dfe9df; border-left:1px solid #edf2ed; font-family:'DejaVu Sans',sans-serif;">
            <?= $specHtml ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
