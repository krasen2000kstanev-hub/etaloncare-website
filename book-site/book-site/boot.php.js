window.__BASE = "";
window.__API = "/api";
window.__CONTENT_OVERRIDES = {"bg":[],"en":[]};
window.__MEDIA = {"logo":"/assets/logo.png","cover":"/assets/cover.png","author":"/assets/author.png","background":"/assets/background.jpg"};
window.__BORICA = true;
if (window.__MEDIA && window.__MEDIA.background) {
  document.documentElement.style.setProperty('--bg-image', "url('" + window.__MEDIA.background + "')");
}
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