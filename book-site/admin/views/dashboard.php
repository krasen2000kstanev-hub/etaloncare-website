<?php /** @var int $userCount @var array $counts @var array $recent */ ?>
<?php
use App\Order;
$title = 'Табло'; ob_start();
?>
<div class="page-head">
  <h1>Табло</h1>
  <p class="muted">Преглед на дейността в магазина.</p>
</div>

<div class="cards-grid">
  <a class="card stat-card" href="<?= e(adminUrl('orders')) ?>">
    <div class="stat-label">Поръчки общо</div>
    <div class="stat-value"><?= e((string) $counts['_total']) ?></div>
    <div class="stat-hint">всички статуси</div>
  </a>
  <a class="card stat-card" href="<?= e(adminUrl('orders?status=received')) ?>">
    <div class="stat-label">Нови</div>
    <div class="stat-value"><?= e((string) ($counts['received'] ?? 0)) ?></div>
    <div class="stat-hint">очакват преглед</div>
  </a>
  <a class="card stat-card" href="<?= e(adminUrl('orders?status=preparing')) ?>">
    <div class="stat-label">Подготвят се</div>
    <div class="stat-value"><?= e((string) ($counts['preparing'] ?? 0)) ?></div>
    <div class="stat-hint">за изпращане</div>
  </a>
  <a class="card stat-card" href="<?= e(adminUrl('orders?status=shipped')) ?>">
    <div class="stat-label">Изпратени</div>
    <div class="stat-value"><?= e((string) ($counts['shipped'] ?? 0)) ?></div>
    <div class="stat-hint">в Speedy</div>
  </a>
  <?php if (\App\Auth::isAdmin()): ?>
  <a class="card stat-card" href="<?= e(adminUrl('settings')) ?>">
    <div class="stat-label">Настройки</div>
    <div class="stat-value">Speedy · SMTP</div>
    <div class="stat-hint">конфигурация</div>
  </a>
  <a class="card stat-card" href="<?= e(adminUrl('content')) ?>">
    <div class="stat-label">Текстове</div>
    <div class="stat-value">Hero · About · Footer</div>
    <div class="stat-hint">редактирай BG / EN</div>
  </a>
  <a class="card stat-card" href="<?= e(adminUrl('media')) ?>">
    <div class="stat-label">Изображения</div>
    <div class="stat-value">Лого · Корица</div>
    <div class="stat-hint">смени на сайта</div>
  </a>
  <a class="card stat-card" href="<?= e(adminUrl('seo')) ?>">
    <div class="stat-label">SEO</div>
    <div class="stat-value">Title · OG image</div>
    <div class="stat-hint">за Google и социални</div>
  </a>
  <a class="card stat-card" href="<?= e(adminUrl('users')) ?>">
    <div class="stat-label">Потребители</div>
    <div class="stat-value">Администратори · Мениджъри</div>
    <div class="stat-hint">управление на акаунти</div>
  </a>
  <?php endif; ?>
</div>

<?php if ($recent): ?>
<section class="card no-padding" style="margin-top:24px;">
  <div style="padding:18px 24px 6px;">
    <h2 style="margin:0;">Последни поръчки</h2>
  </div>
  <table class="table">
    <thead>
      <tr><th>№</th><th>Клиент</th><th>Сума</th><th>Статус</th><th>Получена</th></tr>
    </thead>
    <tbody>
      <?php foreach ($recent as $o): ?>
        <tr onclick="window.location='<?= e(adminUrl('orders/' . $o['id'])) ?>'" style="cursor:pointer;">
          <td data-label="№"><strong>№ <?= e($o['order_number']) ?></strong></td>
          <td data-label="Клиент"><?= e($o['customer_name']) ?> <span class="muted small"><?= e($o['customer_email']) ?></span></td>
          <td data-label="Сума"><?= e(Order::formatMoney((float) $o['total'], $o['currency'])) ?></td>
          <td data-label="Статус"><span class="status status-<?= e($o['status']) ?>"><?= e(Order::statusLabel($o['status'])) ?></span></td>
          <td data-label="Получена" class="muted small"><?= e(date('d.m.Y H:i', strtotime($o['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
