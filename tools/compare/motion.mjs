#!/usr/bin/env node
/*
 * Motion parity between the Next.js reference and the PHP site, with motion
 * allowed: CSS animations, GSAP scroll reveals, header scroll state, menu
 * dialog transitions, background video, button hover wipe.
 *   node tools/compare/motion.mjs [--ref=http://localhost:3000] [--php=http://localhost:8080]
 */
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const require = createRequire(path.resolve(here, '../../../adepc/package.json'));
const { chromium } = require('@playwright/test');
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const SITES = { ref: args.ref ?? 'http://localhost:3000', php: args.php ?? 'http://localhost:8080' };

async function probe(browser, base) {
  const out = {};
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'no-preference' });
  const page = await ctx.newPage();
  await page.goto(`${base}/en`, { waitUntil: 'load' });
  out.jsClass = await page.evaluate(() => document.documentElement.classList.contains('js'));
  out.heroRise = await page.evaluate(() => [...document.querySelectorAll('#hero-title .rise-line > span')].map((s) => {
    const c = getComputedStyle(s);
    return `${c.animationName} ${c.animationDuration} ${c.animationDelay} ${c.animationTimingFunction}`;
  }));
  out.fadeLate = await page.evaluate(() => { const c = getComputedStyle(document.querySelector('.fade-late')); return `${c.animationName} ${c.animationDuration} ${c.animationDelay}`; });
  await page.waitForFunction(() => document.documentElement.classList.contains('motion-ready'), null, { timeout: 10000 }).catch(() => {});
  out.motionReady = await page.evaluate(() => document.documentElement.classList.contains('motion-ready'));

  // A reveal far below the fold is hidden until scrolled to, then shown.
  const sel = '#pillars-title ~ article h3[data-reveal="up"]';
  out.revealBefore = await page.evaluate((s) => { const c = getComputedStyle(document.querySelector(s)); return `${c.opacity} ${c.transform}`; }, sel);
  await page.locator(sel).first().scrollIntoViewIfNeeded();
  await page.waitForTimeout(1800);
  out.revealAfter = await page.evaluate((s) => { const c = getComputedStyle(document.querySelector(s)); return `${c.opacity} ${c.transform}`; }, sel);

  // Image wipe (clip) reveal + media scale.
  const clip = '#pillars-title ~ article [data-reveal="clip"]';
  out.clipAfter = await page.evaluate((s) => { const el = document.querySelector(s); return `${getComputedStyle(el).clipPath} ${getComputedStyle(el.querySelector('[data-reveal-media]')).transform}`; }, clip);
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight - 2000));
  await page.waitForTimeout(300);
  out.clipBefore = await page.evaluate(() => {
    const els = [...document.querySelectorAll('[data-reveal="clip"]')].filter((e) => e.getBoundingClientRect().top > window.innerHeight * 1.2);
    return els[0] ? getComputedStyle(els[0]).clipPath : 'none below fold';
  });

  // Header colour after scrolling.
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(900); // let the 0.5 s colour transition finish
  const header = () => page.evaluate(() => { const h = document.querySelector('header'); return `${h.classList.contains('bg-ink') ? 'ink' : 'transparent'} shade:${getComputedStyle(h.firstElementChild).opacity} ${getComputedStyle(h).transitionDuration}`; });
  out.headerTop = await header();
  await page.evaluate(() => window.scrollTo(0, 400));
  await page.waitForTimeout(800);
  out.headerScrolled = await header();

  // Background video starts after load, with a pause control.
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForSelector('section[aria-labelledby="hero-title"] video', { timeout: 15000 }).catch(() => {});
  out.heroVideo = await page.evaluate(() => { const v = document.querySelector('section[aria-labelledby="hero-title"] video'); return v ? `${v.className} muted:${v.muted} loop:${v.loop}` : 'none'; });
  out.pauseButton = await page.getByRole('button', { name: 'Pause background video' }).isVisible({ timeout: 10000 }).catch(() => false);

  // Button hover wipe (pseudo-element transition).
  out.buttonWipe = await page.evaluate(() => {
    const b = document.querySelector('a.group.relative.isolate');
    const c = getComputedStyle(b, '::before');
    return `${c.transform} ${c.transitionProperty} ${c.transitionDuration} ${c.transitionTimingFunction} ${c.transformOrigin}`;
  });
  await ctx.close();

  // Mobile menu dialog.
  const m = await browser.newContext({ viewport: { width: 390, height: 844 }, reducedMotion: 'no-preference' });
  const mp = await m.newPage();
  await mp.goto(`${base}/en`, { waitUntil: 'load' });
  out.menuClosed = await mp.evaluate(() => { const d = document.querySelector('dialog.menu-dialog'); const c = getComputedStyle(d); return `${d.open} ${c.opacity} ${c.clipPath} ${c.transitionDuration}`; });
  await mp.getByRole('button', { name: 'Open menu' }).click();
  await mp.waitForTimeout(900);
  out.menuOpen = await mp.evaluate(() => { const d = document.querySelector('dialog.menu-dialog'); const c = getComputedStyle(d); return `${d.open} ${c.opacity} ${c.clipPath}`; });
  await mp.keyboard.press('Escape');
  await mp.waitForTimeout(700);
  out.menuEscape = await mp.evaluate(() => document.querySelector('dialog.menu-dialog').open);
  await m.close();
  return out;
}

const browser = await chromium.launch();
const ref = await probe(browser, SITES.ref);
const php = await probe(browser, SITES.php);
await browser.close();
let diffs = 0;
for (const k of Object.keys(ref)) {
  const same = JSON.stringify(ref[k]) === JSON.stringify(php[k]);
  if (!same) diffs++;
  console.log(`${same ? 'same' : 'DIFF'}  ${k.padEnd(15)} ${JSON.stringify(php[k])}${same ? '' : `\n                      ref: ${JSON.stringify(ref[k])}`}`);
}
process.exit(diffs ? 1 : 0);
