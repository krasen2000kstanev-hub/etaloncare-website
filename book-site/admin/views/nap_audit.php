<?php /** @var string $from @var string $to @var string $pay @var ?array $report @var string $error */ ?>
<?php $title = 'Одиторски файл (НАП)'; ob_start(); ?>
<div class="page-head"><h1>Одиторски файл (НАП)</h1>
  <p class="muted">Приложение 38 / Наредба Н-18 — поръчки, <strong>доставени</strong> през избрания месец. Файлът е валиден по XSD на НАП и се подава 1–15 число на следващия месец.</p>
</div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<form method="get" class="card nap-filters" action="<?= e(adminUrl('nap-audit')) ?>">
  <label><span>Начална дата</span>
    <input type="text" id="nap-from" name="from" value="<?= e($from) ?>" autocomplete="off" readonly></label>
  <label><span>Крайна дата</span>
    <input type="text" id="nap-to" name="to" value="<?= e($to) ?>" autocomplete="off" readonly></label>
  <label><span>Плащане</span>
    <select name="pay">
      <option value="all" <?= $pay === 'all' ? 'selected' : '' ?>>И двете</option>
      <option value="card" <?= $pay === 'card' ? 'selected' : '' ?>>Карта</option>
      <option value="cod" <?= $pay === 'cod' ? 'selected' : '' ?>>Наложен платеж</option>
    </select></label>
  <button type="submit" class="btn">Покажи</button>
</form>

<?php if ($report !== null): $orders = $report['orders']; $total = 0.0;
  foreach ($orders as $o) foreach ($o['items'] as $it) $total += $it['qty'] * $it['unit_price']; ?>
  <div class="card no-padding">
    <table class="table">
      <thead><tr><th>№ поръчка</th><th>Дата</th><th>Плащане</th><th>Сума (с ДДС)</th></tr></thead>
      <tbody>
        <?php if (!$orders): ?>
          <tr><td colspan="4" class="empty">Няма доставени поръчки за избрания период.</td></tr>
        <?php else: foreach ($orders as $o): $s = 0.0; foreach ($o['items'] as $it) $s += $it['qty'] * $it['unit_price']; ?>
          <tr>
            <td data-label="№ поръчка"><?= e($o['ord_n']) ?></td>
            <td data-label="Дата"><?= e($o['ord_d']) ?></td>
            <td data-label="Плащане"><?= $o['paym'] === 2 ? 'Карта' : 'Наложен платеж' ?></td>
            <td data-label="Сума"><?= e(number_format($s, 2, ',', ' ')) ?> €</td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <p class="muted">Общо поръчки: <strong><?= count($orders) ?></strong> · Обща сума: <strong><?= e(number_format($total, 2, ',', ' ')) ?> €</strong></p>
  <?php if ($orders): ?>
    <a class="btn btn-primary" href="<?= e(adminUrl('nap-audit/export?from=' . urlencode($from) . '&to=' . urlencode($to) . '&pay=' . $pay)) ?>">Свали XML</a>
  <?php endif; ?>
<?php endif; ?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
jQuery(function ($) {
  $.datepicker.regional['bg'] = {
    closeText: 'Затвори', prevText: '<Назад', nextText: 'Напред>', currentText: 'Днес',
    monthNames: ['Януари','Февруари','Март','Април','Май','Юни','Юли','Август','Септември','Октомври','Ноември','Декември'],
    monthNamesShort: ['Яну','Фев','Мар','Апр','Май','Юни','Юли','Авг','Сеп','Окт','Ное','Дек'],
    dayNames: ['Неделя','Понеделник','Вторник','Сряда','Четвъртък','Петък','Събота'],
    dayNamesShort: ['Нед','Пон','Вто','Сря','Чет','Пет','Съб'],
    dayNamesMin: ['Нд','Пн','Вт','Ср','Чт','Пт','Сб'],
    weekHeader: 'Сед', dateFormat: 'yy-mm-dd', firstDay: 1, isRTL: false, showMonthAfterYear: false, yearSuffix: ''
  };
  $.datepicker.setDefaults($.datepicker.regional['bg']);
  $('#nap-from, #nap-to').datepicker({ firstDay: 1, dateFormat: 'yy-mm-dd' });
});
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
