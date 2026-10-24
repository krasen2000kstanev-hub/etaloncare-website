<?php /** @var array $order @var array $items @var array $log @var ?array $payment */ ?>
<?php
use App\Order;
$title = 'Поръчка № ' . $order['order_number'];
ob_start();
?>
<div class="page-head">
  <div class="head-row">
    <div>
      <a href="<?= e(adminUrl('orders')) ?>" class="back-link">← Поръчки</a>
      <h1>Поръчка № <?= e($order['order_number']) ?></h1>
      <div class="muted small">Получена <?= e(date('d.m.Y H:i', strtotime($order['created_at']))) ?> · Локал: <?= e($order['locale']) ?></div>
    </div>
    <div class="status-badge status-<?= e($order['status']) ?>">
      <?= e(Order::statusLabel($order['status'])) ?>
    </div>
  </div>
</div>

<div class="grid-2-7-5">

<!-- LEFT — order details -->
<div>
  <section class="card">
    <h2>Клиент</h2>
    <div class="kv">
      <div><span>Име</span><strong><?= e($order['customer_name']) ?></strong></div>
      <div><span>Имейл</span><strong><a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></strong></div>
      <div><span>Телефон</span><strong><a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></strong></div>
    </div>
  </section>

  <section class="card">
    <h2>Доставка — <?= $order['delivery_method'] === 'office' ? 'до офис на Speedy' : 'до адрес' ?></h2>
    <p style="margin:0;font-size:14px;color:#4A3C34;"><?= e(Order::deliveryAddressText($order)) ?></p>
    <?php if ($order['tracking_number']): ?>
      <?php $trackUrl = 'https://www.speedy.bg/bg/track-shipment?shipmentNumber=' . urlencode($order['tracking_number']); ?>
      <div class="kv" style="margin-top:14px;">
        <div><span>Товарителница</span><strong><?= e($order['tracking_number']) ?></strong></div>
        <div><span>Проследяване</span><strong><a href="<?= e($trackUrl) ?>" target="_blank" rel="noopener">отвори в Speedy</a></strong></div>
      </div>
      <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?= e(adminUrl('orders/' . $order['id'] . '/waybill.pdf')) ?>" target="_blank" rel="noopener">
          Принтирай товарителница (PDF)
        </a>
      </div>
    <?php elseif (!in_array($order['status'], ['cancelled', 'delivered'], true)): ?>
      <form method="post" style="margin-top:14px;">
        <?= \App\Csrf::field() ?>
        <input type="hidden" name="action" value="create_waybill">
        <button type="submit" class="btn"
                onclick="return confirm('Ще се създаде товарителница в Speedy и поръчката ще премине в „Изпратена\". Клиентът ще бъде уведомен. Продължи?')">
          Създай товарителница в Speedy
        </button>
        <p class="muted small" style="margin:10px 0 0;">
          Изисква попълнени Speedy creds + Sender Object ID в Настройки.
          <?php if ($order['delivery_method'] === 'office' && !$order['delivery_office_speedy_id']): ?>
            <br><strong style="color:var(--error);">Внимание:</strong> поръчката е със static fallback офис (без реално Speedy ID) — създаването ще се провали.
          <?php endif; ?>
        </p>
      </form>
    <?php endif; ?>
  </section>

  <section class="card no-padding">
    <table class="table">
      <thead>
        <tr><th>Артикул</th><th>SKU</th><th>Бр.</th><th>Цена</th><th>Сума</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td data-label="Артикул"><?= e($it['name']) ?></td>
            <td data-label="SKU" class="mono"><?= e($it['sku']) ?></td>
            <td data-label="Бр."><?= e((string) $it['qty']) ?></td>
            <td data-label="Цена"><?= e(Order::formatMoney((float) $it['unit_price'], $order['currency'])) ?></td>
            <td data-label="Сума"><?= e(Order::formatMoney((float) $it['line_total'], $order['currency'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <tr class="totals">
          <td colspan="4" align="right" class="cell-actions">Доставка</td>
          <td data-label="Доставка"><?= ((float) $order['delivery_cost']) > 0 ? e(Order::formatMoney((float) $order['delivery_cost'], $order['currency'])) : '—' ?></td>
        </tr>
        <tr class="totals total">
          <td colspan="4" align="right" class="cell-actions"><strong>Общо</strong></td>
          <td data-label="Общо"><strong><?= e(Order::formatMoney((float) $order['total'], $order['currency'])) ?></strong></td>
        </tr>
      </tbody>
    </table>
  </section>

  <?php if ($order['notes']): ?>
  <section class="card">
    <h2>Бележка от клиента</h2>
    <p style="margin:0;white-space:pre-wrap;"><?= e($order['notes']) ?></p>
  </section>
  <?php endif; ?>
</div>

<!-- RIGHT — status change + log -->
<div>
  <?php if ($order['payment_method'] === 'card' && !empty($payment)): ?>
  <section class="card">
    <h2>Плащане с карта</h2>
    <table class="kv-table">
      <tr><th>Сума</th><td><?= e(Order::formatMoney((float) $payment['amount'], $payment['currency'])) ?></td></tr>
      <tr><th>Резултат</th><td>ACTION <?= e((string) $payment['action']) ?> · RC <?= e((string) $payment['rc']) ?><?php if (!empty($payment['status_msg'])): ?> — <?= e($payment['status_msg']) ?><?php endif; ?></td></tr>
      <tr><th>Валиден подпис</th><td><?= ((int) $payment['signature_valid'] === 1) ? '✓ Да' : '✗ Не' ?></td></tr>
      <tr><th>Одобрение (APPROVAL)</th><td><?= e($payment['approval'] ?: '—') ?></td></tr>
      <tr><th>RRN</th><td><?= e($payment['rrn'] ?: '—') ?></td></tr>
      <tr><th>INT_REF</th><td><?= e($payment['int_ref'] ?: '—') ?></td></tr>
      <tr><th>Референция (ORDER)</th><td><?= e($payment['order_ref'] ?: '—') ?></td></tr>
      <tr><th>Дата</th><td><?= e(date('d.m.Y H:i', strtotime($payment['sent_at'] ?: $payment['created_at']))) ?></td></tr>
    </table>
  </section>
  <?php endif; ?>

  <section class="card">
    <h2>Промяна на статус</h2>
    <p class="muted small" style="margin-bottom:14px;">Клиентът ще получи имейл с новия статус и Вашата бележка.</p>
    <form method="post">
      <?= \App\Csrf::field() ?>
      <input type="hidden" name="action" value="change_status">

      <label>
        <span>Нов статус</span>
        <select name="status">
          <?php foreach (Order::STATUSES as $code => $labels): ?>
            <option value="<?= e($code) ?>" <?= $code === $order['status'] ? 'selected' : '' ?>><?= e($labels['bg']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span>Бележка към клиента (по избор)</span>
        <textarea name="note" rows="4" placeholder="Например: пратката ще бъде взета от куриера утре сутринта."></textarea>
      </label>

      <button type="submit" class="btn btn-primary btn-wide">Запази &amp; уведоми</button>
    </form>
  </section>

  <section class="card">
    <h2>История</h2>
    <ol class="status-log">
      <?php foreach ($log as $entry): ?>
        <li>
          <div class="log-head">
            <strong><?= e(Order::statusLabel($entry['to_status'])) ?></strong>
            <span class="muted small"><?= e(date('d.m.Y H:i', strtotime($entry['created_at']))) ?></span>
          </div>
          <?php if ($entry['from_status']): ?>
            <div class="muted small">от <?= e(Order::statusLabel($entry['from_status'])) ?>
              <?php if ($entry['user_name']): ?> · <?= e($entry['user_name']) ?><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($entry['note']): ?>
            <div class="log-note"><?= e($entry['note']) ?></div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
</div>

</div>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
