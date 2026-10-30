import { readFile } from 'node:fs/promises';
import { Borica } from './borica.mjs';
import { DynamoStore } from './dynamo.mjs';
import { Service, seatPlan } from './service.mjs';
import { makeHandler } from './http.mjs';
import { Smtp } from './smtp.mjs';
const config = JSON.parse(await readFile(new URL('./event.json', import.meta.url)));
const store = new DynamoStore(process.env.TABLE_NAME);
store.seatIds = seatPlan(config).map(s => s.id);
const payment = new Borica({ mode: process.env.BORICA_MODE || 'test', terminal: process.env.BORICA_TERMINAL, merchant: process.env.BORICA_MERCHANT, name: process.env.BORICA_NAME, privateKey: process.env.BORICA_PRIVATE_KEY, passphrase: process.env.BORICA_PASSPHRASE, publicKey: process.env.BORICA_PUBLIC_KEY, backref: process.env.BORICA_BACKREF, siteUrl: process.env.SITE_URL || 'https://etaloncare.com' });
const mailer = new Smtp({ host: process.env.SMTP_HOST, port: process.env.SMTP_PORT, user: process.env.SMTP_USER, pass: process.env.SMTP_PASS });
const bank = { recipient: process.env.BANK_RECIPIENT, iban: process.env.BANK_IBAN, bic: process.env.BANK_BIC };
const apiHandler = makeHandler(new Service({ store, payment, config, mailer, bank }), process.env.SITE_URL || 'https://etaloncare.com');
export const handler = async event => {
  if (process.env.SERVE_STATIC === 'true' && event.rawPath && !event.rawPath.startsWith('/api/')) {
    const response = await (await import('./static.mjs')).serveStatic(event);
    if (response) return response;
  }
  return apiHandler(event);
};
