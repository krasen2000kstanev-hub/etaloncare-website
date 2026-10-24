// Shared small components & utilities

// Resolve media URL — admin overrides via window.__MEDIA, fall back to bundled.
function media(slot, fallback) {
  return (window.__MEDIA && window.__MEDIA[slot]) || fallback;
}

const COVER_SRC = media('cover', 'assets/cover.png');

function Icon({ name, size = 20 }) {
  const paths = {
    arrow: 'M5 12h14M13 5l7 7-7 7',
    arrowDown: 'M12 5v14M5 12l7 7 7-7',
    check: 'M5 13l4 4L19 7',
    close: 'M6 6l12 12M6 18L18 6',
    plus: 'M12 5v14M5 12h14',
    minus: 'M5 12h14',
    chevronDown: 'M6 9l6 6 6-6',
    cart: 'M3 3h2l2 13h13l2-9H6M9 21a1 1 0 100-2 1 1 0 000 2zm9 0a1 1 0 100-2 1 1 0 000 2z',
    book: 'M4 5a2 2 0 012-2h12v18H6a2 2 0 01-2-2V5zM4 5v14',
    mail: 'M4 6h16v12H4zM4 6l8 7 8-7',
    star: 'M12 3l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 18l-5.9 3 1.2-6.5L2.5 9.9 9.1 9z'
  };
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d={paths[name] || ''} />
    </svg>
  );
}

// Cover with subtle tilt + shadow + 3D book illusion
function CoverImage({ tilt = 0, width = 320, shadow = true }) {
  return (
    <div className="cover-wrap" style={{
      width,
      transform: `perspective(1600px) rotateY(-9deg) rotateX(2deg) rotateZ(${tilt}deg)`,
      filter: shadow
        ? 'drop-shadow(0 38px 50px rgba(27, 20, 16, 0.40)) drop-shadow(0 12px 18px rgba(27, 20, 16, 0.22)) drop-shadow(0 2px 4px rgba(27, 20, 16, 0.18))'
        : 'none'
    }}>
      <img src={COVER_SRC} alt="Разширена химия на ноктите — корица" style={{
        width: '100%',
        height: 'auto',
        display: 'block',
        borderRadius: '2px 3px 3px 2px',
        border: '1px solid rgba(27,20,16,0.08)'
      }}/>
      <div className="cover-spine" />
      <div className="cover-pages" />
      <div className="cover-gloss" />
    </div>
  );
}

// Decorative molecular pattern for backgrounds
function MoleculePattern({ opacity = 0.08 }) {
  return (
    <svg className="molecule-bg" viewBox="0 0 400 400" preserveAspectRatio="xMidYMid slice" aria-hidden="true" style={{ opacity }}>
      <defs>
        <pattern id="mol" x="0" y="0" width="120" height="120" patternUnits="userSpaceOnUse">
          <g fill="none" stroke="currentColor" strokeWidth="0.6">
            <circle cx="30" cy="30" r="3" fill="currentColor"/>
            <circle cx="90" cy="30" r="3" fill="currentColor"/>
            <circle cx="60" cy="80" r="3" fill="currentColor"/>
            <line x1="30" y1="30" x2="90" y2="30"/>
            <line x1="30" y1="30" x2="60" y2="80"/>
            <line x1="90" y1="30" x2="60" y2="80"/>
          </g>
        </pattern>
      </defs>
      <rect width="400" height="400" fill="url(#mol)"/>
    </svg>
  );
}

// Kicker label
function Kicker({ children }) {
  return <div className="kicker"><span className="kicker-dot" />{children}</div>;
}

// Section header
function SectionHeader({ kicker, title, align = 'left' }) {
  return (
    <div className={`section-header align-${align}`}>
      <Kicker>{kicker}</Kicker>
      <h2 className="section-title">{title}</h2>
    </div>
  );
}

Object.assign(window, { Icon, CoverImage, MoleculePattern, Kicker, SectionHeader, COVER_SRC, media });
