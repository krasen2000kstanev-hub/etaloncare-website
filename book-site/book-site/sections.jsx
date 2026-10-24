// About the book, author, FAQ, Contact

const { useState: useStateSections } = React;

function About({ t }) {
  return (
    <section id="book" className="section section-about">
      <div className="container">
        <div className="about-grid">
          <div className="about-intro">
            <SectionHeader kicker={t.about.kicker} title={t.about.title} />
            {t.about.body.map((p, i) => <p key={i} className="body-text">{p}</p>)}
          </div>
          <div className="about-stats">
            <div className="stats-label">{t.about.statsLabel}</div>
            {t.about.stats.map((s, i) => (
              <div key={i} className="stat-row">
                <span className="stat-n">{s.n}</span>
                <span className="stat-l">{s.l}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="pillars">
          {t.about.pillars.map((p, i) => (
            <div key={i} className="pillar">
              <div className="pillar-num">{String(i + 1).padStart(2, '0')}</div>
              <h3 className="pillar-title">{p.title}</h3>
              <p className="pillar-body">{p.body}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function Author({ t }) {
  return (
    <section id="author" className="section section-author">
      <div className="container">
        <div className="author-grid">
          <div className="author-portrait">
            <div className="portrait-frame">
              <img src={media('author', 'assets/author.png')} alt={`${t.author.name} — ${t.author.role}`} className="portrait-photo" />
            </div>
          </div>
          <div className="author-content">
            <Kicker>{t.author.kicker}</Kicker>
            <h2 className="author-heading">{t.author.name}</h2>
            <div className="author-role">{t.author.role}</div>
            {t.author.bio.map((p, i) => <p key={i} className="body-text">{p}</p>)}

            <div className="credentials">
              <div className="credentials-label">{t.author.credentialsLabel}</div>
              <ul>
                {t.author.credentials.map((c, i) => (
                  <li key={i}><Icon name="check" size={14} /><span>{c}</span></li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function FAQ({ t }) {
  const [open, setOpen] = useStateSections(0);
  return (
    <section id="faq" className="section section-faq">
      <div className="container container-narrow">
        <SectionHeader kicker={t.faq.kicker} title={t.faq.title} align="center" />
        <div className="faq-list">
          {t.faq.items.map((item, i) => {
            const isOpen = open === i;
            return (
              <div key={i} className={`faq-item ${isOpen ? 'open' : ''}`}>
                <button className="faq-q" onClick={() => setOpen(isOpen ? -1 : i)} aria-expanded={isOpen}>
                  <span>{item.q}</span>
                  <span className="faq-icon"><Icon name={isOpen ? 'minus' : 'plus'} size={16} /></span>
                </button>
                <div className="faq-a-wrap" style={{ maxHeight: isOpen ? '300px' : '0' }}>
                  <p className="faq-a">{item.a}</p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function Contact({ t }) {
  return (
    <section id="contact" className="section section-contact">
      <div className="container container-narrow">
        <div className="contact-card">
          <h2 className="contact-title">{t.contact.title}</h2>
          <ul className="contact-list">
            <li>
              <span className="contact-label">{t.contact.emailLabel}</span>
              <a href={`mailto:${t.contact.email}`}>{t.contact.email}</a>
            </li>
            <li>
              <span className="contact-label">{t.contact.phoneLabel}</span>
              <a href={`tel:${t.contact.phone.replace(/\s/g, '')}`}>{t.contact.phone}</a>
            </li>
          </ul>
        </div>
      </div>
    </section>
  );
}

Object.assign(window, { About, Author, FAQ, Contact });
