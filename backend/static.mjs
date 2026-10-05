import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { resolve, sep, extname } from 'node:path';
import { createHash } from 'node:crypto';
const root = resolve(fileURLToPath(new URL('../', import.meta.url)));
const publicFiles = JSON.parse(await readFile(resolve(root, 'public-files.json'), 'utf8'));
const types = { '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.webp': 'image/webp', '.jpg': 'image/jpeg', '.svg': 'image/svg+xml', '.woff2': 'font/woff2', '.xml': 'application/xml', '.ics': 'text/calendar; charset=utf-8', '.txt': 'text/plain; charset=utf-8' };
const html = await readFile(resolve(root, 'index.html'), 'utf8');
const schema = html.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/)[1];
const hash = createHash('sha256').update(schema).digest('base64');
const secure = { 'X-Content-Type-Options': 'nosniff', 'X-Frame-Options': 'DENY', 'X-Robots-Tag': 'noindex, nofollow', 'Referrer-Policy': 'no-referrer', 'Strict-Transport-Security': 'max-age=31536000', 'Content-Security-Policy': `default-src 'self'; script-src 'self' 'sha256-${hash}'; style-src 'self' 'unsafe-inline'; img-src 'self'; font-src 'self'; connect-src 'self'; form-action 'self' https://3dsgate-dev.borica.bg https://3dsgate.borica.bg; frame-ancestors 'none'; base-uri 'self'; object-src 'none'` };
export async function serveStatic(event) {
  if (!['GET', 'HEAD'].includes(event.requestContext?.http?.method)) return null;
  let name;
  try { name = decodeURIComponent(event.rawPath === '/' ? '/index.html' : event.rawPath); } catch { return null; }
  const path = resolve(root, '.' + name);
  if (!path.startsWith(root + sep) || !types[extname(path)]) return null;
  try {
    if (!(publicFiles.includes(name.slice(1)) || path.startsWith(resolve(root, 'assets') + sep))) throw new Error('Not public');
    let content = await readFile(path);
    if (name === '/config.js') content = Buffer.from(content.toString('utf8').replace("apiBase: ''", 'apiBase: window.location.origin'));
    return { statusCode: 200, headers: { ...secure, 'Content-Type': types[extname(path)], 'Cache-Control': extname(path) === '.html' || name === '/config.js' ? 'no-store' : 'public,max-age=300' }, isBase64Encoded: true, body: event.requestContext.http.method === 'HEAD' ? '' : content.toString('base64') };
  } catch {
    return { statusCode: 404, headers: { ...secure, 'Content-Type': 'text/html; charset=utf-8' }, body: (await readFile(resolve(root, '404.html'), 'utf8')) };
  }
}
