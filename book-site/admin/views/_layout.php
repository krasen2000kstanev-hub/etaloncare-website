<?php /** Layout — variables expected: $title (string), $content (string with already-rendered body) */ ?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title ?? 'Админ') ?> · ÉTALON</title>
<link rel="stylesheet" href="<?= e(assetUrl('admin/assets/admin.css')) ?>">
</head>
<body>
<?php $authed = \App\Auth::check(); ?>
<?php if ($authed): $isAdmin = \App\Auth::isAdmin(); ?>
<header class="topbar">
  <a href="<?= e(adminUrl('dashboard')) ?>" class="brand">
    <img src="<?= e(\App\Media::urlFor('logo')) ?>" alt="ÉTALON">
  </a>
  <button type="button" class="nav-toggle" aria-label="Меню" aria-expanded="false" aria-controls="topnav"
          onclick="var n=document.getElementById('topnav');var o=n.classList.toggle('open');this.setAttribute('aria-expanded',o?'true':'false');">☰</button>
  <nav class="topnav" id="topnav">
    <a href="<?= e(adminUrl('dashboard')) ?>">Табло</a>
    <a href="<?= e(adminUrl('orders')) ?>">Поръчки</a>
    <?php if ($isAdmin): ?>
      <a href="<?= e(adminUrl('content')) ?>">Текстове</a>
      <a href="<?= e(adminUrl('media')) ?>">Изображения</a>
      <a href="<?= e(adminUrl('seo')) ?>">SEO</a>
      <a href="<?= e(adminUrl('users')) ?>">Потребители</a>
      <a href="<?= e(adminUrl('settings')) ?>">Настройки</a>
      <a href="<?= e(adminUrl('nap-audit')) ?>">Одиторски файл</a>
    <?php endif; ?>
  </nav>
  <div class="userbar">
    <a class="user-name" href="<?= e(adminUrl('profile')) ?>"><?= e(\App\Auth::user()['name'] ?? '') ?></a>
    <form method="post" action="<?= e(adminUrl('logout')) ?>">
      <?= \App\Csrf::field() ?>
      <button type="submit" class="link-btn">Изход</button>
    </form>
  </div>
</header>
<?php endif; ?>

<main class="container <?= $authed ? '' : 'narrow' ?>">
  <?php foreach (\App\Flash::pull() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
  <?= $content ?? '' ?>
</main>
</body>
</html>
