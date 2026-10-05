(() => {
  document.documentElement.classList.add('js');
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('nav-links');
  function closeMenu() { nav?.classList.remove('open'); toggle?.setAttribute('aria-expanded', 'false'); }
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('open', open);
  });
  nav?.addEventListener('click', e => { if (e.target.closest('a')) closeMenu(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && nav?.classList.contains('open')) { closeMenu(); toggle.focus(); } });
  const ready = window.ETALON_CONFIG.registrationEnabled && window.ETALON_CONFIG.seatPlanApproved && Date.now() >= Date.parse('2026-11-01T00:00:00+02:00');
  if (ready) {
    document.querySelectorAll('[data-registration-cta]').forEach(link => { link.href = 'booking.html'; link.querySelector('[data-cta-label]').textContent = 'Избери място'; });
    document.querySelectorAll('[data-package-cta] [data-cta-label]').forEach(label => { label.textContent = 'Избери място'; });
  }
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  if (!reducedMotion.matches && 'IntersectionObserver' in window) {
    const targets = document.querySelectorAll('.intro-grid, .section-heading, .topic-card, .program-overview, .speaker-grid, .audience-grid article, .package-common, .package, .venue-grid, .faq-grid, .final-cta > .container');
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        if (entry.target.classList.contains('topic-card')) {
          const index = [...entry.target.parentElement.children].indexOf(entry.target);
          entry.target.style.setProperty('--reveal-delay', (index % 3) * 70 + 'ms');
        }
        entry.target.classList.add('motion-in');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12 });
    targets.forEach(target => observer.observe(target));
    reducedMotion.addEventListener('change', event => { if (event.matches) observer.disconnect(); });
  }
  if (!window.ETALON_CONFIG.preview) document.getElementById('preview-strip')?.setAttribute('hidden', '');
})();
