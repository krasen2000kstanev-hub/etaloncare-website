import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve, sep } from 'node:path';
const origin = 'https://etaloncare.com', root = resolve('output/previous-public-site');
await mkdir(root, { recursive: true });
const htmlResponse = await fetch(origin); if (!htmlResponse.ok) throw new Error('Existing site unavailable');
const html = await htmlResponse.text(); await writeFile(resolve(root, 'index.html'), html);
const references = [...html.matchAll(/(?:src|href)="([^"]+)"/g)].map(m => m[1]);
const files = new Set(references.map(value => { try { return new URL(value, origin); } catch { return null; } }).filter(url => url?.origin === origin && /\.(css|js|jsx|png|svg|webp|jpg)$/.test(url.pathname)).map(url => url.href));
const saved = [];
for (const address of files) {
  const url = new URL(address), path = resolve(root, '.' + decodeURIComponent(url.pathname)); if (!path.startsWith(root + sep)) continue;
  const response = await fetch(url); if (!response.ok) continue;
  await mkdir(dirname(path), { recursive: true }); await writeFile(path, Buffer.from(await response.arrayBuffer())); saved.push(url.pathname);
}
await writeFile(resolve(root, 'ARCHIVE.md'), `Public frontend snapshot of etaloncare.com, captured ${new Date().toISOString()}.\n\nSaved files: ${saved.join(', ')}.\n\nThis is not a full hosting backup: PHP source, database, orders, private configuration and mail require a backup through SuperHosting. Do not use it as a substitute before replacing the live site.\n`);
console.log(`Preserved public HTML and ${saved.length} frontend files; private hosting backup still required.`);
