<?php
$text = isset($text) && !is_null($text) ? $text : '';
$url = isset($url) && !is_null($url) ? $url : '#';
$color = isset($color) && !is_null($color) ? $color : '#000000';
?>
<center data-parsed="">
    <table class="button success float-center" style="Margin: 0 0 16px 0; border-collapse: collapse; border-spacing: 0; float: none; margin: 0 0 16px; padding: 0; text-align: center; vertical-align: top; width: auto;">
        <tbody>
            <tr style="padding: 0; text-align: left; vertical-align: top;">
                <td style="-webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica,Arial,sans-serif; font-size: 16px; font-weight: 400; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                    <table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
                        <tbody>
                            <tr style="padding: 0; text-align: left; vertical-align: top;">
                                <td style="-webkit-hyphens: auto; Margin: 0; background: <?= $color; ?>; border: 0 solid <?= $color; ?>; border-collapse: collapse !important; color: #fefefe; font-family: Helvetica,Arial,sans-serif; font-size: 16px; font-weight: 400; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                                    <a href="<?= $url; ?>" style="Margin: 0; border: 0 solid <?= $color; ?>; border-radius: 3px; color: #fefefe; display: inline-block; font-family: Helvetica,Arial,sans-serif; font-size: 16px; font-weight: 700; line-height: 1.3; margin: 0; padding: 8px 16px; text-align: left; text-decoration: none;"><?= $text; ?></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</center>