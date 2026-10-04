// Port of adepc/tests/e2e/site.spec.ts for the PHP site. Changes are marked "PHP:".
import AxeBuilder from "@axe-core/playwright";
import { expect, test } from "@playwright/test";

// Every page, in both languages: [fr, en].
const PAGES = [
  ["/fr", "/en"],
  ["/fr/eglises", "/en/churches"],
  ["/fr/eglises/montreal", "/en/churches/montreal"],
  ["/fr/eglises/quebec", "/en/churches/quebec"],
  ["/fr/evenements", "/en/events"],
  ["/fr/regarder", "/en/watch"],
  ["/fr/a-propos", "/en/about"],
  ["/fr/histoires", "/en/stories"],
  ["/fr/donner", "/en/give"],
  ["/fr/contact", "/en/contact"],
  ["/fr/confidentialite", "/en/privacy"],
];
const ALL = PAGES.flat();

// Scroll once so lazy content and scroll reveals settle before measuring.
async function settle(page) {
  await page.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 700) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 30));
    }
    window.scrollTo(0, 0);
  });
}

test.describe("every page", () => {
  for (const path of ALL) {
    test(`${path}: accessible, square, and no overflow`, async ({ page }) => {
      await page.emulateMedia({ reducedMotion: "reduce" });
      const res = await page.goto(path);
      expect(res?.status()).toBe(200);
      await settle(page);

      // One h1, and the document language matches the URL.
      await expect(page.locator("h1")).toHaveCount(1);
      await expect(page.locator("html")).toHaveAttribute("lang", path.slice(1, 3));

      // Accessibility: no serious or critical axe violations.
      const axe = await new AxeBuilder({ page })
        .exclude("[id^=paypal-]") // third-party iframe
        .analyze();
      const serious = axe.violations.filter((v) => v.impact === "serious" || v.impact === "critical");
      expect(serious.map((v) => `${v.id}: ${v.nodes[0]?.target}`)).toEqual([]);

      // Brand rule: 0px radius everywhere.
      const rounded = await page.evaluate(() =>
        [...document.querySelectorAll("body *")]
          .filter((el) => parseFloat(getComputedStyle(el).borderTopLeftRadius) > 0)
          .map((el) => el.tagName + "." + (el.getAttribute("class") ?? "").slice(0, 40)),
      );
      expect(rounded).toEqual([]);

      // No sideways scrolling, and no clipped headings or buttons (French runs long).
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      expect(overflow).toBeLessThanOrEqual(0);
      const clipped = await page.evaluate(() =>
        [...document.querySelectorAll("h1, h2, h3, a[class*=min-h], button")]
          .filter((el) => {
            const s = getComputedStyle(el);
            return s.overflow !== "visible" && el.clientWidth > 1 && el.scrollWidth > el.clientWidth + 2;
          })
          .map((el) => el.textContent?.trim().slice(0, 40)),
      );
      expect(clipped).toEqual([]);
    });
  }
});

// Tablet widths run on the 1x desktop project. Stretching the 2.6x phone to
// 900px only makes the local image optimizer encode needlessly huge AVIFs.
test("320px phones and tablets: nothing overflows", async ({ page, isMobile }) => {
  test.setTimeout(90_000);
  for (const width of isMobile ? [320] : [320, 768, 900]) {
    await page.setViewportSize({ width, height: 640 });
    for (const path of ALL) {
      await page.goto(path);
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      expect(overflow, `${path} at ${width}px`).toBeLessThanOrEqual(0);
    }
  }
});

test("header shows the logo, sized for the screen", async ({ page, isMobile }) => {
  await page.goto("/fr");
  const logo = page.locator("header > div a[aria-label] img");
  await expect(logo).toBeVisible();
  expect(await logo.evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);
  const tile = await logo.locator("..").boundingBox();
  expect(tile?.width).toBe(isMobile ? 44 : 52);
});

test("language switcher links each page to its translation", async ({ page }) => {
  for (const [fr, en] of PAGES) {
    await page.goto(fr);
    await expect(page.locator('a[hreflang="en"]').first()).toHaveAttribute("href", en);
    await page.goto(en);
    await expect(page.locator('a[hreflang="fr"]').first()).toHaveAttribute("href", fr);
  }
});

test("hreflang alternates and canonical are present", async ({ page }) => {
  await page.goto("/fr/eglises/ottawa");
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute("href", /\/fr\/eglises\/ottawa$/);
  await expect(page.locator('link[rel="alternate"][hreflang="en-CA"]')).toHaveAttribute("href", /\/en\/churches\/ottawa$/);
  await expect(page.locator('link[rel="alternate"][hreflang="x-default"]')).toHaveCount(1);
  await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(1);
});

test("sitemap and robots", async ({ request }) => {
  const sitemap = await request.get("/sitemap.xml");
  expect(sitemap.ok()).toBe(true);
  const xml = await sitemap.text();
  expect(xml).toContain("/fr/eglises/montreal");
  expect(xml).toContain("/en/churches/montreal");
  const robots = await (await request.get("/robots.txt")).text();
  expect(robots).toContain("Sitemap:");
  expect(robots).toContain("Disallow: /admin"); // PHP: the admin replaces Sanity Studio
});

test("old adepc.ca URLs redirect permanently", async ({ request }) => {
  const cases = [
    ["/pages/about.html", "/en/about"],
    ["/pages/church-ottawa.html", "/en/churches/ottawa"],
    ["/pages/gallery.html", "/en/stories"],
    ["/index.html", "/en"],
  ];
  for (const [from, to] of cases) {
    const res = await request.get(from, { maxRedirects: 0 });
    expect(res.status(), from).toBe(308);
    expect(res.headers().location, from).toBe(to);
  }
});

test("unknown pages return 404", async ({ request }) => {
  expect((await request.get("/en/does-not-exist")).status()).toBe(404);
  expect((await request.get("/en/churches/nowhere")).status()).toBe(404);
});

test("mobile menu opens, traps focus and closes with Escape", async ({ page }, info) => {
  test.skip(info.project.name !== "mobile", "menu button is mobile-only");
  await page.goto("/en");
  const trigger = page.getByRole("button", { name: "Open menu" });
  await trigger.click();
  const dialog = page.getByRole("dialog", { name: "Menu" });
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole("link", { name: "Churches" })).toBeVisible();
  await page.keyboard.press("Escape");
  await expect(dialog).toBeHidden();
  await expect(trigger).toBeFocused();
});

test("This Sunday lists the four churches in service order", async ({ page }) => {
  await page.goto("/en");
  const cities = await page.locator("#this-sunday ol h3").allTextContents();
  expect(cities.map((c) => c.trim())).toEqual(["Ottawa", "Granby", "Montréal", "Québec City"]);
});

test("contact form validates, then falls back to email when Resend is not configured", async ({ page }) => {
  await page.goto("/en/contact?church=granby&purpose=visit");
  await expect(page.locator("#cf-church")).toHaveValue("granby");
  await expect(page.locator("#cf-purpose")).toHaveValue("visit");

  await page.getByRole("button", { name: "Send message" }).click();
  await expect(page.getByText("Required").first()).toBeVisible();

  await page.fill("#cf-name", "Test Person");
  await page.fill("#cf-email", "test@example.com");
  await page.fill("#cf-message", "We would like to visit on Sunday with our family.");
  await page.waitForTimeout(3100); // the bot check rejects instant submissions

  // PHP: email sending is off in the test install, so the mailto fallback applies.
  await page.getByRole("button", { name: "Send message" }).click();
  await expect(page.getByText(/email app should open/i)).toBeVisible();
});

test("Give page shows both real giving paths", async ({ page }) => {
  await page.goto("/en/give");
  await expect(page.getByRole("heading", { name: "Give online" })).toBeVisible();
  await expect(page.getByText("adepc.quebec@adepc.ca").first()).toBeVisible();
});

test("hero background video plays and can be paused (WCAG 2.2.2)", async ({ page }) => {
  await page.goto("/en");
  const pause = page.getByRole("button", { name: "Pause background video" });
  await expect(pause).toBeVisible({ timeout: 15_000 });
  await pause.click();
  await expect(page.getByRole("button", { name: "Play background video" })).toBeVisible();
  expect(await page.locator("section video").first().evaluate((v) => v.paused)).toBe(true);
});

test("no background video for people who prefer reduced motion", async ({ page }) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  await page.goto("/en");
  await page.waitForTimeout(1500);
  await expect(page.locator("section video")).toHaveCount(0);
});

test("the film loads only when someone presses play", async ({ page }) => {
  await page.goto("/en");
  await expect(page.locator("#film video")).toHaveCount(0);
  await page.getByRole("button", { name: /Play the film/ }).click();
  const film = page.locator("#film video");
  await expect(film).toBeVisible();
  await expect(film.locator("source")).toHaveAttribute("src", "/media/video/this-is-adepc.mp4"); // PHP: media library path
});
