# Full Site Editable Block Migration

Date: 2026-06-28T00:31:24.811Z

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
| / | local-html-artifact | 18 | 8 | yes | Mental Health Clinic in Louisville, KY |
| /adolescent-counseling/ | local-html-artifact | 13 | 4 | yes | Teen Therapy in Louisville, KY |
| /adult-counseling/ | local-html-artifact | 12 | 3 | yes | Adult Counseling in Louisville, KY |
| /anxiety-depression-therapy/ | local-html-artifact | 16 | 6 | yes | Anxiety and Depression Therapy in Louisville, KY |
| /articles/ | local-html-artifact | 9 | 0 | yes | Mental Health Articles from Louisville Mental Health Group |
| /articles/family-therapy-vs-individual-therapy/ | local-html-artifact | 10 | 0 | yes | Family Therapy vs. Individual Therapy \| Which Fits? |
| /articles/guide-to-individual-therapy/ | local-html-artifact | 10 | 0 | yes | Guide to Individual Therapy \| What to Expect |
| /articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/ | local-html-artifact | 10 | 0 | yes | How to Talk to Someone About Therapy |
| /articles/top-5-signs-its-time-to-seek-therapy/ | local-html-artifact | 10 | 0 | yes | When to Seek Therapy \| 5 Signs Support May Help |
| /articles/what-to-expect-when-starting-therapy/ | local-html-artifact | 8 | 0 | yes | What to Expect When Starting Therapy |
| /attachment-therapy/ | local-html-artifact | 12 | 3 | yes | Attachment Therapy in Louisville, KY |
| /bullitt-county-ky/ | local-html-artifact | 11 | 1 | yes | Mental Health Services in Bullitt County, KY |
| /careers/ | local-html-artifact | 11 | 0 | yes | Mental Health Careers in Louisville, KY |
| /case-management/ | local-html-artifact | 14 | 4 | yes | Targeted Case Management in Louisville, KY |
| /child-behavioral-intervention/ | local-html-artifact | 13 | 4 | yes | Child Behavioral Therapy in Louisville, KY |
| /child-counseling/ | local-html-artifact | 13 | 4 | yes | Child Counseling in Louisville, KY |
| /co-parenting/ | local-html-artifact | 14 | 5 | yes | Co-Parenting Services in Louisville, KY |
| /community-based-services/ | local-html-artifact | 13 | 4 | yes | Community-Based Mental Health Services in Louisville, KY |
| /community-support/ | local-html-artifact | 13 | 4 | yes | Community Support Services in Louisville, KY |
| /compliance/ | local-html-artifact | 9 | 0 | yes | Mental Health Compliance in Louisville, KY |
| /contact-us/ | local-html-artifact | 14 | 0 | yes | Contact Louisville Mental Health Group |
| /couples-conflict-resolution/ | local-html-artifact | 13 | 4 | yes | Couples Conflict Resolution in Louisville, KY |
| /couples-counseling/ | local-html-artifact | 12 | 3 | yes | Couples Counseling in Louisville, KY |
| /court-ordered/ | local-html-artifact | 12 | 3 | yes | Court-Ordered Services in Louisville, KY |
| /emdr-therapy/ | local-html-artifact | 16 | 5 | yes | EMDR Therapy in Louisville, KY |
| /family-reunification/ | local-html-artifact | 14 | 5 | yes | Family Reunification Services in Louisville, KY |
| /family-therapy/ | local-html-artifact | 12 | 3 | yes | Family Therapy in Louisville, KY |
| /faq/ | local-html-artifact | 8 | 0 | yes | Mental Health Services FAQ in Louisville, KY |
| /faq/about-lmhg/ | local-html-artifact | 10 | 0 | yes | About Louisville Mental Health Group \| Therapy and Support |
| /faq/cost/ | local-html-artifact | 9 | 0 | yes | Therapy Cost in Louisville, KY |
| /faq/our-approach/ | local-html-artifact | 10 | 0 | yes | Louisville Mental Health Group Approach |
| /group-therapy/ | local-html-artifact | 9 | 1 | yes | Group Therapy in Louisville, KY |
| /individual-counseling/ | local-html-artifact | 12 | 3 | yes | Individual Therapy in Louisville, KY |
| /insurance/ | local-html-artifact | 10 | 0 | yes | Medicaid Mental Health Services in Louisville, KY |
| /jefferson-county-ky/ | local-html-artifact | 10 | 1 | yes | Mental Health Services in Jefferson County, KY |
| /locations/ | local-html-artifact | 11 | 0 | yes | Mental Health Services Near Louisville, KY |
| /locations/community/ | local-html-artifact | 14 | 3 | yes | Community-Based Mental Health Care in Louisville, KY |
| /locations/in-home/ | local-html-artifact | 10 | 0 | yes | In-Home Mental Health Services in Louisville, KY |
| /locations/in-person/ | local-html-artifact | 10 | 0 | yes | In-Person Counseling in Louisville, KY |
| /locations/online/ | local-html-artifact | 10 | 0 | yes | Online Therapy in Kentucky |
| /locations/school/ | local-html-artifact | 10 | 0 | yes | School-Based Mental Health Support in Louisville, KY |
| /louisville-ky/ | local-html-artifact | 10 | 1 | yes | Louisville Mental Health Services and Counseling |
| /meet-the-team/ | local-html-artifact | 14 | 1 | yes | Mental Health Providers in Louisville, KY |
| /oldham-county-ky/ | local-html-artifact | 10 | 1 | yes | Mental Health Services in Oldham County, KY |
| /parenting-support/ | local-html-artifact | 14 | 5 | yes | Parenting Support in Louisville, KY |
| /play-therapy/ | local-html-artifact | 14 | 5 | yes | Play Therapy in Louisville, KY |
| /privacy-policy/ | local-html-artifact | 7 | 0 | yes | Privacy Policy for Louisville Mental Health Group |
| /relationship-counseling/ | local-html-artifact | 13 | 4 | yes | Relationship Counseling in Louisville, KY |
| /reviews/ | local-html-artifact | 9 | 0 | yes | Louisville Mental Health Group Reviews \| Client Feedback |
| /services/ | local-html-artifact | 30 | 16 | yes | Mental Health Services in Louisville, KY |
| /specialties/ | local-html-artifact | 32 | 19 | yes | Mental Health Specialties in Louisville, KY |
| /terms-of-use/ | local-html-artifact | 7 | 0 | yes | Terms of Use for Louisville Mental Health Group |
| /therapy-in-your-home/ | local-html-artifact | 14 | 4 | yes | In-Home Therapy in Louisville, KY |
| /trauma-therapy/ | local-html-artifact | 11 | 2 | yes | Trauma Therapy in Louisville, KY |

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
