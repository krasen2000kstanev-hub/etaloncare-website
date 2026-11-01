// Календар и текст на имейлите при заявка с банков превод. Без мрежа, за да се тества лесно.
const DAY = 86400000;
const date = (ms, lang) => lang === 'en' ? new Date(ms).toLocaleDateString('en-GB', { timeZone: 'Europe/Sofia', day: 'numeric', month: 'long', year: 'numeric' }) : new Date(ms).toLocaleDateString('bg-BG', { timeZone: 'Europe/Sofia' }).replace(' г.', '');
const firstDeadline = (order, config) => order.createdAt * 1000 + config.requestDays * DAY;
export const secondDeadline = config => Date.parse(config.secondInstalmentDue);
// Който се запише твърде късно, няма реално време за две вноски и плаща наведнъж.
export const canSplit = (order, config) => firstDeadline(order, config) <= secondDeadline(config);

export function emailsDue(order, config, now) {
  const sent = order.emails || {}, first = firstDeadline(order, config), second = secondDeadline(config), due = [];
  if (order.status === 'requested') {
    due.push('confirmation');
    if (now >= order.createdAt * 1000 + config.firstReminderDay * DAY && now < first) due.push('firstReminder');
  } else if (order.status === 'deposit') {
    due.push('deposit');
    const [early, late] = config.secondReminderDays;
    if (now >= second - early * DAY && now < second - late * DAY) due.push('secondEarly');
    if (now >= second - late * DAY && now <= second) due.push('secondLate');
  } else if (['paid', 'expired', 'forfeited'].includes(order.status)) due.push(order.status);
  return due.filter(key => !sent[key]);
}

function renderEnglish(key, order, config, bank) {
  const seat = `row ${order.seatId.slice(0, -2)}, seat ${Number(order.seatId.slice(-2))}`;
  const half = order.total * config.firstInstalmentPercent / 100, rest = order.total - half, second = date(secondDeadline(config), 'en'), first = date(firstDeadline(order, config), 'en');
  const transfer = `Pay by bank transfer using these details:\nIBAN: ${bank.iban}${bank.bic ? `\nBIC: ${bank.bic}` : ''}\nBank: UniCredit Bulbank\nRecipient: Great Work OOD\nPayment reference: Conference, ${order.name}`;
  const options = canSplit(order, config)
    ? `You can secure your seat with a deposit of half the ticket price (${half} €) or pay the full amount (${order.total} €). The deadline is ${first}. If you pay the deposit, the remaining ${rest} € is due by ${second}.`
    : `You can pay the full amount of ${order.total} € by ${first}.`;
  const lost = 'If you withdraw or do not pay, the seat is lost and the deposit is not refunded.';
  const texts = {
    confirmation: ['You have reserved your seat', `You have reserved your seat: ${seat}, for the conference “The Science of Nails” with Doug Schoon, 21–22 March 2027.\n\n${options}\n\n${transfer}\n\nIf we do not receive payment on time, the request expires and the seat is released.`],
    firstReminder: ['Payment reminder', `Your seat (${seat}) is still unpaid. ${options}\n\n${transfer}\n\nIf we do not receive payment on time, the request expires and the seat is released.`],
    deposit: ['Your deposit has been received', `We have received your deposit of ${half} € for ${seat}.\n\nThe remaining ${rest} € is due by ${second}. ${lost}\n\n${transfer}`],
    secondEarly: ['Second instalment reminder', `The remaining ${rest} € for ${seat} is due by ${second}. ${lost}\n\n${transfer}`],
    secondLate: ['One week left for the second instalment', `The remaining ${rest} € for ${seat} is due by ${second}. ${lost}\n\n${transfer}`],
    paid: ['Your payment has been received', `We have received full payment for ${seat}. We look forward to seeing you on 21–22 March 2027 at Inter Expo Center, Vitosha Hall, Sofia.`],
    expired: ['Your request has expired', `We did not receive payment on time and your request for ${seat} has expired. The seat has been released. If you would still like to attend, you can make a new request at https://etaloncare.com/en/booking.html.`],
    forfeited: ['Your seat has been released', `We did not receive the second instalment on time and your seat (${seat}) has been released. Under the terms, the deposit is not refunded.`]
  };
  const [subject, body] = texts[key];
  return { subject: `${subject} | Conference with Doug Schoon`, text: `Hello ${order.name},\n\n${body}\n\nQuestions: info@etaloncare.com\nETALON CARE` };
}

export function renderEmail(key, order, config, bank) {
  if (order.lang === 'en') return renderEnglish(key, order, config, bank);
  const seat = `ред ${order.seatId.slice(0, -2)}, място ${Number(order.seatId.slice(-2))}`;
  const half = order.total * config.firstInstalmentPercent / 100, rest = order.total - half, second = date(secondDeadline(config)), first = date(firstDeadline(order, config));
  const transfer = `С банков превод с тези данни:\nIBAN: ${bank.iban}${bank.bic ? `\nBIC: ${bank.bic}` : ''}\nБанка: УниКредит Булбанк\nПолучател: Грейт Уърк ООД\nОснование: Конференция, ${order.name}`;
  const options = canSplit(order, config)
    ? `Можеш да капарираш мястото с половината сума от билета (${half} €) или да заплатиш цялата (${order.total} €). Срокът е до ${first}. Ако капарираш, останалите ${rest} € са до ${second}.`
    : `Можеш да заплатиш цялата сума ${order.total} € до ${first}.`;
  const lost = 'При отказ или неплащане мястото отпада, а капарото не се възстановява.';
  const texts = {
    confirmation: ['Ти запази мястото си', `Ти запази мястото си: ${seat}, за конференцията „Науката за ноктите“ с Дъг Шун, 21–22 март 2027 г.\n\n${options}\n\n${transfer}\n\nАко не получим плащане в срок, заявката отпада и мястото се освобождава.`],
    firstReminder: ['Напомняне за плащане', `Мястото ти (${seat}) все още е неплатено. ${options}\n\n${transfer}\n\nАко не получим плащане в срок, заявката отпада и мястото се освобождава.`],
    deposit: ['Мястото ти е капарирано', `Получихме капарото ти ${half} € за ${seat}.\n\nОстаналите ${rest} € са до ${second}. ${lost}\n\n${transfer}`],
    secondEarly: ['Напомняне за втората вноска', `Останалите ${rest} € за ${seat} са до ${second}. ${lost}\n\n${transfer}`],
    secondLate: ['Остава една седмица за втората вноска', `Останалите ${rest} € за ${seat} са до ${second}. ${lost}\n\n${transfer}`],
    paid: ['Плащането е получено', `Получихме пълното плащане за ${seat}. Очакваме те на 21–22 март 2027 г. в Интер Експо Център, зала Витоша, София.`],
    expired: ['Заявката ти отпадна', `Не получихме плащане в срок и заявката ти за ${seat} отпадна. Мястото е освободено. Ако още искаш да участваш, можеш да подадеш нова заявка на https://etaloncare.com/booking.html.`],
    forfeited: ['Мястото ти отпадна', `Не получихме втората вноска в срок и мястото ти (${seat}) отпадна. Съгласно условията капарото не се възстановява.`]
  };
  const [subject, body] = texts[key];
  return { subject: `${subject} | Конференция с Дъг Шун`, text: `Здравей, ${order.name},\n\n${body}\n\nЗа въпроси: info@etaloncare.com\nETALON CARE` };
}
