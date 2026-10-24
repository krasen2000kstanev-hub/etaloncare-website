<?php
/**
 * Не-фискален документ за продажба (стокова разписка) по чл. 52о, ал. 1, т. 1 от Наредба Н-18.
 * Изпраща се на клиента при УСПЕШНО КАРТОВО плащане. НЕ е фискален бон (без УНП/подпис/QR).
 * Vars: $order, $items, $company, $vatRate, $rrn, $docNo, $logoUrl, $brandName
 */

$rate = $vatRate / 100.0;
$money = fn(float $a) => number_format($a, 2, ',', ' ') . ' €';
$docDate = date('d.m.Y', (int) strtotime($order['created_at']));

$sumWith = 0.0;
foreach ($items as $it) {
    $sumWith += (float) $it['unit_price'] * (float) $it['qty'];
}
$sumWith = round($sumWith, 2);
$sumBase = round($sumWith / (1 + $rate), 2);
$sumVat  = round($sumWith - $sumBase, 2);

ob_start();
?>
<h1 style="margin:0 0 6px;font-family:Georgia,'Source Serif 4',serif;font-weight:500;font-size:22px;line-height:1.2;color:#1B1410;">
  Документ за продажба
</h1>
<p style="margin:0 0 18px;font-size:12px;color:#8A7866;">
  Стокова разписка по чл. 52о, ал. 1, т. 1 от Наредба Н-18 · не е фискален бон
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;font-size:14px;color:#4A3C34;">
  <tr>
    <td style="vertical-align:top;padding-right:12px;">
      <strong style="color:#1B1410;"><?= e($company['name']) ?></strong><br>
      ЕИК: <?= e($company['eik']) ?><?php if (!empty($company['vat'])): ?> · ДДС №: <?= e($company['vat']) ?><?php endif; ?><br>
      <?php if (!empty($company['address'])): ?><?= e($company['address']) ?><br><?php endif; ?>
      Електронен магазин: <?= e($company['domain']) ?>
    </td>
    <td style="vertical-align:top;text-align:right;white-space:nowrap;">
      Документ №: <strong style="color:#1B1410;"><?= e($docNo) ?></strong><br>
      Дата: <?= e($docDate) ?><br>
      Поръчка №: <?= e($order['order_number']) ?>
    </td>
  </tr>
</table>

<p style="margin:0 0 12px;font-size:14px;color:#4A3C34;">Клиент: <strong style="color:#1B1410;"><?= e($order['customer_name']) ?></strong></p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 16px;border:1px solid rgba(27,20,16,0.12);border-radius:4px;border-collapse:collapse;font-size:13px;">
  <tr style="background:#F6EFE4;color:#1B1410;">
    <th align="left"   style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.12);">Стока</th>
    <th align="center" style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.12);">К-во</th>
    <th align="right"  style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.12);">Ед. цена без ДДС</th>
    <th align="center" style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.12);">ДДС</th>
    <th align="right"  style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.12);">Сума с ДДС</th>
  </tr>
  <?php foreach ($items as $it):
    $qty = (float) $it['qty']; $gross = (float) $it['unit_price'];
    $lineWith = round($gross * $qty, 2);
    $unitBase = round($gross / (1 + $rate), 2); ?>
  <tr>
    <td style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.07);color:#1B1410;"><?= e($it['name']) ?></td>
    <td align="center" style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.07);color:#4A3C34;"><?= e((string) (int) $qty) ?></td>
    <td align="right"  style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.07);color:#4A3C34;"><?= $money($unitBase) ?></td>
    <td align="center" style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.07);color:#4A3C34;"><?= e((string) $vatRate) ?>%</td>
    <td align="right"  style="padding:8px 10px;border-bottom:1px solid rgba(27,20,16,0.07);color:#1B1410;"><?= $money($lineWith) ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;font-size:14px;">
  <tr><td align="right" style="padding:2px 0;color:#4A3C34;">Сума без ДДС:</td>
      <td align="right" width="120" style="padding:2px 0;color:#1B1410;"><?= $money($sumBase) ?></td></tr>
  <tr><td align="right" style="padding:2px 0;color:#4A3C34;">ДДС (<?= e((string) $vatRate) ?>%):</td>
      <td align="right" style="padding:2px 0;color:#1B1410;"><?= $money($sumVat) ?></td></tr>
  <tr><td align="right" style="padding:6px 0;color:#1B1410;font-weight:bold;border-top:1px solid rgba(27,20,16,0.12);">Обща сума с ДДС:</td>
      <td align="right" style="padding:6px 0;color:#1B1410;font-weight:bold;border-top:1px solid rgba(27,20,16,0.12);"><?= $money($sumWith) ?></td></tr>
</table>

<p style="margin:0 0 6px;font-size:14px;color:#4A3C34;">
  Начин на плащане: <strong style="color:#1B1410;">Карта</strong><?php if (!empty($rrn)): ?> · Референция: <?= e($rrn) ?><?php endif; ?>
</p>
<p style="margin:0;font-size:12px;color:#8A7866;line-height:1.6;">
  Документът е издаден по реда на чл. 52о от Наредба Н-18 (алтернативен режим за електронни магазини) и
  не представлява фискален бон.
</p>
<?php
$emailContent = ob_get_clean();
require __DIR__ . '/_layout.php';
