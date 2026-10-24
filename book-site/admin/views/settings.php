<?php /** @var array $values @var int $cityCount */ ?>
<?php $title = 'Настройки'; ob_start(); ?>
<div class="page-head">
  <h1>Настройки</h1>
  <p class="muted">Тези стойности се пазят в базата данни (таблица <code>settings</code>) и се ползват от backend-а — Speedy за товарителници, SMTP за изходящи имейли.</p>
</div>

<form method="post" class="settings-form">
  <?= \App\Csrf::field() ?>

  <section class="card">
    <h2>Speedy API</h2>
    <p class="muted small">Получавате credentials-ите от Speedy след сключване на договор. Те се ползват само server-side за извличане на офиси и създаване на товарителници.</p>
    <div class="grid-2">
      <label>
        <span>Потребителско име (username)</span>
        <input type="text" name="speedy_username" value="<?= e($values['speedy_username'] ?? '') ?>" autocomplete="off">
      </label>
      <label>
        <span>Парола</span>
        <input type="password" name="speedy_password" value="<?= e($values['speedy_password'] ?? '') ?>" autocomplete="off">
      </label>
      <label class="full">
        <span>Client ID — <strong>задължително</strong> (от договора с Speedy)</span>
        <input type="number" name="speedy_client_id" value="<?= e($values['speedy_client_id'] ?? '') ?>" autocomplete="off">
      </label>
      <label>
        <span>Sender Object ID (по избор — предефиниран подател)</span>
        <input type="number" name="speedy_sender_object_id" value="<?= e($values['speedy_sender_object_id'] ?? '') ?>">
      </label>
      <label>
        <span>Service ID</span>
        <input type="number" name="speedy_service_id" value="<?= e($values['speedy_service_id'] ?? '505') ?>">
      </label>
      <label class="full">
        <span>Тегло на пратка (кг)</span>
        <input type="number" step="0.01" name="speedy_default_weight" value="<?= e($values['speedy_default_weight'] ?? '0.5') ?>">
      </label>
    </div>
    <p class="muted small" style="margin:14px 0 0;">
      <strong>Client ID</strong> е номерът на договора Ви със Speedy — задължителен е за товарителници и изчисляване на цена.
      Sender Object ID и Service ID получавате също от Speedy. Service 505 е стандартен куриерски пакет.
    </p>
  </section>

  <section class="card">
    <h2>SMTP (изходящи имейли)</h2>
    <p class="muted small">Препоръка: ползвайте SMTP акаунт от хостинга вместо PHP <code>mail()</code> — повишава deliverability.</p>
    <div class="grid-2">
      <label>
        <span>SMTP host</span>
        <input type="text" name="smtp_host" value="<?= e($values['smtp_host'] ?? '') ?>" placeholder="smtp.example.com">
      </label>
      <label>
        <span>Port</span>
        <input type="number" name="smtp_port" value="<?= e($values['smtp_port'] ?? '587') ?>" min="1" max="65535">
      </label>
      <label>
        <span>Потребител</span>
        <input type="text" name="smtp_user" value="<?= e($values['smtp_user'] ?? '') ?>" autocomplete="off">
      </label>
      <label>
        <span>Парола</span>
        <input type="password" name="smtp_pass" value="<?= e($values['smtp_pass'] ?? '') ?>" autocomplete="off">
      </label>
      <label>
        <span>Криптиране</span>
        <select name="smtp_secure">
          <?php $sec = $values['smtp_secure'] ?? 'tls'; ?>
          <option value="tls" <?= $sec === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
          <option value="ssl" <?= $sec === 'ssl' ? 'selected' : '' ?>>SSL/TLS</option>
          <option value=""    <?= $sec === ''    ? 'selected' : '' ?>>Без</option>
        </select>
      </label>
      <label>
        <span>From — имейл</span>
        <input type="email" name="smtp_from_email" value="<?= e($values['smtp_from_email'] ?? '') ?>" placeholder="info@etaloncare.bg">
      </label>
      <label class="full">
        <span>From — име</span>
        <input type="text" name="smtp_from_name" value="<?= e($values['smtp_from_name'] ?? 'ÉTALON') ?>">
      </label>
    </div>
  </section>

  <section class="card">
    <h2>НАП — одиторски файл (Приложение 38)</h2>
    <p class="muted small">Данни за хедъра на одиторския XML файл. Уникалният № се получава при регистрация на е-магазина в НАП (чл. 52р).</p>
    <div class="grid-2">
      <label><span>ЕИК на фирмата</span>
        <input type="text" name="nap_company_eik" value="<?= e($values['nap_company_eik'] ?? '') ?>"></label>
      <label><span>Уникален № на е-магазина (чл. 52р)</span>
        <input type="text" name="nap_shop_uid" value="<?= e($values['nap_shop_uid'] ?? '') ?>"></label>
      <label><span>Домейн на магазина</span>
        <input type="text" name="nap_shop_domain" value="<?= e($values['nap_shop_domain'] ?? 'etaloncare.com') ?>"></label>
      <label><span>Тип (1=собствен домейн, 2=платформа)</span>
        <input type="number" name="nap_shop_type" value="<?= e($values['nap_shop_type'] ?? '1') ?>"></label>
      <label><span>№ на ПОС терминал</span>
        <input type="text" name="nap_pos_terminal" value="<?= e($values['nap_pos_terminal'] ?? 'V5402893') ?>"></label>
      <label><span>Идентификатор на ПОС доставчик (proc_id)</span>
        <input type="text" name="nap_pos_provider_id" value="<?= e($values['nap_pos_provider_id'] ?? '') ?>"></label>
      <label><span>ДДС ставка (%)</span>
        <input type="number" name="nap_vat_rate" value="<?= e($values['nap_vat_rate'] ?? '9') ?>"></label>
      <label><span>Наименование на фирмата</span>
        <input type="text" name="nap_company_name" value="<?= e($values['nap_company_name'] ?? 'ГРЕЙТ УЪРК ООД') ?>"></label>
      <label><span>ДДС № (по избор)</span>
        <input type="text" name="nap_company_vat" value="<?= e($values['nap_company_vat'] ?? '') ?>"></label>
      <label class="full"><span>Адрес на фирмата</span>
        <input type="text" name="nap_company_address" value="<?= e($values['nap_company_address'] ?? '') ?>"></label>
    </div>
  </section>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Запази настройките</button>
  </div>
</form>

<section class="card" style="margin-top:24px;">
  <h2>Кеш на сайта</h2>
  <p class="muted small" style="margin-bottom:14px;">
    Текуща версия: <strong>v<?= e((string) \App\Cache::version()) ?></strong>.
    Натиснете „Изчисти кеша" след като промените текстове, изображения или SEO —
    ще принуди браузърите на потребителите да заредят новите версии вместо кешираните.
    Освен това ще изчисти server-side кеша на Speedy офисите и PHP opcache (където е достъпен).
  </p>
  <form method="post" action="<?= e(adminUrl('cache/bust')) ?>">
    <?= \App\Csrf::field() ?>
    <button type="submit" class="btn btn-primary">Изчисти кеша на сайта</button>
  </form>
</section>

<section class="card" style="margin-top:24px;">
  <h2>Speedy данни — синхронизация</h2>
  <p class="muted small" style="margin-bottom:14px;">
    В кеша: <strong><?= e((string) $cityCount) ?></strong> града.
    Синхронизацията изтегля актуалния списък градове от Speedy. Офисите се теглят
    при заявка от клиент и се кешират за 24ч.
  </p>
  <form method="post">
    <?= \App\Csrf::field() ?>
    <button type="submit" name="action" value="sync_speedy" class="btn"
            onclick="return confirm('Това ще презапише кеша с градове. Продължи?')">
      Синхронизирай градове от Speedy
    </button>
  </form>
</section>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
