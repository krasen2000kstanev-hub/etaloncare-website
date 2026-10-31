import assert from 'node:assert/strict';
import { readFile, access } from 'node:fs/promises';
import { resolve } from 'node:path';
const html = await readFile('index.html', 'utf8');
assert.ok(html.includes('<html lang="bg">'));
assert.equal((html.match(/<h1[\s>]/g) || []).length, 1);
assert.match(html, /<title>[^<]{30,90}<\/title>/);
assert.match(html, /name="description" content="[^"]{100,200}"/);
const schema = JSON.parse(html.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/)[1]);
assert.equal(schema.startDate, '2027-03-21'); assert.equal(schema.endDate, '2027-03-22'); assert.equal(schema.maximumAttendeeCapacity, 126);
assert.deepEqual(schema.offers.map(o => o.price), ['650', '750']); assert.ok(!('aggregateRating' in schema));
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
const english = await readFile('en/index.html', 'utf8');
assert.ok(english.includes('<html lang="en">')); assert.equal((english.match(/<h1[\s>]/g) || []).length, 1); assert.doesNotMatch(english.replace('aria-label="Български"', ''), /[А-Яа-я]{4,}/);
const englishSchema = JSON.parse(english.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/)[1]);
assert.deepEqual(englishSchema.offers.map(o => o.price), schema.offers.map(o => o.price)); assert.equal(englishSchema.maximumAttendeeCapacity, schema.maximumAttendeeCapacity);
for (const match of english.matchAll(/(?:src|href)="([^"#][^"]*)"/g)) { if (!/^[a-z]+:/i.test(match[1])) await access(resolve('en', match[1].split(/[?#]/)[0])); }
for (const page of ['index', 'booking', 'payment', 'privacy']) assert.match(await readFile(`${page}.html`, 'utf8'), new RegExp(`href="en/${page}.html"`));
console.log('SEO: language, heading, metadata, Event schema, anchors, images, files and calendar passed.');
