<?php $store = config(\Config\Store::class); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Your verification code</title>
</head>
<body style="margin:0;padding:24px 12px;background:#fbf8f1;color:#1f1714;font-family:Georgia,'Times New Roman',serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;border:1px solid #c9a24b;background:#fffdf8;">
          <tr>
            <td align="center" style="padding:20px 24px;background:#5b1226;color:#fff9ee;font-size:22px;letter-spacing:6px;"><?= esc($store->shortName) ?></td>
          </tr>
          <tr>
            <td align="center" style="padding:32px 24px 8px;font-size:18px;">Your verification code</td>
          </tr>
          <tr>
            <td align="center" style="padding:8px 24px 16px;color:#5b1226;font-size:36px;letter-spacing:8px;"><?= esc($code) ?></td>
          </tr>
          <tr>
            <td align="center" style="padding:0 24px 32px;color:#655a50;font-size:15px;line-height:22px;">Enter this code to finish creating your <?= esc($store->name) ?> account. If this wasn't you, you can ignore this email.</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
