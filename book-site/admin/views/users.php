<?php /** @var array $users @var array $me @var int $adminCount */ ?>
<?php
use App\User;
$title = 'Потребители'; ob_start();
?>
<div class="page-head">
  <div class="head-row">
    <div>
      <h1>Потребители <span class="badge"><?= e((string) count($users)) ?></span></h1>
      <p class="muted small" style="margin-top:6px;">Активни администратори: <strong><?= e((string) $adminCount) ?></strong></p>
    </div>
    <a class="btn btn-primary" href="<?= e(adminUrl('users/new')) ?>">+ Нов потребител</a>
  </div>
</div>

<div class="card no-padding">
  <table class="table">
    <thead>
      <tr>
        <th>Име</th>
        <th>Имейл</th>
        <th>Роля</th>
        <th>Последен вход</th>
        <th>Създаден</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): $isMe = ((int) $u['id'] === (int) $me['id']); ?>
        <tr>
          <td>
            <strong><?= e($u['name']) ?></strong>
            <?php if ($isMe): ?><span class="badge" style="background:var(--ink);color:var(--bg);">Вие</span><?php endif; ?>
          </td>
          <td class="muted"><?= e($u['email']) ?></td>
          <td><span class="status status-<?= $u['role'] === 'admin' ? 'delivered' : 'preparing' ?>"><?= e(User::ROLE_LABELS[$u['role']] ?? $u['role']) ?></span></td>
          <td class="muted small">
            <?= $u['last_login_at'] ? e(date('d.m.Y H:i', strtotime($u['last_login_at']))) : '—' ?>
          </td>
          <td class="muted small"><?= e(date('d.m.Y', strtotime($u['created_at']))) ?></td>
          <td>
            <?php if ($isMe): ?>
              <a href="<?= e(adminUrl('profile')) ?>" class="link">Профил →</a>
            <?php else: ?>
              <a href="<?= e(adminUrl('users/' . $u['id'])) ?>" class="link">Редактирай →</a>
              <form method="post" action="<?= e(adminUrl('users/' . $u['id'] . '/delete')) ?>" style="display:inline;margin-left:8px;"
                    onsubmit="return confirm('Изтриване на „<?= e(addslashes($u['name'])) ?>'? Това действие е необратимо.')">
                <?= \App\Csrf::field() ?>
                <button type="submit" class="link-btn" style="color:var(--error);">Изтрий</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/_layout.php'; ?>
