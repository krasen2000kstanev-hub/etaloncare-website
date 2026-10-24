<?php
/**
 * Sent on every status change. Includes admin's note (if any) verbatim.
 * Vars: $order, $newStatus, $statusLabel, $note (string|null), $logoUrl, $brandName
 */
use App\Order;

$money = fn(float $a) => Order::formatMoney($a, $order['currency']);

$messages = [
    'received'   => 'Поръчката Ви е приета и е в процес на обработка.',
    'confirmed'  => 'Поръчката Ви е потвърдена. Подготвяме я за изпращане.',
    'preparing'  => 'Подготвяме Вашата поръчка за изпращане.',
    'shipped'    => 'Поръчката Ви е предадена на Speedy и е на път към Вас.',
    'delivered'  => 'Поръчката Ви е доставена. Благодарим Ви, че избрахте ÉTALON!',
    'cancelled'  => 'Поръчката Ви е отменена. Ако смятате, че това е грешка, моля свържете се с нас.',
];

ob_start();
?>
<h1 style="margin:0 0 16px;font-family:Georgia,'Source Serif 4',serif;font-weight:500;font-size:24px;line-height:1.2;color:#1B1410;">
  Промяна на статус — поръчка № <?= e($order['order_number']) ?>
</h1>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
  <tr>
    <td style="padding:14px 18px;background:#1B1410;color:#FBF6EC;border-radius:4px;font-family:Georgia,serif;font-size:18px;font-weight:500;text-align:center;">
      <?= e($statusLabel) ?>
    </td>
  </tr>
</table>

<p style="margin:0 0 22px;font-size:15px;line-height:1.6;color:#4A3C34;">
  <?= e($messages[$newStatus] ?? '') ?>
</p>

<?php if (!empty($note)): ?>
<div style="background:#F6EFE4;border-left:3px solid #8A3A2A;padding:14px 16px;margin:0 0 22px;font-size:14px;color:#4A3C34;">
  <strong style="color:#1B1410;display:block;margin-bottom:4px;">Бележка от нас:</strong>
  <?= nl2br(e($note)) ?>
</div>
<?php endif; ?>

<?php if (!empty($order['tracking_number'])): ?>
<div style="margin:0 0 22px;padding:16px;background:#EFE6D5;border-radius:4px;font-size:14px;">
  <div style="font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A7866;font-weight:600;margin-bottom:6px;">
    Товарителница Speedy
  </div>
  <div style="color:#1B1410;font-family:'JetBrains Mono',ui-monospace,monospace;font-size:14px;">
    № <?= e($order['tracking_number']) ?>
  </div>
  <div style="margin-top:8px;">
    <?php $emailTrackUrl = 'https://www.speedy.bg/bg/track-shipment?shipmentNumber=' . urlencode($order['tracking_number']); ?>
    <a href="<?= e($emailTrackUrl) ?>" style="color:#8A3A2A;font-family:-apple-system,BlinkMacSystemFont,Arial,sans-serif;font-size:13px;">Проследи пратката →</a>
  </div>
</div>
<?php endif; ?>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 22px;border:1px solid rgba(27,20,16,0.12);border-radius:4px;">
  <tr>
    <td style="padding:12px 14px;border-bottom:1px solid rgba(27,20,16,0.07);font-size:14px;color:#4A3C34;">
      <strong style="color:#1B1410;">Поръчка:</strong> № <?= e($order['order_number']) ?>
    </td>
  </tr>
  <tr>
    <td style="padding:12px 14px;border-bottom:1px solid rgba(27,20,16,0.07);font-size:14px;color:#4A3C34;">
      <strong style="color:#1B1410;">Доставка:</strong> <?= e(Order::deliveryAddressText($order)) ?>
    </td>
  </tr>
  <tr>
    <td style="padding:12px 14px;font-size:14px;color:#4A3C34;">
      <strong style="color:#1B1410;">Сума:</strong> <?= e($money((float) $order['total'])) ?>
    </td>
  </tr>
</table>

<p style="margin:0;font-size:13px;color:#8A7866;line-height:1.6;">
  При въпроси, отговорете на този имейл или ни пишете на
  <a href="mailto:info@etaloncare.bg" style="color:#8A3A2A;">info@etaloncare.bg</a>.
</p>
<?php
$emailContent = ob_get_clean();
require __DIR__ . '/_layout.php';
