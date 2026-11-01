(() => {
  const api = window.ETALON_CONFIG.apiBase, key = 'etalon:admin';
  const login = document.getElementById('login'), board = document.getElementById('board'), rows = document.getElementById('rows'), status = document.getElementById('status');
  const labels = { requested: 'Чака плащане', deposit: 'Капаро', paid: 'Платено', expired: 'Изтекла', forfeited: 'Отпаднала', cancelled: 'Отказ' };
  const actions = { deposit: 'Капаро получено', paid: 'Платено изцяло', cancelled: 'Отказ' };
  const ask = { deposit: 'Да отбележа капаро? Участникът ще получи имейл.', paid: 'Да отбележа пълно плащане? Участникът ще получи имейл.', cancelled: 'Да отбележа отказ? Мястото се освобождава веднага, без имейл до участника.' };
  const date = seconds => new Date(seconds * 1000).toLocaleDateString('bg-BG', { timeZone: 'Europe/Sofia' });
  const token = () => { try { return sessionStorage.getItem(key); } catch { return null; } };
  const show = (text, type = '') => { status.textContent = text; status.className = `form-status ${type}`; };
  async function call(path, options = {}) {
    const response = await fetch(`${api}${path}`, { ...options, headers: { ...options.headers, Authorization: `Bearer ${token()}` }, signal: AbortSignal.timeout(20000) });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) { const error = new Error(data.message || 'Системата не отговаря. Опитай отново.'); error.code = response.status; throw error; }
    return data;
  }
  function logout(message = '') {
    try { sessionStorage.removeItem(key); } catch {}
    board.hidden = true; login.hidden = false; login.reset();
    document.getElementById('login-status').textContent = message;
  }
  // Имената идват от посетители, затова всичко се рисува само през textContent.
  function cell(row, text) { const td = document.createElement('td'); td.textContent = text; row.append(td); return td; }
  function draw(orders, held) {
    rows.replaceChildren();
    for (const order of orders) {
      const row = document.createElement('tr');
      cell(row, `Ред ${order.seatId.slice(0, -2)}, място ${Number(order.seatId.slice(-2))} (${order.tier === 'premium' ? 'Премиум' : 'Стандарт'})`);
      const person = cell(row, order.name); if (order.invoice) { const invoice = document.createElement('small'); invoice.className = 'admin-invoice'; invoice.textContent = `Фактура: ${order.invoice}`; person.append(invoice); }
      const contact = cell(row, ''); for (const [scheme, value] of [['mailto:', order.email], ['tel:', order.phone]]) { const link = document.createElement('a'); link.href = scheme + value; link.textContent = value; contact.append(link); }
      cell(row, `${order.total} €${order.status === 'deposit' ? ` (капаро ${order.total / 2} €)` : ''}`);
      const state = cell(row, ''); const badge = document.createElement('span'); badge.className = `admin-badge ${order.status}`; badge.textContent = labels[order.status] || order.status; state.append(badge);
      cell(row, `${date(order.createdAt)}${order.lang === 'en' ? ' · EN' : ''}`);
      cell(row, order.deadline ? date(order.deadline) : '—');
      const tools = cell(row, '');
      for (const action of order.actions) {
        const button = document.createElement('button'); button.type = 'button'; button.className = `admin-action ${action}`; button.textContent = actions[action];
        button.setAttribute('aria-label', `${actions[action]}: ${order.name}`);
        button.addEventListener('click', () => { if (confirm(`${ask[action]}\n\n${order.name}`)) update(order.id, action); });
        tools.append(button);
      }
      rows.append(row);
    }
    if (!orders.length) { const row = document.createElement('tr'); cell(row, 'Още няма заявки.').colSpan = 8; rows.append(row); }
    const count = status => orders.filter(order => order.status === status).length;
    const taken = count('requested') + count('deposit') + count('paid');
    document.getElementById('counts').textContent = `Заети места: ${taken} от 126 · Запазени от нас: ${held} · Чакат плащане: ${count('requested')} · Капаро: ${count('deposit')} · Платени: ${count('paid')}`;
  }
  async function load() {
    show('Зареждам…');
    try {
      const data = await call('/api/admin/orders');
      login.hidden = true; board.hidden = false; draw(data.orders, data.held); show('');
    } catch (error) {
      if (error.code === 401) logout('Ключът не е верен.'); else { board.hidden || show(error.message, 'error'); document.getElementById('login-status').textContent = error.message; }
    }
  }
  async function update(id, action) {
    rows.querySelectorAll('button').forEach(button => { button.disabled = true; });
    show('Записвам…');
    try {
      const result = await call(`/api/admin/orders/${id}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action }) });
      await load();
      show(action === 'cancelled' ? 'Записано. Мястото е освободено.' : result.emailed ? 'Записано. Имейлът е изпратен.' : 'Записано. Имейлът ще тръгне до 15 минути.', 'success');
    } catch (error) {
      await load();
      show(error.message, 'error');
    }
  }
  login.addEventListener('submit', event => {
    event.preventDefault();
    try { sessionStorage.setItem(key, new FormData(login).get('token').trim()); } catch { document.getElementById('login-status').textContent = 'Браузърът не позволява временно съхранение.'; return; }
    load();
  });
  document.getElementById('refresh').addEventListener('click', load);
  document.getElementById('logout').addEventListener('click', () => logout());
  if (token()) load();
})();
