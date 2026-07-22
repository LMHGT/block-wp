import { chromium } from 'playwright';

const base = process.env.LMHG_WORDPRESS_URL || 'http://100.116.130.39:8093';
const widths = [319, 360, 390, 600, 768, 1024, 1292, 1440];
const workersPerViewport = 6;

const typesResponse = await fetch(`${base}/wp-json/wp/v2/types?context=view`);
if (!typesResponse.ok) {
	throw new Error(`Unable to load public content types: HTTP ${typesResponse.status}`);
}

const typeRegistry = await typesResponse.json();
const publicTypes = ['page', 'post'].map((slug) => typeRegistry[slug]).filter(Boolean);
if (publicTypes.length !== 2) {
	throw new Error('The WordPress Page and Post REST types are required for the text audit.');
}

const fetchTypeRoutes = async (type) => {
	const typeRoutes = [];
	let pageNumber = 1;
	let totalPages = 1;
	do {
		const response = await fetch(
			`${base}/wp-json/wp/v2/${type.rest_base}?per_page=100&page=${pageNumber}&status=publish&_fields=link`,
		);
		if (!response.ok) {
			throw new Error(`Unable to load ${type.slug} page ${pageNumber}: HTTP ${response.status}`);
		}
		totalPages = Number.parseInt(response.headers.get('x-wp-totalpages') || '1', 10);
		for (const item of await response.json()) {
			typeRoutes.push({ path: new URL(item.link).pathname, type: type.slug });
		}
		pageNumber += 1;
	} while (pageNumber <= totalPages);
	return typeRoutes;
};

const routes = [...new Map(
	(await Promise.all(publicTypes.map(fetchTypeRoutes)))
		.flat()
		.map((route) => [route.path, route]),
).values()].sort((left, right) => left.path.localeCompare(right.path));
if (!routes.length) {
	throw new Error('The text audit did not discover any published Pages or Posts.');
}

const browser = await chromium.launch({
	headless: true,
	executablePath: process.env.CHROME_PATH || '/usr/bin/google-chrome',
});
const results = [];

try {
	for (const width of widths) {
		const context = await browser.newContext({ viewport: { width, height: 900 } });
		await context.route('**/*', async (route) => {
			const type = route.request().resourceType();
			if (type === 'image' || type === 'media') {
				await route.abort();
				return;
			}
			await route.continue();
		});

		let nextIndex = 0;
		const workers = Array.from(
			{ length: Math.min(workersPerViewport, routes.length) },
			async () => {
				const page = await context.newPage();
				while (nextIndex < routes.length) {
					const route = routes[nextIndex++];
					let httpStatus = null;
					let metrics = null;
					let error = null;
					try {
						const response = await page.goto(`${base}${route.path}`, {
							waitUntil: 'domcontentloaded',
							timeout: 30000,
						});
						httpStatus = response?.status() || null;
						await page.waitForTimeout(120);
						metrics = await page.evaluate(() => {
							const tolerance = 1;
							const boundarySelector = [
								'.wp-block-group',
								'.wp-block-column',
								'.wp-block-buttons',
								'.wp-block-button',
								'.wp-block-list',
								'.wp-block-image',
								'header',
								'main',
								'footer',
							].join(',');
							const candidates = document.querySelectorAll([
								'header p', 'header a', 'header button',
								'main p', 'main h1', 'main h2', 'main h3', 'main h4', 'main h5', 'main h6',
								'main li', 'main a', 'main button', 'main figcaption', 'main summary', 'main blockquote',
								'footer p', 'footer a', 'footer button', 'footer li', 'footer h2', 'footer h3',
							].join(','));
							const failures = [];

							for (const element of candidates) {
								if (!element.textContent?.trim()) continue;
								const style = getComputedStyle(element);
								const rect = element.getBoundingClientRect();
								if (
									style.display === 'none'
									|| style.visibility === 'hidden'
									|| Number.parseFloat(style.opacity) === 0
									|| rect.width <= 0
									|| rect.height <= 0
								) continue;

								const range = document.createRange();
								range.selectNodeContents(element);
								const textRects = [...range.getClientRects()].filter(
									(textRect) => textRect.width > 0 && textRect.height > 0,
								);
								if (!textRects.length) continue;

								let boundary = element.closest(boundarySelector);
								if (boundary === element) boundary = element.parentElement?.closest(boundarySelector) || null;
								const boundaryRect = boundary?.getBoundingClientRect() || null;
								const outsideViewport = textRects.some(
									(textRect) => textRect.left < -tolerance || textRect.right > window.innerWidth + tolerance,
								);
								const outsideBoundary = boundaryRect
									? textRects.some(
										(textRect) => textRect.left < boundaryRect.left - tolerance
											|| textRect.right > boundaryRect.right + tolerance,
									)
									: false;

								if (outsideViewport || outsideBoundary) {
									failures.push({
										tag: element.tagName.toLowerCase(),
										text: element.textContent.trim().replace(/\s+/g, ' ').slice(0, 120),
										outsideViewport,
										outsideBoundary,
										boundary: boundary
											? `${boundary.tagName.toLowerCase()}.${[...boundary.classList].join('.')}`
											: null,
									});
								}
							}

							return {
								documentOverflow: document.documentElement.scrollWidth
									> document.documentElement.clientWidth + tolerance,
								failures: failures.slice(0, 20),
							};
						});
					} catch (caught) {
						error = String(caught);
					}
					results.push({ ...route, width, httpStatus, metrics, error });
				}
				await page.close();
			},
		);
		await Promise.all(workers);
		await context.close();
	}
} finally {
	await browser.close();
}

const failures = results.filter(({ path, httpStatus, metrics, error }) => (
	error
	|| httpStatus !== (path === '/not-found/' ? 404 : 200)
	|| metrics?.documentOverflow
	|| metrics?.failures?.length
));

console.log(JSON.stringify({
	base,
	publicTypes: publicTypes.map((type) => type.slug),
	routeCount: routes.length,
	widths,
	audited: results.length,
	failed: failures.length,
	failures: failures.slice(0, 25),
}, null, 2));

if (failures.length) process.exitCode = 1;
