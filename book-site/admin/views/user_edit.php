<?php /** @var string $mode @var ?array $user @var array $form @var array $errors */ ?>
<?php
use App\User;
$isCreate = $mode === 'create';
$title = $isCreate ? 'Нов потребител' : 'Редакция: ' . ($user['name'] ?? '');
ob_start();
?>
<div class="page-head">
  <a href="<?= e(adminUrl('users')) ?>" class="back-link">← Потребители</a>
  <h1><?= e($title) ?></h1>
  <?php if (!$isCreate): ?>
    <p class="muted small">ID: <?= e((string) $user['id']) ?> · Създаден: <?= e(date('d.m.Y H:i', strtotime($user['created_at']))) ?></p>
  <?php endif; ?>
</div>

<form method="post" class="user-form">
  <?= \App\Csrf::field() ?>

  <section class="card">
    <label>
      <span>Име</span>
      <input type="text" name="name" value="<?= e($form['name']) ?>" required>
      <?php if (!empty($errors['name'])): ?><em class="err"><?= e($errors['name']) ?></em><?php endif; ?>
    </label>
    <label>
      <span>Имейл</span>
      <input type="email" name="email" value="<?= e($form['email']) ?>" autocomplete="off" required>
      <?php if (!empty($errors['email'])): ?><em class="err"><?= e($errors['email']) ?></em><?php endif; ?>
    </label>
    <label>
      <span>Роля</span>
      <select name="role">
        <?php foreach (User::ROLES as $r): ?>
          <option value="<?= e($r) ?>" <?= $form['role'] === $r ? 'selected' : '' ?>><?= e(User::ROLE_LABELS[$r]) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['role'])): ?><em class="err"><?= e($errors['role']) ?></em><?php endif; ?>
      <p class="muted small" style="margin-top:6px;">
        <strong>Администратор:</strong> пълен достъп до всичко (потребители, настройки, текстове, медия, SEO).<br>
        <strong>Мениджър:</strong> само поръчки + собствен профил.
      </p>
    </label>
    <label>
      <span><?= $isCreate ? 'Парола' : 'Нова парола (оставете празно за да запазите текущата)' ?></span>
      <input type="password" name="password" autocomplete="new-password" <?= $isCreate ? 'required' : '' ?>>
      <?php if (!empty($errors['password'])): ?><em class="err"><?= e($errors['password']) ?></em><?php endif; ?>
      <p class="muted small" style="margin-top:6px;">Минимум 8 символа.</p>
    </label>
  </section>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $isCreate ? 'Създай потребител' : 'Запази промените' ?></button>
    <a href="<?= e(adminUrl('users')) ?>" class="btn">Отказ</a>
  </div>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
