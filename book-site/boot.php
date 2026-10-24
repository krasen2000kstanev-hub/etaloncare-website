<?php
declare(strict_types=1);

/**
 * Public bootstrap script — emits JS that injects content/media overrides
 * into the React frontend. Loaded BEFORE the app's React init so values
 * are available synchronously.
 *
 * If anything fails (DB down, etc.), emits an empty overrides object so
 * the site falls back to bundled defaults from translations.js.
 */

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$content = ['bg' => [], 'en' => []];
$media = [];
$base = '';
$apiBase = '/api';
$boricaEnabled = false;

try {
    require __DIR__ . '/app/bootstrap.php';
    \App\Migrate::run();
    $content = \App\Content::exportOverrides();
    $media   = \App\Media::exportPublic();
    $base    = basePath();
    $apiBase = apiUrl();
    $boricaEnabled = \App\Borica::isEnabled();
} catch (\Throwable $e) {
    error_log('boot.php failed: ' . $e->getMessage());
}

$enc = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

echo "window.__BASE = " . $enc($base) . ";\n";
echo "window.__API = " . $enc($apiBase) . ";\n";
echo "window.__CONTENT_OVERRIDES = " . $enc($content) . ";\n";
echo "window.__MEDIA = " . $enc($media) . ";\n";
echo "window.__BORICA = " . $enc($boricaEnabled) . ";\n";

// Apply background image override via CSS variable (so styles.css picks it up)
echo "if (window.__MEDIA && window.__MEDIA.background) {\n";
echo "  document.documentElement.style.setProperty('--bg-image', \"url('\" + window.__MEDIA.background + \"')\");\n";
echo "}\n";

// Deep-merge content overrides into TRANSLATIONS once it's loaded.
echo <<<'JS'
(function () {
  function deepMerge(target, source) {
    for (var k in source) {
      if (source[k] && typeof source[k] === 'object' && !Array.isArray(source[k])) {
        if (!target[k] || typeof target[k] !== 'object') target[k] = {};
        deepMerge(target[k], source[k]);
      } else if (source[k] !== undefined && source[k] !== null && source[k] !== '') {
        target[k] = source[k];
      }
    }
    return target;
  }
  function apply() {
    if (!window.TRANSLATIONS || !window.__CONTENT_OVERRIDES) return;
    if (window.__CONTENT_OVERRIDES.bg) deepMerge(window.TRANSLATIONS.bg || (window.TRANSLATIONS.bg = {}), window.__CONTENT_OVERRIDES.bg);
    if (window.__CONTENT_OVERRIDES.en) deepMerge(window.TRANSLATIONS.en || (window.TRANSLATIONS.en = {}), window.__CONTENT_OVERRIDES.en);
  }
  if (window.TRANSLATIONS) { apply(); }
  else { document.addEventListener('DOMContentLoaded', apply); }
})();
JS;
