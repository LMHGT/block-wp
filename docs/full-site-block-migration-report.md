# Full Site Editable Block Migration

Date: 2026-06-28T00:51:04.165Z

Source: https://staging.website-production-26u.pages.dev

This manifest converts every current `200` Cloudflare staging route into
serialized editable Gutenberg content for the no-gap WordPress transition.

## Import Contract

```bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json
```

## Routes

| Route | Source mode | Blocks | Asset blocks | Text hash matches source | H1 |
|---|---:|---:|---:|---:|---|
| / | live-staging-browser | 15 | 8 | yes | Mental Health Clinic in Louisville, KY |
| /404.html | live-staging-browser | 4 | 0 | yes | Page Not Found |
| /adolescent-counseling/ | live-staging-browser | 10 | 4 | yes | Teen Therapy in Louisville, KY |
| /adult-counseling/ | live-staging-browser | 9 | 3 | yes | Adult Counseling in Louisville, KY |
| /anxiety-depression-therapy/ | live-staging-browser | 13 | 6 | yes | Anxiety and Depression Therapy in Louisville, KY |
| /articles/ | live-staging-browser | 7 | 0 | yes | Mental Health Articles from Louisville Mental Health Group |
| /articles/family-therapy-vs-individual-therapy/ | live-staging-browser | 8 | 0 | yes | Family Therapy vs. Individual Therapy \| Which Fits? |
| /articles/guide-to-individual-therapy/ | live-staging-browser | 8 | 0 | yes | Guide to Individual Therapy \| What to Expect |
| /articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/ | live-staging-browser | 8 | 0 | yes | How to Talk to Someone About Therapy |
| /articles/top-5-signs-its-time-to-seek-therapy/ | live-staging-browser | 8 | 0 | yes | When to Seek Therapy \| 5 Signs Support May Help |
| /articles/what-to-expect-when-starting-therapy/ | live-staging-browser | 6 | 0 | yes | What to Expect When Starting Therapy |
| /attachment-therapy/ | live-staging-browser | 9 | 3 | yes | Attachment Therapy in Louisville, KY |
| /bullitt-county-ky/ | live-staging-browser | 9 | 1 | yes | Mental Health Services in Bullitt County, KY |
| /careers/ | live-staging-browser | 7 | 0 | yes | Mental Health Careers in Louisville, KY |
| /case-management/ | live-staging-browser | 11 | 4 | yes | Targeted Case Management in Louisville, KY |
| /child-behavioral-intervention/ | live-staging-browser | 10 | 4 | yes | Child Behavioral Therapy in Louisville, KY |
| /child-counseling/ | live-staging-browser | 10 | 4 | yes | Child Counseling in Louisville, KY |
| /co-parenting/ | live-staging-browser | 11 | 5 | yes | Co-Parenting Services in Louisville, KY |
| /community-based-services/ | live-staging-browser | 10 | 4 | yes | Community-Based Mental Health Services in Louisville, KY |
| /community-support/ | live-staging-browser | 10 | 4 | yes | Community Support Services in Louisville, KY |
| /compliance/ | live-staging-browser | 7 | 0 | yes | Mental Health Compliance in Louisville, KY |
| /contact-us/ | live-staging-browser | 8 | 0 | yes | Contact Louisville Mental Health Group |
| /couples-conflict-resolution/ | live-staging-browser | 10 | 4 | yes | Couples Conflict Resolution in Louisville, KY |
| /couples-counseling/ | live-staging-browser | 9 | 3 | yes | Couples Counseling in Louisville, KY |
| /court-ordered/ | live-staging-browser | 9 | 3 | yes | Court-Ordered Services in Louisville, KY |
| /emdr-therapy/ | live-staging-browser | 12 | 5 | yes | EMDR Therapy in Louisville, KY |
| /family-reunification/ | live-staging-browser | 11 | 5 | yes | Family Reunification Services in Louisville, KY |
| /family-therapy/ | live-staging-browser | 9 | 3 | yes | Family Therapy in Louisville, KY |
| /faq/ | live-staging-browser | 6 | 0 | yes | Mental Health Services FAQ in Louisville, KY |
| /faq/about-lmhg/ | live-staging-browser | 7 | 0 | yes | About Louisville Mental Health Group \| Therapy and Support |
| /faq/cost/ | live-staging-browser | 7 | 0 | yes | Therapy Cost in Louisville, KY |
| /faq/our-approach/ | live-staging-browser | 7 | 0 | yes | Louisville Mental Health Group Approach |
| /group-therapy/ | live-staging-browser | 6 | 1 | yes | Group Therapy in Louisville, KY |
| /individual-counseling/ | live-staging-browser | 9 | 3 | yes | Individual Therapy in Louisville, KY |
| /insurance/ | live-staging-browser | 6 | 0 | yes | Medicaid Mental Health Services in Louisville, KY |
| /jefferson-county-ky/ | live-staging-browser | 9 | 1 | yes | Mental Health Services in Jefferson County, KY |
| /locations/ | live-staging-browser | 9 | 0 | yes | Mental Health Services Near Louisville, KY |
| /locations/community/ | live-staging-browser | 11 | 3 | yes | Community-Based Mental Health Care in Louisville, KY |
| /locations/in-home/ | live-staging-browser | 7 | 0 | yes | In-Home Mental Health Services in Louisville, KY |
| /locations/in-person/ | live-staging-browser | 7 | 0 | yes | In-Person Counseling in Louisville, KY |
| /locations/online/ | live-staging-browser | 7 | 0 | yes | Online Therapy in Kentucky |
| /locations/school/ | live-staging-browser | 7 | 0 | yes | School-Based Mental Health Support in Louisville, KY |
| /louisville-ky/ | live-staging-browser | 9 | 1 | yes | Louisville Mental Health Services and Counseling |
| /meet-the-team/ | live-staging-browser | 6 | 1 | yes | Mental Health Providers in Louisville, KY |
| /oldham-county-ky/ | live-staging-browser | 9 | 1 | yes | Mental Health Services in Oldham County, KY |
| /parenting-support/ | live-staging-browser | 11 | 5 | yes | Parenting Support in Louisville, KY |
| /play-therapy/ | live-staging-browser | 11 | 5 | yes | Play Therapy in Louisville, KY |
| /privacy-policy/ | live-staging-browser | 5 | 0 | yes | Privacy Policy for Louisville Mental Health Group |
| /relationship-counseling/ | live-staging-browser | 10 | 4 | yes | Relationship Counseling in Louisville, KY |
| /reviews/ | live-staging-browser | 6 | 0 | yes | Louisville Mental Health Group Reviews \| Client Feedback |
| /services/ | live-staging-browser | 28 | 16 | yes | Mental Health Services in Louisville, KY |
| /specialties/ | live-staging-browser | 29 | 19 | yes | Mental Health Specialties in Louisville, KY |
| /terms-of-use/ | live-staging-browser | 5 | 0 | yes | Terms of Use for Louisville Mental Health Group |
| /therapy-in-your-home/ | live-staging-browser | 11 | 4 | yes | In-Home Therapy in Louisville, KY |
| /trauma-therapy/ | live-staging-browser | 8 | 2 | yes | Trauma Therapy in Louisville, KY |

## Media And Visual Asset Correlation

| Asset ID | Kind | Route usages | Source URL |
|---|---:|---:|---|
| asset-01f8b32d49f2 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/emdr-therapy-page-graphic-transparent-512w.webp |
| asset-07935ffc1a31 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/relationship-counseling-page-graphic-transparent-512w.webp |
| asset-0d6ce57ca79d | image | 5 | https://staging.website-production-26u.pages.dev/illustrations/specialties/adolescent-counseling-card-icon-transparent-320w.webp |
| asset-11f262a25fdc | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/attachment-therapy-page-graphic-transparent-512w.webp |
| asset-11fe9c4c6d59 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/anxiety-depression-therapy-page-graphic-transparent-512w.webp |
| asset-1b2453a8ae4b | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-areas/louisville-ky-county-shape-transparent.svg |
| asset-1b3d44de7cf6 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/family-reunification-page-graphic-transparent-512w.webp |
| asset-1e9b31e08114 | image | 4 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/trauma-therapy-card-icon-transparent-320w.webp |
| asset-1ef1a71e30c9 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/trauma-therapy-category-graphic-transparent-512w.webp |
| asset-213a817af1f0 | image | 6 | https://staging.website-production-26u.pages.dev/illustrations/specialties/case-management-card-icon-transparent-320w.webp |
| asset-22c3e0f9cdfc | image | 13 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/family-therapy-card-icon-transparent-320w.webp |
| asset-2cdb8f7c7fb5 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/child-counseling-category-graphic-transparent-512w.webp |
| asset-433e14d8148c | image | 3 | https://staging.website-production-26u.pages.dev/illustrations/specialties/emdr-therapy-card-icon-transparent-320w.webp |
| asset-4e715ebcb602 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/attachment-therapy-card-icon-transparent-320w.webp |
| asset-5f497cd6e3da | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-areas/jefferson-county-ky-shape-transparent.svg |
| asset-6294f862e815 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/case-management-page-graphic-transparent-512w.webp |
| asset-6dc900e366f4 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/child-behavioral-intervention-page-graphic-transparent-512w.webp |
| asset-72ea4fc0e0e8 | image | 3 | https://staging.website-production-26u.pages.dev/illustrations/specialties/therapy-in-your-home-card-icon-transparent-320w.webp |
| asset-75718613e100 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/court-ordered-category-graphic-transparent-512w.webp |
| asset-764a96d9203d | image | 2 | https://staging.website-production-26u.pages.dev/illustrations/specialties/adult-counseling-card-icon-transparent-320w.webp |
| asset-76ba467172e8 | image | 5 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/couples-counseling-card-icon-transparent-320w.webp |
| asset-863fc5f1236a | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/group-therapy-category-graphic-transparent-512w.webp |
| asset-8656c22b479e | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/couples-conflict-resolution-page-graphic-transparent-512w.webp |
| asset-9680b0b25263 | image | 7 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/individual-counseling-card-icon-transparent-320w.webp |
| asset-99004420e59a | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/play-therapy-page-graphic-transparent-512w.webp |
| asset-9b66cac0e31c | image | 2 | https://staging.website-production-26u.pages.dev/illustrations/specialties/relationship-counseling-card-icon-transparent-320w.webp |
| asset-9cefa3443cce | image | 4 | https://staging.website-production-26u.pages.dev/illustrations/specialties/play-therapy-card-icon-transparent-320w.webp |
| asset-9da229e49f70 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/couples-counseling-category-graphic-transparent-512w.webp |
| asset-a1923bbddf80 | image | 3 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/group-therapy-card-icon-transparent-320w.webp |
| asset-aac5df3ada9a | image | 5 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/court-ordered-card-icon-transparent-320w.webp |
| asset-aafac787c6b6 | image | 1 | https://staging.website-production-26u.pages.dev/images/team/brooklyn-atherton-lpca-256w.webp |
| asset-ac1eb67df08e | image | 5 | https://staging.website-production-26u.pages.dev/illustrations/specialties/anxiety-depression-therapy-card-icon-transparent-320w.webp |
| asset-ae8c560a0736 | image | 4 | https://staging.website-production-26u.pages.dev/illustrations/specialties/family-reunification-card-icon-transparent-320w.webp |
| asset-aeeae6accab5 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/parenting-support-page-graphic-transparent-512w.webp |
| asset-b3e87127bec9 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/adult-counseling-page-graphic-transparent-512w.webp |
| asset-b4b2d82162cd | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/co-parenting-page-graphic-transparent-512w.webp |
| asset-b76e4bbc5400 | image | 3 | https://staging.website-production-26u.pages.dev/illustrations/specialties/child-behavioral-intervention-card-icon-transparent-320w.webp |
| asset-bcc9fa0aed1a | image | 5 | https://staging.website-production-26u.pages.dev/illustrations/specialties/co-parenting-card-icon-transparent-320w.webp |
| asset-c53d0c2c0a2c | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/individual-counseling-category-graphic-transparent-512w.webp |
| asset-cb99d539775a | image | 6 | https://staging.website-production-26u.pages.dev/illustrations/specialties/parenting-support-card-icon-transparent-320w.webp |
| asset-da621c2f36bd | image | 3 | https://staging.website-production-26u.pages.dev/illustrations/specialties/couples-conflict-resolution-card-icon-transparent-320w.webp |
| asset-e70550bac20f | image | 9 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/child-counseling-card-icon-transparent-320w.webp |
| asset-e7d8cb3b61bf | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/therapy-in-your-home-page-graphic-transparent-512w.webp |
| asset-f0f9897cb8cc | image | 6 | https://staging.website-production-26u.pages.dev/illustrations/specialties/community-support-card-icon-transparent-320w.webp |
| asset-f467133beaf2 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-areas/oldham-county-ky-shape-transparent.svg |
| asset-f483aa47eefb | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/community-based-services-category-graphic-transparent-512w.webp |
| asset-f868511776a2 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-areas/bullitt-county-ky-shape-transparent.svg |
| asset-f8a73dba7c85 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/community-support-page-graphic-transparent-512w.webp |
| asset-fc96c11a84f9 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/specialties/adolescent-counseling-page-graphic-transparent-512w.webp |
| asset-fe946fed5c09 | image | 7 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/community-based-services-card-icon-transparent-320w.webp |
| asset-ff0f75f87e79 | image | 1 | https://staging.website-production-26u.pages.dev/illustrations/service-categories/family-therapy-category-graphic-transparent-512w.webp |
