import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
const base = (process.env.TEST_URL || 'http://127.0.0.1:4173').replace(/\/$/, '');
let server;
try { await fetch(base); } catch { server = spawn(process.execPath, ['scripts/dev.mjs'], { stdio: 'ignore' }); await new Promise(r => setTimeout(r, 700)); }
await mkdir('output/qa', { recursive: true });
const browser = await chromium.launch({ headless: true, channel: process.env.TEST_BROWSER || (process.platform === 'win32' ? 'chrome' : undefined) });
const report = [];
try {
  for (const width of [360, 390, 768, 1440]) {
    const context = await browser.newContext({ viewport: { width, height: 950 }, deviceScaleFactor: 1 });
    const page = await context.newPage();
    const failures = []; page.on('pageerror', e => failures.push(e.message)); page.on('response', r => { if (r.status() >= 400 && r.url().startsWith(base)) failures.push(`${r.status()} ${r.url()}`); });
    await page.goto(base, { waitUntil: 'networkidle' }); await page.evaluate(() => document.fonts.ready);
    await page.waitForFunction(() => getComputedStyle(document.querySelector('.hero-copy')).opacity === '1');
    assert.equal(await page.locator('h1').count(), 1);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth); assert.equal(overflow, false, `Home overflows at ${width}`);
    for (const image of await page.locator('img').all()) { await image.scrollIntoViewIfNeeded(); await image.evaluate(img => img.decode()); }
    await page.evaluate(() => window.scrollTo(0, 0));
    const broken = await page.locator('img').evaluateAll(imgs => imgs.filter(i => !i.complete || i.naturalWidth === 0).map(i => i.src)); assert.deepEqual(broken, []);
    if (width < 760) {
      await page.getByRole('button', { name: 'Меню' }).click(); assert.equal(await page.getByRole('button', { name: 'Меню' }).getAttribute('aria-expanded'), 'true');
      await page.keyboard.press('Escape'); assert.equal(await page.getByRole('button', { name: 'Меню' }).getAttribute('aria-expanded'), 'false');
      await page.getByRole('button', { name: 'Меню' }).click(); await page.locator('#nav-links').getByRole('link', { name: 'Темите' }).click(); assert.equal(await page.getByRole('button', { name: 'Меню' }).getAttribute('aria-expanded'), 'false');
    }
    await page.locator('summary').first().click(); assert.equal(await page.locator('details').first().getAttribute('open'), '');
    const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    report.push({ page: 'home', width, overflow, errors: failures, accessibility: accessibility.violations.map(v => ({ id: v.id, impact: v.impact, elements: v.nodes.map(n => n.target) })) });
    assert.deepEqual(failures, []);
    await page.evaluate(() => scrollTo(0, 0)); await page.screenshot({ path: `output/qa/home-${width}.png`, fullPage: true });
    await page.goto(`${base}/booking.html?package=premium`, { waitUntil: 'networkidle' });
    assert.equal(await page.locator('.seat').count(), 100); assert.equal(await page.locator('.seat.premium').count(), 20);
    assert.equal(await page.locator('#reserve-button').isEnabled(), false); assert.equal(await page.locator('input[name=email]').isEnabled(), false);
    await page.locator('[data-seat="A01"]').click(); assert.equal(await page.locator('#selected-price').innerText(), '700 €'); assert.match(await page.locator('#form-status').innerText(), /Не е създадена резервация/);
    await page.locator('[data-seat="C03"]').click(); assert.equal(await page.locator('#selected-price').innerText(), '600 €');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, `Booking overflows at ${width}`);
    const bookingAxe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    report.push({ page: 'booking', width, accessibility: bookingAxe.violations.map(v => ({ id: v.id, impact: v.impact, elements: v.nodes.map(n => n.target) })) });
    await page.screenshot({ path: `output/qa/booking-${width}.png`, fullPage: true });
    await context.close();
  }
  const reduced = await browser.newPage({ reducedMotion: 'reduce' }); await reduced.goto(base);
  assert.equal(await reduced.locator('.hero-copy').evaluate(e => getComputedStyle(e).animationName), 'none'); await reduced.close();
  const noJs = await browser.newPage({ javaScriptEnabled: false }); await noJs.goto(base); assert.match(await noJs.locator('h1').innerText(), /Науката/); assert.ok(await noJs.locator('#participation').isVisible()); await noJs.close();
  await writeFile('output/qa/browser-report.json', JSON.stringify(report, null, 2));
  const violations = report.flatMap(r => r.accessibility);
  console.log(JSON.stringify(report, null, 2));
  assert.equal(violations.length, 0, 'Accessibility findings must be fixed');
  console.log('Browser QA passed at 360, 390, 768, 1440px, including accessibility, no-JS and reduced motion.');
} finally { await browser.close(); server?.kill(); }
