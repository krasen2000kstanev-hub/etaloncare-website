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
  if (!window.ETALON_CONFIG.preview) document.getElementById('preview-strip')?.setAttribute('hidden', '');
})();
