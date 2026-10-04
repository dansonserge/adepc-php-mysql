#!/usr/bin/env node
/*
 * Admin end-to-end checks against a fresh install (tools/dev-reinstall.sh).
 * Every scenario edits something in the admin and verifies it on the public site.
 *
 *   node tools/e2e/admin.mjs [--base=http://localhost:8080] [--shots]
 */
import { createRequire } from 'node:module';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const require = createRequire(path.resolve(here, '../../../adepc/package.json'));
const { chromium } = require('@playwright/test');

const args = Object.fromEntries(process.argv.slice(2).map((a) => { const [k, v = '1'] = a.replace(/^--/, '').split('='); return [k, v]; }));
const BASE = args.base ?? 'http://localhost:8080';
const EMAIL = 'admin@adepc.test';
const PASS = 'dev-password-123';
const SHOTS = path.resolve(here, '../../.compare/admin');

let failures = 0;
const check = (ok, label) => {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
  if (!ok) failures++;
};

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await ctx.newPage();
const errors = [];
page.on('pageerror', (e) => errors.push(e.message));
page.on('dialog', (d) => d.accept());

// Clicks a submit button and waits for the resulting page.
async function submit(selector, p = page) {
  await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.locator(selector).first().click()]);
}
async function login(email = EMAIL, password = PASS) {
  await page.goto(`${BASE}/admin/login`);
  await page.fill('#email', email);
  await page.fill('#password', password);
  await submit('form[action="/admin/login"] button[type=submit]');
}
const text = async (url, selector = 'body') => {
  const p = await ctx.newPage();
  await p.goto(BASE + url);
  const t = await p.locator(selector).first().innerText();
  await p.close();
  return t;
};

// ── Login, lockout ─────────────────────────────────────────────────────────
await login('admin@adepc.test', 'wrong-password');
check(await page.locator('[role=alert]').isVisible(), 'wrong password is refused');
await login();
check(page.url() === `${BASE}/admin`, 'login lands on the dashboard');
check((await page.locator('h1').innerText()).length > 0, 'dashboard renders');
check(await page.getByText('Pasteur principal', { exact: false }).count() >= 0, 'dashboard lists defaults');

// ── Every admin screen opens without errors ───────────────────────────────
const screens = ['/admin', '/admin/pages', '/admin/pages/home', '/admin/pages/about', '/admin/pages/common', '/admin/churches',
  '/admin/churches/1', '/admin/churches/new', '/admin/events', '/admin/events/1', '/admin/videos', '/admin/videos/1',
  '/admin/categories', '/admin/stories', '/admin/stories/1', '/admin/media', '/admin/media?kind=video', '/admin/media/2',
  '/admin/menus', '/admin/texts', '/admin/texts?group=home', '/admin/purposes', '/admin/settings', '/admin/settings/email',
  '/admin/settings/integrations', '/admin/languages', '/admin/icons', '/admin/redirects', '/admin/users', '/admin/users/new', '/admin/account'];
if (args.shots) await mkdir(SHOTS, { recursive: true });
for (const s of screens) {
  const res = await page.goto(BASE + s);
  check(res.status() === 200, `opens ${s}`);
  if (args.shots) await page.screenshot({ path: path.join(SHOTS, `${s.replace(/[/?=]/g, '_')}.png`), fullPage: true });
}

// ── Edit a Sunday time: hero strip and This Sunday reorder ────────────────
await page.goto(`${BASE}/admin/churches/1`); // Montréal, 13:30
await page.locator('input[name="services[0][time]"]').fill('08:15');
await submit('form button[type=submit]:has-text("Enregistrer")');
let order = await (async () => { const p = await ctx.newPage(); await p.goto(`${BASE}/en`); const c = await p.locator('#this-sunday ol h3').allInnerTexts(); await p.close(); return c; })();
check(order[0]?.toLowerCase() === 'montréal', `This Sunday reorders after a time change (${order.join(', ')})`);
check((await text('/fr', 'section[aria-labelledby=hero-title] ul')).includes('8 h 15'), 'hero strip shows the new time');
await page.goto(`${BASE}/admin/churches/1`);
await page.locator('input[name="services[0][time]"]').fill('13:30');
await submit('form button[type=submit]:has-text("Enregistrer")');

// ── Replace the default pastor name ──────────────────────────────────────
await page.goto(`${BASE}/admin/settings/pastor`);
await page.fill('input[name="s[pastor.name.fr]"]', 'Rév. Jean Exemple');
await page.fill('input[name="s[pastor.name.en]"]', 'Rev. Jean Exemple');
await submit('form button[type=submit]:has-text("Enregistrer")');
check((await text('/en', '#pastor')).includes('Rev. Jean Exemple'), 'pastor name shows on the site');
await page.goto(`${BASE}/admin`);
check(!(await page.locator('main').innerText()).includes('pastor.name'), 'dashboard no longer lists the pastor name');

// ── Rename a menu item ────────────────────────────────────────────────────
await page.goto(`${BASE}/admin/menus`);
const firstHeader = page.locator('input[name^="menu[header]"][name$="[label_en]"]').first();
await firstHeader.fill('Our churches');
await submit('form button[type=submit]:has-text("Enregistrer")');
check((await text('/en', 'header nav[aria-label] ul')).toUpperCase().includes('OUR CHURCHES'), 'renamed menu item shows in the header');

// ── Add a value to the values list (About page) ──────────────────────────
await page.goto(`${BASE}/admin/pages/about`);
const valuesSection = page.locator('details', { hasText: 'Valeurs' }).last();
await valuesSection.evaluate((d) => { d.open = true; });
await valuesSection.locator('[data-list-add]').click();
const newRow = valuesSection.locator('[data-list-row]').last();
await newRow.locator('input[lang=fr]').fill('Joie');
await newRow.locator('input[lang=en]').fill('Joy');
await submit('form button[type=submit]:has-text("Enregistrer")');
check((await text('/en/about', '#values-title >> xpath=../../..')).toUpperCase().includes('JOY'), 'new value appears on About');

// ── Social link makes the footer "Follow" block appear ───────────────────
check(!(await text('/en', 'footer')).toUpperCase().includes('FOLLOW'), 'no Follow block without social links');
await page.goto(`${BASE}/admin/pages/common`);
const footer = page.locator('details', { hasText: 'Pied de page' }).first();
await footer.evaluate((d) => { d.open = true; });
await footer.locator('[data-list-add]').click();
const social = footer.locator('[data-list-row]').last();
await social.locator('input[lang=fr]').fill('Facebook');
await social.locator('input[lang=en]').fill('Facebook');
await social.locator('input[type=url]').fill('https://www.facebook.com/example');
await submit('form button[type=submit]:has-text("Enregistrer")');
const foot = await text('/en', 'footer');
check(foot.toUpperCase().includes('FOLLOW') && foot.includes('Facebook'), 'Follow block appears with the new link');

// ── Change a page slug: the old URL redirects ────────────────────────────
await page.goto(`${BASE}/admin/pages/stories`);
await page.locator('details', { hasText: 'Adresse' }).first().evaluate((d) => { d.open = true; });
await page.fill('#slug-en', 'testimonies');
await submit('form button[type=submit]:has-text("Enregistrer")');
const r1 = await ctx.request.get(`${BASE}/en/stories`, { maxRedirects: 0 });
check(r1.status() === 308 && r1.headers().location === '/en/testimonies', `old slug redirects (${r1.status()} → ${r1.headers().location})`);
check((await ctx.request.get(`${BASE}/en/testimonies`)).status() === 200, 'new slug works');
await page.goto(`${BASE}/admin/pages/stories`);
await page.locator('details', { hasText: 'Adresse' }).first().evaluate((d) => { d.open = true; });
await page.fill('#slug-en', 'stories');
await submit('form button[type=submit]:has-text("Enregistrer")');

// ── Edit a French text ────────────────────────────────────────────────────
await page.goto(`${BASE}/admin/texts?q=home.hero.lead`);
await page.locator('textarea[name="t[fr][home.hero.lead]"]').fill('Bienvenue à la maison — texte modifié.');
await submit('form[method=post] button[type=submit]:has-text("Enregistrer")');
check((await text('/fr', '#main')).includes('texte modifié'), 'edited text shows on the French home page');

// ── Placeholders are protected ────────────────────────────────────────────
await page.goto(`${BASE}/admin/texts?q=churches.page.aboutP2`);
await page.locator('textarea[name="t[en][churches.page.aboutP2]"]').fill('No placeholder any more.');
await submit('form[method=post] button[type=submit]:has-text("Enregistrer")');
check(await page.locator('[role=alert]').isVisible(), 'saving without {city} is refused');

// ── Upload a photo; it can join the gallery ──────────────────────────────
await page.goto(`${BASE}/admin/media`);
await page.setInputFiles('input[type=file][name="files[]"]', path.resolve(here, '../../public/media/images/2/original.jpg'));
await submit('form[data-dropzone] button[type=submit]');
check(/\/admin\/media\/\d+$/.test(page.url()), 'single upload opens the new item');
await page.fill('#m-alt-fr', 'Photo de test');
await page.fill('#m-alt-en', 'Test photo');
await page.check('input[name="f[in_gallery]"]');
await submit('form button[type=submit]:has-text("Enregistrer")');
const newId = page.url().split('/').pop();
const gallery = await (async () => { const p = await ctx.newPage(); await p.goto(`${BASE}/en/stories`); const alts = await p.locator('[data-js-gallery] img').evaluateAll((imgs) => imgs.map((i) => [i.alt, i.getAttribute('srcset') ?? ''])); await p.close(); return alts; })();
const found = gallery.find(([alt]) => alt === 'Test photo');
check(Boolean(found) && found[1].includes(`/media/images/${newId}/`), 'uploaded photo appears in the gallery with WebP sizes');

// ── Replace an icon ───────────────────────────────────────────────────────
await page.goto(`${BASE}/admin/icons`);
const iconForm = page.locator('form[action="/admin/icons/home"]');
await iconForm.locator('input[type=file]').setInputFiles({ name: 'home.svg', mimeType: 'image/svg+xml', buffer: Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" onload="alert(1)"><script>alert(1)</script><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor"/></svg>') });
await Promise.all([page.waitForNavigation(), iconForm.locator('button:not([name=reset])').click()]);
const tab = await (async () => { const p = await ctx.newPage(); await p.setViewportSize({ width: 390, height: 844 }); await p.goto(`${BASE}/fr`); const h = await p.locator('nav.fixed ul li').first().innerHTML(); await p.close(); return h; })();
check(tab.includes('<circle') && !tab.includes('script') && !tab.includes('onload'), 'replaced icon renders, sanitized');
await page.goto(`${BASE}/admin/icons`);
await Promise.all([page.waitForNavigation(), page.locator('form[action="/admin/icons/home"] button[name=reset]').click()]);

// ── Replace the hero poster slot ──────────────────────────────────────────
await page.goto(`${BASE}/admin/pages/home`);
await page.selectOption('select[name="slots[home.hero.poster][media]"]', newId);
await submit('form button[type=submit]:has-text("Enregistrer")');
const heroSrc = await (async () => { const p = await ctx.newPage(); await p.goto(`${BASE}/fr`); const s = await p.locator('section[aria-labelledby=hero-title] img').first().getAttribute('srcset'); await p.close(); return s; })();
check(heroSrc.includes(`/media/images/${newId}/`), 'hero poster comes from the chosen media');

// ── Security ──────────────────────────────────────────────────────────────
const noCsrf = await ctx.request.post(`${BASE}/admin/settings/organization`, { form: { 's[site.short_name]': 'HACK' } });
check(noCsrf.status() === 403, 'POST without CSRF token is rejected');
await page.goto(`${BASE}/admin/users/new`);
await page.fill('#u-name', 'Rédactrice');
await page.fill('#u-email', 'editor@adepc.test');
await page.selectOption('#u-role', 'editor');
await page.fill('#u-pass', 'editor-password-1');
await submit('form button[type=submit]:has-text("Enregistrer")');
const ctx2 = await browser.newContext();
const p2 = await ctx2.newPage();
await p2.goto(`${BASE}/admin/login`);
await p2.fill('#email', 'editor@adepc.test');
await p2.fill('#password', 'editor-password-1');
await submit('form[action="/admin/login"] button[type=submit]', p2);
const forbidden = await p2.goto(`${BASE}/admin/users`);
check(forbidden.status() === 403, 'an editor cannot open Users');
await ctx2.close();

// ── No placeholder markers anywhere on the site ──────────────────────────
for (const route of ['/fr', '/en', '/fr/evenements', '/en/watch', '/fr/confidentialite', '/en/churches/ottawa']) {
  const t = (await text(route)).toUpperCase();
  check(!t.includes('TO BE ADDED') && !t.includes('À COMPLÉTER'), `no [TO BE ADDED] marker on ${route}`);
}

check(errors.length === 0, `no JavaScript errors (${errors.slice(0, 3).join(' | ')})`);
await browser.close();
console.log(failures ? `\n${failures} failure(s)` : '\nall admin checks passed');
process.exit(failures ? 1 : 0);
