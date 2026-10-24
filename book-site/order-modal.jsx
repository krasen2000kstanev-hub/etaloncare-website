// Order modal with form + mock card checkout
// Speedy courier integration is mocked client-side. Real integration must hit
// Speedy's REST API from a backend (cities/sites endpoints) — frontend cannot
// call it directly due to CORS and exposed credentials.

const { useState: useStateOrder, useEffect: useEffectOrder, useRef: useRefOrder, useMemo: useMemoOrder } = React;

const PRICE = 69.00;
const SHIP_COURIER = 5.00; // fallback estimate before live calc returns
const SHIP_OFFICE = 3.50;  // fallback estimate before live calc returns
const FREE_SHIP_THRESHOLD = 40;

// Speedy cities + offices are now fetched from /api/speedy/* endpoints
// (cached on the backend; falls back to a static list if not yet synced).

function fmt(n) {
  return n.toFixed(2).replace('.', ',') + ' €';
}

function OrderModal({ open, onClose, t, lang, onTerms, onPrivacy }) {
  const [step, setStep] = useStateOrder(1);
  const [submitting, setSubmitting] = useStateOrder(false);
  const [serverError, setServerError] = useStateOrder('');
  const [form, setForm] = useStateOrder({
    name: '', phone: '', email: '',
    // To-address fields
    city: '', zip: '',
    street: '', number: '',
    block: '', entrance: '', floor: '', apartment: '',
    // To-office fields
    officeCity: '',     // city display name
    officeCityId: 0,    // Speedy site_id (positive) or fallback id (negative)
    officeId: '',       // office display name
    officeSpeedyId: 0,  // Speedy office id (positive) or fallback id (negative)
    qty: 1,
    delivery: 'courier',
    payment: 'cod',
    notes: ''
  });
  const [errors, setErrors] = useStateOrder({});
  const [orderNo, setOrderNo] = useStateOrder('');
  const [cities, setCities] = useStateOrder([]);
  const [offices, setOffices] = useStateOrder([]);
  const [loadingOffices, setLoadingOffices] = useStateOrder(false);
  // Live shipping price from Speedy. null = not yet fetched (use estimate).
  const [shipLive, setShipLive] = useStateOrder(null);
  const [shipLoading, setShipLoading] = useStateOrder(false);
  const shipReqRef = useRefOrder(0);

  useEffectOrder(() => {
    if (open) {
      document.body.style.overflow = 'hidden';
      setStep(1);
      setErrors({});
      setSubmitting(false);
      setServerError('');
    } else {
      document.body.style.overflow = '';
    }
  }, [open]);

  // Load Speedy cities once on mount.
  useEffectOrder(() => {
    const apiBase = window.__API || '/api';
    fetch(apiBase + '/speedy/cities')
      .then(r => r.json())
      .then(d => { if (d.cities) setCities(d.cities); })
      .catch(() => {/* leave empty; UI shows loading state */});
  }, []);

  // Load offices when officeCity changes.
  useEffectOrder(() => {
    if (!form.officeCityId) { setOffices([]); return; }
    setLoadingOffices(true);
    const apiBase = window.__API || '/api';
    fetch(`${apiBase}/speedy/offices/${form.officeCityId}?name=${encodeURIComponent(form.officeCity)}`)
      .then(r => r.json())
      .then(d => setOffices(d.offices || []))
      .catch(() => setOffices([]))
      .finally(() => setLoadingOffices(false));
  }, [form.officeCityId]);

  // Live shipping price — fires when delivery details, qty or payment change.
  // Debounced 350ms; out-of-order responses are dropped via shipReqRef.
  useEffectOrder(() => {
    // Decide whether we have enough info to ask Speedy.
    let payload = null;
    if (form.delivery === 'office' && form.officeCityId > 0 && form.officeSpeedyId > 0) {
      payload = {
        method: 'office',
        office_site_id: form.officeCityId,
        office_speedy_id: form.officeSpeedyId,
        qty: form.qty,
        payment: form.payment,
      };
    } else if (form.delivery === 'address' && form.city.trim() !== '') {
      payload = {
        method: 'address',
        city_name: form.city.trim(),
        post_code: form.zip || null,
        qty: form.qty,
        payment: form.payment,
      };
    }
    if (!payload) {
      setShipLive(null);
      setShipLoading(false);
      return;
    }
    setShipLoading(true);
    const myReq = ++shipReqRef.current;
    const t = setTimeout(() => {
      const apiBase = window.__API || '/api';
      fetch(apiBase + '/speedy/calculate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload),
      })
        .then(r => r.json())
        .then(d => {
          if (myReq !== shipReqRef.current) return; // stale
          if (d && typeof d.amount === 'number') setShipLive({ amount: d.amount, source: d.source });
        })
        .catch(() => {/* keep estimate */})
        .finally(() => {
          if (myReq === shipReqRef.current) setShipLoading(false);
        });
    }, 350);
    return () => clearTimeout(t);
  }, [
    form.delivery,
    form.officeCityId,
    form.officeSpeedyId,
    form.city,
    form.zip,
    form.qty,
    form.payment,
  ]);

  if (!open) return null;

  const subtotal = PRICE * form.qty;
  const isFreeShip = subtotal >= FREE_SHIP_THRESHOLD;
  const shipEstimate = form.delivery === 'courier' ? SHIP_COURIER : SHIP_OFFICE;
  // Trust live price when we have it; otherwise show static estimate.
  const ship = isFreeShip ? 0 : (shipLive ? shipLive.amount : shipEstimate);
  const total = subtotal + ship;

  function update(k, v) {
    setForm(f => ({ ...f, [k]: v }));
    if (errors[k]) setErrors(e => ({ ...e, [k]: null }));
  }

  function validate() {
    const e = {};
    if (!form.name.trim()) e.name = t.order.required;
    if (!form.phone.trim() || form.phone.replace(/\D/g, '').length < 7) e.phone = t.order.invalidPhone;
    if (!form.email.trim() || !/.+@.+\..+/.test(form.email)) e.email = t.order.invalidEmail;
    if (form.delivery === 'office') {
      if (!form.officeCityId) e.officeCity = t.order.required;
      if (!form.officeSpeedyId) e.officeId = t.order.invalidOffice;
    } else {
      if (!form.city.trim()) e.city = t.order.required;
      if (!form.street.trim()) e.street = t.order.required;
      if (!form.number.trim()) e.number = t.order.required;
    }
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  function buildPayload() {
    const d = form.delivery === 'office'
      ? {
          method: 'office',
          office_city: form.officeCity,
          office_city_id: form.officeCityId,
          office_name: form.officeId,
          office_speedy_id: form.officeSpeedyId,
        }
      : {
          method: 'address',
          city: form.city,
          zip: form.zip,
          street: form.street,
          number: form.number,
          block: form.block,
          entrance: form.entrance,
          floor: form.floor,
          apartment: form.apartment,
        };
    return {
      customer: { name: form.name, email: form.email, phone: form.phone },
      delivery: d,
      payment: { method: form.payment },
      qty: form.qty,
      notes: form.notes,
      locale: lang,
    };
  }

  async function submit(ev) {
    ev.preventDefault();
    setServerError('');
    if (!validate()) return;
    setSubmitting(true);
    try {
      const apiBase = window.__API || '/api';
      const res = await fetch(apiBase + '/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(buildPayload()),
      });
      const data = await res.json().catch(() => ({}));
      if (res.status === 422 && data.errors) {
        setErrors(data.errors);
        setSubmitting(false);
        return;
      }
      if (!res.ok || !data.ok) {
        setServerError(data.message || 'Грешка при изпращане. Опитайте отново.');
        setSubmitting(false);
        return;
      }
      // Card orders are redirected to БОРИКА's 3DS page; result is shown on return.
      if (data.redirect) {
        window.location.assign(data.redirect);
        return;
      }
      setOrderNo(data.order_number);
      setStep(2);
      setSubmitting(false);
    } catch (e) {
      setServerError('Грешка в мрежата. Проверете връзката и опитайте отново.');
      setSubmitting(false);
    }
  }

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(e) => e.stopPropagation()}>
        <button className="modal-close" onClick={onClose} aria-label="Close">
          <Icon name="close" size={20} />
        </button>

        {step === 1 && (
          <form className="order-form" onSubmit={submit}>
            <div className="order-grid">
              <div className="order-main">
                <Kicker>{t.order.kicker}</Kicker>
                <h2 className="modal-title">{t.order.title}</h2>
                <p className="modal-body">{t.order.body}</p>

                <div className="form-grid">
                  <Field label={t.order.name} err={errors.name}>
                    <input value={form.name} onChange={(e) => update('name', e.target.value)} autoComplete="name" />
                  </Field>
                  <Field label={t.order.phone} err={errors.phone}>
                    <input value={form.phone} onChange={(e) => update('phone', e.target.value)} autoComplete="tel" placeholder="+359 88 000 0000" />
                  </Field>
                  <Field label={t.order.email} err={errors.email} full>
                    <input type="email" value={form.email} onChange={(e) => update('email', e.target.value)} autoComplete="email" />
                  </Field>
                </div>

                <fieldset className="choice-group">
                  <legend>{t.order.delivery}</legend>
                  <label className={`choice ${form.delivery === 'courier' ? 'active' : ''}`}>
                    <input type="radio" name="delivery" checked={form.delivery === 'courier'} onChange={() => update('delivery', 'courier')} />
                    <span>{t.order.deliveryCourier}</span>
                  </label>
                  <label className={`choice ${form.delivery === 'office' ? 'active' : ''}`}>
                    <input type="radio" name="delivery" checked={form.delivery === 'office'} onChange={() => { update('delivery', 'office'); }} />
                    <span>{t.order.deliveryOffice}</span>
                  </label>
                </fieldset>

                {form.delivery === 'office' ? (
                  <div className="form-grid speedy-grid">
                    <Field label={t.order.speedyCity} err={errors.officeCity} full>
                      <CityCombo
                        cities={cities}
                        value={form.officeCityId}
                        valueLabel={form.officeCity}
                        placeholder={t.order.selectCity}
                        emptyLabel={t.order.selectCity}
                        onSelect={(c) => {
                          setForm(f => ({
                            ...f,
                            officeCityId: c ? c.id : 0,
                            officeCity: c ? c.name : '',
                            officeId: '',
                            officeSpeedyId: 0,
                          }));
                          if (errors.officeCity || errors.officeId) {
                            setErrors(er => ({ ...er, officeCity: null, officeId: null }));
                          }
                        }}
                      />
                    </Field>
                    <Field label={t.order.speedyOffice} err={errors.officeId} full>
                      <select
                        value={form.officeSpeedyId || ''}
                        onChange={(e) => {
                          const id = parseInt(e.target.value, 10);
                          const o = offices.find(o => o.id === id);
                          setForm(f => ({
                            ...f,
                            officeSpeedyId: id || 0,
                            officeId: o ? o.name : '',
                          }));
                          if (errors.officeId) setErrors(er => ({ ...er, officeId: null }));
                        }}
                        disabled={!form.officeCityId || loadingOffices}
                      >
                        <option value="">
                          {!form.officeCityId
                            ? t.order.selectCityFirst
                            : loadingOffices ? '…' : t.order.selectOffice}
                        </option>
                        {offices.map(o => (
                          <option key={o.id} value={o.id}>
                            {o.name}{o.address ? ` — ${o.address}` : ''}
                          </option>
                        ))}
                      </select>
                    </Field>
                  </div>
                ) : (
                  <div className="form-grid">
                    <Field label={t.order.city} err={errors.city}>
                      <input value={form.city} onChange={(e) => update('city', e.target.value)} autoComplete="address-level2" />
                    </Field>
                    <Field label={t.order.zip}>
                      <input value={form.zip} onChange={(e) => update('zip', e.target.value)} autoComplete="postal-code" />
                    </Field>
                    <Field label={t.order.street} err={errors.street} full>
                      <input value={form.street} onChange={(e) => update('street', e.target.value)} autoComplete="address-line1" />
                    </Field>
                    <Field label={t.order.streetNumber} err={errors.number}>
                      <input value={form.number} onChange={(e) => update('number', e.target.value)} inputMode="numeric" />
                    </Field>
                    <Field label={t.order.block}>
                      <input value={form.block} onChange={(e) => update('block', e.target.value)} />
                    </Field>
                    <div className="form-row-3">
                      <Field label={t.order.entrance}>
                        <input value={form.entrance} onChange={(e) => update('entrance', e.target.value)} />
                      </Field>
                      <Field label={t.order.floor}>
                        <input value={form.floor} onChange={(e) => update('floor', e.target.value)} inputMode="numeric" />
                      </Field>
                      <Field label={t.order.apartment}>
                        <input value={form.apartment} onChange={(e) => update('apartment', e.target.value)} />
                      </Field>
                    </div>
                  </div>
                )}

                <fieldset className="choice-group">
                  <legend>{t.order.payment}</legend>
                  {window.__BORICA ? (
                    <React.Fragment>
                      <label className={`choice ${form.payment === 'cod' ? 'active' : ''}`}>
                        <input type="radio" name="payment" checked={form.payment === 'cod'} onChange={() => update('payment', 'cod')} />
                        <span>{t.order.paymentCod}</span>
                      </label>
                      <label className={`choice ${form.payment === 'card' ? 'active' : ''}`}>
                        <input type="radio" name="payment" checked={form.payment === 'card'} onChange={() => update('payment', 'card')} />
                        <span>{t.order.paymentCard}</span>
                      </label>
                    </React.Fragment>
                  ) : (
                    <div className="choice active static">
                      <Icon name="check" size={14} />
                      <span>{t.order.paymentCod}</span>
                    </div>
                  )}
                </fieldset>

                {window.__BORICA && form.payment === 'card' && (
                  <p className="card-hint">{t.order.cardHint}</p>
                )}

                <Field label={t.order.notes} full>
                  <textarea rows="2" value={form.notes} onChange={(e) => update('notes', e.target.value)} />
                </Field>
              </div>

              <aside className="order-summary">
                <div className="summary-head">
                  <img src={COVER_SRC} alt={t.order.itemName} className="summary-thumb" />
                  <div>
                    <div className="summary-name">{t.order.itemName}</div>
                    <div className="summary-format">{t.order.itemFormat}</div>
                  </div>
                </div>
                <div className="qty-row">
                  <span>{t.order.qty}</span>
                  <div className="qty-control">
                    <button type="button" onClick={() => update('qty', Math.max(1, form.qty - 1))}><Icon name="minus" size={14} /></button>
                    <span>{form.qty}</span>
                    <button type="button" onClick={() => update('qty', Math.min(20, form.qty + 1))}><Icon name="plus" size={14} /></button>
                  </div>
                </div>
                <div className="summary-line"><span>{t.order.subtotal}</span><span>{fmt(subtotal)}</span></div>
                <div className="summary-line">
                  <span>{t.order.delivery}</span>
                  <span>
                    {shipLoading
                      ? <span className="ship-loading">…</span>
                      : ship === 0
                        ? '—'
                        : fmt(ship)}
                  </span>
                </div>
                <div className="summary-total"><span>{t.order.total}</span><span>{fmt(total)}</span></div>
                {serverError && (
                  <div className="server-error">{serverError}</div>
                )}
                <button type="submit" className="btn btn-primary btn-wide" disabled={submitting}>
                  <span>{submitting ? '…' : t.order.submit}</span>
                  {!submitting && <Icon name="arrow" size={16} />}
                </button>
                <p className="summary-legal">
                  {t.order.legalNoticePrefix}
                  <button type="button" className="legal-link" onClick={onTerms}>{t.footer.terms}</button>
                  {t.order.legalNoticeAnd}
                  <button type="button" className="legal-link" onClick={onPrivacy}>{t.footer.privacy}</button>
                  {t.order.legalNoticeSuffix}
                </p>
                <div className="summary-note">
                  <Icon name="check" size={12} /> {t.order.secureNote}
                </div>
              </aside>
            </div>
          </form>
        )}

        {step === 2 && (
          <div className="order-success">
            <div className="success-check"><Icon name="check" size={36} /></div>
            <h2 className="modal-title">{t.order.success}</h2>
            <p className="modal-body">{t.order.successBody}</p>
            <div className="order-no">
              <span>{t.order.orderNo}</span>
              <strong>{orderNo}</strong>
            </div>
            <button className="btn btn-primary" onClick={onClose}>{t.order.successCta}</button>
          </div>
        )}
      </div>
    </div>
  );
}

function Field({ label, err, children, full }) {
  return (
    <label className={`field ${full ? 'full' : ''} ${err ? 'has-err' : ''}`}>
      <span className="field-label">{label}{err ? <em className="field-err"> — {err}</em> : null}</span>
      {children}
    </label>
  );
}

// Searchable combobox for the Speedy city dropdown.
// Filters by case-insensitive substring on name and post_code.
function CityCombo({ cities, value, valueLabel, placeholder, onSelect }) {
  const [open, setOpen] = useStateOrder(false);
  const [query, setQuery] = useStateOrder('');
  const [activeIdx, setActiveIdx] = useStateOrder(-1);
  const wrapRef = useRefOrder(null);
  const inputRef = useRefOrder(null);
  const listRef = useRefOrder(null);

  // Sync external selection into the input text when not actively typing.
  useEffectOrder(() => {
    if (!open) setQuery(valueLabel || '');
  }, [valueLabel, open]);

  // Close on outside click.
  useEffectOrder(() => {
    if (!open) return;
    function onDocClick(e) {
      if (wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false);
    }
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, [open]);

  const filtered = useMemoOrder(() => {
    const q = query.trim().toLowerCase();
    if (!q || q === (valueLabel || '').toLowerCase()) return cities;
    return cities.filter(c => {
      const n = (c.name || '').toLowerCase();
      const p = (c.post_code || '').toLowerCase();
      return n.includes(q) || p.includes(q);
    });
  }, [cities, query, valueLabel]);

  function commit(c) {
    onSelect(c);
    setQuery(c ? c.name : '');
    setOpen(false);
    setActiveIdx(-1);
  }

  function onKeyDown(e) {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!open) setOpen(true);
      setActiveIdx(i => Math.min(filtered.length - 1, i + 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIdx(i => Math.max(0, i - 1));
    } else if (e.key === 'Enter') {
      if (open && activeIdx >= 0 && filtered[activeIdx]) {
        e.preventDefault();
        commit(filtered[activeIdx]);
      }
    } else if (e.key === 'Escape') {
      if (open) { e.preventDefault(); setOpen(false); }
    }
  }

  // Scroll active item into view.
  useEffectOrder(() => {
    if (!open || activeIdx < 0 || !listRef.current) return;
    const el = listRef.current.querySelector(`[data-idx="${activeIdx}"]`);
    if (el) el.scrollIntoView({ block: 'nearest' });
  }, [activeIdx, open]);

  return (
    <div className={`combo ${open ? 'open' : ''}`} ref={wrapRef}>
      <input
        ref={inputRef}
        type="text"
        className="combo-input"
        value={query}
        placeholder={placeholder}
        onChange={(e) => {
          setQuery(e.target.value);
          setOpen(true);
          setActiveIdx(-1);
          if (value && e.target.value !== valueLabel) {
            // user is editing — clear current selection until they pick again
            onSelect(null);
          }
        }}
        onFocus={() => setOpen(true)}
        onKeyDown={onKeyDown}
        autoComplete="off"
        role="combobox"
        aria-expanded={open}
        aria-autocomplete="list"
      />
      <span className="combo-caret" aria-hidden="true">
        <Icon name="chevronDown" size={14} />
      </span>
      {open && (
        <ul className="combo-list" role="listbox" ref={listRef}>
          {filtered.length === 0 ? (
            <li className="combo-empty">—</li>
          ) : filtered.map((c, idx) => (
            <li
              key={c.id}
              data-idx={idx}
              role="option"
              aria-selected={c.id === value}
              className={`combo-item ${idx === activeIdx ? 'active' : ''} ${c.id === value ? 'selected' : ''}`}
              onMouseDown={(e) => { e.preventDefault(); commit(c); }}
              onMouseEnter={() => setActiveIdx(idx)}
            >
              <span className="combo-item-name">{c.name}</span>
              {c.post_code && <span className="combo-item-meta">{c.post_code}</span>}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

Object.assign(window, { OrderModal });
