import assert from 'node:assert/strict';
import { readFile, access } from 'node:fs/promises';
import { resolve } from 'node:path';
const html = await readFile('index.html', 'utf8');
assert.ok(html.includes('<html lang="bg">'));
assert.equal((html.match(/<h1[\s>]/g) || []).length, 1);
assert.match(html, /<title>[^<]{30,90}<\/title>/);
assert.match(html, /name="description" content="[^"]{100,200}"/);
const schema = JSON.parse(html.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/)[1]);
assert.equal(schema.startDate, '2027-03-21'); assert.equal(schema.endDate, '2027-03-22'); assert.equal(schema.maximumAttendeeCapacity, 100);
assert.deepEqual(schema.offers.map(o => o.price), ['600', '700']); assert.ok(!('aggregateRating' in schema));
assert.equal(schema.url, html.match(/rel="canonical" href="([^"]+)"/)[1]);
const ids = [...html.matchAll(/\sid="([^"]+)"/g)].map(m => m[1]); assert.equal(new Set(ids).size, ids.length);
for (const match of html.matchAll(/(?:src|href)="([^"]+)"/g)) {
  const url = match[1]; if (url === '#') continue;
  if (url.startsWith('#')) { assert.ok(ids.includes(url.slice(1)), `Anchor ${url}`); continue; }
  if (/^[a-z]+:/i.test(url)) continue;
  await access(resolve('.', url.split(/[?#]/)[0]));
}
for (const image of html.matchAll(/<img\b[^>]*>/g)) { assert.match(image[0], /\balt="[^"]+"/); assert.match(image[0], /\bwidth="\d+"/); assert.match(image[0], /\bheight="\d+"/); }
assert.match(await readFile('assets/conference.ics', 'utf8'), /DTEND;VALUE=DATE:20270323/);
console.log('SEO: language, heading, metadata, Event schema, anchors, images, files and calendar passed.');
