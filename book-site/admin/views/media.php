<?php
use App\Media;
$title = 'Изображения'; ob_start();
?>
<div class="page-head">
  <h1>Изображения</h1>
  <p class="muted">
    Качете нови файлове за лого, корица, снимка на автора и фон. Позволени формати: JPG, PNG, WebP, SVG. Макс. 5MB.
    „Възстанови" връща оригиналното изображение от <code>/assets/</code>.
  </p>
</div>

<div class="media-grid">
  <?php foreach (Media::SLOTS as $slot => $meta):
    $current = Media::get($slot);
    $url = Media::urlFor($slot);
  ?>
  <section class="card media-card">
    <div class="media-head">
      <h2><?= e($meta['label']) ?></h2>
      <?php if ($current): ?>
        <span class="badge">персонализирано</span>
      <?php else: ?>
        <span class="muted small">по подразбиране</span>
      <?php endif; ?>
    </div>

    <div class="media-preview">
      <img src="<?= e($url) ?>?t=<?= e((string) ($current['size'] ?? 0)) ?>" alt="<?= e($meta['label']) ?>">
    </div>
    <div class="muted small media-meta">
      <?= e($url) ?>
      <?php if ($current && $current['size']): ?>
        · <?= e(number_format($current['size'] / 1024, 0)) ?>KB
      <?php endif; ?>
    </div>

    <form method="post" enctype="multipart/form-data" class="media-form">
      <?= \App\Csrf::field() ?>
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="slot" value="<?= e($slot) ?>">
      <input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" required>
      <button type="submit" class="btn btn-primary">Качи нов</button>
    </form>
    <?php if ($current): ?>
    <form method="post" class="media-reset-form">
      <?= \App\Csrf::field() ?>
      <input type="hidden" name="action" value="reset">
      <input type="hidden" name="slot" value="<?= e($slot) ?>">
      <button type="submit" class="btn"
              onclick="return confirm('Възстановяване към оригинала. Продължи?')">
        Възстанови оригинала
      </button>
    </form>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
