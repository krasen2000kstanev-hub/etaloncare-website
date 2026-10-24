<?php
/**
 * Sent to the customer immediately after they place an order.
 * Vars: $order (array), $items (array), $logoUrl, $brandName
 */
use App\Order;

$money = fn(float $a) => Order::formatMoney($a, $order['currency']);
$deliveryLabel = $order['delivery_method'] === 'office'
    ? 'До офис на Speedy'
    : 'До адрес — Speedy';
$paymentLabel = $order['payment_method'] === 'cod' ? 'Наложен платеж' : 'Карта';

ob_start();
?>
<h1 style="margin:0 0 16px;font-family:Georgia,'Source Serif 4',serif;font-weight:500;font-size:24px;line-height:1.2;color:#1B1410;">
  Благодарим Ви за поръчката!
</h1>
<p style="margin:0 0 22px;font-size:15px;line-height:1.6;color:#4A3C34;">
  Получихме Вашата поръчка <strong style="color:#1B1410;">№ <?= e($order['order_number']) ?></strong>.
  В рамките на работния ден ще се свържем с Вас за потвърждение и подготовка за изпращане.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 24px;border:1px solid rgba(27,20,16,0.12);border-radius:4px;overflow:hidden;">
  <thead>
    <tr style="background:#EFE6D5;">
      <th align="left" style="padding:10px 14px;font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;">Артикул</th>
      <th align="right" style="padding:10px 14px;font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;">Бр.</th>
      <th align="right" style="padding:10px 14px;font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;">Цена</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it): ?>
    <tr>
      <td style="padding:12px 14px;border-top:1px solid rgba(27,20,16,0.07);"><?= e($it['name']) ?></td>
      <td align="right" style="padding:12px 14px;border-top:1px solid rgba(27,20,16,0.07);"><?= e((string) $it['qty']) ?></td>
      <td align="right" style="padding:12px 14px;border-top:1px solid rgba(27,20,16,0.07);"><?= e($money((float) $it['line_total'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <td colspan="2" style="padding:10px 14px;border-top:1px solid rgba(27,20,16,0.07);color:#4A3C34;">Доставка</td>
      <td align="right" style="padding:10px 14px;border-top:1px solid rgba(27,20,16,0.07);">
        <?= ((float) $order['delivery_cost']) > 0 ? e($money((float) $order['delivery_cost'])) : '—' ?>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="padding:14px;border-top:1px solid rgba(27,20,16,0.12);font-family:Georgia,serif;font-size:18px;font-weight:500;">Общо</td>
      <td align="right" style="padding:14px;border-top:1px solid rgba(27,20,16,0.12);font-family:Georgia,serif;font-size:18px;font-weight:500;"><?= e($money((float) $order['total'])) ?></td>
    </tr>
  </tbody>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
  <tr>
    <td width="50%" style="padding-right:12px;vertical-align:top;">
      <div style="font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;margin-bottom:6px;">Доставка</div>
      <div style="color:#1B1410;line-height:1.5;font-size:14px;">
        <?= e($deliveryLabel) ?><br>
        <?= e(Order::deliveryAddressText($order)) ?>
      </div>
    </td>
    <td width="50%" style="padding-left:12px;vertical-align:top;">
      <div style="font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;margin-bottom:6px;">Плащане</div>
      <div style="color:#1B1410;font-size:14px;"><?= e($paymentLabel) ?></div>
    </td>
  </tr>
</table>

<?php if (!empty($order['notes'])): ?>
<div style="background:#F6EFE4;border-left:3px solid #8A3A2A;padding:14px 16px;margin:0 0 22px;font-size:14px;color:#4A3C34;">
  <strong style="color:#1B1410;display:block;margin-bottom:4px;">Вашата бележка:</strong>
  <?= nl2br(e($order['notes'])) ?>
</div>
<?php endif; ?>

<p style="margin:0;font-size:13px;color:#8A7866;line-height:1.6;">
  При въпроси, отговорете на този имейл или ни пишете на
  <a href="mailto:info@etaloncare.bg" style="color:#8A3A2A;">info@etaloncare.bg</a>.
</p>
<?php
$emailContent = ob_get_clean();
require __DIR__ . '/_layout.php';
