import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { randomUUID, randomBytes, generateKeyPairSync, sign } from 'node:crypto';
import { Service, seatPlan } from '../backend/service.mjs';
import { SqliteStore } from '../backend/sqlite.mjs';
import { Borica, macString, responseString } from '../backend/borica.mjs';
import { makeHandler } from '../backend/http.mjs';
import { serveStatic } from '../backend/static.mjs';
const baseConfig = JSON.parse(await readFile(new URL('../backend/event.json', import.meta.url)));
const merchant = generateKeyPairSync('rsa', { modulusLength: 2048 });
const gateway = generateKeyPairSync('rsa', { modulusLength: 2048 });
const payment = new Borica({ mode: 'test', terminal: 'V1234567', merchant: '1234567890', name: 'ETALON CARE', privateKey: merchant.privateKey.export({ type: 'pkcs8', format: 'pem' }), publicKey: gateway.publicKey.export({ type: 'spki', format: 'pem' }), backref: 'https://etaloncare.com/api/payment/callback', siteUrl: 'https://etaloncare.com' });
function setup(extra = {}) { const store = new SqliteStore(); const config = { ...baseConfig, paymentMode: 'card', registrationEnabled: true, seatPlanApproved: true, termsApproved: true, ...extra }; const service = new Service({ store, payment, config, clock: () => Date.parse('2026-11-02T12:00:00Z') }); return { store, service }; }
function input(seatId = '301') { return { seatId, name: 'Мария Иванова', email: 'test@example.com', phone: '+359888123456', cardholderName: 'MARIA IVANOVA', billingAddress: 'Sofia, Example street 1', consent: true, requestId: randomUUID(), token: randomBytes(32).toString('hex') }; }
function bankFields(order, changes = {}) {
  const fields = { ACTION: '0', RC: '00', APPROVAL: '123456', TERMINAL: payment.config.terminal, TRTYPE: '1', AMOUNT: `${order.total}.00`, CURRENCY: 'EUR', ORDER: order.bankOrder, RRN: '123456789012', INT_REF: '1234567890123456', PARES_STATUS: 'Y', ECI: '05', TIMESTAMP: '20261102120001', NONCE: order.nonce, ...changes };
  fields.P_SIGN = sign('RSA-SHA256', Buffer.from(responseString(fields)), gateway.privateKey).toString('hex').toUpperCase(); return fields;
}
test('registration stays closed until date, layout, terms and payment are ready', async () => {
  for (const patch of [{ registrationEnabled: false }, { seatPlanApproved: false }, { termsApproved: false }, { registrationOpens: '2026-12-01T00:00:00Z' }, { registrationCloses: '2026-11-01T00:00:00Z' }]) {
    const { store, service } = setup(patch); await assert.rejects(service.create(input()), { status: 403 }); store.close();
  }
  const { store, service } = setup(); service.payment = new Borica({}); await assert.rejects(service.create(input()), { status: 403 }); store.close();
});
test('server calculates the correct package price and does not expose attendees', async () => {
  const { store, service } = setup(); const data = input('101'); data.total = 1;
  const order = await service.create(data); assert.equal(order.total, 700); assert.equal(order.checkout.fields.AMOUNT, '700.00');
  const availability = await service.availability(); assert.equal(availability.seats.filter(s => !s.available).length, 1); assert.ok(!JSON.stringify(availability).includes(data.email)); assert.ok(!JSON.stringify(availability).includes(data.name)); store.close();
});
test('30 simultaneous requests for one seat yield exactly one reservation', async () => {
  const { store, service } = setup(); const results = await Promise.allSettled(Array.from({ length: 30 }, (_, i) => service.create(input(), `source-${i}`)));
  assert.equal(results.filter(r => r.status === 'fulfilled').length, 1); assert.equal((await store.occupied()).length, 1); assert.ok(results.filter(r => r.status === 'rejected').every(r => r.reason.status === 409)); store.close();
});
test('simultaneous retry uses one immutable order and the same signed checkout', async () => {
  const { store, service } = setup(); const data = input(); const results = await Promise.all(Array.from({ length: 4 }, () => service.create(data)));
  assert.equal(new Set(results.map(r => r.checkout.fields.ORDER)).size, 1); assert.equal((await store.occupied()).length, 1);
  await assert.rejects(service.create({ ...data, seatId: '302' }), { status: 409 }); await assert.rejects(service.create({ ...data, token: '0'.repeat(64) }), { status: 409 }); store.close();
});
test('input validation rejects unknown seats, missing consent, bad names and honeypot', async () => {
  const { store, service } = setup();
  for (const patch of [{ seatId: 'Z99' }, { consent: false }, { email: 'invalid' }, { phone: '<script>' }, { cardholderName: 'Мария' }, { billingAddress: '' }, { name: 'x\n<script>' }, { website: 'bot' }, { requestId: '123' }, { token: 'x' }]) await assert.rejects(service.create({ ...input(), ...patch }), { status: 400 });
  assert.equal((await store.occupied()).length, 0); store.close();
});
test('public order lookup requires secret token and returns no PII', async () => {
  const { store, service } = setup(); const data = input(); const result = await service.create(data);
  await assert.rejects(service.status(result.reference, 'wrong'), { status: 404 });
  const status = await service.status(result.reference, data.token); assert.equal(status.status, 'pending'); assert.ok(!('email' in status)); assert.ok(!('cardholderName' in status)); store.close();
});
test('valid signed bank result confirms the seat and remains idempotent', async () => {
  const { store, service } = setup(); const result = await service.create(input()); const order = await store.get(result.reference);
  assert.equal((await service.bankResult(bankFields(order))).status, 'paid'); await service.bankResult(bankFields(order)); assert.equal((await store.get(order.id)).status, 'paid'); assert.deepEqual(await store.occupied(), ['301']);
  await service.bankResult(bankFields(order, { ACTION: '2', RC: '05' })); assert.equal((await store.get(order.id)).status, 'paid'); store.close();
});
test('forged signature or altered bank amount never marks an order paid', async () => {
  const { store, service } = setup(); const result = await service.create(input()); const order = await store.get(result.reference);
  const tampered = bankFields(order); tampered.AMOUNT = '1.00'; await assert.rejects(service.bankResult(tampered), { status: 400 });
  for (const changes of [{ AMOUNT: '1.00' }, { CURRENCY: 'USD' }, { TERMINAL: 'V7654321' }, { NONCE: 'A'.repeat(32) }, { TRTYPE: '12' }, { RRN: '' }]) await assert.rejects(service.bankResult(bankFields(order, changes)), { status: 400 });
  assert.equal((await store.get(order.id)).status, 'pending'); store.close();
});
test('nonfinal bank error keeps the seat held; final decline releases it', async () => {
  const { store, service } = setup(); const result = await service.create(input()); const order = await store.get(result.reference);
  await service.bankResult(bankFields(order, { ACTION: '3', RC: '-25' })); assert.equal((await store.get(order.id)).status, 'pending'); assert.equal((await store.occupied()).length, 1);
  await service.bankResult(bankFields(order, { ACTION: '2', RC: '05' })); assert.equal((await store.get(order.id)).status, 'failed'); assert.equal((await store.occupied()).length, 0);
  assert.ok(await service.create(input())); store.close();
});
test('status query checks its nonce and releases only after the guard time', async () => {
  const { store, service } = setup(); const result = await service.create(input()); const order = await store.get(result.reference); const nonce = 'B'.repeat(32);
  const fields = bankFields(order, { TRTYPE: '90', TRAN_TRTYPE: '1', NONCE: nonce, ACTION: '3', RC: '-40' });
  await service.bankResult(fields, { statusQuery: true, nonce }); assert.equal((await store.occupied()).length, 1);
  service.clock = () => Date.parse('2026-11-02T12:17:00Z'); await service.bankResult(fields, { statusQuery: true, nonce }); assert.equal((await store.occupied()).length, 0); store.close();
});
test('missed callback is reconciled against signed bank status', async () => {
  const { store, service } = setup(); const result = await service.create(input()); const order = await store.get(result.reference);
  service.payment = Object.create(payment); service.payment.query = async (_, nonce) => bankFields(order, { TRTYPE: '90', TRAN_TRTYPE: '1', NONCE: nonce });
  service.clock = () => Date.parse('2026-11-02T12:17:00Z'); assert.deepEqual(await service.reconcile(), { checked: 1, unresolved: 0, expired: 0, forfeited: 0, emails: 0 }); assert.equal((await store.get(order.id)).status, 'paid'); store.close();
});
test('per-source reservation limit is shared and enforced', async () => {
  const { store, service } = setup(); for (let i = 1; i <= 5; i++) await service.create(input(`30${i}`), 'same-source');
  await assert.rejects(service.create(input('306'), 'same-source'), { status: 429 }); store.close();
});
test('BORICA MAC_GENERAL matches documented UTF-8 length-prefix example', () => {
  const fields = { TERMINAL: 'V1800001', TRTYPE: '1', AMOUNT: '9.00', CURRENCY: 'BGN', ORDER: '154744', TIMESTAMP: '20201012124757', NONCE: '9EADBD70C0A5AFBAD3DF405902602F79' };
  assert.equal(macString(fields, ['TERMINAL','TRTYPE','AMOUNT','CURRENCY','ORDER','TIMESTAMP','NONCE','RFU']), '8V18000011149.003BGN61547441420201012124757329EADBD70C0A5AFBAD3DF405902602F79-');
  assert.equal(macString({ a: 'Ж' }, ['a','b']), '2Ж-');
});
test('invalid plans are rejected rather than silently rendering wrong inventory', () => {
  assert.throws(() => seatPlan({ rows: [{ label: 'A', count: 99, tier: 'standard' }] }));
});
test('HTTP rejects foreign origins, invalid JSON, oversized bodies and public admin routes', async () => {
  const { store, service } = setup(); const handle = makeHandler(service, 'https://etaloncare.com');
  const event = { rawPath: '/api/orders', requestContext: { http: { method: 'POST', sourceIp: 'x' } }, headers: { origin: 'https://bad.example', 'content-type': 'application/json' }, body: '{}' };
  assert.equal((await handle(event)).statusCode, 403);
  assert.equal((await handle({ ...event, headers: { origin: 'https://etaloncare.com', 'content-type': 'application/json' }, body: '{' })).statusCode, 400);
  assert.equal((await handle({ ...event, body: 'x'.repeat(12001) })).statusCode, 413);
  assert.equal((await handle({ ...event, rawPath: '/api/admin' })).statusCode, 404); store.close();
});
test('Lambda static preview serves HTML/assets and blocks traversal', async () => {
  const request = rawPath => ({ rawPath, requestContext: { http: { method: 'GET' } } });
  const page = await serveStatic(request('/')); assert.equal(page.statusCode, 200); assert.match(Buffer.from(page.body, 'base64').toString(), /Науката/);
  const config = await serveStatic(request('/config.js')); assert.match(Buffer.from(config.body, 'base64').toString(), /apiBase: window.location.origin/);
  assert.equal((await serveStatic(request('/assets/logo.webp'))).statusCode, 200);
  assert.equal((await serveStatic(request('/not-here.html'))).statusCode, 404);
  assert.equal(await serveStatic(request('/../backend/event.json')), null);
  assert.equal((await serveStatic(request('/tests/browser.mjs'))), null);
  assert.equal((await serveStatic(request('/node_modules/playwright/index.js'))).statusCode, 404);
});
test('notify list validates input, honours honeypot and deduplicates by email', async () => {
  const { store, service } = setup({ registrationEnabled: false });
  await assert.rejects(service.notify({ name: 'М', email: 'a@b.bg', consent: true }), { status: 400 });
  await assert.rejects(service.notify({ name: 'Мария', email: 'bad', consent: true }), { status: 400 });
  await assert.rejects(service.notify({ name: 'Мария', email: 'a@b.bg' }), { status: 400 });
  await service.notify({ name: 'Бот', email: 'bot@b.bg', consent: true, website: 'x' });
  await service.notify({ name: 'Мария', email: 'A@B.bg', consent: true });
  await service.notify({ name: 'Мария Иванова', email: 'a@b.bg', consent: true });
  assert.deepEqual(store.db.prepare('SELECT email, name FROM subscribers').all().map(r => ({ ...r })), [{ email: 'a@b.bg', name: 'Мария Иванова' }]);
  store.close();
});
test('request mode records the seat without card data or a bank', async () => {
  const { store, service } = setup({ paymentMode: 'request' }); service.payment = new Borica({});
  const { cardholderName, billingAddress, ...data } = input('101');
  const order = await service.create(data); assert.equal(order.status, 'requested'); assert.equal(order.total, 700); assert.equal(order.checkout, undefined);
  assert.equal((await service.create(data)).reference, order.reference);
  const availability = await service.availability(); assert.equal(availability.paymentMode, 'request'); assert.equal(availability.registrationOpen, true); assert.equal(availability.seats.find(s => s.id === '101').available, false);
  await assert.rejects(service.create({ ...input('101') }), { status: 409 });
  assert.deepEqual(await store.pending(), []); assert.deepEqual(await service.reconcile(), { checked: 0, unresolved: 0, expired: 0, forfeited: 0, emails: 0 }); store.close();
});
test('unpaid requests free the seat after 14 days plus grace, marked ones stay', async () => {
  const { store, service } = setup({ paymentMode: 'request' }); service.payment = new Borica({}); const start = Date.parse('2026-11-02T12:00:00Z'); let now = start; service.clock = () => now;
  const { cardholderName, billingAddress, ...unpaid } = input('301'); const { cardholderName: c2, billingAddress: b2, ...paid } = input('302');
  await service.create(unpaid); await service.create(paid);
  const marked = await store.get(paid.requestId); store.db.prepare('UPDATE orders SET data=? WHERE id=?').run(JSON.stringify({ ...marked, status: 'deposit' }), paid.requestId);
  now = start + 17 * 86400000 - 1000; assert.equal((await service.reconcile()).expired, 0); assert.equal((await store.occupied()).length, 2);
  now = start + 17 * 86400000 + 1000; assert.equal((await service.reconcile()).expired, 1); assert.deepEqual(await store.occupied(), ['302']);
  assert.equal((await store.get(unpaid.requestId)).status, 'expired'); assert.equal((await store.get(paid.requestId)).status, 'deposit'); assert.equal((await service.reconcile()).expired, 0);
  const { cardholderName: c3, billingAddress: b3, ...again } = input('301'); assert.equal((await service.create(again)).status, 'requested'); store.close();
});
function requestSetup(start = '2026-11-02T12:00:00Z') {
  const { store, service } = setup({ paymentMode: 'request' }); const sent = []; let now = Date.parse(start);
  service.payment = new Borica({}); service.clock = () => now; service.bank = { recipient: 'ETALON', iban: 'BG00TEST', bic: 'TESTBGSF' };
  service.mailer = { ready: true, send: async mail => { sent.push(mail); } };
  const request = seatId => { const { cardholderName, billingAddress, ...data } = input(seatId); return data; };
  const mark = async (id, status) => { const order = await store.get(id); store.db.prepare('UPDATE orders SET data=? WHERE id=?').run(JSON.stringify({ ...order, status }), id); };
  return { store, service, sent, request, mark, at: value => { now = Date.parse(value); } };
}
test('confirmation and day 10 reminder go out once, with bank data and both amounts', async () => {
  const { store, service, sent, request, at } = requestSetup(); await service.create(request('101'));
  assert.equal(sent.length, 1); assert.match(sent[0].subject, /Заявката ви е приета/); assert.match(sent[0].text, /BG00TEST/); assert.match(sent[0].text, /700 €/); assert.match(sent[0].text, /350 €/); assert.match(sent[0].text, /ред 1, място 1/); assert.match(sent[0].text, /31\.01\.2027/);
  at('2026-11-11T12:00:00Z'); await service.reconcile(); assert.equal(sent.length, 1);
  at('2026-11-12T13:00:00Z'); await service.reconcile(); assert.equal(sent.length, 2); assert.match(sent[1].subject, /Напомняне/);
  await service.reconcile(); assert.equal(sent.length, 2);
  at('2026-11-19T11:00:00Z'); assert.equal((await service.reconcile()).expired, 0);
  at('2026-11-20T13:00:00Z'); assert.equal((await service.reconcile()).expired, 1); assert.match(sent.at(-1).subject, /отпадна/); assert.equal((await store.occupied()).length, 0); store.close();
});
test('deposit gets capture mail, two reminders, then forfeits the seat and keeps the deposit', async () => {
  const { store, service, sent, request, mark, at } = requestSetup(); const data = request('301'); await service.create(data); sent.length = 0;
  await mark(data.requestId, 'deposit'); at('2026-11-20T13:00:00Z'); await service.reconcile();
  assert.equal(sent.length, 1); assert.match(sent[0].subject, /капарирано/); assert.match(sent[0].text, /300 €/); assert.equal((await store.occupied()).length, 1);
  at('2027-01-16T12:00:00Z'); await service.reconcile(); assert.equal(sent.length, 1);
  at('2027-01-18T12:00:00Z'); await service.reconcile(); await service.reconcile(); assert.equal(sent.length, 2); assert.match(sent[1].subject, /втората вноска/);
  at('2027-01-25T12:00:00Z'); await service.reconcile(); assert.equal(sent.length, 3); assert.match(sent[2].subject, /една седмица/);
  at('2027-02-02T12:00:00Z'); assert.equal((await service.reconcile()).forfeited, 0); assert.equal((await store.occupied()).length, 1);
  at('2027-02-04T12:00:00Z'); assert.equal((await service.reconcile()).forfeited, 1); assert.equal((await store.occupied()).length, 0); assert.match(sent.at(-1).text, /не се възстановява/); assert.equal((await store.get(data.requestId)).status, 'forfeited'); store.close();
});
test('fully paid order keeps its seat with no reminders', async () => {
  const { store, service, sent, request, mark, at } = requestSetup(); const data = request('302'); await service.create(data); sent.length = 0;
  await mark(data.requestId, 'paid'); at('2027-02-10T12:00:00Z'); await service.reconcile(); await service.reconcile();
  assert.equal(sent.length, 1); assert.match(sent[0].subject, /получено/); assert.equal((await store.occupied()).length, 1); store.close();
});
test('late request is asked for the full amount only', async () => {
  const { store, service, sent, request } = requestSetup('2027-01-20T12:00:00Z'); await service.create(request('303'));
  assert.match(sent[0].text, /цялата сума 600 €/); assert.doesNotMatch(sent[0].text, /вноска/); store.close();
});
test('mail failure keeps the reservation and retries on the next run', async () => {
  const { store, service, sent, request } = requestSetup(); const ok = service.mailer.send; service.mailer.send = async () => { throw new Error('down'); };
  const data = request('304'); const order = await service.create(data); assert.equal(order.status, 'requested'); assert.equal((await store.occupied()).length, 1); assert.equal(sent.length, 0);
  assert.equal((await service.reconcile()).emails, 0); service.mailer.send = ok;
  assert.equal((await service.reconcile()).emails, 1); assert.equal(sent.length, 1); store.close();
});
