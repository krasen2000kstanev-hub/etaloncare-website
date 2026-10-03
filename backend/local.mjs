import { createServer } from 'node:http';
import { readFile, mkdir } from 'node:fs/promises';
import { SqliteStore } from './sqlite.mjs';
import { Borica } from './borica.mjs';
import { Service } from './service.mjs';
import { makeHandler } from './http.mjs';
const config = JSON.parse(await readFile(new URL('./event.json', import.meta.url)));
await mkdir('tmp', { recursive: true });
const service = new Service({ store: new SqliteStore('tmp/etalon.sqlite'), payment: new Borica({}), config });
const handle = makeHandler(service, 'http://127.0.0.1:4173');
createServer(async (req, res) => {
  const chunks = []; let size = 0;
  for await (const chunk of req) { size += chunk.length; if (size > 12000) { res.writeHead(413); return res.end(); } chunks.push(chunk); }
  const response = await handle({ rawPath: new URL(req.url, 'http://localhost').pathname, body: Buffer.concat(chunks).toString(), headers: req.headers, requestContext: { http: { method: req.method, sourceIp: req.socket.remoteAddress } } });
  res.writeHead(response.statusCode, response.headers); res.end(response.body);
}).listen(4174, '127.0.0.1', () => console.log('Local API http://127.0.0.1:4174; registration closed.'));
