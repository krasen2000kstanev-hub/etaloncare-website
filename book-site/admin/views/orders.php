<?php /** @var array $orders @var array $counts @var string $currentStatus @var string $q */ ?>
<?php
use App\Order;
$title = 'Поръчки';
ob_start();
?>
<div class="page-head">
  <h1>Поръчки <span class="badge"><?= e((string) ($counts['_total'] ?? 0)) ?></span></h1>
</div>

<form method="get" class="orders-filters" action="<?= e(adminUrl('orders')) ?>">
  <a href="<?= e(adminUrl('orders')) ?>" class="chip <?= $currentStatus === '' ? 'active' : '' ?>">
    Всички <em><?= e((string) ($counts['_total'] ?? 0)) ?></em>
  </a>
  <?php foreach (Order::STATUSES as $code => $labels): ?>
    <a href="<?= e(adminUrl('orders?status=' . $code)) ?>" class="chip <?= $currentStatus === $code ? 'active' : '' ?>">
      <?= e($labels['bg']) ?> <em><?= e((string) ($counts[$code] ?? 0)) ?></em>
    </a>
  <?php endforeach; ?>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Търси по №, име, имейл…" class="search">
  <button type="submit" class="btn">Филтрирай</button>
</form>

<div class="card no-padding">
  <table class="table">
    <thead>
      <tr>
        <th>№</th>
        <th>Клиент</th>
        <th>Доставка</th>
        <th>Сума</th>
        <th>Статус</th>
        <th>Получена</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$orders): ?>
        <tr><td colspan="7" class="empty">Няма поръчки за избрания филтър.</td></tr>
      <?php else: foreach ($orders as $o): ?>
        <tr>
          <td data-label="№"><strong>№ <?= e($o['order_number']) ?></strong></td>
          <td data-label="Клиент">
            <?= e($o['customer_name']) ?><br>
            <span class="muted small"><?= e($o['customer_email']) ?></span>
          </td>
          <td data-label="Доставка">
            <?= $o['delivery_method'] === 'office' ? 'Офис' : 'Адрес' ?><br>
            <span class="muted small"><?= e(Order::deliveryAddressText($o)) ?></span>
          </td>
          <td data-label="Сума"><?= e(Order::formatMoney((float) $o['total'], $o['currency'])) ?></td>
          <td data-label="Статус"><span class="status status-<?= e($o['status']) ?>"><?= e(Order::statusLabel($o['status'])) ?></span></td>
          <td data-label="Получена" class="muted small"><?= e(date('d.m.Y H:i', strtotime($o['created_at']))) ?></td>
          <td class="cell-actions"><a href="<?= e(adminUrl('orders/' . $o['id'])) ?>" class="link">Виж →</a></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
