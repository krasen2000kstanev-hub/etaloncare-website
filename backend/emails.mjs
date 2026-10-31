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
  const firstAmount = order.total * config.firstInstalmentPercent / 100, secondAmount = order.total - firstAmount, second = date(secondDeadline(config), 'en'), first = date(firstDeadline(order, config), 'en');
  const transfer = `Bank transfer details:\nRecipient: ${bank.recipient}\nIBAN: ${bank.iban}\nBIC: ${bank.bic}\nPayment reference: Conference, ${seat}, ${order.name}`;
  const options = canSplit(order, config)
    ? `You can pay the full amount of ${order.total} € or a deposit of ${firstAmount} € by ${first}. The second instalment of ${secondAmount} € is due by ${second}.`
    : `Please pay the full amount of ${order.total} € by ${first}.`;
  const lost = 'If you withdraw or do not pay, the seat is lost and the deposit is not refunded.';
  const texts = {
    confirmation: ['Your request has been received', `We have reserved ${seat} for the conference “The Science of Nails” with Doug Schoon, 21–22 March 2027.\n\n${options}\n\n${transfer}\n\nIf we do not receive payment on time, the request expires and the seat is released.`],
    firstReminder: ['Payment reminder', `Your request for ${seat} is still unpaid. ${options}\n\n${transfer}\n\nIf we do not receive payment on time, the request expires and the seat is released.`],
    deposit: ['Your deposit has been received', `We have received your deposit of ${firstAmount} € for ${seat}.\n\nThe second instalment of ${secondAmount} € is due by ${second}. ${lost}\n\n${transfer}`],
    secondEarly: ['Second instalment reminder', `The second instalment of ${secondAmount} € for ${seat} is due by ${second}. ${lost}\n\n${transfer}`],
    secondLate: ['One week left for the second instalment', `The second instalment of ${secondAmount} € for ${seat} is due by ${second}. ${lost}\n\n${transfer}`],
    paid: ['Your payment has been received', `We have received full payment for ${seat}. We look forward to seeing you on 21–22 March 2027 at Inter Expo Center, Vitosha Hall, Sofia.`],
    expired: ['Your request has expired', `We did not receive payment on time and your request for ${seat} has expired. The seat has been released. If you would still like to attend, you can make a new request at https://etaloncare.com/en/booking.html.`],
    forfeited: ['Your seat has been released', `We did not receive the second instalment on time and your seat (${seat}) has been released. Under the terms, the deposit is not refunded.`]
  };
  const [subject, body] = texts[key];
  return { subject: `${subject} | The Science of Nails`, text: `Hello ${order.name},\n\n${body}\n\nQuestions: info@etaloncare.com\nETALON CARE` };
}

export function renderEmail(key, order, config, bank) {
  if (order.lang === 'en') return renderEnglish(key, order, config, bank);
  const seat = `ред ${order.seatId.slice(0, -2)}, място ${Number(order.seatId.slice(-2))}`;
  const firstAmount = order.total * config.firstInstalmentPercent / 100, second = date(secondDeadline(config)), first = date(firstDeadline(order, config));
  const transfer = `Данни за банков превод:\nПолучател: ${bank.recipient}\nIBAN: ${bank.iban}\nBIC: ${bank.bic}\nОснование: Конференция, ${seat}, ${order.name}`;
  const options = canSplit(order, config)
    ? `Можете да платите цялата сума ${order.total} € или първа вноска (капаро) ${firstAmount} € до ${first}. Втората вноска ${order.total - firstAmount} € е до ${second}.`
    : `Моля, платете цялата сума ${order.total} € до ${first}.`;
  const texts = {
    confirmation: ['Заявката ви е приета', `Запазихме ${seat} за конференцията „Науката за ноктите“ с Дъг Шун, 21–22 март 2027 г.\n\n${options}\n\n${transfer}\n\nАко не получим плащане в срок, заявката отпада и мястото се освобождава.`],
    firstReminder: ['Напомняне за плащане', `Заявката ви за ${seat} все още е неплатена. ${options}\n\n${transfer}\n\nАко не получим плащане в срок, заявката отпада и мястото се освобождава.`],
    deposit: ['Мястото ви е капарирано', `Получихме капарото ви ${firstAmount} € за ${seat}.\n\nВтората вноска ${order.total - firstAmount} € е до ${second}. При отказ или неплатена втора вноска мястото отпада, а капарото не се възстановява.\n\n${transfer}`],
    secondEarly: ['Напомняне за втората вноска', `Втората вноска ${order.total - firstAmount} € за ${seat} е до ${second}. При отказ или неплащане мястото отпада, а капарото не се възстановява.\n\n${transfer}`],
    secondLate: ['Остава една седмица за втората вноска', `Втората вноска ${order.total - firstAmount} € за ${seat} е до ${second}. При отказ или неплащане мястото отпада, а капарото не се възстановява.\n\n${transfer}`],
    paid: ['Плащането е получено', `Получихме пълното плащане за ${seat}. Очакваме ви на 21–22 март 2027 г. в Интер Експо Център, зала Витоша, София.`],
    expired: ['Заявката ви отпадна', `Не получихме плащане в срок и заявката ви за ${seat} отпадна. Мястото е освободено. Ако още искате да участвате, може да подадете нова заявка на https://etaloncare.com/booking.html.`],
    forfeited: ['Мястото ви отпадна', `Не получихме втората вноска в срок и мястото ви (${seat}) отпадна. Съгласно условията капарото не се възстановява.`]
  };
  const [subject, body] = texts[key];
  return { subject: `${subject} | Науката за ноктите`, text: `Здравейте, ${order.name},\n\n${body}\n\nЗа въпроси: info@etaloncare.com\nETALON CARE` };
}
