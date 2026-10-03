(() => {
  const config = window.ETALON_CONFIG;
  const form = document.getElementById('booking-form');
  const status = document.getElementById('form-status');
  const reserveButton = document.getElementById('reserve-button');
  const rows = document.getElementById('seat-rows');
  let selected = null, busy = false, live = false;
  // ponytail: one attendee per order; group registration needs a reviewed multi-seat transaction.
  let seats = Array.from({ length: 100 }, (_, i) => ({ id: `${String.fromCharCode(65 + Math.floor(i / 10))}${String(i % 10 + 1).padStart(2, '0')}`, row: String.fromCharCode(65 + Math.floor(i / 10)), number: i % 10 + 1, tier: i < 20 ? 'premium' : 'standard', available: true }));
  const query = new URLSearchParams(location.search);
  const wanted = query.get('package') === 'premium' ? 'premium' : 'standard';
  document.getElementById('selected-package').textContent = wanted === 'premium' ? 'Премиум' : 'Стандарт';
  document.getElementById('selected-price').textContent = wanted === 'premium' ? '700 €' : '600 €';
  function message(text, type = '') { status.textContent = text; status.className = `form-status ${type}`; }
  function updateButton() { reserveButton.disabled = !live || !selected || busy; }
  function draw() {
    rows.replaceChildren();
    const rowNames = [...new Set(seats.map(s => s.row))];
    rowNames.forEach(row => {
      const line = document.createElement('div'); line.className = 'seat-row';
      const label = document.createElement('span'); label.className = 'row-label'; label.textContent = row; line.append(label);
      seats.filter(s => s.row === row).forEach(seat => {
        const button = document.createElement('button'); button.type = 'button'; button.className = `seat ${seat.tier}`;
        button.textContent = String(seat.number); button.dataset.seat = seat.id;
        button.setAttribute('aria-label', `Ред ${seat.row}, място ${seat.number}, ${seat.tier === 'premium' ? 'Премиум, 700 евро' : 'Стандарт, 600 евро'}${seat.available ? '' : ', заето'}`);
        button.setAttribute('aria-pressed', String(selected?.id === seat.id)); button.disabled = !seat.available || busy;
        button.addEventListener('click', () => {
          if (busy) return;
          selected = seat;
          rows.querySelectorAll('.seat').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.seat === seat.id)));
          document.getElementById('selected-label').textContent = `Ред ${seat.row} · Място ${seat.number}${live ? '' : ' · примерен избор'}`;
          document.getElementById('selected-package').textContent = seat.tier === 'premium' ? 'Премиум' : 'Стандарт';
          document.getElementById('selected-price').textContent = seat.tier === 'premium' ? '700 €' : '600 €';
          message(live ? '' : 'Това е преглед на избора. Не е създадена резервация.'); updateButton();
        }); line.append(button);
      }); rows.append(line);
    });
  }
  async function request(path, options = {}) {
    const response = await fetch(`${config.apiBase}${path}`, { ...options, signal: AbortSignal.timeout(15000) });
    const data = await response.json();
    if (!response.ok) { const error = new Error(data.message || 'Заявката не може да бъде обработена.'); error.code = response.status; throw error; }
    return data;
  }
  async function refresh() {
    if (!config.apiBase) return;
    try {
      const data = await request('/api/seats'); seats = data.seats;
      live = Boolean(data.registrationOpen && config.registrationEnabled && config.seatPlanApproved && !config.preview);
      if (selected && !seats.find(s => s.id === selected.id)?.available) { selected = null; message('Мястото вече не е свободно. Изберете друго.', 'error'); }
      if (data.planApproved) document.getElementById('seat-help').textContent = data.planDescription;
      if (live) {
        document.getElementById('booking-notice').textContent = 'Изберете свободно място и подайте заявка. Окончателното потвърждение е след плащане.';
        form.querySelectorAll('input:not([name=website])').forEach(input => { input.disabled = false; });
        reserveButton.textContent = 'Подай заявка за участие';
        document.getElementById('payment-hint').textContent = 'Ще продължите към защитената страница на UniCredit/BORICA. Данни за карта не се въвеждат на този сайт.';
      }
      draw(); updateButton();
    } catch { live = false; updateButton(); message('Връзката със системата за места не е достъпна. Опитайте отново по-късно.', 'error'); }
  }
  let requestId = null;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!live || !selected || busy || !form.reportValidity()) return;
    busy = true; updateButton(); message('Изпращаме заявката…');
    requestId ||= crypto.randomUUID();
    const values = Object.fromEntries(new FormData(form));
    try {
      let token;
      try {
        token = sessionStorage.getItem(`etalon:${requestId}`);
        if (!token) { token = Array.from(crypto.getRandomValues(new Uint8Array(32)), b => b.toString(16).padStart(2, '0')).join(''); sessionStorage.setItem(`etalon:${requestId}`, token); }
      } catch { throw new Error('За проверка на плащането е необходимо временно съхранение в браузъра. Разрешете го или се свържете с организатора.'); }
      const result = await request('/api/orders', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...values, consent: values.consent === 'on', seatId: selected.id, requestId, token }) });
      if (!['https://3dsgate-dev.borica.bg/cgi-bin/cgi_link', 'https://3dsgate.borica.bg/cgi-bin/cgi_link'].includes(result.checkout?.url)) throw new Error('Платежната система не е достъпна.');
      const checkout = document.createElement('form'); checkout.method = 'POST'; checkout.action = result.checkout.url;
      for (const [key, value] of Object.entries(result.checkout.fields)) { const input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = value; checkout.append(input); }
      document.body.append(checkout); message('Продължаваме към защитената страница на UniCredit/BORICA…'); checkout.submit();
    } catch (error) {
      message(error.code === 409 ? 'Мястото вече е избрано от друг участник. Изберете друго.' : error.message || 'Възникна грешка. Опитайте отново.', 'error');
      if (error.code === 409) { selected = null; requestId = null; await refresh(); }
    } finally { busy = false; updateButton(); }
  });
  draw(); refresh();
})();
