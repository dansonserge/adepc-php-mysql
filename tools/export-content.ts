/*
 * One-time export of the Next.js site's content into seed data for MySQL.
 * Dev-only tooling: it reads the old project (never writes to it) and
 * produces app/database/seed/*.json plus the media files in public/media.
 * The PHP site never sees this file; it only sees the JSON it produces.
 *
 * Run from the old project so its tsx, tsconfig paths and image loader apply:
 *   cd ../adepc && node --import tsx --require ./scripts/lib/register-assets.cjs \
 *     ../adepc-cpanel/tools/export-content.ts
 */
import fs from "node:fs";
import path from "node:path";
import { churches } from "../../adepc/src/content/churches";
import { events } from "../../adepc/src/content/events";
import { seniorPastor } from "../../adepc/src/content/people";
import { allPhotos } from "../../adepc/src/content/photos";
import { fromBanner, removed } from "../../adepc/src/content/removed";
import { settings as site } from "../../adepc/src/content/settings";
import { stories } from "../../adepc/src/content/stories";
import { heroLoop, videos } from "../../adepc/src/content/videos";
import { routing } from "../../adepc/src/i18n/routing";
import en from "../../adepc/src/messages/en.json";
import fr from "../../adepc/src/messages/fr.json";

const OLD = path.resolve(__dirname, "../../adepc");
const NEW = path.resolve(__dirname, "..");
const SEED = path.join(NEW, "app/database/seed");
const MEDIA = path.join(NEW, "public/media");
fs.mkdirSync(SEED, { recursive: true });

type Row = Record<string, string | number | null>;
const write = (name: string, rows: unknown) =>
  fs.writeFileSync(path.join(SEED, `${name}.json`), JSON.stringify(rows, null, 2) + "\n");

// ── Translations ─────────────────────────────────────────────────────────
type Tree = { [k: string]: string | Tree };
function flatten(tree: Tree, prefix = "", out: Record<string, string> = {}) {
  for (const [k, v] of Object.entries(tree)) {
    const key = prefix ? `${prefix}.${k}` : k;
    if (typeof v === "string") out[key] = v;
    else flatten(v, key, out);
  }
  return out;
}
const msg = { fr: flatten(fr as Tree), en: flatten(en as Tree) };

// Keys whose content moves into dedicated tables (menus, lists, categories,
// purposes) or that existed only for the [TO BE ADDED] markers.
const moved = [
  /^nav\.(home|churches|events|tabEvents|watch|about|stories|give|contact)$/,
  /^pending\./,
  /^meta\.siteName$/,
  /^home\.hero\.line[123]$/,
  /^home\.mission\.(proclaim|disciple|serve)(Body)?$/,
  /^home\.pillars\.(worship|community|service)(Body)?$/,
  /^watch\.categories\./,
  /^watch\.(empty|liveLead)$/,
  /^contact\.form\.purpose[A-Z]/,
  /^about\.heritage\.(adepr|paoc)(Short)?$/,
  /^about\.glance\./,
  /^about\.mission\.banner[12]$/,
  /^about\.strategic\.(worship|discipleship|family|compassion|multiplication)(Body)?$/,
  /^about\.values\.list$/,
  /^about\.beliefs\.(scripture|salvation|spirit)(Body)?$/,
];

// Strings that were written into components or config rather than the
// message files. They become ordinary, editable translations.
const added: Record<string, { fr: string; en: string }> = {
  "format.quote": { fr: "« {text} »", en: "« {text} »" },
  "format.quoteTight": { fr: "« {text} »", en: "« {text} »" },
  "format.churchCity": { fr: "ADEPC {city}", en: "ADEPC {city}" },
  "format.dayTime": { fr: "{day} · {time}", en: "{day} · {time}" },
  "format.hqLine": { fr: "{label} · {building}", en: "{label} · {building}" },
  "format.localityPostal": { fr: "{locality} {postal}", en: "{locality} {postal}" },
  "format.localityRegionPostal": { fr: "{locality} ({region}) {postal}", en: "{locality} ({region}) {postal}" },
  "format.streetLocality": { fr: "{street}, {locality}", en: "{street}, {locality}" },
  "format.streetLocalityPostal": { fr: "{street}, {locality} {postal}", en: "{street}, {locality} {postal}" },
  "format.directionsAddress": { fr: "{street}, {locality}, {region} {postal}", en: "{street}, {locality}, {region} {postal}" },
  "format.srLabel": { fr: "{label} ", en: "{label} " },
  "format.separator": { fr: " · ", en: " · " },
  "format.timeWithSuffix": { fr: "{time} {suffix}", en: "{time} {suffix}" },
  // Contact email (sent in the default language). Was built in actions.ts.
  "format.contactSubject": { fr: "{confidential} [adepc.ca · {purpose}] {church} {name}", en: "{confidential} [adepc.ca · {purpose}] {church} {name}" },
  "format.contactConfidential": { fr: "[CONFIDENTIEL]", en: "[CONFIDENTIAL]" },
  "format.contactChurch": { fr: "[{church}]", en: "[{church}]" },
  "format.contactBody": { fr: "{message}\n\n— {name} <{email}>{church}", en: "{message}\n\n— {name} <{email}>{church}" },
  "format.contactBodyChurch": { fr: "\nÉglise : {church}", en: "\nChurch: {church}" },
  "date.monthYear": { fr: "{month} {year}", en: "{month} {year}" },
  "date.timeOnHour": { fr: "{h} h", en: "{h12}" },
  "date.timeWithMinutes": { fr: "{h} h {mm}", en: "{h12}:{mm}" },
  "date.am": { fr: "", en: "AM" },
  "date.pm": { fr: "", en: "PM" },
  "events.venue": { fr: msg.fr["pending.venue"], en: msg.en["pending.venue"] },
  "events.time": { fr: msg.fr["pending.time"], en: msg.en["pending.time"] },
  "events.registration": { fr: msg.fr["pending.registration"], en: msg.en["pending.registration"] },
  "notFound.code": { fr: "404", en: "404" },
  "notFound.homeShort": { fr: msg.fr["nav.home"], en: msg.en["nav.home"] },
  "contact.form.honeypot": { fr: "Website", en: "Website" },
  "home.testimonial.mark": { fr: "“", en: "“" },
  // The church page eyebrow was `${t("eyebrow")} · ADEPC` in code.
  "churches.page.eyebrow": {
    fr: `${msg.fr["churches.page.eyebrow"]} · ADEPC`,
    en: `${msg.en["churches.page.eyebrow"]} · ADEPC`,
  },
};

// Month and weekday words, generated with the same Intl options the Next.js
// site formats with, so the PHP output matches it character for character.
const intl = { fr: "fr-CA", en: "en-CA" } as const;
const TZ = "America/Toronto";
for (const l of ["fr", "en"] as const) {
  const long = (o: Intl.DateTimeFormatOptions, d: Date) =>
    new Intl.DateTimeFormat(intl[l], { ...o, timeZone: TZ }).format(d);
  for (let m = 1; m <= 12; m++) {
    const d = new Date(Date.UTC(2026, m - 1, 15, 16));
    (added[`date.month.${m}`] ??= { fr: "", en: "" })[l] = long({ month: "long" }, d);
    // EventList: month "short", first "." removed.
    (added[`date.monthShort.${m}`] ??= { fr: "", en: "" })[l] = long({ month: "short" }, d).replace(".", "");
  }
  for (let w = 0; w < 7; w++) {
    const d = new Date(Date.UTC(2026, 0, 4 + w, 16)); // 2026-01-04 is a Sunday
    (added[`date.weekday.${w}`] ??= { fr: "", en: "" })[l] = long({ weekday: "long" }, d);
  }
  // formatDayDate pattern, read from formatToParts so literals are exact.
  const parts = new Intl.DateTimeFormat(intl[l], { weekday: "long", day: "numeric", month: "long", timeZone: TZ })
    .formatToParts(new Date(Date.UTC(2026, 9, 4, 16)));
  (added["date.dayDate"] ??= { fr: "", en: "" })[l] = parts
    .map((p) => (p.type === "literal" ? p.value : `{${p.type}}`))
    .join("");
}

const translations: Row[] = [];
for (const l of ["fr", "en"] as const) {
  const keys = new Set([...Object.keys(msg[l]), ...Object.keys(added)]);
  for (const key of [...keys].sort()) {
    if (moved.some((re) => re.test(key))) continue;
    const value = added[key]?.[l] ?? msg[l][key];
    translations.push({ locale: l, tkey: key, value });
  }
}
write("translations", translations);

// ── Locales & pages ──────────────────────────────────────────────────────
write("locales", [
  { code: "fr", name: "Français", short_label: "FR", hreflang: "fr-CA", og_locale: "fr_CA", is_default: 1, sort: 1 },
  { code: "en", name: "English", short_label: "EN", hreflang: "en-CA", og_locale: "en_CA", is_default: 0, sort: 2 },
]);

const slug = (internal: string, l: "fr" | "en") => {
  const p = routing.pathnames[internal as keyof typeof routing.pathnames];
  const v = typeof p === "string" ? p : p[l];
  return v.replace(/^\//, "");
};
const pageKeys = ["home", "churches", "events", "watch", "about", "stories", "give", "contact", "privacy"];
write(
  "pages",
  pageKeys.map((k, i) => {
    const internal = k === "home" ? "/" : `/${k}`;
    return {
      pkey: k,
      slug_fr: slug(internal, "fr"),
      slug_en: slug(internal, "en"),
      in_sitemap: 1,
      changefreq: ["home", "events", "watch"].includes(k) ? "weekly" : "monthly",
      priority: k === "home" ? 1 : k === "churches" ? 0.9 : 0.7,
      sort: i,
    };
  }),
);

// Legacy URLs of the previous static adepc.ca (next.config.ts), 308.
write(
  "redirects",
  [
    ["/index.html", "/en"],
    ["/pages/about.html", "/en/about"],
    ["/pages/mission.html", "/en/about#mission"],
    ["/pages/events.html", "/en/events"],
    ["/pages/contact.html", "/en/contact"],
    ["/pages/churches.html", "/en/churches"],
    ["/pages/church-montreal.html", "/en/churches/montreal"],
    ["/pages/church-granby.html", "/en/churches/granby"],
    ["/pages/church-ottawa.html", "/en/churches/ottawa"],
    ["/pages/church-quebec.html", "/en/churches/quebec"],
    ["/pages/gallery.html", "/en/stories"],
  ].map(([from_path, to_path]) => ({ from_path, to_path, status: 308, is_auto: 0 })),
);

// ── Menus ────────────────────────────────────────────────────────────────
const label = (k: string) => ({ label_fr: msg.fr[`nav.${k}`], label_en: msg.en[`nav.${k}`] });
const menu: Row[] = [];
const addMenu = (name: string, items: [string, string, string | null, number?][]) =>
  items.forEach(([page, key, icon, highlight], i) =>
    menu.push({ menu: name, page_key: page, ...label(key), icon, highlight: highlight ?? 0, sort: i + 1, visible: 1 }),
  );
const primary = ["churches", "events", "watch", "about", "stories"];
addMenu("header", primary.map((p) => [p, p, null] as [string, string, null]));
addMenu("overlay", [...primary, "contact"].map((p) => [p, p, null] as [string, string, null]));
addMenu("cta", [["give", "give", null]]);
addMenu("footer", [...primary, "give", "contact"].map((p) => [p, p, null] as [string, string, null]));
addMenu("tabbar", [
  ["home", "home", "home"],
  ["churches", "churches", "church"],
  ["watch", "watch", "play"],
  ["events", "tabEvents", "calendar"],
  ["give", "give", "give", 1],
]);
write("menu_items", menu);

// ── Media ────────────────────────────────────────────────────────────────
type Img = { src: string; width: number; height: number };
const media: Row[] = [];
const byRef: Record<string, number> = {};
const churchId: Record<string, number> = Object.fromEntries(churches.map((c, i) => [c.slug, i + 1]));

function addImage(ref: string, file: string, width: number, height: number, extra: Row = {}) {
  const id = media.length + 1;
  const ext = path.extname(file).toLowerCase().replace(".jpeg", ".jpg");
  const rel = `images/${id}/original${ext}`;
  fs.mkdirSync(path.join(MEDIA, `images/${id}`), { recursive: true });
  fs.copyFileSync(file, path.join(MEDIA, rel));
  media.push({
    id, ref, kind: "image", path: rel, mime: ext === ".png" ? "image/png" : "image/jpeg", width, height,
    variants: null, blur: null, alt_fr: "", alt_en: "", focus: null, church_id: null, tags: null,
    in_gallery: 0, in_church_moments: 0, label: path.basename(file), sort: id, ...extra,
  });
  byRef[ref] = id;
  return id;
}
function addVideo(ref: string, file: string) {
  const id = media.length + 1;
  const rel = `video/${path.basename(file)}`;
  fs.mkdirSync(path.join(MEDIA, "video"), { recursive: true });
  fs.copyFileSync(file, path.join(MEDIA, rel));
  media.push({
    id, ref, kind: "video", path: rel, mime: file.endsWith(".webm") ? "video/webm" : "video/mp4",
    width: null, height: null, variants: null, blur: null, alt_fr: null, alt_en: null, focus: null,
    church_id: null, tags: null, in_gallery: 0, in_church_moments: 0, label: path.basename(file), sort: id,
  });
  byRef[ref] = id;
  return id;
}

for (const p of allPhotos) {
  const img = p.src as unknown as Img;
  const isStill = p.id.startsWith("video-");
  addImage(p.id, img.src, img.width, img.height, {
    alt_fr: p.alt.fr,
    alt_en: p.alt.en,
    focus: p.focus ?? null,
    church_id: p.church ? churchId[p.church] : null,
    tags: p.tags.join(","),
    in_gallery: !isStill && p.id !== "mtl-0019-portrait" ? 1 : 0,
    in_church_moments: p.church && !isStill && !p.tags.includes("portrait") ? 1 : 0,
  });
}
const brand = (ref: string, file: string, w: number, h: number, alt: { fr: string; en: string }) =>
  addImage(ref, path.join(OLD, file), w, h, { alt_fr: alt.fr, alt_en: alt.en });
const none = { fr: "", en: "" };
brand("brand-emblem", "src/assets/brand/logo-mark.png", 512, 512, none);
brand("brand-logo", "src/assets/brand/logo.jpeg", 996, 996, site.orgName);
brand("brand-icon", "src/app/icon.png", 192, 192, none);
brand("brand-apple-icon", "src/app/apple-icon.png", 180, 180, none);
const shareAlt = fs.readFileSync(path.join(OLD, "src/app/[locale]/opengraph-image.alt.txt"), "utf8").trim();
brand("brand-share", "src/app/[locale]/opengraph-image.jpg", 1200, 630, { fr: shareAlt, en: shareAlt });

const vid = (name: string) => path.join(OLD, "public", name);
addVideo("hero-loop-mp4", vid(heroLoop.mp4));
addVideo("hero-loop-webm", vid(heroLoop.webm!));
for (const v of videos) if (v.source.kind === "file") addVideo(`video-${v.id}`, vid(v.source.mp4));
write("media", media);

const slots: Row[] = [
  ["home.hero.poster", "video-hero"],
  ["home.hero.mp4", "hero-loop-mp4"],
  ["home.hero.webm", "hero-loop-webm"],
  ["home.film.poster", "mtl-9864", "50% 78%"],
  ["home.stories.1", "mtl-9665"],
  ["home.stories.2", "ott-0024"],
  ["home.stories.3", "gby-9889"],
  ["home.stories.4", "mtl-9709"],
  ["about.hero", "mtl-9863"],
  ["about.story", "mtl-0077"],
  ["churches.hero", "mtl-9933"],
  ["stories.hero", "gby-9889"],
  ["give.hero", "ott-9771"],
  ["pastor.photo", seniorPastor.photo.id],
  ["brand.emblem", "brand-emblem"],
  ["brand.logo", "brand-logo"],
  ["brand.icon", "brand-icon"],
  ["brand.apple_icon", "brand-apple-icon"],
  ["brand.share", "brand-share"],
].map(([skey, ref, focus]) => ({ skey, media_id: byRef[ref], focus: focus ?? null }));
write("media_slots", slots);

// ── Lists (repeating items that were written into components) ───────────
const lists: Row[] = [];
function list(key: string, items: Row[]) {
  items.forEach((it, i) =>
    lists.push({
      list_key: key, title_fr: null, title_en: null, short_fr: null, short_en: null, body_fr: null, body_en: null,
      value: null, value_source: null, url: null, media_id: null, sort: i + 1, visible: 1, ...it,
    }),
  );
}
const both = (k: string) => ({ fr: msg.fr[k], en: msg.en[k] });
const tb = (titleKey: string, bodyKey?: string): Row => ({
  title_fr: both(titleKey).fr, title_en: both(titleKey).en,
  ...(bodyKey ? { body_fr: both(bodyKey).fr, body_en: both(bodyKey).en } : {}),
});
list("home.hero.lines", ["line1", "line2", "line3"].map((k) => tb(`home.hero.${k}`)));
list("home.mission.rows", ["proclaim", "disciple", "serve"].map((k) => tb(`home.mission.${k}`, `home.mission.${k}Body`)));
list("home.pillars", [
  ["worship", "mtl-9786"],
  ["community", "gby-9884"],
  ["service", "qc-9750"],
].map(([k, ref]) => ({ ...tb(`home.pillars.${k}`, `home.pillars.${k}Body`), media_id: byRef[ref] })));
list("about.heritage", ["adepr", "paoc"].map((k) => ({
  short_fr: both(`about.heritage.${k}Short`).fr, short_en: both(`about.heritage.${k}Short`).en,
  ...tb(`about.heritage.${k}`),
})));
list("about.glance", [
  ["churches", "churches", null],
  ["cities", "cities", null],
  ["provinces", "provinces", null],
  ["languages", "manual", "2"],
].map(([k, source, value]) => ({ ...tb(`about.glance.${k}`), value_source: source, value })));
list("about.mission.banner", ["banner1", "banner2"].map((k) => tb(`about.mission.${k}`)));
list("about.strategic", ["worship", "discipleship", "family", "compassion", "multiplication"].map((k) =>
  tb(`about.strategic.${k}`, `about.strategic.${k}Body`)));
const values = { fr: msg.fr["about.values.list"].split("|"), en: msg.en["about.values.list"].split("|") };
list("about.values", values.fr.map((v, i) => ({ title_fr: v, title_en: values.en[i] })));
list("about.beliefs", ["scripture", "salvation", "spirit"].map((k) => tb(`about.beliefs.${k}`, `about.beliefs.${k}Body`)));
list("social", []); // hidden in the footer until an admin adds a link
write("list_items", lists);

// ── Video categories & contact purposes ─────────────────────────────────
const cats = ["message", "special", "youth", "live", "moments"];
const emptyKey = (c: string) => (c === "live" ? "watch.liveLead" : c === "message" ? "watch.latestPending" : "watch.empty");
write("video_categories", cats.map((c, i) => ({
  id: i + 1, slug: c, role: c === "message" ? "messages" : c === "live" ? "live" : null,
  name_fr: msg.fr[`watch.categories.${c}`], name_en: msg.en[`watch.categories.${c}`],
  empty_fr: msg.fr[emptyKey(c)], empty_en: msg.en[emptyKey(c)], sort: i + 1, visible: 1,
})));
const purposes = ["prayer", "general", "church", "visit", "volunteer", "story"];
const cap = (s: string) => s[0].toUpperCase() + s.slice(1);
write("contact_purposes", purposes.map((p, i) => ({
  pkey: p, label_fr: msg.fr[`contact.form.purpose${cap(p)}`], label_en: msg.en[`contact.form.purpose${cap(p)}`],
  is_prayer: p === "prayer" ? 1 : 0, sort: i + 1, visible: 1,
})));

// ── Churches, services ───────────────────────────────────────────────────
const bandFocus: Record<string, string> = { montreal: "30% 50%", granby: "35% 50%", ottawa: "55% 50%", quebec: "70% 50%" };
const str = (v: unknown) => (typeof v === "string" ? v : null);
write("churches", churches.map((c, i) => ({
  id: i + 1, slug: c.slug, name: c.name, city_fr: c.city.fr, city_en: c.city.en, region: c.region,
  street: c.address.street, locality: c.address.locality, postal_code: c.address.postalCode,
  lat: c.coords.lat, lng: c.coords.lng, phone: str(c.phone), email: str(c.email),
  // Default wording until the church gives its pastor's name.
  pastor_fr: str(c.pastor) ?? `Équipe pastorale de l'${c.name}`,
  pastor_en: str(c.pastor) ?? `${c.name} pastoral team`,
  pastor_is_default: str(c.pastor) ? 0 : 1,
  summary_fr: c.summary.fr, summary_en: c.summary.en, intro_fr: c.intro.fr, intro_en: c.intro.en,
  about_title_fr: c.aboutTitle.fr, about_title_en: c.aboutTitle.en,
  hero_media_id: byRef[c.heroPhoto.id], band_focus: bandFocus[c.slug] ?? null, sort: i + 1, published: 1,
})));
write("church_services", churches.flatMap((c, i) => c.services.map((s, j) => ({
  church_id: i + 1, day: s.day, time: s.time, label_fr: s.label.fr, label_en: s.label.en, sort: j + 1,
}))));

// ── Events ───────────────────────────────────────────────────────────────
const tba = {
  date: { fr: "Date à annoncer", en: "Date to be announced" },
  time: { fr: "Heure à annoncer", en: "Time to be announced" },
  venue: { fr: "Lieu à annoncer", en: "Venue to be announced" },
};
const isPend = (v: unknown) => typeof v === "object" && v !== null && (v as { pending?: boolean }).pending === true;
write("events", events.map((e, i) => {
  const dateMode = e.date === undefined ? "none" : isPend(e.date) ? "text" : "date";
  const timeMode = e.time === undefined ? "none" : isPend(e.time) ? "text" : "time";
  const venue = e.venue === undefined ? null : isPend(e.venue) ? tba.venue : (e.venue as { fr: string; en: string });
  return {
    id: i + 1, kind: e.kind, title_fr: e.title.fr, title_en: e.title.en,
    description_fr: e.description.fr, description_en: e.description.en,
    date_mode: dateMode, event_date: dateMode === "date" ? (e.date as string) : null,
    date_text_fr: dateMode === "text" ? tba.date.fr : null, date_text_en: dateMode === "text" ? tba.date.en : null,
    date_is_default: dateMode === "text" ? 1 : 0,
    recurrence_fr: e.recurrence?.fr ?? null, recurrence_en: e.recurrence?.en ?? null,
    time_mode: timeMode, event_time: timeMode === "time" ? (e.time as string) : null,
    time_text_fr: timeMode === "text" ? tba.time.fr : null, time_text_en: timeMode === "text" ? tba.time.en : null,
    time_is_default: timeMode === "text" ? 1 : 0,
    venue_fr: venue?.fr ?? null, venue_en: venue?.en ?? null, venue_is_default: isPend(e.venue) ? 1 : 0,
    registration_url: typeof e.registrationUrl === "string" ? e.registrationUrl : null,
    people: e.people?.join("\n") ?? null, all_churches: e.churches === "all" ? 1 : 0,
    media_id: byRef[e.photo.id], show_sunday_times: e.id === "sunday-worship" ? 1 : 0, sort: i + 1, published: 1,
  };
}));
write("event_churches", events.flatMap((e, i) =>
  e.churches === "all" ? [] : e.churches.map((s, j) => ({ event_id: i + 1, church_id: churchId[s], sort: j + 1 }))));

// ── Videos & stories ─────────────────────────────────────────────────────
write("videos", videos.map((v, i) => ({
  id: i + 1, title_fr: v.title.fr, title_en: v.title.en, category_id: cats.indexOf(v.category) + 1,
  church_id: v.church ? churchId[v.church] : null, date_label: v.date ?? null, duration_seconds: v.durationSeconds ?? null,
  poster_media_id: byRef[v.poster.id], source_kind: v.source.kind,
  mp4_media_id: v.source.kind === "file" ? byRef[`video-${v.id}`] : null, webm_media_id: null,
  youtube_id: v.source.kind === "youtube" ? v.source.id : null, sort: i + 1, published: 1,
})));
write("stories", stories.map((s, i) => ({
  id: i + 1, quote_fr: s.quote.fr, quote_en: s.quote.en, name: s.person.name,
  detail_fr: s.person.detail.fr, detail_en: s.person.detail.en, church_id: s.church ? churchId[s.church] : null,
  media_id: byRef[s.photo.id], consent: 1, confirm_note: s.confirm ?? null, sort: i + 1, published: 1,
})));

// ── Settings ─────────────────────────────────────────────────────────────
const set: Row[] = [];
const S = (skey: string, value: string | null, is_default = 0) => set.push({ skey, value, is_default });
S("site.url", "https://adepc.ca");
S("site.short_name", "ADEPC");
S("site.theme_color", "#0b0d12");
S("org.name.fr", site.orgName.fr);
S("org.name.en", site.orgName.en);
S("org.email", site.email);
S("org.phone", typeof site.phone === "string" ? site.phone : "");
S("org.country", "CA");
S("hq.label.fr", site.hq.label.fr);
S("hq.label.en", site.hq.label.en);
S("hq.building", "ADEPC Montréal");
S("hq.street", site.hq.street);
S("hq.locality", site.hq.locality);
S("hq.postal_code", site.hq.postalCode);
S("hq.city", "Montréal");
S("hq.region", "QC");
S("motto.text.fr", site.motto.text.fr);
S("motto.text.en", site.motto.text.en);
S("motto.ref.fr", site.motto.reference.fr);
S("motto.ref.en", site.motto.reference.en);
S("pastor.name.fr", "Pasteur principal", 1);
S("pastor.name.en", "Senior Pastor", 1);
S("pastor.role.fr", site.orgName.fr, 1);
S("pastor.role.en", site.orgName.en, 1);
S("giving.paypal.client_id", site.giving.paypal.clientId);
S("giving.paypal.button_id", site.giving.paypal.hostedButtonId);
S("giving.paypal.currency", site.giving.paypal.currency);
S("giving.paypal.sdk_url", "https://www.paypal.com/sdk/js?client-id={client}&components=hosted-buttons&disable-funding=venmo&currency={currency}");
S("giving.interac.email", site.giving.interac.email);
S("youtube.channel_id", "");
S("youtube.feed_url", "https://www.youtube.com/feeds/videos.xml?channel_id={channel}");
S("youtube.embed_url", "https://www.youtube-nocookie.com/embed/{id}?autoplay=1&rel=0");
S("youtube.thumb_url", "https://i.ytimg.com/vi/{id}/hqdefault.jpg");
S("youtube.live_pattern", "live|en direct");
S("youtube.limit", "12");
S("maps.directions_url", "https://www.google.com/maps/dir/?api=1&destination={destination}");
S("maps.mapbox_token", "");
S("maps.mapbox_style", "mapbox://styles/mapbox/light-v11");
S("maps.mapbox_js", "https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.js");
S("maps.mapbox_css", "https://api.mapbox.com/mapbox-gl-js/v3.15.0/mapbox-gl.css");
S("phone.country_code", "1");
S("schedule.timezone", "America/Toronto");
S("analytics.ga_id", "");
S("seo.robots_disallow", "/admin\n/api/");
S("home.film.video_id", "1");
S("contact.min_seconds", "3");
S("mail.transport", "none");
S("mail.smtp_host", "");
S("mail.smtp_port", "465");
S("mail.smtp_secure", "ssl");
S("mail.smtp_user", "");
S("mail.smtp_pass", "");
S("mail.from_address", "");
S("mail.from_name", "ADEPC");
S("mail.to", site.email);
S("mail.to_pastor", "");
S("privacy.policy.fr", [
  "L'ADEPC ne recueille que les renseignements personnels que vous choisissez de lui transmettre.",
  "Les messages envoyés avec le formulaire de contact sont acheminés par courriel à l'ADEPC et ne sont pas conservés sur ce site. Les visites sont comptées par des statistiques sans témoins ; Google Analytics, qui utilise des témoins, ne se charge que si vous l'acceptez. Les dons sont traités par PayPal ou par votre banque (virement Interac) : l'ADEPC ne voit jamais les données de votre carte.",
  "Pour consulter, corriger ou supprimer vos renseignements, écrivez à info@adepc.ca.",
].join("\n\n"), 1);
S("privacy.policy.en", [
  "ADEPC only collects the personal information you choose to send it.",
  "Messages sent with the contact form are delivered to ADEPC by email and are not kept on this website. Visits are counted with cookie-free statistics; Google Analytics, which uses cookies, only loads if you accept it. Donations are processed by PayPal or by your bank (Interac e-Transfer): ADEPC never sees your card details.",
  "To access, correct or delete your information, write to info@adepc.ca.",
].join("\n\n"), 1);
S("privacy.officer.fr", "La direction de l'ADEPC — info@adepc.ca", 1);
S("privacy.officer.en", "ADEPC leadership — info@adepc.ca", 1);
write("settings", set);

// ── Editorial notes for the dashboard (content/removed.ts) ──────────────
const removedFr = [
  ["Événements : Congrès régional de réveil (24 oct. 2024), conférence jeunesse Ignite 2024, Festival des récoltes, distribution de pain Unity", "Événements passés ou gabarits au texte générique ; rien n'indique qu'ils aient eu lieu comme décrits."],
  ["« Une famille de foi depuis 1940 » / « fondée en 1940 »", "Contredit par « plus de 3 ans de ministère » sur la même page. Année de fondation inconnue."],
  ["Statistiques : « 100+ membres et amis », « 3+ ans de ministère »", "Non vérifiées. Seuls les chiffres vérifiables (4 églises, 4 villes) sont affichés."],
  ["Programmes des églises : ministère jeunesse (hebdomadaire), enfants 3–12 ans, réunion de prière (hebdomadaire), évangélisation (mensuelle), sainte cène le premier dimanche", "Texte générique identique sur les quatre pages d'église. À rétablir par église une fois confirmé."],
  ["Heures de bureau : lun.–ven. 8 h–17 h, samedi 9 h–13 h", "Semble provenir d'un gabarit ; confirmer qu'un bureau est réellement ouvert."],
  ["Option de don « Virement bancaire — comptes canadiens »", "Présentée sans coordonnées bancaires. PayPal et le virement Interac sont conservés."],
  ["Message de la semaine « Marcher par la foi et non par la vue »", "Non daté (« Semaine du — ») ; remplacé par la section Regarder, alimentée par YouTube."],
  ["Téléphone +1 (514) 000-0000", "Numéro fictif. Le vrai numéro de chaque église (tiré de la bannière de l'ADEPC) est affiché à la place."],
];
const bannerFr = [
  "Numéros de téléphone et adresses Gmail des églises (Montréal, Granby, Ottawa, Québec). Note : le numéro de Granby a l'indicatif 418.",
  "Énoncé de mission : « Proclamer l'évangile de Jésus Christ selon la Sainte Bible. Offrir aux communautés les services socioéconomiques qui apportent le plein développement. »",
  "Verset de la devise : Lévitique 6:6.",
  "L'adresse de virement Interac adepc.quebec@adepc.ca (ancien site) diffère du courriel de Québec sur la bannière, adepc.quebec@gmail.com — confirmer l'adresse de virement.",
];
write("admin_notes", [
  ...removed.map((r, i) => ({ kind: "removed", what_fr: removedFr[i][0], what_en: r.what, why_fr: removedFr[i][1], why_en: r.why, sort: i + 1 })),
  ...fromBanner.map((b, i) => ({ kind: "banner", what_fr: bannerFr[i], what_en: b, why_fr: null, why_en: null, sort: i + 1 })),
]);

console.log(`exported ${translations.length} translations, ${media.length} media, ${lists.length} list items`);
