(() => {
  const id = new URLSearchParams(location.hash.slice(1)).get('order');
  const output = document.getElementById('payment-status');
  const button = document.getElementById('check-status');
  const en = document.documentElement.lang === 'en';
  document.querySelectorAll('.lang-switch a:not([aria-current])').forEach(link => { link.href += location.hash; });
  async function check() {
    button.disabled = true;
    try {
      const token = id && sessionStorage.getItem(`etalon:${id}`);
      if (!id || !token || !window.ETALON_CONFIG.apiBase) { output.textContent = en ? 'We cannot check this request in this browser. Please contact the organiser with your request number.' : 'Не можем да проверим заявката в този браузър. Свържете се с организатора с номера на заявката.'; return; }
      const response = await fetch(`${window.ETALON_CONFIG.apiBase}/api/orders/${encodeURIComponent(id)}`, { headers: { Authorization: `Bearer ${token}` }, signal: AbortSignal.timeout(15000) });
      const data = await response.json(); if (!response.ok) throw new Error(data.message);
      const texts = en ? { paid: 'Your payment is confirmed and your seat is reserved. We look forward to seeing you on 21–22 March 2027!', pending: 'We are still waiting for the final result from the payment system. Check again in a few minutes. Do not make a second payment.', failed: 'The payment was not completed and the seat has been released. You can make a new request.' } : { paid: 'Плащането е потвърдено. Вашето място е запазено. Очакваме ви на 21–22 март 2027 г.!', pending: 'Все още очакваме окончателния резултат от платежната система. Проверете отново след няколко минути. Не създавайте повторно плащане.', failed: 'Плащането не е завършено успешно и мястото е освободено. Можете да направите нова заявка.' };
      output.textContent = texts[data.status] || (en ? 'Please contact the organiser to check your request.' : 'Свържете се с организатора за проверка на заявката.');
      document.getElementById('payment-reference').textContent = en ? `Request ${data.reference} · Seat ${data.seatId} · ${data.total} €` : `Заявка ${data.reference} · Място ${data.seatId} · ${data.total} €`;
    } catch { output.textContent = en ? 'The status is temporarily unavailable. Please try again or contact the organiser.' : 'Статусът временно не е достъпен. Опитайте отново или се свържете с организатора.'; }
    finally { button.disabled = false; }
  }
  button.addEventListener('click', check); check();
})();
