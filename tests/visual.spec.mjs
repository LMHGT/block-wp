import fs from "node:fs";
import path from "node:path";
import { expect, test } from "@playwright/test";

const screenshotDir = path.join(process.cwd(), "artifacts/screenshots");
const pages = [
  { name: "home", path: "/" },
  { name: "about", path: "/about/" }
];

test.beforeAll(() => {
  fs.mkdirSync(screenshotDir, { recursive: true });
});

for (const pageInfo of pages) {
  test(`${pageInfo.name} renders meaningful content`, async ({ page }, testInfo) => {
    const consoleErrors = [];
    page.on("console", (message) => {
      if (message.type() === "error") consoleErrors.push(message.text());
    });
    await page.goto(pageInfo.path, { waitUntil: "networkidle" });
    await expect(page.locator("body")).toBeVisible();
    const title = await page.title();
    expect(title.length).toBeGreaterThan(0);
    const main = page.locator("main").first();
    await expect(main).toBeVisible();
    await expect(main).not.toHaveText(/^\\s*$/);
    await page.screenshot({
      path: path.join(screenshotDir, `${testInfo.project.name}-${pageInfo.name}.png`),
      fullPage: true
    });
    expect(consoleErrors).toEqual([]);
  });
}
