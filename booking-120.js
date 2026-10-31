(() => {
  const config = window.ETALON_CONFIG;
  const en = document.documentElement.lang === 'en';
  document.querySelectorAll('.lang-switch a:not([aria-current])').forEach(link => { link.href += location.search; });
  const form = document.getElementById('booking-form');
  const status = document.getElementById('form-status');
  const reserveButton = document.getElementById('reserve-button');
  const rows = document.getElementById('seat-rows');
  let selected = null, busy = false, live = false;
  // ponytail: one attendee per order; group registration needs a reviewed multi-seat transaction.
  let seats = Array.from({ length: 126 }, (_, i) => { const row = String(Math.floor(i / 14) + 1), number = i % 14 + 1; return { id: `${row}${String(number).padStart(2, '0')}`, row, number, tier: i < 28 ? 'premium' : 'standard', available: true }; });
  const query = new URLSearchParams(location.search);
  const wanted = query.get('package') === 'premium' ? 'premium' : 'standard';
  document.getElementById('selected-package').textContent = wanted === 'premium' ? (en ? 'Premium' : 'Премиум') : (en ? 'Standard' : 'Стандарт');
  document.getElementById('selected-price').textContent = wanted === 'premium' ? '750 €' : '650 €';
  function message(text, type = '') { status.textContent = text; status.className = `form-status ${type}`; }
  function updateButton() { reserveButton.disabled = !live || !selected || busy; }
  function draw() {
    rows.replaceChildren();
    const rowNames = [...new Set(seats.map(s => s.row))];
    rowNames.forEach((row, rowIndex) => {
      const line = document.createElement('div'); line.className = 'seat-row';
      line.style.setProperty('--seat-delay', `${Math.min(rowIndex, 7) * 35}ms`);
      const label = document.createElement('span'); label.className = 'row-label'; label.textContent = row; line.append(label);
      seats.filter(s => s.row === row).forEach(seat => {
        const button = document.createElement('button'); button.type = 'button'; button.className = `seat ${seat.tier}`;
        button.textContent = String(seat.number); button.dataset.seat = seat.id;
        button.setAttribute('aria-label', en ? `Row ${seat.row}, seat ${seat.number}, ${seat.tier === 'premium' ? 'Premium, 750 euro' : 'Standard, 650 euro'}${seat.available ? '' : ', taken'}` : `Ред ${seat.row}, място ${seat.number}, ${seat.tier === 'premium' ? 'Премиум, 750 евро' : 'Стандарт, 650 евро'}${seat.available ? '' : ', заето'}`);
        button.setAttribute('aria-pressed', String(selected?.id === seat.id)); button.disabled = !seat.available || busy;
        button.addEventListener('click', () => {
          if (busy) return;
          selected = seat;
          rows.querySelectorAll('.seat').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.seat === seat.id)));
          document.getElementById('selected-label').textContent = en ? `Row ${seat.row} · Seat ${seat.number}${live ? '' : ' · preview'}` : `Ред ${seat.row} · Място ${seat.number}${live ? '' : ' · примерен избор'}`;
          document.getElementById('selected-package').textContent = seat.tier === 'premium' ? (en ? 'Premium' : 'Премиум') : (en ? 'Standard' : 'Стандарт');
          const price = document.getElementById('selected-price');
          const nextPrice = seat.tier === 'premium' ? '750 €' : '650 €';
          const changed = price.textContent !== nextPrice;
          price.textContent = nextPrice;
          if (changed && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
            price.getAnimations().forEach(animation => animation.cancel());
            price.animate([{ opacity: .5, transform: 'translateY(3px)' }, { opacity: 1, transform: 'none' }], { duration: 180, easing: 'ease-out' });
          }
          message(live ? '' : en ? 'This is a preview of your choice. No reservation has been made.' : 'Това е преглед на избора. Не е създадена резервация.'); updateButton();
        }); line.append(button);
      }); rows.append(line);
    });
  }
  // Заявките са идемпотентни по requestId, затова при 429 е безопасно да се опита пак.
  async function request(path, options = {}, attempt = 0) {
    const response = await fetch(`${config.apiBase}${path}`, { ...options, signal: AbortSignal.timeout(15000) });
    if (response.status === 429 && attempt < 3) { await new Promise(resolve => setTimeout(resolve, 1000 * (attempt + 1))); return request(path, options, attempt + 1); }
    const data = await response.json();
    if (!response.ok) { const error = new Error(en ? 'Your request could not be processed. Please try again.' : data.message || 'Заявката не може да бъде обработена.'); error.code = response.status; throw error; }
    return data;
  }
  async function refresh() {
    if (!config.apiBase) return;
    try {
      const data = await request('/api/seats'); seats = data.seats;
      live = Boolean(data.registrationOpen && config.registrationEnabled && config.seatPlanApproved && !config.preview);
      if (selected && !seats.find(s => s.id === selected.id)?.available) { selected = null; message(en ? 'That seat is no longer free. Please choose another.' : 'Мястото вече не е свободно. Изберете друго.', 'error'); }
      if (data.planApproved) document.getElementById('seat-help').textContent = data.planDescription;
      if (live) {
        const requests = data.paymentMode === 'request';
        if (!requests) form.querySelectorAll('[data-card-field]').forEach(field => { field.hidden = false; });
        document.getElementById('booking-notice').textContent = en ? (requests ? 'Choose a free seat and leave your details. You reserve the seat and receive an email with the payment details within 24 hours.' : 'Choose a free seat and submit a request. Final confirmation follows payment.') : requests ? 'Изберете свободно място и оставете данните си. Запазвате мястото и в рамките на 24 часа ще получите имейл с данните за плащане.' : 'Изберете свободно място и подайте заявка. Окончателното потвърждение е след плащане.';
        form.querySelectorAll('input:not([name=website])').forEach(input => { input.disabled = Boolean(input.closest('[hidden]')); });
        reserveButton.textContent = en ? 'Submit your request' : 'Подай заявка за участие';
        document.getElementById('payment-hint').textContent = en ? (requests ? 'No online payment now. You reserve your seat and receive an email with the payment details within 24 hours.' : 'You will continue to the secure UniCredit/BORICA page. Card details are not entered on this site.') : requests ? 'Сега не се плаща онлайн. Запазвате мястото, а в рамките на 24 часа ще получите имейл с данните за плащане.' : 'Ще продължите към защитената страница на UniCredit/BORICA. Данни за карта не се въвеждат на този сайт.';
      }
      draw(); updateButton();
    } catch { live = false; updateButton(); message(en ? 'The seat system is not available right now. Please try again later.' : 'Връзката със системата за места не е достъпна. Опитайте отново по-късно.', 'error'); }
  }
  let requestId = null;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!live || !selected || busy || !form.reportValidity()) return;
    busy = true; updateButton(); message(en ? 'Sending your request…' : 'Изпращаме заявката…');
    requestId ||= crypto.randomUUID();
    const values = Object.fromEntries(new FormData(form));
    try {
      let token;
      try {
        token = sessionStorage.getItem(`etalon:${requestId}`);
        if (!token) { token = Array.from(crypto.getRandomValues(new Uint8Array(32)), b => b.toString(16).padStart(2, '0')).join(''); sessionStorage.setItem(`etalon:${requestId}`, token); }
      } catch { throw new Error(en ? 'Temporary browser storage is needed to check your request. Please allow it or contact the organiser.' : 'За проверка на плащането е необходимо временно съхранение в браузъра. Разрешете го или се свържете с организатора.'); }
      const result = await request('/api/orders', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...values, consent: values.consent === 'on', seatId: selected.id, requestId, token, lang: en ? 'en' : 'bg' }) });
      if (result.status === 'requested') {
        const seat = selected; selected = null; requestId = null; form.reset();
        document.getElementById('selected-label').textContent = en ? 'Choose a seat on the plan' : 'Изберете място от схемата';
        await refresh();
        message(en ? `Your request has been received. We have reserved row ${seat.row}, seat ${seat.number}. You will receive an email with the payment details at ${values.email} within 24 hours.` : `Заявката е приета. Запазихме ред ${seat.row}, място ${seat.number}. В рамките на 24 часа ще получите имейл с данните за плащане на ${values.email}.`, 'success');
        return;
      }
      if (!['https://3dsgate-dev.borica.bg/cgi-bin/cgi_link', 'https://3dsgate.borica.bg/cgi-bin/cgi_link'].includes(result.checkout?.url)) throw new Error(en ? 'The payment system is not available.' : 'Платежната система не е достъпна.');
      const checkout = document.createElement('form'); checkout.method = 'POST'; checkout.action = result.checkout.url;
      for (const [key, value] of Object.entries(result.checkout.fields)) { const input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = value; checkout.append(input); }
      document.body.append(checkout); message(en ? 'Continuing to the secure UniCredit/BORICA page…' : 'Продължаваме към защитената страница на UniCredit/BORICA…'); checkout.submit();
    } catch (error) {
      message(error.code === 409 ? (en ? 'That seat has just been taken by another participant. Please choose another.' : 'Мястото вече е избрано от друг участник. Изберете друго.') : error.message || (en ? 'Something went wrong. Please try again.' : 'Възникна грешка. Опитайте отново.'), 'error');
      if (error.code === 409) { selected = null; requestId = null; await refresh(); }
    } finally { busy = false; draw(); updateButton(); }
  });
  form.querySelectorAll('.form-field').forEach((field, index) => field.style.setProperty('--field-index', index));
  draw(); refresh();
})();
