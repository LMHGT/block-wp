import fs from "node:fs";
import path from "node:path";
import { expect, test } from "@playwright/test";

const screenshotDir = path.join(process.cwd(), "artifacts/screenshots");
const pages = [
  { name: "home", path: "/" },
  { name: "services", path: "/services/" },
  { name: "individual-counseling", path: "/individual-counseling/" },
  { name: "child-counseling", path: "/child-counseling/" },
  { name: "play-therapy", path: "/play-therapy/" },
  { name: "community-based-services", path: "/community-based-services/" },
  { name: "locations", path: "/locations/" },
  { name: "faq", path: "/faq/" },
  { name: "contact", path: "/contact-us/" },
  { name: "article", path: "/articles/guide-to-individual-therapy/" },
  { name: "team", path: "/meet-the-team/" }
];

test.beforeAll(() => {
  fs.mkdirSync(screenshotDir, { recursive: true });
});

for (const pageInfo of pages) {
  test(`${pageInfo.name} renders canonical meaningful content`, async ({ page }, testInfo) => {
    const consoleErrors = [];
    page.on("console", (message) => {
      if (message.type() === "error") consoleErrors.push(message.text());
    });
    const response = await page.goto(pageInfo.path, { waitUntil: "networkidle" });
    expect(response?.status()).toBe(200);
    expect(new URL(page.url()).pathname).toBe(pageInfo.path);
    await expect(page.locator("body")).toBeVisible();
    const title = await page.title();
    expect(title.length).toBeGreaterThan(0);
    await expect(page.locator("a.lmhg-header-cta")).toHaveAttribute("href", "https://intakeq.com/new/g91Z8x/bjxuno");
    await expect(page.locator("a.lmhg-footer-phone")).toHaveAttribute("href", "tel:5024161416");
    const main = page.locator("main").first();
    await expect(main).toBeVisible();
    await expect(main).not.toHaveText(/^\\s*$/);
    await expect(main).not.toContainText(/Migration stub|LMHG Block WP|WordPress proof track/i);
    await page.screenshot({
      path: path.join(screenshotDir, `${testInfo.project.name}-${pageInfo.name}.png`),
      fullPage: true
    });
    expect(consoleErrors).toEqual([]);
  });
}
