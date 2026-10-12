// Замъглява публичния сайт до старта. Собственикът отключва с ?preview=<ключ> (пази се в localStorage).
// ponytail: клиентска защита — съдържанието е в изходния код; истинско скриване изисква сървър.
(function () {
  var OPENS = Date.parse('2026-10-31T22:00:00Z'); // 1 ноември 00:00 София
  var KEY_HASH = '210e823ba79e19eb42f3706ff1cf89e770c7b2131feba3be55005aa3ccb9bccd';
  var STORE = 'etalon-preview';
  var root = document.documentElement;

  if (!/(^|\.)etaloncare\.com$/.test(location.hostname) || Date.now() >= OPENS) return;
  var param = new URLSearchParams(location.search).get('preview');
  try {
    if (param === 'off') localStorage.removeItem(STORE);
    if (localStorage.getItem(STORE) === KEY_HASH) return;
  } catch (e) {}

  root.classList.add('soon-locked');
  var css = document.createElement('style');
  css.textContent =
    'html.soon-locked,html.soon-locked body{overflow:hidden}' +
    'html.soon-locked body>*:not(#soon){filter:blur(14px);pointer-events:none;user-select:none}' +
    // flex + margin:auto: центрира, но при висок прозорец остава скролируем отгоре.
    '#soon{position:fixed;inset:0;z-index:100;display:flex;padding:16px;background:rgba(12,21,60,.55);font-family:Manrope,system-ui,sans-serif;overflow:auto}' +
    '#soon .box{margin:auto;width:min(540px,100%);background:#fff;color:#14213c;border-radius:14px;padding:28px;box-shadow:0 30px 80px rgba(12,21,60,.35);text-align:center}' +
    '#soon .bk{display:flex;gap:18px;align-items:center;text-align:left;padding-bottom:22px;margin-bottom:22px;border-bottom:1px solid #dbe3ea}' +
    '#soon .bk img{width:92px;height:auto;flex:none;border-radius:4px;box-shadow:0 8px 20px rgba(12,21,60,.25)}' +
    '#soon .bk small{display:block;font-size:11px;font-weight:800;letter-spacing:.12em;color:#116b80}' +
    '#soon .bk strong{display:block;font-size:20px;line-height:1.25;margin:4px 0 12px;letter-spacing:-.02em}' +
    '#soon a.book{display:inline-flex;align-items:center;min-height:44px;padding:10px 14px;line-height:1.3;border-radius:6px;background:#0c153c;color:#fff;font-weight:800;font-size:13px;text-decoration:none}' +
    '#soon a.book:hover{background:#1c2d70}' +
    '#soon h1{font-size:clamp(22px,4.5vw,28px);margin:0 0 8px;letter-spacing:-.03em}' +
    '#soon p{margin:0 0 20px;color:#526079;font-size:15px;line-height:1.6}' +
    '#soon .count{display:flex;justify-content:center;gap:10px;margin:0 0 24px}' +
    '#soon .count div{min-width:70px;padding:10px 6px;border-radius:8px;background:#0c153c;color:#fff}' +
    '#soon .count b{display:block;font-size:28px;line-height:1.2}#soon .count span{font-size:11px;letter-spacing:.08em;color:#73d4dc}' +
    '#soon form{display:grid;gap:10px;text-align:left}' +
    '#soon input[type=text],#soon input[type=email]{width:100%;min-height:48px;padding:10px 12px;border:1px solid #dbe3ea;border-radius:6px;font:inherit}' +
    '#soon label.c{display:flex;gap:8px;font-size:12px;color:#526079;align-items:flex-start}#soon label.c input{margin-top:3px}' +
    '#soon button{min-height:50px;border:0;border-radius:6px;background:#73d4dc;color:#0c153c;font:800 14px/1 Manrope,system-ui,sans-serif;cursor:pointer}' +
    '#soon button:disabled{opacity:.6;cursor:wait}#soon .st{min-height:20px;font-size:13px;margin:4px 0 0;text-align:center}' +
    '#soon .hp{position:absolute;left:-9999px}';
  root.appendChild(css);

  function pad(n) { return String(n).padStart(2, '0'); }

  function mount() {
    var el = document.createElement('div');
    el.id = 'soon';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-labelledby', 'soon-title');
    el.innerHTML =
      '<div class="box">' +
      '<div class="bk"><img src="https://book.etaloncare.com/assets/cover.png" alt="Корица на „Разширена химия на ноктите“" width="92">' +
      '<div><small>НОВАТА КНИГА НА ДЪГ ШУН</small><strong>Разширена химия на ноктите</strong>' +
      '<a class="book" href="https://book.etaloncare.com/">За да видиш книгата, натисни тук →</a></div></div>' +
      '<h1 id="soon-title">Регистрацията за конференцията стартира на 1 ноември</h1>' +
      '<p>Науката за ноктите с Дъг Шун · София, 21–22 март 2027 г.<br>Остави име и имейл и ще ти изпратим линк, щом регистрацията започне.</p>' +
      '<div class="count" aria-live="off"><div><b data-u="d">00</b><span>ДНИ</span></div><div><b data-u="h">00</b><span>ЧАСА</span></div><div><b data-u="m">00</b><span>МИН</span></div><div><b data-u="s">00</b><span>СЕК</span></div></div>' +
      '<form novalidate>' +
      '<input type="text" name="name" placeholder="Име и фамилия" autocomplete="name" required maxlength="100" aria-label="Име и фамилия">' +
      '<input type="email" name="email" placeholder="Имейл" autocomplete="email" required maxlength="190" aria-label="Имейл">' +
      '<input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">' +
      '<label class="c"><input type="checkbox" name="consent" required> <span>Съгласен/на съм ETALON CARE да използва името и имейла ми, за да ме уведоми за старта на регистрацията.</span></label>' +
      '<button type="submit">Уведоми ме</button>' +
      '<p class="st" role="status"></p>' +
      '</form></div>';
    document.body.appendChild(el);

    var units = {};
    el.querySelectorAll('[data-u]').forEach(function (b) { units[b.dataset.u] = b; });
    function tick() {
      var left = Math.max(0, OPENS - Date.now());
      if (!left) return location.reload();
      var s = Math.floor(left / 1000);
      units.d.textContent = pad(Math.floor(s / 86400));
      units.h.textContent = pad(Math.floor(s % 86400 / 3600));
      units.m.textContent = pad(Math.floor(s % 3600 / 60));
      units.s.textContent = pad(s % 60);
    }
    tick();
    setInterval(tick, 1000);

    var form = el.querySelector('form');
    var status = el.querySelector('.st');
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var name = form.name.value.trim();
      var email = form.email.value.trim();
      status.style.color = '#b3233c';
      if (name.length < 2) return (status.textContent = 'Моля, въведи име.');
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return (status.textContent = 'Моля, въведи валиден имейл.');
      if (!form.consent.checked) return (status.textContent = 'Моля, потвърди съгласието.');
      var btn = form.querySelector('button');
      btn.disabled = true;
      status.textContent = '';
      fetch((window.ETALON_CONFIG && window.ETALON_CONFIG.apiBase || '') + '/api/notify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, email: email, consent: true, website: form.website.value })
      }).then(function (r) {
        if (!r.ok) throw new Error(r.status);
        form.innerHTML = '<p class="st" role="status" style="color:#176b51">Благодарим! Ще ти пишем на ' +
          email.replace(/[<>&"]/g, '') + ', когато регистрацията започне.</p>';
      }).catch(function () {
        btn.disabled = false;
        status.textContent = 'Нещо се обърка. Опитай пак след малко.';
      });
    });
  }

  if (param) {
    // Ключът се проверява по hash, за да не стои в кода.
    crypto.subtle.digest('SHA-256', new TextEncoder().encode(param)).then(function (buf) {
      var hex = Array.from(new Uint8Array(buf), function (b) { return b.toString(16).padStart(2, '0'); }).join('');
      if (hex === KEY_HASH) {
        try { localStorage.setItem(STORE, KEY_HASH); } catch (e) {}
        root.classList.remove('soon-locked');
        var el = document.getElementById('soon');
        if (el) el.remove();
      }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
  else mount();
})();
