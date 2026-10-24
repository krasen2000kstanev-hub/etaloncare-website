<?php
/**
 * Sent to the site administrator(s) immediately after a new order is placed.
 * Vars: $order (array), $items (array), $logoUrl, $brandName
 */
use App\Order;

$money = fn(float $a) => Order::formatMoney($a, $order['currency']);
$deliveryLabel = $order['delivery_method'] === 'office'
    ? 'До офис на Speedy'
    : 'До адрес — Speedy';
$paymentLabel = $order['payment_method'] === 'cod' ? 'Наложен платеж' : 'Карта';
$adminOrderUrl = adminUrl('orders/' . (int) $order['id']);

ob_start();
?>
<div style="background:#8A3A2A;color:#FBF6EC;padding:6px 12px;border-radius:3px;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;font-weight:600;margin-bottom:14px;">
  Нова поръчка
</div>
<h1 style="margin:0 0 16px;font-family:Georgia,'Source Serif 4',serif;font-weight:500;font-size:22px;line-height:1.25;color:#1B1410;">
  Поръчка № <?= e($order['order_number']) ?>
</h1>
<p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#4A3C34;">
  Получена е нова поръчка през сайта. Подробностите са по-долу.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
  <tr>
    <td width="50%" style="padding:0 12px 16px 0;vertical-align:top;">
      <div style="font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;margin-bottom:6px;">Клиент</div>
      <div style="color:#1B1410;line-height:1.55;font-size:14px;">
        <strong><?= e($order['customer_name']) ?></strong><br>
        <a href="mailto:<?= e($order['customer_email']) ?>" style="color:#8A3A2A;text-decoration:none;"><?= e($order['customer_email']) ?></a><br>
        <a href="tel:<?= e(preg_replace('/\s/', '', $order['customer_phone'] ?? '')) ?>" style="color:#1B1410;text-decoration:none;"><?= e($order['customer_phone'] ?? '—') ?></a>
      </div>
    </td>
    <td width="50%" style="padding:0 0 16px 12px;vertical-align:top;">
      <div style="font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;margin-bottom:6px;">Плащане</div>
      <div style="color:#1B1410;font-size:14px;line-height:1.55;">
        <?= e($paymentLabel) ?><br>
        <span style="color:#8A7866;font-size:13px;">Общо: <strong style="color:#1B1410;"><?= e($money((float) $order['total'])) ?></strong></span>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="padding:0 0 16px;vertical-align:top;">
      <div style="font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;margin-bottom:6px;">Доставка</div>
      <div style="color:#1B1410;line-height:1.55;font-size:14px;">
        <strong><?= e($deliveryLabel) ?></strong><br>
        <?= e(Order::deliveryAddressText($order)) ?>
      </div>
    </td>
  </tr>
</table>

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

<?php if (!empty($order['notes'])): ?>
<div style="background:#F6EFE4;border-left:3px solid #8A3A2A;padding:14px 16px;margin:0 0 22px;font-size:14px;color:#4A3C34;">
  <strong style="color:#1B1410;display:block;margin-bottom:4px;">Бележка от клиента:</strong>
  <?= nl2br(e($order['notes'])) ?>
</div>
<?php endif; ?>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 0;">
  <tr>
    <td align="center" style="padding:0;">
      <a href="<?= e($adminOrderUrl) ?>"
         style="display:inline-block;background:#1B1410;color:#FBF6EC;text-decoration:none;padding:14px 28px;border-radius:4px;font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:14px;font-weight:600;letter-spacing:0.02em;">
        Отвори в админ панела
      </a>
    </td>
  </tr>
</table>

<p style="margin:18px 0 0;font-size:12px;color:#8A7866;line-height:1.55;text-align:center;">
  Това известие се изпраща автоматично при всяка нова поръчка.
</p>
<?php
$emailContent = ob_get_clean();
require __DIR__ . '/_layout.php';
