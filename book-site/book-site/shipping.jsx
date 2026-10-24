// Shipping information modal

const { useEffect: useEffectShipping } = React;

function ShippingBlock({ block }) {
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

function ShippingModal({ open, onClose, t }) {
  useEffectShipping(() => {
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
  const s = t.shipping;
  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal modal-privacy" onClick={(e) => e.stopPropagation()} role="dialog" aria-labelledby="shipping-title">
        <button className="modal-close" onClick={onClose} aria-label={s.close}>
          <Icon name="close" size={20} />
        </button>
        <div className="privacy-content">
          <Kicker>{t.footer.shipping}</Kicker>
          <h2 className="modal-title" id="shipping-title">{s.title}</h2>
          <div className="privacy-updated">{s.updated}</div>
          <p className="privacy-intro">{s.intro}</p>
          {s.sections.map((sec, i) => (
            <section key={i} className="privacy-section">
              <h3 className="privacy-heading">{sec.heading}</h3>
              {sec.blocks.map((b, j) => <ShippingBlock key={j} block={b} />)}
            </section>
          ))}
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { ShippingModal });
