// Three distinct hero layouts

const { useState } = React;

function HeroMeta({ t }) {
  return (
    <div className="hero-meta">
      <span>{t.hero.meta1}</span>
      <span className="dot">·</span>
      <span>{t.hero.meta2}</span>
    </div>
  );
}

function CTAs({ t, onOrder, onExcerpt, compact }) {
  return (
    <div className={`ctas ${compact ? 'compact' : ''}`}>
      <button className="btn btn-primary" onClick={onOrder}>
        <span>{t.hero.cta}</span>
        <Icon name="arrow" size={18} />
      </button>
      <button className="btn btn-ghost" onClick={onExcerpt}>
        {t.hero.ctaSecondary}
      </button>
    </div>
  );
}

// ---------- Variant A: Classic editorial split ----------
function HeroA({ t, onOrder, onExcerpt }) {
  return (
    <section className="hero hero-a">
      <div className="hero-a-inner">
        <div className="hero-a-text">
          <div className="eyebrow">{t.hero.eyebrow}</div>
          <h1 className="hero-title">{t.hero.title}</h1>
          <p className="hero-subtitle">{t.hero.subtitle}</p>
          <p className="hero-tagline">{t.hero.tagline}</p>

          <div className="author-line">
            <div className="author-avatar"><img src={media('author', 'assets/author.png')} alt={t.hero.author} /></div>
            <div>
              <div className="author-name">{t.hero.author}</div>
              <div className="author-sub">{t.hero.authorSub}</div>
            </div>
          </div>

          <div className="hero-buy">
            <div className="price-block">
              <div className="price">{t.hero.price}</div>
            </div>
            <CTAs t={t} onOrder={onOrder} onExcerpt={onExcerpt} />
          </div>

          <HeroMeta t={t} />
        </div>
        <div className="hero-a-cover">
          <div className="cover-stage">
            <div className="cover-backplate" />
            <CoverImage tilt={-2} width={380} />
          </div>
          <div className="featured-badge">
            <Icon name="star" size={14} />
            <span>{t.hero.featured}</span>
          </div>
        </div>
      </div>
    </section>
  );
}

// ---------- Variant B: Big centered cover ----------
function HeroB({ t, onOrder, onExcerpt }) {
  return (
    <section className="hero hero-b">
      <div className="hero-b-eyebrow">{t.hero.eyebrow}</div>
      <h1 className="hero-b-title">
        <span className="line-1">{t.hero.title.split(' ').slice(0, -1).join(' ')}</span>
        <span className="line-2">{t.hero.title.split(' ').slice(-1)[0]}</span>
      </h1>
      <div className="hero-b-center">
        <div className="hero-b-side hero-b-left">
          <div className="side-label">{t.hero.author}</div>
          <div className="side-sub">{t.hero.authorSub}</div>
          <div className="side-divider" />
          <p className="side-tagline">{t.hero.tagline}</p>
        </div>
        <div className="hero-b-cover">
          <div className="cover-stage">
            <CoverImage tilt={0} width={320} />
          </div>
        </div>
        <div className="hero-b-side hero-b-right">
          <div className="side-label">{t.hero.price}</div>
          <div className="side-divider" />
          <div className="side-meta">
            <div>{t.hero.meta1}</div>
            <div>{t.hero.meta2}</div>
          </div>
        </div>
      </div>
      <p className="hero-b-subtitle">{t.hero.subtitle}</p>
      <div className="hero-b-ctas">
        <CTAs t={t} onOrder={onOrder} onExcerpt={onExcerpt} />
      </div>
    </section>
  );
}

// ---------- Variant C: Magazine grid with metadata rail ----------
function HeroC({ t, onOrder, onExcerpt }) {
  return (
    <section className="hero hero-c">
      <div className="hero-c-grid">
        <div className="hero-c-rail">
          <div className="rail-num">01</div>
          <div className="rail-label">{t.hero.eyebrow}</div>
          <div className="rail-divider" />
          <div className="rail-spec">
            <div className="spec-k">Автор / Author</div>
            <div className="spec-v">Doug Schoon</div>
          </div>
          <div className="rail-spec">
            <div className="spec-k">Формат / Format</div>
            <div className="spec-v">{t.hero.meta1}</div>
          </div>
          <div className="rail-spec">
            <div className="spec-k">Страници / Pages</div>
            <div className="spec-v">320</div>
          </div>
          <div className="rail-spec">
            <div className="spec-k">Цена / Price</div>
            <div className="spec-v">{t.hero.price}</div>
          </div>
        </div>

        <div className="hero-c-main">
          <h1 className="hero-c-title">{t.hero.title}</h1>
          <p className="hero-c-subtitle">{t.hero.subtitle}</p>
          <div className="hero-c-rule" />
          <p className="hero-c-tagline">{t.hero.tagline}</p>
          <div className="hero-c-actions">
            <CTAs t={t} onOrder={onOrder} onExcerpt={onExcerpt} compact />
          </div>
        </div>

        <div className="hero-c-cover">
          <div className="cover-stage">
            <CoverImage tilt={0} width={340} />
          </div>
          <div className="cover-caption">
            <Icon name="book" size={14} />
            <span>{t.hero.featured}</span>
          </div>
        </div>
      </div>
    </section>
  );
}

function Hero({ variant, t, onOrder, onExcerpt }) {
  if (variant === 'B') return <HeroB t={t} onOrder={onOrder} onExcerpt={onExcerpt} />;
  if (variant === 'C') return <HeroC t={t} onOrder={onOrder} onExcerpt={onExcerpt} />;
  return <HeroA t={t} onOrder={onOrder} onExcerpt={onExcerpt} />;
}

Object.assign(window, { Hero });
