# Hosted Verification Workflow

Date: 2026-06-27

This repository is ready for hosted verification of the editable block migration
slice, but the current GitHub OAuth token does not have `workflow` scope. GitHub
therefore rejected creating `.github/workflows/verify-block-slice.yml` from this
session.

When a token with `workflow` scope is available, install this workflow to run the
first block-slice generator and verifier on GitHub-hosted infrastructure. This
keeps local machines free from WordPress and Docker requirements for this source
manifest proof.

```yaml
name: Verify Editable Block Slice

on:
  workflow_dispatch:
  push:
    paths:
      - "data/lmhg/**"
      - "docs/**"
      - "plan/**"
      - "tools/**"
      - "wp-content/**"
      - "package.json"
      - "package-lock.json"

jobs:
  verify:
    name: Generate and verify first block slice
    runs-on: ubuntu-latest
    permissions:
      contents: read
    steps:
      - name: Check out repository
        uses: actions/checkout@v4

      - name: Set up Node
        uses: actions/setup-node@v4
        with:
          node-version: "22"
          cache: npm

      - name: Install dependencies
        run: npm ci

      - name: Install Playwright browser
        run: npx playwright install --with-deps chromium

      - name: Generate block slice from live staging
        run: npm run generate:block-slice -- --live

      - name: Verify block slice
        run: npm run verify:block-slice

      - name: Verify staging snapshot contract
        run: npm run verify:staging-snapshot

      - name: Run static checks
        run: npm run check:static

      - name: Upload block migration reports
        uses: actions/upload-artifact@v4
        if: always()
        with:
          name: block-migration-slice
          path: |
            data/lmhg/block-migration/first-slice-block-manifest.json
            data/lmhg/block-migration/first-slice-media-manifest.json
            docs/block-migration-slice-report.md
```

This is not the final private WordPress staging proof. The next infrastructure
step still needs a cloud WordPress runtime that can run:

```bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/first-slice-block-manifest.json data/lmhg/block-migration/first-slice-media-manifest.json
```
