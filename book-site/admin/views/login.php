<?php /** @var string $email */ ?>
<?php $title = 'Вход'; ob_start(); ?>
<div class="card auth-card">
  <img src="<?= e(publicUrl('assets/logo.png')) ?>" alt="ÉTALON" class="auth-logo">
  <h1>Вход в администрацията</h1>

  <form method="post" novalidate>
    <?= \App\Csrf::field() ?>
    <label>
      <span>Имейл</span>
      <input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" autofocus required>
    </label>
    <label>
      <span>Парола</span>
      <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button type="submit" class="btn btn-primary btn-wide">Вход</button>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
