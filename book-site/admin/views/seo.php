<?php /** @var array $values */ ?>
<?php $title = 'SEO'; ob_start(); ?>
<div class="page-head">
  <h1>SEO &amp; социални мрежи</h1>
  <p class="muted">
    Заглавие и описание за резултатите в Google + open graph данни за Facebook / LinkedIn / Twitter.
    Препоръчителни дължини: title до 60 символа, description до 160 символа.
  </p>
</div>

<form method="post">
  <?= \App\Csrf::field() ?>

  <section class="card">
    <h2>Google резултати</h2>
    <label>
      <span>Заглавие на страницата (title)</span>
      <input type="text" name="seo_title" value="<?= e($values['seo_title'] ?? '') ?>" maxlength="80" placeholder="Разширена химия на ноктите — Дъг Шун">
    </label>
    <label>
      <span>Meta description</span>
      <textarea name="seo_description" rows="3" maxlength="200" placeholder="Кратко описание за резултатите в Google..."><?= e($values['seo_description'] ?? '') ?></textarea>
    </label>
  </section>

  <section class="card">
    <h2>Open Graph (Facebook / LinkedIn / Twitter)</h2>
    <label>
      <span>OG title</span>
      <input type="text" name="seo_og_title" value="<?= e($values['seo_og_title'] ?? '') ?>" maxlength="100">
    </label>
    <label>
      <span>OG description</span>
      <textarea name="seo_og_description" rows="3" maxlength="220"><?= e($values['seo_og_description'] ?? '') ?></textarea>
    </label>
    <label>
      <span>OG image (път от корена на сайта)</span>
      <input type="text" name="seo_og_image" value="<?= e($values['seo_og_image'] ?? '/assets/cover.png') ?>" placeholder="/assets/cover.png">
      <p class="muted small" style="margin-top:6px;">Препоръка: 1200×630 px. Качете изображението в „Изображения" или в <code>/uploads/</code> и тук поставете пътя.</p>
    </label>
  </section>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Запази SEO</button>
  </div>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
