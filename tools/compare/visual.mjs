#!/usr/bin/env node
/*
 * Browser comparison of the Next.js reference and the PHP site.
 * For every route and width (reduced motion, fonts loaded, lazy images in):
 *   1. layout: the box (x, y, width, height) of every element in <body>, in
 *      document order, must match within 1 px;
 *   2. pixels: full-page screenshots are diffed; the share of pixels that
 *      differ noticeably is reported and a diff image is written.
 *
 * Dev-only. Uses Playwright and sharp from the old project's dependencies.
 *   node tools/compare/visual.mjs [--ref=http://localhost:3000] [--php=http://localhost:8080]
 *        [--widths=390,1440] [--only=/fr,/en/about] [--out=.compare]
 */
import { createRequire } from 'node:module';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const require = createRequire(path.resolve(here, '../../../adepc/package.json'));
const { chromium } = require('@playwright/test');
const sharp = require(require.resolve('sharp', { paths: [require.resolve('next')] }));

const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const REF = args.ref ?? 'http://localhost:3000';
const PHP = args.php ?? 'http://localhost:8080';
const OUT = path.resolve(args.out ?? path.join(here, '../../.compare'));
const ROUTES = (args.only?.split(',')) ?? [
  '/fr', '/en', '/fr/eglises', '/en/churches', '/fr/eglises/montreal', '/en/churches/ottawa',
  '/fr/evenements', '/en/events', '/fr/regarder', '/en/watch', '/fr/a-propos', '/en/about',
  '/fr/histoires', '/en/stories', '/fr/donner', '/en/give', '/fr/contact', '/en/contact',
  '/fr/confidentialite', '/en/privacy', '/fr/introuvable', '/en/does-not-exist',
];
const WIDTHS = (args.widths ?? '390,768,1024,1440,1920').split(',').map(Number);
const HEIGHTS = { 320: 640, 390: 844, 768: 1024, 900: 900, 1024: 768, 1440: 900, 1920: 1080 };

async function settle(page) {
  await page.evaluate(async () => {
    await document.fonts.ready;
    for (let y = 0; y < document.body.scrollHeight; y += 500) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 40));
    }
    window.scrollTo(0, 0);
    // Measurement only: load every image so both sites are measured fully loaded.
    document.querySelectorAll('img[loading=lazy]').forEach((img) => { img.loading = 'eager'; });
    const loaded = Promise.all([...document.images].map((img) => img.complete ? null : new Promise((r) => { img.onload = img.onerror = r; })));
    await Promise.race([loaded, new Promise((r) => setTimeout(r, 15000))]);
    await new Promise((r) => setTimeout(r, 300));
  });
}

async function boxes(page) {
  return page.evaluate(() => {
    const out = [];
    const blocks = [...document.querySelectorAll('section, footer, header, nav[aria-label], dialog')];
    const blockOf = (el) => el.closest('section, footer, header, nav[aria-label], dialog');
    const sectionTop = (el) => {
      const s = blockOf(el);
      return s ? s.getBoundingClientRect().y + window.scrollY : 0;
    };
    // Same-kind index of the outermost block, so sections line up across sites.
    const blockKey = (el) => {
      let b = blockOf(el);
      while (b && b.parentElement && blockOf(b.parentElement)) b = blockOf(b.parentElement);
      return b ? `${b.tagName}${blocks.filter((x) => x.tagName === b.tagName && !blockOf(x.parentElement ?? document.body)).indexOf(b)}` : 'BODY';
    };
    const walk = (el) => {
      for (const c of el.children) {
        if (['SCRIPT', 'TEMPLATE', 'NOSCRIPT', 'STYLE', 'LINK'].includes(c.tagName)) continue;
        if (c.tagName === 'DIV' && c.hidden && !c.children.length) continue;
        if (c.tagName === 'INPUT' && c.type === 'hidden') continue;
        if (c.tagName === 'NEXTJS-PORTAL') continue;
        const r = c.getBoundingClientRect();
        const id = c.id ? `#${c.id}` : c.className && typeof c.className === 'string' ? '.' + c.className.split(/\s+/).filter(Boolean).slice(0, 2).join('.') : '';
        out.push({ block: blockKey(c), tag: c.tagName, id, x: r.x + window.scrollX, y: r.y + window.scrollY - sectionTop(c), w: r.width, h: r.height, text: c.children.length ? '' : (c.textContent || '').trim().slice(0, 40) });
        walk(c);
      }
    };
    walk(document.body);
    return { list: out, height: document.documentElement.scrollHeight, width: document.documentElement.scrollWidth };
  });
}

async function shoot(browser, url, width) {
  const ctx = await browser.newContext({ viewport: { width, height: HEIGHTS[width] ?? 900 }, reducedMotion: 'reduce', deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  await page.goto(url, { waitUntil: 'load' });
  await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
  await settle(page);
  const layout = await boxes(page);
  const png = await page.screenshot({ fullPage: true, animations: 'disabled' });
  await ctx.close();
  return { layout, png };
}

async function pixelDiff(a, b, file) {
  const ia = sharp(a); const ib = sharp(b);
  const [ma, mb] = [await ia.metadata(), await ib.metadata()];
  const w = Math.min(ma.width, mb.width); const h = Math.min(ma.height, mb.height);
  const ra = await sharp(a).extract({ left: 0, top: 0, width: w, height: h }).raw().ensureAlpha().toBuffer();
  const rb = await sharp(b).extract({ left: 0, top: 0, width: w, height: h }).raw().ensureAlpha().toBuffer();
  const diff = Buffer.alloc(w * h * 4);
  let bad = 0;
  for (let i = 0; i < w * h * 4; i += 4) {
    const d = Math.max(Math.abs(ra[i] - rb[i]), Math.abs(ra[i + 1] - rb[i + 1]), Math.abs(ra[i + 2] - rb[i + 2]));
    const gray = (ra[i] + ra[i + 1] + ra[i + 2]) / 3;
    if (d > 40) { bad++; diff[i] = 255; diff[i + 1] = 0; diff[i + 2] = 60; diff[i + 3] = 255; }
    else { diff[i] = diff[i + 1] = diff[i + 2] = gray * 0.35 + 160; diff[i + 3] = 255; }
  }
  await sharp(diff, { raw: { width: w, height: h, channels: 4 } }).png().toFile(file);
  return { ratio: bad / (w * h), size: [ma.width, ma.height, mb.width, mb.height] };
}

const browser = await chromium.launch();
await mkdir(OUT, { recursive: true });
const report = [];
for (const width of WIDTHS) {
  for (const route of ROUTES) {
    let ref; let php;
    try {
      [ref, php] = await Promise.all([shoot(browser, REF + route, width), shoot(browser, PHP + route, width)]);
    } catch (e) {
      console.log(`${width} ${route} ERROR ${e.message.split('\n')[0]}`);
      continue;
    }
    const name = `${width}${route.replace(/\//g, '_')}`;
    await writeFile(path.join(OUT, `${name}.ref.png`), ref.png);
    await writeFile(path.join(OUT, `${name}.php.png`), php.png);
    const px = await pixelDiff(ref.png, php.png, path.join(OUT, `${name}.diff.png`));
    const a = ref.layout.list; const b = php.layout.list;
    const key = (e) => e.tag + e.id;
    // Align the two element lists (LCS), so a differing element doesn't shift the rest.
    const n = a.length; const m = b.length;
    const L = Array.from({ length: n + 1 }, () => new Uint16Array(m + 1));
    for (let i = n - 1; i >= 0; i--) for (let j = m - 1; j >= 0; j--) L[i][j] = key(a[i]) === key(b[j]) ? L[i + 1][j + 1] + 1 : Math.max(L[i + 1][j], L[i][j + 1]);
    const moved = []; const unmatched = [];
    let i = 0; let j = 0;
    while (i < n && j < m) {
      if (key(a[i]) === key(b[j])) {
        const d = Math.max(Math.abs(a[i].x - b[j].x), Math.abs(a[i].y - b[j].y), Math.abs(a[i].w - b[j].w), Math.abs(a[i].h - b[j].h));
        if (d > 1) moved.push(`[${a[i].block}] ${a[i].tag}${a[i].id} Δ${d.toFixed(1)} ref(${[a[i].x, a[i].y, a[i].w, a[i].h].map((v) => v.toFixed(0))}) php(${[b[j].x, b[j].y, b[j].w, b[j].h].map((v) => v.toFixed(0))}) "${a[i].text}"`);
        i++; j++;
      } else if (L[i + 1][j] >= L[i][j + 1]) { unmatched.push(`[${a[i].block}] ref only: ${a[i].tag}${a[i].id} "${a[i].text}"`); i++; }
      else { unmatched.push(`[${b[j].block}] php only: ${b[j].tag}${b[j].id} "${b[j].text}"`); j++; }
    }
    for (; i < n; i++) unmatched.push(`ref only: ${a[i].tag}${a[i].id}`);
    for (; j < m; j++) unmatched.push(`php only: ${b[j].tag}${b[j].id}`);
    // Blocks whose content changed on purpose (default values) may legitimately move inside.
    const affected = new Set(unmatched.map((u) => u.slice(1, u.indexOf(']'))));
    affected.add('BODY');
    const unexpected = moved.filter((m) => !affected.has(m.slice(1, m.indexOf(']'))) && !m.includes('[MAIN') && !m.includes('[FOOTER'));
    const line = `${String(width).padEnd(5)} ${route.padEnd(22)} height ${ref.layout.height}/${php.layout.height}  elements ${a.length}/${b.length}  moved ${moved.length} (unexpected ${unexpected.length})  unmatched ${unmatched.length}  pixels ${(px.ratio * 100).toFixed(2)}%`;
    console.log(line);
    unexpected.slice(0, Number(args.max ?? 6)).forEach((m) => console.log('       UNEXPECTED', m));
    report.push({ width, route, refHeight: ref.layout.height, phpHeight: php.layout.height, moved, unmatched, unexpected, pixels: px.ratio });
  }
}
await writeFile(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
await browser.close();
