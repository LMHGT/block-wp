# Full Site Editable Block Migration

Date: 2026-06-28T16:57:29.383Z

Source: /Users/tyler-lcsw/projects/lmhg-astro-integrate

This manifest converts every current `200` Cloudflare staging route into
serialized editable Gutenberg content for the no-gap WordPress transition. The
content model is now driven by Astro source files and the route manifest; the
Cloudflare staging site remains the route and browser-verification surface.

## Import Contract

```bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json
```

## Routes

| Route | Source mode | Family / template | Blocks | Asset blocks | H1 |
|---|---:|---:|---:|---:|---|
| / | astro-source-json | homepage / home | 43 | 7 | Mental Health Clinic in Louisville, KY |
| /404.html | astro-source-json | page / not-found | 4 | 0 | Page Not Found |
| /adolescent-counseling/ | astro-source-json | concern-condition / specialty | 43 | 4 | Teen Therapy in Louisville, KY |
| /adult-counseling/ | astro-source-json | specialty / specialty | 38 | 3 | Adult Counseling in Louisville, KY |
| /anxiety-depression-therapy/ | astro-source-json | concern-condition / specialty | 49 | 6 | Anxiety and Depression Therapy in Louisville, KY |
| /articles/ | astro-source-json | secondary-footer / article | 10 | 0 | Mental Health Articles from Louisville Mental Health Group |
| /articles/family-therapy-vs-individual-therapy/ | astro-source-markdown | support / article | 38 | 3 | Family Therapy vs. Individual Therapy \| Which Fits? |
| /articles/guide-to-individual-therapy/ | astro-source-markdown | support / article | 35 | 1 | Guide to Individual Therapy \| What to Expect |
| /articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/ | astro-source-markdown | support / article | 47 | 2 | How to Talk to Someone About Therapy |
| /articles/top-5-signs-its-time-to-seek-therapy/ | astro-source-markdown | support / article | 39 | 2 | When to Seek Therapy \| 5 Signs Support May Help |
| /articles/what-to-expect-when-starting-therapy/ | astro-source-markdown | article / article | 11 | 0 | What to Expect When Starting Therapy |
| /attachment-therapy/ | astro-source-json | specialty / specialty | 34 | 3 | Attachment Therapy in Louisville, KY |
| /bullitt-county-ky/ | astro-source-json | service-area / location-access | 31 | 2 | Mental Health Services in Bullitt County, KY |
| /careers/ | astro-source-json | secondary-footer / trust | 10 | 0 | Mental Health Careers in Louisville, KY |
| /case-management/ | astro-source-json | specialty / specialty | 47 | 4 | Targeted Case Management in Louisville, KY |
| /child-behavioral-intervention/ | astro-source-json | specialty / specialty | 43 | 4 | Child Behavioral Therapy in Louisville, KY |
| /child-counseling/ | astro-source-json | broad-service-category / service | 35 | 4 | Child Counseling in Louisville, KY |
| /co-parenting/ | astro-source-json | specialty / specialty | 44 | 5 | Co-Parenting Services in Louisville, KY |
| /community-based-services/ | astro-source-json | broad-service-category / service | 35 | 4 | Community-Based Mental Health Services in Louisville, KY |
| /community-support/ | astro-source-json | specialty / specialty | 43 | 4 | Community Support Services in Louisville, KY |
| /compliance/ | astro-source-json | utility / legal-utility | 19 | 0 | Mental Health Compliance in Louisville, KY |
| /contact-us/ | astro-source-json | primary-hub / trust | 27 | 0 | Contact Louisville Mental Health Group |
| /couples-conflict-resolution/ | astro-source-json | concern-condition / specialty | 39 | 4 | Couples Conflict Resolution in Louisville, KY |
| /couples-counseling/ | astro-source-json | broad-service-category / service | 30 | 3 | Couples Counseling in Louisville, KY |
| /court-ordered/ | astro-source-json | broad-service-category / service | 30 | 3 | Court-Ordered Services in Louisville, KY |
| /emdr-therapy/ | astro-source-json | specialty / specialty | 36 | 5 | EMDR Therapy in Louisville, KY |
| /family-reunification/ | astro-source-json | specialty / specialty | 44 | 5 | Family Reunification Services in Louisville, KY |
| /family-therapy/ | astro-source-json | broad-service-category / service | 30 | 3 | Family Therapy in Louisville, KY |
| /faq/ | astro-source-json | primary-hub / faq | 17 | 0 | Mental Health Services FAQ in Louisville, KY |
| /faq/about-lmhg/ | astro-source-json | support / faq | 20 | 0 | About Louisville Mental Health Group \| Therapy and Support |
| /faq/cost/ | astro-source-json | support / faq | 20 | 0 | Therapy Cost in Louisville, KY |
| /faq/our-approach/ | astro-source-json | support / faq | 20 | 0 | Louisville Mental Health Group Approach |
| /group-therapy/ | astro-source-json | broad-service-category / service | 20 | 1 | Group Therapy in Louisville, KY |
| /individual-counseling/ | astro-source-json | broad-service-category / service | 30 | 3 | Individual Therapy in Louisville, KY |
| /insurance/ | astro-source-json | secondary-footer / trust | 7 | 0 | Medicaid Mental Health Services in Louisville, KY |
| /jefferson-county-ky/ | astro-source-json | service-area / location-access | 32 | 3 | Mental Health Services in Jefferson County, KY |
| /locations/ | astro-source-json | primary-hub / location-access | 8 | 0 | Mental Health Services Near Louisville, KY |
| /locations/community/ | astro-source-json | contextual-parent / location-access | 36 | 2 | Community-Based Mental Health Care in Louisville, KY |
| /locations/in-home/ | astro-source-json | contextual-parent / location-access | 33 | 2 | In-Home Mental Health Services in Louisville, KY |
| /locations/in-person/ | astro-source-json | contextual-parent / location-access | 33 | 1 | In-Person Counseling in Louisville, KY |
| /locations/online/ | astro-source-json | contextual-parent / location-access | 33 | 1 | Online Therapy in Kentucky |
| /locations/school/ | astro-source-json | contextual-parent / location-access | 39 | 4 | School-Based Mental Health Support in Louisville, KY |
| /louisville-ky/ | astro-source-json | service-area / location-access | 28 | 1 | Louisville Mental Health Services and Counseling |
| /meet-the-team/ | astro-source-json | primary-hub / trust | 8 | 0 | Mental Health Providers in Louisville, KY |
| /oldham-county-ky/ | astro-source-json | service-area / location-access | 31 | 2 | Mental Health Services in Oldham County, KY |
| /parenting-support/ | astro-source-json | concern-condition / specialty | 44 | 5 | Parenting Support in Louisville, KY |
| /play-therapy/ | astro-source-json | specialty / specialty | 44 | 5 | Play Therapy in Louisville, KY |
| /privacy-policy/ | astro-source-json | utility / legal-utility | 8 | 0 | Privacy Policy for Louisville Mental Health Group |
| /relationship-counseling/ | astro-source-json | specialty / specialty | 39 | 4 | Relationship Counseling in Louisville, KY |
| /reviews/ | astro-source-json | secondary-footer / trust | 8 | 0 | Louisville Mental Health Group Reviews \| Client Feedback |
| /services/ | astro-source-json | primary-hub / service | 47 | 0 | Mental Health Services in Louisville, KY |
| /specialties/ | astro-source-json | primary-hub / specialty | 37 | 5 | Mental Health Specialties in Louisville, KY |
| /terms-of-use/ | astro-source-json | utility / legal-utility | 8 | 0 | Terms of Use for Louisville Mental Health Group |
| /therapy-in-your-home/ | astro-source-json | specialty / specialty | 47 | 4 | In-Home Therapy in Louisville, KY |
| /trauma-therapy/ | astro-source-json | broad-service-category / service | 24 | 2 | Trauma Therapy in Louisville, KY |

## Media And Visual Asset Correlation

| Asset ID | Kind | Route usages | Source path |
|---|---:|---:|---|
| asset-012e6ebfb46a | image | 1 | public/illustrations/service-categories/community-based-services-category-graphic-transparent.webp |
| asset-022fdaf81ef9 | image | 1 | public/illustrations/service-categories/couples-counseling-category-graphic-transparent.webp |
| asset-07d357941a26 | image | 2 | public/illustrations/specialties/couples-conflict-resolution-card-icon-transparent.webp |
| asset-089bf375fd20 | image | 1 | public/illustrations/specialties/attachment-therapy-page-graphic-transparent.webp |
| asset-0f0b69e11d7c | image | 1 | public/illustrations/service-categories/group-therapy-card-icon-transparent.webp |
| asset-13fd29a04a80 | image | 1 | public/illustrations/specialties/play-therapy-page-graphic-transparent.webp |
| asset-1663165df253 | image | 1 | public/illustrations/service-categories/group-therapy-category-graphic-transparent.webp |
| asset-1b2453a8ae4b | image | 3 | public/illustrations/service-areas/louisville-ky-county-shape-transparent.svg |
| asset-1c824377db07 | image | 1 | public/illustrations/specialties/attachment-therapy-card-icon-transparent.webp |
| asset-29ae339d1f52 | image | 1 | public/illustrations/specialties/emdr-therapy-page-graphic-transparent.webp |
| asset-312c3ea95211 | image | 1 | public/illustrations/specialties/family-reunification-page-graphic-transparent.webp |
| asset-335ad19808b4 | image | 2 | public/illustrations/specialties/relationship-counseling-card-icon-transparent.webp |
| asset-379907324737 | image | 2 | public/illustrations/specialties/adult-counseling-card-icon-transparent.webp |
| asset-39f58ca95833 | image | 6 | public/illustrations/specialties/parenting-support-card-icon-transparent.webp |
| asset-43c7a6710298 | image | 6 | public/illustrations/service-categories/community-based-services-card-icon-transparent.webp |
| asset-4613d91f49c1 | image | 2 | public/illustrations/service-categories/trauma-therapy-card-icon-transparent.webp |
| asset-545d173a8f62 | image | 1 | public/illustrations/service-categories/trauma-therapy-category-graphic-transparent.webp |
| asset-566000de0b17 | image | 1 | public/illustrations/specialties/adult-counseling-page-graphic-transparent.webp |
| asset-5c520ab9fb34 | image | 1 | public/illustrations/specialties/parenting-support-page-graphic-transparent.webp |
| asset-5f497cd6e3da | image | 1 | public/illustrations/service-areas/jefferson-county-ky-shape-transparent.svg |
| asset-60446669b79f | image | 15 | public/illustrations/service-categories/family-therapy-card-icon-transparent.webp |
| asset-623c3bb8fa9b | image | 6 | public/illustrations/specialties/community-support-card-icon-transparent.webp |
| asset-641dd3d1bfce | image | 10 | public/illustrations/service-categories/individual-counseling-card-icon-transparent.webp |
| asset-646cbda1b142 | image | 3 | public/illustrations/specialties/co-parenting-card-icon-transparent.webp |
| asset-67f05f28eb42 | image | 1 | public/illustrations/specialties/community-support-page-graphic-transparent.webp |
| asset-693dd6df5431 | image | 1 | public/illustrations/service-categories/family-therapy-category-graphic-transparent.webp |
| asset-6c171a8329b6 | image | 4 | public/illustrations/service-categories/couples-counseling-card-icon-transparent.webp |
| asset-6db775023709 | image | 4 | public/illustrations/specialties/child-behavioral-intervention-card-icon-transparent.webp |
| asset-75d4a1f5238e | image | 1 | public/illustrations/service-categories/child-counseling-category-graphic-transparent.webp |
| asset-7a5241d8ee89 | image | 3 | public/illustrations/specialties/adolescent-counseling-card-icon-transparent.webp |
| asset-7d278659d92f | image | 3 | public/illustrations/specialties/family-reunification-card-icon-transparent.webp |
| asset-822c78d11e61 | image | 1 | public/illustrations/service-categories/court-ordered-category-graphic-transparent.webp |
| asset-856d6d21edf7 | image | 1 | public/illustrations/specialties/couples-conflict-resolution-page-graphic-transparent.webp |
| asset-87a1fb4bd518 | image | 9 | public/illustrations/service-categories/child-counseling-card-icon-transparent.webp |
| asset-8c6a48519327 | image | 1 | public/illustrations/specialties/relationship-counseling-page-graphic-transparent.webp |
| asset-90fff235b17b | image | 3 | public/illustrations/specialties/case-management-card-icon-transparent.webp |
| asset-979016d8aef9 | image | 1 | public/illustrations/specialties/therapy-in-your-home-page-graphic-transparent.webp |
| asset-980093adedd6 | image | 1 | public/illustrations/specialties/co-parenting-page-graphic-transparent.webp |
| asset-9b0b7580e52c | image | 3 | public/illustrations/service-categories/court-ordered-card-icon-transparent.webp |
| asset-a6475425b6df | image | 1 | public/illustrations/specialties/case-management-page-graphic-transparent.webp |
| asset-c7953ebd0fe3 | image | 1 | public/illustrations/specialties/adolescent-counseling-page-graphic-transparent.webp |
| asset-cc7c4f34fa7a | image | 1 | public/illustrations/service-categories/individual-counseling-category-graphic-transparent.webp |
| asset-cea2ffe8a559 | image | 1 | public/illustrations/specialties/anxiety-depression-therapy-page-graphic-transparent.webp |
| asset-de0d0d7be174 | image | 5 | public/illustrations/specialties/anxiety-depression-therapy-card-icon-transparent.webp |
| asset-e1143e812909 | image | 2 | public/illustrations/specialties/play-therapy-card-icon-transparent.webp |
| asset-e3e40a3d3f41 | image | 3 | public/illustrations/specialties/therapy-in-your-home-card-icon-transparent.webp |
| asset-ecc0cec38d1a | image | 1 | public/illustrations/specialties/child-behavioral-intervention-page-graphic-transparent.webp |
| asset-f467133beaf2 | image | 1 | public/illustrations/service-areas/oldham-county-ky-shape-transparent.svg |
| asset-f65de2694370 | image | 2 | public/illustrations/specialties/emdr-therapy-card-icon-transparent.webp |
| asset-f868511776a2 | image | 1 | public/illustrations/service-areas/bullitt-county-ky-shape-transparent.svg |
