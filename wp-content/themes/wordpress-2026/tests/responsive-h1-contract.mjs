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
	throw new Error('The WordPress Page and Post REST types are required for the H1 audit.');
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
	throw new Error('The H1 audit did not discover any published Pages or Posts.');
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
					const { path } = route;
					let httpStatus = null;
					let metrics = null;
					let error = null;
					try {
						const pageResponse = await page.goto(`${base}${path}`, {
							waitUntil: 'domcontentloaded',
							timeout: 30000,
						});
						httpStatus = pageResponse?.status() || null;
						await page.waitForTimeout(120);
						metrics = await page.evaluate(() => {
							const headings = [...document.querySelectorAll('main h1')].filter((heading) => {
								const style = getComputedStyle(heading);
								const rect = heading.getBoundingClientRect();
								return style.display !== 'none'
									&& style.visibility !== 'hidden'
									&& rect.width > 0
									&& rect.height > 0;
							});
							if (headings.length !== 1) {
								return { h1Count: headings.length };
							}

							const h1 = headings[0];
							const rect = h1.getBoundingClientRect();
							const boundary = h1.parentElement?.getBoundingClientRect() || null;
							const range = document.createRange();
							range.selectNodeContents(h1);
							const lineRects = [...range.getClientRects()]
								.filter((line) => line.width > 0 && line.height > 0);
							const lineTops = [...new Set(
								lineRects
									.map((line) => Math.round(line.top * 10) / 10),
							)];
							const textLeft = Math.min(...lineRects.map((line) => line.left));
							const textRight = Math.max(...lineRects.map((line) => line.right));
							const style = getComputedStyle(h1);
							const fitViewport = Number.parseFloat(h1.style.getPropertyValue('--wp2026-title-fit-vw'));
							const fitContainer = Number.parseFloat(h1.style.getPropertyValue('--wp2026-title-fit-cqi'));
							const queryContainer = h1.closest(
								'.wp2026-hero-copy, .wp2026-content-section, main[class*="wp2026-template-"]',
							);
							const containerRect = queryContainer?.getBoundingClientRect();
							const containerStyle = queryContainer ? getComputedStyle(queryContainer) : null;
							const containerWidth = containerRect && containerStyle
								? containerRect.width
									- (Number.parseFloat(containerStyle.paddingLeft) || 0)
									- (Number.parseFloat(containerStyle.paddingRight) || 0)
								: window.innerWidth;
							const probe = document.createElement('span');
							probe.style.cssText = 'font-size:var(--wp2026-heading-1);position:absolute;visibility:hidden';
							document.body.append(probe);
							const tokenFontSize = Number.parseFloat(getComputedStyle(probe).fontSize);
							probe.remove();
							const expectedFontSize = Math.min(
								tokenFontSize,
								fitViewport * window.innerWidth / 100,
								fitContainer * containerWidth / 100,
							);

							return {
								h1Count: headings.length,
								text: h1.textContent?.trim() || '',
								fontSize: Number.parseFloat(style.fontSize),
								expectedFontSize,
								fontMatchesContract: Math.abs(Number.parseFloat(style.fontSize) - expectedFontSize) < 0.25,
								fitted: h1.classList.contains('wp2026-title-fit'),
								hasViewportFit: Boolean(h1.style.getPropertyValue('--wp2026-title-fit-vw')),
								hasContainerFit: Boolean(h1.style.getPropertyValue('--wp2026-title-fit-cqi')),
								whiteSpace: style.whiteSpace,
								lineCount: lineTops.length,
								viewportContained: rect.left >= -1 && rect.right <= window.innerWidth + 1,
								parentContained: boundary
									? rect.left >= boundary.left - 1 && rect.right <= boundary.right + 1
									: true,
								textViewportContained: textLeft >= -1 && textRight <= window.innerWidth + 1,
								textParentContained: boundary
									? textLeft >= boundary.left - 1 && textRight <= boundary.right + 1
									: true,
								documentOverflow: document.documentElement.scrollWidth
									> document.documentElement.clientWidth + 1,
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

const routeSizes = new Map();
for (const result of results) {
	if (!routeSizes.has(result.path)) routeSizes.set(result.path, []);
	if (Number.isFinite(result.metrics?.fontSize)) {
		routeSizes.get(result.path).push(result.metrics.fontSize);
	}
}

const nonFluidRoutes = new Set(
	[...routeSizes]
		.filter(([, sizes]) => sizes.length !== widths.length || Math.max(...sizes) - Math.min(...sizes) < 0.5)
		.map(([path]) => path),
);

const failures = results.filter(({ path, httpStatus, metrics, error }) => (
	error
	|| httpStatus !== (path === '/not-found/' ? 404 : 200)
	|| metrics?.h1Count !== 1
	|| !metrics?.fitted
	|| !metrics?.hasViewportFit
	|| !metrics?.hasContainerFit
	|| !metrics?.fontMatchesContract
	|| metrics?.whiteSpace !== 'nowrap'
	|| metrics?.lineCount !== 1
	|| !metrics?.viewportContained
	|| !metrics?.parentContained
	|| !metrics?.textViewportContained
	|| !metrics?.textParentContained
	|| metrics?.documentOverflow
	|| nonFluidRoutes.has(path)
));

console.log(JSON.stringify({
	base,
	publicTypes: publicTypes.map((type) => type.slug),
	routeCount: routes.length,
	widths,
	audited: results.length,
	failed: failures.length,
	nonFluidRoutes: [...nonFluidRoutes],
	failures: failures.slice(0, 25),
}, null, 2));

if (failures.length) process.exitCode = 1;
