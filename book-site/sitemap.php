<?php
// Dynamic sitemap.xml — generates the sitemap from the canonical app URL.
// Routed here from sitemap.xml via .htaccess.
// Resilient: if DB / bootstrap fails we still emit a valid sitemap with the
// canonical fallback URL (matches the canonical declared in index.php).

$base = 'https://www.etaloncare.bg';
try {
    require __DIR__ . '/app/bootstrap.php';
    $base = rtrim(appUrl(), '/');
} catch (\Throwable $e) {
    error_log('sitemap.php bootstrap failed, using canonical fallback: ' . $e->getMessage());
}

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex'); // do not index the sitemap itself

$lastmod = date('Y-m-d', filemtime(__DIR__ . '/index.php'));

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
  <url>
    <loc><?= htmlspecialchars($base . '/', ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= $lastmod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
    <xhtml:link rel="alternate" hreflang="bg" href="<?= htmlspecialchars($base . '/', ENT_XML1, 'UTF-8') ?>"/>
    <xhtml:link rel="alternate" hreflang="en" href="<?= htmlspecialchars($base . '/', ENT_XML1, 'UTF-8') ?>"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($base . '/', ENT_XML1, 'UTF-8') ?>"/>
  </url>
</urlset>
