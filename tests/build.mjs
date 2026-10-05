import assert from 'node:assert/strict';
import { cp, mkdir, mkdtemp, readFile, rm } from 'node:fs/promises';
import { resolve, sep } from 'node:path';
import { spawnSync } from 'node:child_process';
const root = resolve('output');
await mkdir(root, { recursive: true });
const fixture = await mkdtemp(resolve(root, 'build-test-'));
assert.ok(fixture.startsWith(root + sep));
try {
  const publicFiles = JSON.parse(await readFile('public-files.json', 'utf8'));
  for (const file of [...publicFiles, 'assets', 'public-files.json']) await cp(file, resolve(fixture, file), { recursive: true });
  const build = url => spawnSync(process.execPath, [resolve('scripts/build-site.mjs'), '--production'], { cwd: fixture, env: { ...process.env, ETALON_API_URL: url }, encoding: 'utf8' });
  assert.equal(build('https://api.example.invalid/').status, 0);
  assert.match(await readFile(resolve(fixture, 'output/site/config.js'), 'utf8'), /apiBase: "https:\/\/api.example.invalid"/);
  assert.match(await readFile(resolve(fixture, 'output/site/index.html'), 'utf8'), /index, follow/);
  for (const file of ['backend/event.json', 'public-files.json', 'deployment.local.json', 'README.md']) await assert.rejects(readFile(resolve(fixture, 'output/site', file)));
  for (const url of ['', 'http://api.example.invalid', 'https://user:secret@api.example.invalid', 'https://api.example.invalid/#token']) assert.notEqual(build(url).status, 0);
  console.log('Production build: external API configuration, indexing and HTTPS validation passed.');
} finally { await rm(fixture, { recursive: true, force: true }); }
