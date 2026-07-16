# LMHG SEO Copy Intake Resources

This folder keeps owner answers, model inferences, SEO findings, and page copy drafts separate from deployable WordPress files.

## Durable Sources

- `brand-and-page-copy-intake.json`: direct owner answers and confirmed page rules.
- `rank-math-keyword-map.json`: canonical SEO Decision Lab keyword authority for Rank Math and deployable metadata. Newer route-specific entries in this ledger override older Core30 and tracking artifacts.
- `confidence-led-page-briefs.json`: high-, medium-, and low-confidence inferences for pages that were drafted without another full interview.
- `page-copy-drafts/`: optimized copy drafts for all 23 Services and Specialties pages, including both hubs, plus the consolidated In-Home location page.
- `scripts/verify-service-specialty-copy.mjs`: checks page inventory, keyword coverage, copy length, reading grade, metadata, and the gentle next-step section.
- `unresolved-questions.md`: one cumulative set of only the decisions that could not be made with high confidence.
- `page-architecture-recommendations.md`: provisional keep, rename, merge, redirect, and conditional-add recommendations based on service boundaries and current search intent.

## Promotion Boundary

These resources are not deployable page data. Copy should move to the WordPress source or live editor only after owner review. This keeps the `resources` branch reusable and prevents draft assumptions from becoming live service claims by accident.
