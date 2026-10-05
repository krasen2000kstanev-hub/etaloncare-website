import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { spawn } from 'node:child_process';
const base = (process.env.TEST_URL || 'http://127.0.0.1:4173').replace(/\/$/, '');
let server;
try { await fetch(base); } catch { server = spawn(process.execPath, ['scripts/dev.mjs'], { stdio: 'ignore' }); await new Promise(r => setTimeout(r, 700)); }
const browser = await chromium.launch({channel: process.platform === 'win32' ? 'chrome' : undefined});
try {
  const page = await browser.newPage({viewport:{width:1440,height:900}});
  await page.goto(base, {waitUntil:'networkidle'});
  const card = page.locator('.topic-card').first(); await card.scrollIntoViewIfNeeded();
  await page.waitForFunction(() => document.querySelector('.topic-card').classList.contains('motion-in'));
  await page.waitForFunction(() => document.querySelector('.topic-card').getAnimations().length === 0);
  await page.evaluate(() => scrollTo(0,0)); await card.scrollIntoViewIfNeeded();
  assert.equal(await card.evaluate(e=>e.getAnimations().length),0,'Reveal must not replay');
  await page.locator('.package').first().hover();
  await page.waitForFunction(()=>getComputedStyle(document.querySelector('.package')).transform !== 'none');
  await page.emulateMedia({reducedMotion:'reduce'});
  for (const selector of ['.topic-card','.hero-visual','.orbit']) assert.equal(await page.locator(selector).first().evaluate(e=>getComputedStyle(e).animationName),'none');
  await page.goto(base+'/booking.html',{waitUntil:'networkidle'}); await page.locator('[data-seat="A01"]').click();
  assert.equal(await page.locator('#selected-price').innerText(),'700 €');
  assert.equal(await page.locator('#selected-price').evaluate(e=>e.getAnimations().length),0);
  await page.emulateMedia({reducedMotion:'no-preference'}); await page.locator('[data-seat="C03"]').click();
  assert.equal(await page.locator('#selected-price').innerText(),'600 €');
  await page.waitForFunction(()=>document.querySelector('#selected-price').getAnimations().length===0);
  await page.setViewportSize({width:390,height:900}); await page.goto(base,{waitUntil:'networkidle'});
  assert.equal(await page.locator('.orbit').first().evaluate(e=>getComputedStyle(e).animationName),'none');
  console.log('Motion: one-time reveal, hover, mobile fallback, price feedback and live reduced-motion setting passed.');
} finally {await browser.close();server?.kill();}
