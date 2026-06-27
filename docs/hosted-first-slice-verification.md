# Hosted First Slice Verification

Date: 2026-06-27

Runtime URL:

```text
http://racknerd.beagle-perch.ts.net:8091
```

Verified commit:

```text
2aebc76 fix: preserve tailnet review port
```

## Runtime State

The RackNerd runtime is a private tailnet WordPress instance served on the
tailnet IP and port `8091`. It does not replace the existing Tailscale Serve
mapping on `https://racknerd.beagle-perch.ts.net/`.

Remote checks confirmed the running WordPress container sees the current repo
theme and plugin bind mounts:

```text
/var/www/html/wp-content/themes/lmhg-block-theme /dev/vda2[/home/codex/lmhg-blockwp-first-slice/wp-content/themes/lmhg-block-theme] ext4 ro,relatime
theme_loaded=yes
is_block=yes
plugin_loaded=yes
home=http://racknerd.beagle-perch.ts.net:8091/
```

The bootstrap import completed with:

```text
{"created":0,"updated":55,"skipped":0,"failed":0,"redirects":117}
{"updated":4,"missing":0,"skipped":0,"failed":0,"blocks":93,"assets":5,"mediaImported":0,"mediaExisting":2,"mediaSkipped":3,"mediaFailed":0}
```

## Header And Indexing Checks

Each first-slice route returned `HTTP/1.1 200 OK` with the development robots
header:

```text
X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex
```

Checked routes:

```text
/
/compliance/
/privacy-policy/
/terms-of-use/
/individual-counseling/
```

## Rendered Page Checks

The first-slice pages render non-empty Gutenberg output with no direct
references back to `staging.website-production-26u.pages.dev`.

| Route | Bytes | Robots Meta | Migrated Block Markers | Staging URL Refs | H1 |
|---|---:|---|---:|---:|---|
| `/compliance/` | 41372 | yes | 70 | 0 | Mental Health Compliance in Louisville, KY |
| `/privacy-policy/` | 38578 | yes | 32 | 0 | Privacy Policy for Louisville Mental Health Group |
| `/terms-of-use/` | 38092 | yes | 32 | 0 | Terms of Use for Louisville Mental Health Group |
| `/individual-counseling/` | 47385 | yes | 46 | 0 | Individual Therapy in Louisville, KY |

The REST API also returns editable block content for the representative service
page:

```text
37 individual-counseling 4811 46
```

## Media Checks

The first service page uses WordPress media URLs for sideloaded visual assets:

```text
img_count 2
wp_uploads 2
staging_imgs 0
```

The imported WordPress attachments are:

```text
adult-counseling-card-icon-transparent-320w.webp
anxiety-depression-therapy-card-icon-transparent-320w.webp
```

A direct upload request returned:

```text
200 image/webp 2346
```

## Scope

This is a first-review slice, not the final full-site acceptance report. It
proves that the cloud-run WordPress runtime can serve imported, editable
Gutenberg pages with transferred media, suppressed indexing, and no dependency
on the Astro staging runtime for the checked pages.
