<?php /** @var array $me @var array $errors @var array $passErrors @var array $form */ ?>
<?php
use App\User;
$title = 'Моят профил'; ob_start();
?>
<div class="page-head">
  <h1>Моят профил</h1>
  <p class="muted small">
    Роля: <strong><?= e(User::ROLE_LABELS[$me['role']] ?? $me['role']) ?></strong>
    · Последен вход: <?= $me['last_login_at'] ? e(date('d.m.Y H:i', strtotime($me['last_login_at']))) : '—' ?>
  </p>
</div>

<form method="post" class="user-form">
  <?= \App\Csrf::field() ?>
  <input type="hidden" name="action" value="profile">

  <section class="card">
    <h2>Лични данни</h2>
    <label>
      <span>Име</span>
      <input type="text" name="name" value="<?= e($form['name'] ?? $me['name']) ?>" required>
      <?php if (!empty($errors['name'])): ?><em class="err"><?= e($errors['name']) ?></em><?php endif; ?>
    </label>
    <label>
      <span>Имейл</span>
      <input type="email" name="email" value="<?= e($form['email'] ?? $me['email']) ?>" required>
      <?php if (!empty($errors['email'])): ?><em class="err"><?= e($errors['email']) ?></em><?php endif; ?>
    </label>
    <button type="submit" class="btn btn-primary">Запази профила</button>
  </section>
</form>

<form method="post" class="user-form" style="margin-top:20px;">
  <?= \App\Csrf::field() ?>
  <input type="hidden" name="action" value="password">

  <section class="card">
    <h2>Смяна на парола</h2>
    <label>
      <span>Текуща парола</span>
      <input type="password" name="current" autocomplete="current-password" required>
      <?php if (!empty($passErrors['current'])): ?><em class="err"><?= e($passErrors['current']) ?></em><?php endif; ?>
    </label>
    <label>
      <span>Нова парола (мин. 8 символа)</span>
      <input type="password" name="new" autocomplete="new-password" required>
      <?php if (!empty($passErrors['new'])): ?><em class="err"><?= e($passErrors['new']) ?></em><?php endif; ?>
    </label>
    <button type="submit" class="btn btn-primary">Промени паролата</button>
  </section>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
