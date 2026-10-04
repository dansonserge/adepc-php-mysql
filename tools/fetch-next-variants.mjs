#!/usr/bin/env node
/*
 * Dev-only, optional: replaces the GD-generated WebP sizes of the seed images
 * with the exact files the Next.js image optimizer serves (same encoder, same
 * dimensions), so the seed photos are byte-identical to the original site.
 * Requires the reference site running: (cd ../adepc && pnpm build && pnpm start).
 *
 *   node tools/fetch-next-variants.mjs [--next=http://localhost:3000]
 */
import { readFile, writeFile, readdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '..');
const NEXT = (process.argv.find((a) => a.startsWith('--next=')) ?? '--next=http://localhost:3000').slice(7);
const nextMedia = path.resolve(root, '../adepc/.next/static/media');
const seedFile = path.join(root, 'app/database/seed/media.json');

const ALL = [32, 48, 64, 96, 128, 256, 384, 640, 750, 828, 1080, 1200, 1920, 2048, 3840];
// Quality each image was requested with on the original site.
const QUALITY = { 'brand-emblem': 85 };

const files = await readdir(nextMedia);
const rows = JSON.parse(await readFile(seedFile, 'utf8'));
let done = 0;
for (const row of rows) {
  if (row.kind !== 'image' || !row.variants) continue;
  const base = path.basename(row.label, path.extname(row.label));
  const match = files.find((f) => f.startsWith(base + '.') && !f.endsWith('.woff2'));
  if (!match) { console.log('no Next counterpart for', row.ref, row.label); continue; }
  const q = QUALITY[row.ref] ?? 75;
  const dir = path.join(root, 'public/media', path.dirname(row.path));
  for (const w of row.variants.split(',').map(Number)) {
    // The full-size variant is served by Next for the next allowed width (no enlargement).
    const ask = ALL.includes(w) ? w : ALL.find((s) => s >= w);
    const url = `${NEXT}/_next/image?url=${encodeURIComponent('/_next/static/media/' + match)}&w=${ask}&q=${q}`;
    const res = await fetch(url, { headers: { Accept: 'image/webp,*/*;q=0.1' } });
    if (!res.ok || !res.headers.get('content-type')?.includes('webp')) throw new Error(`${url} → ${res.status} ${res.headers.get('content-type')}`);
    await writeFile(path.join(dir, `${w}.webp`), Buffer.from(await res.arrayBuffer()));
  }
  done++;
}
console.log(`replaced the WebP sizes of ${done} images with Next.js optimizer output`);
