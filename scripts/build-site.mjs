import { cp, mkdir, readFile, writeFile } from 'node:fs/promises';
const production = process.argv.includes('--production');
await mkdir('output/site', { recursive: true }); await cp('site', 'output/site', { recursive: true });
const deployment = await readFile('deployment.local.json', 'utf8').then(JSON.parse).catch(() => ({}));
let config = await readFile('site/config.js', 'utf8');
const apiBase = process.env.ETALON_API_URL || deployment.ApiUrl;
if (production && !apiBase) throw new Error('Set ETALON_API_URL for a production build.');
if (apiBase) {
  const url = new URL(apiBase);
  if (url.protocol !== 'https:' || url.username || url.password || url.search || url.hash) throw new Error('ETALON_API_URL must be a plain HTTPS API URL.');
  config = config.replace("apiBase: ''", `apiBase: ${JSON.stringify(url.href.replace(/\/$/, ''))}`);
}
config = config.replace('preview: true', `preview: ${!production}`);
await writeFile('output/site/config.js', config);
if (production) {
  let html = await readFile('site/index.html', 'utf8');
  html = html.replace('noindex, nofollow', 'index, follow').replace(/\s*<div class="preview-strip"[^>]*>[^<]*<\/div>/, '');
  await writeFile('output/site/index.html', html);
  await writeFile('output/site/robots.txt', 'User-agent: *\nAllow: /\nSitemap: https://etaloncare.com/sitemap.xml\n');
}
console.log(`Built ${production ? 'production' : 'preview'} static files; real registration stays closed until approved configuration.`);
