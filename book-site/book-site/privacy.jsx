// Privacy policy modal + cookie consent banner

const { useState: useStatePrivacy, useEffect: useEffectPrivacy } = React;

function PrivacyBlock({ block }) {
  if (block.type === 'p') return <p className="privacy-p">{block.text}</p>;
  if (block.type === 'sub') return <h4 className="privacy-sub">{block.text}</h4>;
  if (block.type === 'list') {
    return (
      <ul className="privacy-list">
        {block.items.map((item, i) => <li key={i}>{item}</li>)}
      </ul>
    );
  }
  return null;
}

function PrivacyModal({ open, onClose, t }) {
  useEffectPrivacy(() => {
    if (!open) return;
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    function onKey(e) { if (e.key === 'Escape') onClose(); }
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.style.overflow = prev;
      window.removeEventListener('keydown', onKey);
    };
  }, [open, onClose]);

  if (!open) return null;
  const p = t.privacy;
  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal modal-privacy" onClick={(e) => e.stopPropagation()} role="dialog" aria-labelledby="privacy-title">
        <button className="modal-close" onClick={onClose} aria-label={p.close}>
          <Icon name="close" size={20} />
        </button>
        <div className="privacy-content">
          <Kicker>{t.footer.privacy}</Kicker>
          <h2 className="modal-title" id="privacy-title">{p.title}</h2>
          <div className="privacy-updated">{p.updated}</div>
          <p className="privacy-intro">{p.intro}</p>
          <p className="privacy-intro">{p.intro2}</p>
          {p.sections.map((s, i) => (
            <section key={i} className="privacy-section">
              <h3 className="privacy-heading">{s.heading}</h3>
              {s.blocks.map((b, j) => <PrivacyBlock key={j} block={b} />)}
            </section>
          ))}
        </div>
      </div>
    </div>
  );
}

const COOKIE_KEY = 'acn-cookie-consent';

function CookieBanner({ t, onPrivacy }) {
  const [visible, setVisible] = useStatePrivacy(false);

  useEffectPrivacy(() => {
    const stored = localStorage.getItem(COOKIE_KEY);
    if (!stored) {
      // small delay so it doesn't pop in instantly with the page
      const timer = setTimeout(() => setVisible(true), 600);
      return () => clearTimeout(timer);
    }
  }, []);

  function decide(value) {
    localStorage.setItem(COOKIE_KEY, value);
    setVisible(false);
    if (typeof window.gtag === 'function') {
      const state = value === 'accepted' ? 'granted' : 'denied';
      window.gtag('consent', 'update', {
        ad_storage: state,
        ad_user_data: state,
        ad_personalization: state,
        analytics_storage: state
      });
    }
  }

  if (!visible) return null;
  const c = t.cookie;
  return (
    <div className="cookie-banner" role="dialog" aria-label={c.linkText}>
      <p className="cookie-text">
        {c.message}{' '}
        <button type="button" className="cookie-link" onClick={onPrivacy}>{c.linkText}</button>
      </p>
      <div className="cookie-actions">
        <button type="button" className="btn btn-ghost btn-sm" onClick={() => decide('declined')}>{c.decline}</button>
        <button type="button" className="btn btn-primary btn-sm" onClick={() => decide('accepted')}>{c.accept}</button>
      </div>
    </div>
  );
}

Object.assign(window, { PrivacyModal, CookieBanner });
