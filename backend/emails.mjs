// Календар и текст на имейлите при заявка с банков превод. Без мрежа, за да се тества лесно.
const DAY = 86400000;
const date = ms => new Date(ms).toLocaleDateString('bg-BG', { timeZone: 'Europe/Sofia' }).replace(' г.', '');
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

export function renderEmail(key, order, config, bank) {
  const seat = `ред ${order.seatId.slice(0, -2)}, място ${Number(order.seatId.slice(-2))}`;
  const firstAmount = order.total * config.firstInstalmentPercent / 100, second = date(secondDeadline(config)), first = date(firstDeadline(order, config));
  const transfer = `Данни за банков превод:\nПолучател: ${bank.recipient}\nIBAN: ${bank.iban}\nBIC: ${bank.bic}\nОснование: Конференция, ${seat}, ${order.name}`;
  const options = canSplit(order, config)
    ? `Можете да платите цялата сума ${order.total} € или първа вноска ${firstAmount} € до ${first}. Втората вноска ${order.total - firstAmount} € е до ${second}.`
    : `Моля, платете цялата сума ${order.total} € до ${first}.`;
  const texts = {
    confirmation: ['Заявката ви е приета', `Запазихме ${seat} за конференцията „Науката за ноктите“ с Дъг Шун, 21–22 март 2027 г.\n\n${options}\n\n${transfer}\n\nАко не получим плащане в срок, заявката отпада и мястото се освобождава.`],
    firstReminder: ['Напомняне за плащане', `Заявката ви за ${seat} все още е неплатена. ${options}\n\n${transfer}\n\nАко не получим плащане в срок, заявката отпада и мястото се освобождава.`],
    deposit: ['Мястото ви е капарирано', `Получихме първата ви вноска ${firstAmount} € за ${seat}.\n\nВтората вноска ${order.total - firstAmount} € е до ${second}. При неплатена втора вноска мястото отпада, а първата вноска не се възстановява.\n\n${transfer}`],
    secondEarly: ['Напомняне за втората вноска', `Втората вноска ${order.total - firstAmount} € за ${seat} е до ${second}. При неплащане мястото отпада, а първата вноска не се възстановява.\n\n${transfer}`],
    secondLate: ['Остава една седмица за втората вноска', `Втората вноска ${order.total - firstAmount} € за ${seat} е до ${second}. При неплащане мястото отпада, а първата вноска не се възстановява.\n\n${transfer}`],
    paid: ['Плащането е получено', `Получихме пълното плащане за ${seat}. Очакваме ви на 21–22 март 2027 г. в Интер Експо Център, зала Витоша, София.`],
    expired: ['Заявката ви отпадна', `Не получихме плащане в срок и заявката ви за ${seat} отпадна. Мястото е освободено. Ако още искате да участвате, може да подадете нова заявка на https://etaloncare.com/booking.html.`],
    forfeited: ['Мястото ви отпадна', `Не получихме втората вноска в срок и мястото ви (${seat}) отпадна. Съгласно условията първата вноска не се възстановява.`]
  };
  const [subject, body] = texts[key];
  return { subject: `${subject} | Науката за ноктите`, text: `Здравейте, ${order.name},\n\n${body}\n\nЗа въпроси: info@etaloncare.com\nETALON CARE` };
}
