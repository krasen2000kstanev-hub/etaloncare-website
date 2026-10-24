<?php $title = 'Не е намерено'; ob_start(); ?>
<div class="card auth-card">
  <h1>404</h1>
  <p class="muted">Страницата не съществува.</p>
  <a class="btn btn-primary" href="<?= e(adminUrl('dashboard')) ?>">Към таблото</a>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
