<?php
// Server-rendered SEO meta from DB. Falls back to defaults if backend unavailable.
$seo = [
    'title'          => 'Разширена химия на ноктите — Дъг Шун',
    'description'    => 'Съвременно образователно ръководство за продуктите и структурата на нокътя.',
    'og_title'       => 'Разширена химия на ноктите',
    'og_description' => 'Наука и факти за продуктите за нокти — за любознателни умове и отдадените професионалисти.',
    'og_image'       => '/assets/cover.png',
];
$canonical = 'https://www.etaloncare.bg';
$bookPrice = 69.00;
try {
    require __DIR__ . '/app/bootstrap.php';
    \App\Migrate::run();
    $stored = \App\Settings::getMany([
        'seo_title', 'seo_description', 'seo_og_title', 'seo_og_description', 'seo_og_image',
    ]);
    foreach ([
        'seo_title' => 'title', 'seo_description' => 'description',
        'seo_og_title' => 'og_title', 'seo_og_description' => 'og_description',
        'seo_og_image' => 'og_image',
    ] as $k => $f) {
        if (!empty($stored[$k])) $seo[$f] = $stored[$k];
    }
    $canonical = $GLOBALS['__config']['app']['url'] ?? $canonical;
    $bookPrice = (float)\App\Order::PRICE_PER_UNIT;
} catch (\Throwable $e) {
    error_log('index.php SEO load failed: ' . $e->getMessage());
}
$h = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$canonicalRoot = rtrim($canonical, '/');
$coverUrl = $canonicalRoot . $seo['og_image'];
$logoUrl  = $canonicalRoot . '/assets/logo.png';

// FAQ data mirrors translations.js (BG canonical) — used for FAQPage JSON-LD.
$faqItems = [
    ['q' => 'За кого е подходяща книгата?', 'a' => 'Създадена е за професионалисти в индустрията за нокти — маникюристи, преподаватели, дистрибутори и регулатори — но е написана така, че да е достъпна за всеки любознателен читател със сериозен интерес към темата.'],
    ['q' => 'На какъв език е изданието?', 'a' => 'Настоящото издание е превод на български от оригиналното англоезично издание на Дъг Шун. Преводът е направен от професионалисти с познания в областта.'],
    ['q' => 'Как се извършва доставката?', 'a' => 'Доставяме чрез куриерска фирма до адрес или офис на куриер в рамките на 1–3 работни дни в България. Доставка за ЕС — 5–10 работни дни.'],
    ['q' => 'Какви са начините на плащане?', 'a' => 'Поддържаме наложен платеж (плащане при получаване) и плащане с дебитна/кредитна карта.'],
    ['q' => 'Мога ли да върна книгата?', 'a' => 'Имате право да откажете поръчката в рамките на 14 дни от получаването, при условие че книгата е в оригиналния си вид.'],
    ['q' => 'Предлагате ли количествени отстъпки за училища и салони?', 'a' => 'Да — за поръчки над 10 броя свържете се с нас чрез формата за контакт за индивидуална оферта.'],
];

// JSON-LD structured data: Book, Product, Organization, WebSite, FAQPage
$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Book',
            '@id' => $canonicalRoot . '#book',
            'name' => 'Разширена химия на ноктите',
            'alternateName' => 'Advanced Chemistry of Nails',
            'author' => [
                '@type' => 'Person',
                'name' => 'Дъг Шун',
                'alternateName' => 'Doug Schoon',
                'description' => 'Магистър по химия (UC Irvine), 35+ години опит в козметичната индустрия, основател на CATS.',
            ],
            'inLanguage' => 'bg',
            'bookFormat' => 'https://schema.org/Paperback',
            'numberOfPages' => 320,
            'publisher' => [
                '@type' => 'Organization',
                '@id' => $canonicalRoot . '#org',
            ],
            'image' => $coverUrl,
            'description' => $seo['description'],
            'url' => $canonical,
        ],
        [
            '@type' => 'Product',
            '@id' => $canonicalRoot . '#product',
            'name' => 'Разширена химия на ноктите — меки корици',
            'image' => $coverUrl,
            'description' => $seo['og_description'],
            'sku' => 'BOOK-ACN-BG-2026',
            'brand' => [
                '@type' => 'Brand',
                'name' => 'ÉTALON',
            ],
            'category' => 'Books > Health & Beauty > Nail Care',
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => number_format($bookPrice, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => $canonical,
                'seller' => [
                    '@type' => 'Organization',
                    '@id' => $canonicalRoot . '#org',
                ],
            ],
        ],
        [
            '@type' => 'Organization',
            '@id' => $canonicalRoot . '#org',
            'name' => 'ГРЕЙТ УЪРК ООД',
            'alternateName' => 'GREAT WORK Ltd.',
            'legalName' => 'ГРЕЙТ УЪРК ООД',
            'url' => $canonical,
            'logo' => $logoUrl,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'ул. Лисец 1',
                'addressLocality' => 'Перник',
                'postalCode' => '2300',
                'addressCountry' => 'BG',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+359 897 886 840',
                'email' => 'info@etaloncare.com',
                'contactType' => 'customer service',
                'availableLanguage' => ['bg', 'en'],
            ],
            'taxID' => '206456831',
        ],
        [
            '@type' => 'WebSite',
            '@id' => $canonicalRoot . '#website',
            'url' => $canonical,
            'name' => 'ÉTALON — Разширена химия на ноктите',
            'inLanguage' => ['bg', 'en'],
            'publisher' => [
                '@id' => $canonicalRoot . '#org',
            ],
        ],
        [
            '@type' => 'FAQPage',
            '@id' => $canonicalRoot . '#faq',
            'mainEntity' => array_map(fn($item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['a'],
                ],
            ], $faqItems),
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= $h($seo['title']) ?></title>
<meta name="description" content="<?= $h($seo['description']) ?>">
<meta name="keywords" content="разширена химия на ноктите, Дъг Шун, Doug Schoon, химия на ноктите, нокти, маникюр, гел лак, акрилни нокти, нокти образование, ÉTALON, нокти професионалисти, книга нокти, advanced chemistry of nails, nail products science">
<meta name="author" content="Doug Schoon">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<meta name="theme-color" content="#F6EFE4">
<link rel="canonical" href="<?= $h($canonical) ?>">

<link rel="alternate" hreflang="bg" href="<?= $h($canonical) ?>">
<link rel="alternate" hreflang="en" href="<?= $h($canonical) ?>">
<link rel="alternate" hreflang="x-default" href="<?= $h($canonical) ?>">

<meta property="og:type" content="book">
<meta property="og:site_name" content="ÉTALON">
<meta property="og:title" content="<?= $h($seo['og_title']) ?>">
<meta property="og:description" content="<?= $h($seo['og_description']) ?>">
<meta property="og:image" content="<?= $h($coverUrl) ?>">
<meta property="og:image:alt" content="Корица на „Разширена химия на ноктите“ от Дъг Шун">
<meta property="og:url" content="<?= $h($canonical) ?>">
<meta property="og:locale" content="bg_BG">
<meta property="og:locale:alternate" content="en_US">
<meta property="book:author" content="Doug Schoon">
<meta property="book:release_date" content="2026">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $h($seo['og_title']) ?>">
<meta name="twitter:description" content="<?= $h($seo['og_description']) ?>">
<meta name="twitter:image" content="<?= $h($coverUrl) ?>">
<meta name="twitter:image:alt" content="Корица на „Разширена химия на ноктите“ от Дъг Шун">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="image" href="<?= $h($coverUrl) ?>" fetchpriority="high">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,500;0,8..60,600;1,8..60,400&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $h(assetUrl('styles.css')) ?>">

<!-- Boot UX:
     1. .seo-only — SEO content visually hidden (WCAG sr-only pattern); crawlers + AT still index it.
     2. .boot-spinner — full-screen spinner shown during React mount; React replaces #root contents on mount, removing the spinner.
     3. <noscript> — graceful fallback: if JS is disabled, hide the spinner and show SEO content as a plain readable page. -->
<style>
.seo-only{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important;}
.boot-spinner{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:#F6EFE4;z-index:9999;}
.boot-spinner::before{content:"";display:block;width:36px;height:36px;border:3px solid rgba(27,20,16,0.15);border-top-color:#1B1410;border-radius:50%;animation:bootspin 0.8s linear infinite;}
@keyframes bootspin{to{transform:rotate(360deg);}}
</style>
<noscript><style>
.seo-only{position:static!important;width:auto!important;height:auto!important;margin:0 auto!important;padding:40px 24px!important;max-width:820px;overflow:visible!important;clip:auto!important;white-space:normal!important;font-family:Georgia,serif;line-height:1.6;}
.boot-spinner{display:none!important;}
</style></noscript>

<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>

<!-- Google tag (gtag.js) with Consent Mode v2 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-QVF66VV1VR"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}

  // Deny by default until the user opts in via the cookie banner.
  gtag('consent', 'default', {
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
    analytics_storage: 'denied'
  });

  // Restore prior decision (banner stores it under the same key).
  try {
    var __consent = localStorage.getItem('acn-cookie-consent');
    if (__consent === 'accepted') {
      gtag('consent', 'update', {
        ad_storage: 'granted',
        ad_user_data: 'granted',
        ad_personalization: 'granted',
        analytics_storage: 'granted'
      });
    }
  } catch(e) {}

  gtag('js', new Date());
  gtag('config', 'G-QVF66VV1VR');
</script>
</head>
<body>
<div id="root">
  <!-- Boot spinner: visible until React mounts (createRoot.render() clears #root's children).
       aria-hidden because it's a transient UI affordance, not content for AT users. -->
  <div class="boot-spinner" aria-hidden="true" role="presentation"></div>
  <!-- Server-rendered SEO content. Visually hidden via .seo-only (sr-only pattern)
       so users don't see a flash of unstyled content; React replaces it on mount;
       crawlers + screen readers still index it. <noscript> shows it if JS is off. -->
  <div class="seo-only">
  <header>
    <h1>Разширена химия на ноктите — Дъг Шун</h1>
    <p>Съвременно образователно ръководство за продуктите и структурата на нокътя — българско издание.</p>
  </header>
  <main>
    <section>
      <h2>За книгата</h2>
      <p>Това ръководство събира над три десетилетия лабораторна работа, публикации и консултации на Дъг Шун в една достъпна, но задълбочена книга. Тя обяснява какво наистина се случва под повърхността — от молекулите на мономерите до микроструктурата на нокътя.</p>
      <ul>
        <li>Меки корици · 320 страници · 12 глави</li>
        <li>На български език</li>
        <li>Цена: <?= $h(number_format($bookPrice, 2, ',', ' ')) ?> €</li>
        <li>Издание на ÉTALON / ГРЕЙТ УЪРК ООД (2026)</li>
      </ul>
    </section>
    <section>
      <h2>За автора</h2>
      <p>Дъг Шун (Doug Schoon) — магистър по химия от UC Irvine, с над 35 години опит в козметичната индустрия. Основател на Chemical Awareness Training Service (CATS) и автор на серия книги и научни публикации в областта на химията на ноктите и козметичните продукти.</p>
    </section>
    <section>
      <h2>Често задавани въпроси</h2>
      <?php foreach ($faqItems as $item): ?>
        <h3><?= $h($item['q']) ?></h3>
        <p><?= $h($item['a']) ?></p>
      <?php endforeach; ?>
    </section>
    <section>
      <h2>Контакти</h2>
      <p>ГРЕЙТ УЪРК ООД, гр. Перник, ул. Лисец 1. Имейл: <a href="mailto:info@etaloncare.com">info@etaloncare.com</a>. Телефон: <a href="tel:+359897886840">+359 897 886 840</a>.</p>
    </section>
    <nav aria-label="Бърза навигация">
      <a href="#book">За книгата</a> ·
      <a href="#author">За автора</a> ·
      <a href="#faq">Въпроси</a> ·
      <a href="#contact">Контакти</a>
    </nav>
  </main>
  </div>
</div>

<script src="https://unpkg.com/react@18.3.1/umd/react.development.js" integrity="sha384-hD6/rw4ppMLGNu3tX5cjIb+uRZ7UkRJ6BPkLpg4hAu/6onKUg4lLsHAs9EBPT82L" crossorigin="anonymous"></script>
<script src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.development.js" integrity="sha384-u6aeetuaXnQ38mYT8rp6sbXaQe3NL9t+IBXmnYxwkUI2Hw4bsp2Wvmx4yRQF1uAm" crossorigin="anonymous"></script>
<script src="https://unpkg.com/@babel/standalone@7.29.0/babel.min.js" integrity="sha384-m08KidiNqLdpJqLq95G/LEi8Qvjl/xUYll3QILypMoQ65QorJ9Lvtp2RXYGBFj1y" crossorigin="anonymous"></script>

<script>
// Tweaks defaults block
window.__TWEAKS = /*EDITMODE-BEGIN*/{
  "heroVariant": "A"
}/*EDITMODE-END*/;
</script>

<script src="boot.php"></script>
<script src="<?= $h(assetUrl('translations.js')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('components.jsx')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('hero.jsx')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('sections.jsx')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('order-modal.jsx')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('privacy.jsx')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('terms.jsx')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('shipping.jsx')) ?>"></script>
<script type="text/babel" src="<?= $h(assetUrl('returns.jsx')) ?>"></script>

<script type="text/babel">
const { useState, useEffect, useRef } = React;

function Nav({ t, onOrder }) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const navRef = useRef(null);

  useEffect(() => {
    if (!mobileOpen) return;
    function onKey(e) { if (e.key === 'Escape') setMobileOpen(false); }
    function onDown(e) {
      if (navRef.current && !navRef.current.contains(e.target)) setMobileOpen(false);
    }
    window.addEventListener('keydown', onKey);
    document.addEventListener('mousedown', onDown);
    return () => {
      window.removeEventListener('keydown', onKey);
      document.removeEventListener('mousedown', onDown);
    };
  }, [mobileOpen]);

  function goHome(e) {
    e.preventDefault();
    setMobileOpen(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (window.location.hash) {
      history.replaceState(null, '', window.location.pathname + window.location.search);
    }
  }
  function closeMenu() { setMobileOpen(false); }

  const home = (typeof window !== 'undefined' && window.__BASE) ? window.__BASE : '/';
  return (
    <nav className="nav" ref={navRef}>
      <div className="nav-inner">
        <a href={home} onClick={goHome} className="nav-brand" aria-label="ÉTALON">
          <img src={media('logo', 'assets/logo.png')} alt="ÉTALON salon &amp; academy" className="nav-logo" />
        </a>
        <div id="mobile-nav" className={`nav-links ${mobileOpen ? 'open' : ''}`}>
          <a href="#book" onClick={closeMenu}>{t.nav.book}</a>
          <a href="#author" onClick={closeMenu}>{t.nav.author}</a>
          <a href="#faq" onClick={closeMenu}>{t.nav.faq}</a>
          <a href="#contact" onClick={closeMenu}>{t.nav.contact}</a>
        </div>
        <div className="nav-right">
          <button className="btn btn-primary nav-order-btn" onClick={onOrder}>
            <Icon name="cart" size={16} />
            <span className="nav-order-full">{t.nav.orderBtn}</span>
            <span className="nav-order-short">{t.nav.order}</span>
          </button>
          <button
            type="button"
            className={`nav-hamburger ${mobileOpen ? 'open' : ''}`}
            onClick={() => setMobileOpen(o => !o)}
            aria-expanded={mobileOpen}
            aria-controls="mobile-nav"
            aria-label={mobileOpen ? t.nav.menuClose : t.nav.menuOpen}
          >
            <span className="hamburger-line" aria-hidden="true"></span>
            <span className="hamburger-line" aria-hidden="true"></span>
            <span className="hamburger-line" aria-hidden="true"></span>
          </button>
        </div>
      </div>
    </nav>
  );
}

function LangSwitch({ lang, setLang }) {
  return (
    <div className="lang-switch" role="group" aria-label="Language">
      <button className={lang === 'bg' ? 'active' : ''} onClick={() => setLang('bg')} aria-pressed={lang === 'bg'}>BG</button>
      <button className={lang === 'en' ? 'active' : ''} onClick={() => setLang('en')} aria-pressed={lang === 'en'}>EN</button>
    </div>
  );
}

function VisaMark() {
  return (
    <svg className="card-mark" viewBox="0 0 80 28" width="56" height="20" role="img" aria-label="Visa">
      <rect width="80" height="28" rx="4" fill="#fff" />
      <text x="40" y="21" textAnchor="middle"
        fontFamily="Arial, Helvetica, sans-serif"
        fontWeight="900" fontStyle="italic" fontSize="20"
        fill="#1A1F71" letterSpacing="0.5">VISA</text>
    </svg>
  );
}

function MastercardMark() {
  return (
    <svg className="card-mark" viewBox="0 0 60 38" width="42" height="26" role="img" aria-label="Mastercard">
      <rect width="60" height="38" rx="4" fill="#fff" />
      <circle cx="22" cy="19" r="11.5" fill="#EB001B" />
      <circle cx="38" cy="19" r="11.5" fill="#F79E1B" />
      <path d="M30 10.74 A11.5 11.5 0 0 1 30 27.26 A11.5 11.5 0 0 1 30 10.74 Z" fill="#FF5F00" />
    </svg>
  );
}

function UniCreditMark() {
  return (
    <img className="card-mark card-mark-unicredit"
         src="<?= $h(assetUrl('assets/unicredit-bulbank-logo.svg')) ?>"
         alt="UniCredit Bulbank"
         height="22" />
  );
}

function Footer({ t, onPrivacy, onTerms, onShipping, onReturns }) {
  return (
    <footer className="footer">
      <div className="footer-inner">
        <div className="footer-col">
          <div>{t.footer.rights}</div>
          <div className="footer-payments">
            <div className="payment-marks">
              <VisaMark />
              <MastercardMark />
              <UniCreditMark />
            </div>
            <span className="payment-label">{t.footer.paymentsLabel}</span>
          </div>
        </div>
        <div className="footer-groups">
          <div className="footer-group">
            <div className="footer-group-title">{t.footer.headingInfo}</div>
            <button type="button" className="footer-link-btn" onClick={onShipping}>{t.footer.shipping}</button>
            <button type="button" className="footer-link-btn" onClick={onReturns}>{t.footer.returns}</button>
            <a href="#contact" className="footer-link-btn">{t.footer.contact}</a>
          </div>
          <div className="footer-group">
            <div className="footer-group-title">{t.footer.headingLegal}</div>
            <button type="button" className="footer-link-btn" onClick={onPrivacy}>{t.footer.privacy}</button>
            <button type="button" className="footer-link-btn" onClick={onTerms}>{t.footer.terms}</button>
          </div>
        </div>
      </div>
    </footer>
  );
}

function TweaksPanel({ variant, setVariant, visible }) {
  if (!visible) return null;
  return (
    <div className="tweaks-panel">
      <div className="tweaks-title">Tweaks · Hero layout</div>
      <div className="tweaks-row">
        {['A', 'B', 'C'].map(v => (
          <button key={v} className={variant === v ? 'active' : ''} onClick={() => setVariant(v)}>
            {v === 'A' ? 'Split' : v === 'B' ? 'Centered' : 'Magazine'}
          </button>
        ))}
      </div>
      <div className="tweaks-hint">Изберете компоновка за Hero секцията.</div>
    </div>
  );
}

function PaymentResultModal({ result, t, onClose }) {
  useEffect(() => {
    if (!result) return;
    document.body.style.overflow = 'hidden';
    const onKey = (e) => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', onKey);
    return () => { document.body.style.overflow = ''; window.removeEventListener('keydown', onKey); };
  }, [result]);
  if (!result) return null;
  const pr = (t.order && t.order.paymentResult) || {};
  const ok = result.status === 'success';
  const body = ((ok ? pr.successBody : pr.failedBody) || '').replace('{order}', result.order || '');
  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(e) => e.stopPropagation()}>
        <button className="modal-close" onClick={onClose} aria-label="Close"><Icon name="close" size={20} /></button>
        <div className="order-success">
          <div className="success-check"><Icon name={ok ? 'check' : 'close'} size={36} /></div>
          <h2 className="modal-title">{ok ? pr.successTitle : pr.failedTitle}</h2>
          <p className="modal-body">{body}</p>
          {result.order && (
            <div className="order-no">
              <span>{t.order.orderNo}</span>
              <strong>{result.order}</strong>
            </div>
          )}
          <button className="btn btn-primary" onClick={onClose}>{pr.close}</button>
        </div>
      </div>
    </div>
  );
}

function App() {
  const [lang, setLang] = useState(() => localStorage.getItem('acn-lang') || 'bg');
  const [variant, setVariant] = useState(window.__TWEAKS.heroVariant || 'A');
  const [modalOpen, setModalOpen] = useState(false);
  const [privacyOpen, setPrivacyOpen] = useState(false);
  const [termsOpen, setTermsOpen] = useState(false);
  const [shippingOpen, setShippingOpen] = useState(false);
  const [returnsOpen, setReturnsOpen] = useState(false);
  const [tweaksVisible, setTweaksVisible] = useState(false);
  const [payResult, setPayResult] = useState(() => {
    const p = new URLSearchParams(window.location.search);
    const r = p.get('payment');
    return (r === 'success' || r === 'failed') ? { status: r, order: p.get('order') || '' } : null;
  });

  function closePayResult() {
    setPayResult(null);
    window.history.replaceState({}, '', window.location.pathname);
  }

  const t = window.TRANSLATIONS[lang];

  useEffect(() => {
    localStorage.setItem('acn-lang', lang);
    document.documentElement.lang = lang;
  }, [lang]);

  // Edit-mode protocol
  useEffect(() => {
    function onMsg(e) {
      const d = e.data || {};
      if (d.type === '__activate_edit_mode') setTweaksVisible(true);
      if (d.type === '__deactivate_edit_mode') setTweaksVisible(false);
    }
    window.addEventListener('message', onMsg);
    // Announce availability AFTER listener is live
    try { window.parent.postMessage({ type: '__edit_mode_available' }, '*'); } catch(e) {}
    return () => window.removeEventListener('message', onMsg);
  }, []);

  function changeVariant(v) {
    setVariant(v);
    try {
      window.parent.postMessage({ type: '__edit_mode_set_keys', edits: { heroVariant: v } }, '*');
    } catch(e) {}
  }

  function onOrder() { setModalOpen(true); }
  function onExcerpt() {
    document.getElementById('book')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  return (
    <>
      <Nav t={t} onOrder={onOrder} />
      <LangSwitch lang={lang} setLang={setLang} />
      <Hero variant={variant} t={t} onOrder={onOrder} onExcerpt={onExcerpt} />
      <About t={t} />
      <Author t={t} />
      <FAQ t={t} />
      <Contact t={t} />
      <Footer
        t={t}
        onPrivacy={() => setPrivacyOpen(true)}
        onTerms={() => setTermsOpen(true)}
        onShipping={() => setShippingOpen(true)}
        onReturns={() => setReturnsOpen(true)}
      />
      <OrderModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        t={t}
        lang={lang}
        onTerms={() => setTermsOpen(true)}
        onPrivacy={() => setPrivacyOpen(true)}
      />
      <PrivacyModal open={privacyOpen} onClose={() => setPrivacyOpen(false)} t={t} />
      <TermsModal open={termsOpen} onClose={() => setTermsOpen(false)} t={t} />
      <ShippingModal open={shippingOpen} onClose={() => setShippingOpen(false)} t={t} />
      <ReturnsModal open={returnsOpen} onClose={() => setReturnsOpen(false)} t={t} />
      <PaymentResultModal result={payResult} t={t} onClose={closePayResult} />
      <CookieBanner t={t} onPrivacy={() => setPrivacyOpen(true)} />
      <TweaksPanel variant={variant} setVariant={changeVariant} visible={tweaksVisible} />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
</script>
</body>
</html>
