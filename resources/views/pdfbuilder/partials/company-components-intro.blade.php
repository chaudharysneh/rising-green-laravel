<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 18px;">
    <tr>
        <td align="left">
            <div
                style="font-size: 42px; font-weight: bold; margin-bottom: 16px; line-height:1.12; font-family: 'Montserrat', sans-serif; color:#000; border-left:8px solid #4b9349; padding-left:18px;">
                <?= esc($componentsTitle !== '' ? $componentsTitle : 'SOLAR COMPONENTS') ?>
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;<?= !empty($componentsIntroExpanded) ? ' min-height:580px;' : '' ?>">
                <tr>
                    <td width="6" style="background-color: #4b9349; font-size: 0; line-height: 0;">&nbsp;</td>
                    <td style="padding: <?= !empty($componentsIntroExpanded) ? '26px 28px 24px 24px' : '20px 24px 18px 22px' ?>; background-color: #f8fbf8;">
                        <div class="pdf-rich-content pdf-rich-content-spacious pdf-company-page-about">
                        <?php if ($componentsActive === 1 && $componentsDesc !== ''): ?>
                        <?= $componentsDesc ?>
                        <?php else: ?>
                        <p><b>High-quality</b> components from trusted <b>Tier-1</b> OEMs, selected for performance,
                        safety, and long-term ROI.</p>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
