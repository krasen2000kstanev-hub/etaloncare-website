<?php /** @var array $errors @var string $email @var string $name */ ?>
<?php $title = 'Първоначална настройка'; ob_start(); ?>
<div class="card auth-card">
  <img src="<?= e(publicUrl('assets/logo.png')) ?>" alt="ÉTALON" class="auth-logo">
  <h1>Първоначална настройка</h1>
  <p class="muted">Създайте първия администраторски акаунт. Тази стъпка е достъпна само веднъж.</p>

  <form method="post" novalidate>
    <?= \App\Csrf::field() ?>
    <label>
      <span>Име</span>
      <input type="text" name="name" value="<?= e($name) ?>" required>
      <?php if (!empty($errors['name'])): ?><em class="err"><?= e($errors['name']) ?></em><?php endif; ?>
    </label>
    <label>
      <span>Имейл</span>
      <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
      <?php if (!empty($errors['email'])): ?><em class="err"><?= e($errors['email']) ?></em><?php endif; ?>
    </label>
    <label>
      <span>Парола (мин. 8 символа)</span>
      <input type="password" name="password" autocomplete="new-password" required>
      <?php if (!empty($errors['password'])): ?><em class="err"><?= e($errors['password']) ?></em><?php endif; ?>
    </label>
    <button type="submit" class="btn btn-primary btn-wide">Създай акаунт</button>
  </form>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
