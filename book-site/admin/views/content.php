<?php /** @var array $data */ ?>
<?php
use App\Content;
$title = 'Текстове'; ob_start();

// Group editable keys by their `group` attribute
$grouped = [];
foreach (Content::EDITABLE as $key => $meta) {
    $grouped[$meta['group']][$key] = $meta;
}
?>
<div class="page-head">
  <h1>Текстове на сайта</h1>
  <p class="muted">
    Промените се прилагат веднага при следващо зареждане на сайта.
    Полета със сив бордюр са все още по подразбиране (от <code>translations.js</code>) — редактирай за да зададеш override.
    Изпразни полето и запази за да върнеш стойността по подразбиране.
    По-сложни секции (FAQ, биография, политика) се редактират в <code>translations.js</code> в кода.
  </p>
</div>

<form method="post" id="content-form">
  <?= \App\Csrf::field() ?>

  <?php foreach ($grouped as $groupName => $items): ?>
  <section class="card">
    <h2><?= e($groupName) ?></h2>
    <table class="content-grid">
      <thead>
        <tr>
          <th class="key-col"></th>
          <th>BG</th>
          <th>EN</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $key => $meta):
          $bg = $data[$key]['bg'] ?? '';
          $en = $data[$key]['en'] ?? '';
          $tag = $meta['long'] ? 'textarea' : 'input';
        ?>
        <tr>
          <td class="key-col">
            <span class="content-label"><?= e($meta['label']) ?></span>
            <code class="content-key"><?= e($key) ?></code>
          </td>
          <td>
            <?php if ($meta['long']): ?>
              <textarea name="values[<?= e($key) ?>][bg]" rows="3"><?= e($bg) ?></textarea>
            <?php else: ?>
              <input type="text" name="values[<?= e($key) ?>][bg]" value="<?= e($bg) ?>">
            <?php endif; ?>
          </td>
          <td>
            <?php if ($meta['long']): ?>
              <textarea name="values[<?= e($key) ?>][en]" rows="3"><?= e($en) ?></textarea>
            <?php else: ?>
              <input type="text" name="values[<?= e($key) ?>][en]" value="<?= e($en) ?>">
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endforeach; ?>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Запази промените</button>
  </div>
</form>

<script src="<?= e(assetUrl('translations.js')) ?>"></script>
<script>
(function () {
  // Pre-fill empty inputs with the current TRANSLATIONS default so admins
  // can see what's currently on the live site. Tag those inputs with
  // .is-default so:
  //   1) they get a grey border (visual cue that no override is set)
  //   2) on submit, we strip the value back to "" so the DB keeps using
  //      the default (empty override = falls through to translations.js).
  // The moment the admin edits the field, .is-default is removed.

  if (typeof window.TRANSLATIONS !== 'object') return;

  function lookup(locale, dottedKey) {
    var parts = dottedKey.split('.');
    var ref = window.TRANSLATIONS[locale];
    for (var i = 0; i < parts.length; i++) {
      if (ref == null || typeof ref !== 'object') return '';
      ref = ref[parts[i]];
    }
    return (typeof ref === 'string') ? ref : '';
  }

  var fields = document.querySelectorAll('#content-form input[name^="values["], #content-form textarea[name^="values["]');
  fields.forEach(function (el) {
    // name="values[hero.title][bg]"
    var m = el.name.match(/^values\[([^\]]+)\]\[(bg|en)\]$/);
    if (!m) return;
    var key = m[1], locale = m[2];
    var def = lookup(locale, key);
    el.dataset.default = def;
    if (el.value === '' && def !== '') {
      el.value = def;
      el.classList.add('is-default');
    }
    el.addEventListener('input', function () {
      el.classList.remove('is-default');
    });
  });

  var form = document.getElementById('content-form');
  if (form) {
    form.addEventListener('submit', function () {
      // Untouched fields go back to "" so we don't persist the default
      // as a redundant override row.
      fields.forEach(function (el) {
        if (el.classList.contains('is-default')) el.value = '';
      });
    });
  }
})();
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
