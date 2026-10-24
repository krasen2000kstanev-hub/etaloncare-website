// Terms and Conditions modal

const { useEffect: useEffectTerms } = React;

function TermsBlock({ block }) {
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

function TermsModal({ open, onClose, t }) {
  useEffectTerms(() => {
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
  const tt = t.terms;
  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal modal-privacy" onClick={(e) => e.stopPropagation()} role="dialog" aria-labelledby="terms-title">
        <button className="modal-close" onClick={onClose} aria-label={tt.close}>
          <Icon name="close" size={20} />
        </button>
        <div className="privacy-content">
          <Kicker>{t.footer.terms}</Kicker>
          <h2 className="modal-title" id="terms-title">{tt.title}</h2>
          <div className="privacy-updated">{tt.updated}</div>
          <p className="privacy-intro">{tt.intro}</p>
          <p className="privacy-intro">{tt.intro2}</p>
          {tt.sections.map((s, i) => (
            <section key={i} className="privacy-section">
              <h3 className="privacy-heading">{s.heading}</h3>
              {s.blocks.map((b, j) => <TermsBlock key={j} block={b} />)}
            </section>
          ))}
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { TermsModal });
