(() => {
  const id = new URLSearchParams(location.hash.slice(1)).get('order');
  const output = document.getElementById('payment-status');
  const button = document.getElementById('check-status');
  async function check() {
    button.disabled = true;
    try {
      const token = id && sessionStorage.getItem(`etalon:${id}`);
      if (!id || !token || !window.ETALON_CONFIG.apiBase) { output.textContent = 'Не можем да проверим заявката в този браузър. Свържете се с организатора с номера на заявката.'; return; }
      const response = await fetch(`${window.ETALON_CONFIG.apiBase}/api/orders/${encodeURIComponent(id)}`, { headers: { Authorization: `Bearer ${token}` }, signal: AbortSignal.timeout(15000) });
      const data = await response.json(); if (!response.ok) throw new Error(data.message);
      const texts = { paid: 'Плащането е потвърдено. Вашето място е запазено. Очакваме ви на 21–22 март 2027 г.!', pending: 'Все още очакваме окончателния резултат от платежната система. Проверете отново след няколко минути. Не създавайте повторно плащане.', failed: 'Плащането не е завършено успешно и мястото е освободено. Можете да направите нова заявка.' };
      output.textContent = texts[data.status] || 'Свържете се с организатора за проверка на заявката.';
      document.getElementById('payment-reference').textContent = `Заявка ${data.reference} · Място ${data.seatId} · ${data.total} €`;
    } catch { output.textContent = 'Статусът временно не е достъпен. Опитайте отново или се свържете с организатора.'; }
    finally { button.disabled = false; }
  }
  button.addEventListener('click', check); check();
})();
