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
function setup(extra = {}) { const store = new SqliteStore(); const config = { ...baseConfig, registrationEnabled: true, seatPlanApproved: true, termsApproved: true, ...extra }; const service = new Service({ store, payment, config, clock: () => Date.parse('2026-11-02T12:00:00Z') }); return { store, service }; }
function input(seatId = 'C01') { return { seatId, name: 'Мария Иванова', email: 'test@example.com', phone: '+359888123456', cardholderName: 'MARIA IVANOVA', billingAddress: 'Sofia, Example street 1', consent: true, requestId: randomUUID(), token: randomBytes(32).toString('hex') }; }
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
  const { store, service } = setup(); const data = input('A01'); data.total = 1;
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
  await assert.rejects(service.create({ ...data, seatId: 'C02' }), { status: 409 }); await assert.rejects(service.create({ ...data, token: '0'.repeat(64) }), { status: 409 }); store.close();
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
  assert.equal((await service.bankResult(bankFields(order))).status, 'paid'); await service.bankResult(bankFields(order)); assert.equal((await store.get(order.id)).status, 'paid'); assert.deepEqual(await store.occupied(), ['C01']);
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
  service.clock = () => Date.parse('2026-11-02T12:17:00Z'); assert.deepEqual(await service.reconcile(), { checked: 1, unresolved: 0 }); assert.equal((await store.get(order.id)).status, 'paid'); store.close();
});
test('per-source reservation limit is shared and enforced', async () => {
  const { store, service } = setup(); for (let i = 1; i <= 5; i++) await service.create(input(`C0${i}`), 'same-source');
  await assert.rejects(service.create(input('C06'), 'same-source'), { status: 429 }); store.close();
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
