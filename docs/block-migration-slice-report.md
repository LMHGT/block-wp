# Editable Block Migration Slice

Date: 2026-06-27T20:32:20.648Z

Source: https://staging.website-production-26u.pages.dev

This is the first review-slice manifest for moving LMHG pages from generated
proof content to editable Gutenberg block documents. It is designed for a
Codex/cloud WordPress runtime and does not require local Docker or local
WordPress.

## Import Contract

```bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/first-slice-block-manifest.json data/lmhg/block-migration/first-slice-media-manifest.json
```

The block import writes serialized core Gutenberg blocks to `post_content` and
stores source-to-block correlation metadata for audit and future editor tooling.

## Route Slice

| Route | Source mode | Blocks | Asset blocks | H1 |
|---|---:|---:|---:|---|
| /compliance/ | local-html-artifact | 36 | 1 | Mental Health Compliance in Louisville, KY |
| /privacy-policy/ | local-html-artifact | 17 | 1 | Privacy Policy for Louisville Mental Health Group |
| /terms-of-use/ | local-html-artifact | 17 | 1 | Terms of Use for Louisville Mental Health Group |
| /individual-counseling/ | local-html-artifact | 23 | 2 | Individual Therapy in Louisville, KY |

## Media And Visual Asset Correlation

| Asset ID | Kind | Route usages | Source URL |
|---|---:|---:|---|
| asset-764a96d9203d | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/adult-counseling-card-icon-transparent-320w.webp |
| asset-ac1eb67df08e | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/anxiety-depression-therapy-card-icon-transparent-320w.webp |
| inline-svg-7e036dcea96a | inline-svg | 1 | (inline) |
| inline-svg-8b0697135c7d | inline-svg | 1 | (inline) |
| inline-svg-a30622f2e7c3 | inline-svg | 1 | (inline) |

## Current Limits

- Image blocks still reference staging asset URLs in serialized block content;
  the paired media manifest records the assets to sideload and rewrite in the
  cloud runtime.
- Inline SVG illustrations are preserved as editable custom HTML blocks for the
  first slice; a later pass can convert repeatable icons/illustrations to block
  patterns or media-library SVG records if the host permits SVG uploads.
- This slice proves block editability and correlation. It is not yet the final
  visual-parity pass.
