import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { generateKeyPairSync, sign, verify } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { Service } from '../backend/service.mjs';
import { SqliteStore } from '../backend/sqlite.mjs';
import { Borica, macString, responseString } from '../backend/borica.mjs';
import { makeHandler } from '../backend/http.mjs';
const merchant = generateKeyPairSync('rsa', { modulusLength: 2048 }), bank = generateKeyPairSync('rsa', { modulusLength: 2048 });
const payment = new Borica({ mode: 'test', terminal: 'V1234567', merchant: '1234567890', name: 'ETALON CARE', privateKey: merchant.privateKey.export({ type: 'pkcs8', format: 'pem' }), publicKey: bank.publicKey.export({ type: 'spki', format: 'pem' }), backref: 'https://example.com/api/payment/callback', siteUrl: 'https://example.com' });
const config = { ...JSON.parse(await readFile('backend/event.json', 'utf8')), registrationEnabled: true, seatPlanApproved: true, termsApproved: true };
const store = new SqliteStore(); const service = new Service({ store, payment, config, clock: () => Date.parse('2026-11-02T12:00:00Z') });
const base = 'http://127.0.0.1:4173', handle = makeHandler(service, base);
let server;
try { await fetch(base); } catch { server = spawn(process.execPath, ['scripts/dev.mjs'], { stdio: 'ignore' }); await new Promise(r => setTimeout(r, 700)); }
const browser = await chromium.launch({ headless: true, channel: process.platform === 'win32' ? 'chrome' : undefined });
try {
  const context = await browser.newContext(), page = await context.newPage();
  await page.route('**/config.js', route => route.fulfill({ contentType: 'text/javascript', body: `window.ETALON_CONFIG={apiBase:'${base}',registrationEnabled:true,seatPlanApproved:true,preview:false};` }));
  await page.route('**/api/**', async route => {
    const request = route.request(), url = new URL(request.url());
    const response = await handle({ rawPath: url.pathname, headers: request.headers(), body: request.postData() || '', requestContext: { http: { method: request.method(), sourceIp: 'checkout-test' } } });
    await route.fulfill({ status: response.statusCode, headers: response.headers, body: response.body });
  });
  let bankReached = false;
  await page.route('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link', async route => {
    // Intercept before any network request: no real bank or real payment is contacted.
    const fields = Object.fromEntries(new URLSearchParams(route.request().postData()));
    assert.equal(fields.AMOUNT, '600.00'); assert.equal(fields.CURRENCY, 'EUR');
    assert.ok(verify('RSA-SHA256', Buffer.from(macString(fields, ['TERMINAL','TRTYPE','AMOUNT','CURRENCY','ORDER','TIMESTAMP','NONCE','RFU'])), merchant.publicKey, Buffer.from(fields.P_SIGN, 'hex')));
    const order = await store.byBank(fields.ORDER);
    const reply = { ACTION:'0',RC:'00',APPROVAL:'123456',TERMINAL:fields.TERMINAL,TRTYPE:'1',AMOUNT:fields.AMOUNT,CURRENCY:'EUR',ORDER:fields.ORDER,RRN:'123456789012',INT_REF:'1234567890123456',NONCE:fields.NONCE,TIMESTAMP:'20261102120001',PARES_STATUS:'Y',ECI:'05' };
    reply.P_SIGN = sign('RSA-SHA256', Buffer.from(responseString(reply)), bank.privateKey).toString('hex').toUpperCase();
    const response = await handle({ rawPath:'/api/payment/callback',headers:{},body:new URLSearchParams(reply).toString(),requestContext:{http:{method:'POST'}} });
    assert.equal(response.statusCode,303); assert.equal((await store.get(order.id)).status,'paid'); bankReached = true;
    await route.fulfill({ status: 303, headers: { Location: response.headers.Location }, body:'' });
  });
  await page.goto(`${base}/booking.html`); await page.locator('input[name=email]').waitFor({ state: 'visible' });
  await page.waitForFunction(() => !document.querySelector('input[name=email]').disabled);
  await page.locator('[data-seat="C03"]').click();
  await page.getByLabel('Име и фамилия').fill('Тест Участник'); await page.getByLabel('Имейл', { exact: true }).fill('test@example.com'); await page.getByLabel('Телефон').fill('+359888123456');
  await page.getByLabel('Име на картодържателя').fill('TEST ATTENDEE'); await page.getByLabel('Адрес за плащане').fill('Sofia, Example street 1'); await page.locator('input[name=consent]').check();
  await page.getByRole('button',{name:'Подай заявка за участие'}).click();
  await page.waitForURL('**/payment.html#order=*'); await page.getByText('Плащането е потвърдено.',{exact:false}).waitFor();
  assert.ok(bankReached); assert.deepEqual(await store.occupied(),['C03']);
  console.log('Checkout: browser → atomic reservation → signed request → intercepted bank → verified callback → paid status passed. No bank network request was made.');
} finally { await browser.close(); store.close(); server?.kill(); }
