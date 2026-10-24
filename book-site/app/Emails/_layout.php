<?php
/**
 * Email shell — wrap $emailContent in branded HTML with logo + footer.
 * Inline styles only (most email clients strip <style>).
 *
 * Expected vars: $emailContent (string HTML), $logoUrl, $brandName
 */
?><!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?= e($brandName) ?></title>
</head>
<body style="margin:0;padding:0;background-color:#F6EFE4;font-family:Georgia,'Source Serif 4',serif;color:#1B1410;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F6EFE4;">
    <tr><td align="center" style="padding:32px 16px;">

      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
             style="max-width:600px;width:100%;background:#FBF6EC;border:1px solid rgba(27,20,16,0.12);border-radius:6px;overflow:hidden;">

        <tr><td align="center" style="padding:32px 32px 24px;border-bottom:1px solid rgba(27,20,16,0.07);">
          <a href="<?= e(appUrl()) ?>" style="text-decoration:none;color:#1B1410;">
            <img src="<?= e($logoUrl) ?>" alt="<?= e($brandName) ?>" width="160" style="display:block;width:160px;height:auto;border:0;">
          </a>
        </td></tr>

        <tr><td style="padding:32px 36px 36px;font-family:Georgia,'Source Serif 4',serif;font-size:16px;line-height:1.6;color:#1B1410;">
          <?= $emailContent ?>
        </td></tr>

        <tr><td style="padding:24px 36px 28px;background:#EFE6D5;font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:12px;color:#8A7866;line-height:1.55;">
          <strong style="color:#4A3C34;">ГРЕЙТ УЪРК ООД</strong> · ЕИК 206456831 · гр. Перник, ул. Лисец 1<br>
          Имейл: <a href="mailto:info@etaloncare.bg" style="color:#8A3A2A;text-decoration:none;">info@etaloncare.bg</a>
          · Телефон: +359 897 886 840<br>
          <a href="<?= e(appUrl()) ?>" style="color:#8A3A2A;text-decoration:none;"><?= e(appUrl()) ?></a>
        </td></tr>

      </table>

    </td></tr>
  </table>
</body>
</html>
