<?php
$galleryImgStyle = static function (int $height): string {
    return 'width:100%;height:' . $height . 'px;object-fit:cover;object-position:center;display:block;';
};
?>
<div style="page-break-inside: avoid;">
<table width="100%" cellpadding="0" cellspacing="0" style="margin: 4px 0 8px; border-collapse: collapse;">
    <tr>
        <td width="50%" valign="top" style="padding-right: <?= (int) ($galleryGap / 2) ?>px;">
            <div style="width:100%;height:<?= (int) $galleryLeftHeight ?>px;overflow:hidden;background:#eef4ee;">
                <img src="<?= $img1 ?>" alt="Solar Installation" style="<?= $galleryImgStyle((int) $galleryLeftHeight) ?>">
            </div>
        </td>
        <td width="50%" valign="top" style="padding-left: <?= (int) ($galleryGap / 2) ?>px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                    <td valign="top" style="padding-bottom: <?= (int) ($galleryGap / 2) ?>px;">
                        <div style="width:100%;height:<?= (int) $galleryRightHeight ?>px;overflow:hidden;background:#eef4ee;">
                            <img src="<?= $img2 ?>" alt="Solar Installation" style="<?= $galleryImgStyle((int) $galleryRightHeight) ?>">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td valign="top" style="padding-top: <?= (int) ($galleryGap / 2) ?>px;">
                        <div style="width:100%;height:<?= (int) $galleryRightHeight ?>px;overflow:hidden;background:#eef4ee;">
                            <img src="<?= $img3 ?>" alt="Solar Installation" style="<?= $galleryImgStyle((int) $galleryRightHeight) ?>">
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 6px;">
    <tr>
        <td align="center">
            <div style="font-size: 13px; text-align: center; font-family: 'Montserrat', sans-serif; color: #444;">
                Each site is installed end to end with 5 years of AMC & monitoring
            </div>
        </td>
    </tr>
</table>
</div>
