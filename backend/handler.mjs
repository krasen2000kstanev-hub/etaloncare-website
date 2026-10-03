import { readFile } from 'node:fs/promises';
import { Borica } from './borica.mjs';
import { DynamoStore } from './dynamo.mjs';
import { Service, seatPlan } from './service.mjs';
import { makeHandler } from './http.mjs';
const config = JSON.parse(await readFile(new URL('./event.json', import.meta.url)));
const store = new DynamoStore(process.env.TABLE_NAME);
store.seatIds = seatPlan(config).map(s => s.id);
const payment = new Borica({ mode: process.env.BORICA_MODE || 'test', terminal: process.env.BORICA_TERMINAL, merchant: process.env.BORICA_MERCHANT, name: process.env.BORICA_NAME, privateKey: process.env.BORICA_PRIVATE_KEY, passphrase: process.env.BORICA_PASSPHRASE, publicKey: process.env.BORICA_PUBLIC_KEY, backref: process.env.BORICA_BACKREF, siteUrl: process.env.SITE_URL || 'https://etaloncare.com' });
const apiHandler = makeHandler(new Service({ store, payment, config }), process.env.SITE_URL || 'https://etaloncare.com');
export const handler = async event => {
  if (process.env.SERVE_STATIC === 'true' && event.rawPath && !event.rawPath.startsWith('/api/')) {
    const response = await (await import('./static.mjs')).serveStatic(event);
    if (response) return response;
  }
  return apiHandler(event);
};
