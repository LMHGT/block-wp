# WordPress vs Cloudflare Staging Gap Report

Snapshot date: 2026-06-27T19:31:32.165Z

WordPress base URL: http://localhost:8888

Staging snapshot: `data/lmhg/staging-snapshot/routes.json`

This report intentionally compares the current WordPress proof surface against
the verbatim Cloudflare staging snapshot. It is expected to fail until the full
content, asset, theme, metadata, and noindex parity work is implemented.

## Summary

- Comparable staging routes: 54
- Routes with issues: 54
- Issue counts: `{"title mismatch":32,"h1 mismatch":31,"visible text hash mismatch":54,"missing staging X-Robots-Tag noindex":54,"missing staging robots meta noindex":54,"status 404 != staging 200":3}`

## Route Gaps

| Route | WP Status | Classification | Staging H1 | WP H1 | Staging Assets | WP Assets | Issues |
| --- | --- | --- | --- | --- | --- | --- | --- |
| / | 200 | migrate-verbatim | Mental Health Clinic in Louisville, KY |  | 31 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /adolescent-counseling/ | 200 | migrate-verbatim | Teen Therapy in Louisville, KY | Teen Therapy in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /adult-counseling/ | 200 | migrate-verbatim | Adult Counseling in Louisville, KY | Adult Counseling in Louisville, KY | 15 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /anxiety-depression-therapy/ | 200 | migrate-verbatim | Anxiety and Depression Therapy in Louisville, KY | Anxiety and Depression Therapy in Louisville, KY | 24 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /articles/ | 200 | migrate-verbatim | Mental Health Articles from Louisville Mental Health Group | Articles | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /articles/family-therapy-vs-individual-therapy/ | 200 | migrate-verbatim | Family Therapy vs. Individual Therapy \| Which Fits? | Family Therapy Vs Individual Therapy | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /articles/guide-to-individual-therapy/ | 200 | migrate-verbatim | Guide to Individual Therapy \| What to Expect | Guide To Individual Therapy | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/ | 200 | migrate-verbatim | How to Talk to Someone About Therapy | How To Talk To Your Loved Ones About Going To Therapy | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /articles/top-5-signs-its-time-to-seek-therapy/ | 200 | migrate-verbatim | When to Seek Therapy \| 5 Signs Support May Help | Top 5 Signs Its Time To Seek Therapy | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /articles/what-to-expect-when-starting-therapy/ | 200 | migrate-verbatim | What to Expect When Starting Therapy | What to Expect When Starting Therapy | 7 | 0 | title mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /attachment-therapy/ | 200 | migrate-verbatim | Attachment Therapy in Louisville, KY | Attachment Therapy in Louisville, KY | 15 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /bullitt-county-ky/ | 200 | migrate-verbatim | Mental Health Services in Bullitt County, KY | Bullitt County Ky | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /careers/ | 200 | migrate-verbatim | Mental Health Careers in Louisville, KY | Careers | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /case-management/ | 200 | migrate-verbatim | Targeted Case Management in Louisville, KY | Targeted Case Management in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /child-behavioral-intervention/ | 200 | migrate-verbatim | Child Behavioral Therapy in Louisville, KY | Child Behavioral Therapy in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /child-counseling/ | 200 | migrate-verbatim | Child Counseling in Louisville, KY | Child Counseling in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /co-parenting/ | 200 | migrate-verbatim | Co-Parenting Services in Louisville, KY | Co-Parenting Services in Louisville, KY | 21 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /community-based-services/ | 200 | migrate-verbatim | Community-Based Mental Health Services in Louisville, KY | Community-Based Mental Health Services in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /community-support/ | 200 | migrate-verbatim | Community Support Services in Louisville, KY | Community Support Services in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /compliance/ | 404 | requires-verbatim-decision | Mental Health Compliance in Louisville, KY | Page Not Found | 7 | 0 | status 404 != staging 200; title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /contact-us/ | 200 | migrate-verbatim | Contact Louisville Mental Health Group | Contact Us | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /couples-conflict-resolution/ | 200 | migrate-verbatim | Couples Conflict Resolution in Louisville, KY | Couples Conflict Resolution in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /couples-counseling/ | 200 | migrate-verbatim | Couples Counseling in Louisville, KY | Couples Counseling in Louisville, KY | 15 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /court-ordered/ | 200 | migrate-verbatim | Court-Ordered Services in Louisville, KY | Court-Ordered Services in Louisville, KY | 15 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /emdr-therapy/ | 200 | migrate-verbatim | EMDR Therapy in Louisville, KY | Emdr Therapy | 21 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /family-reunification/ | 200 | migrate-verbatim | Family Reunification Services in Louisville, KY | Family Reunification Services in Louisville, KY | 21 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /family-therapy/ | 200 | migrate-verbatim | Family Therapy in Louisville, KY | Family Therapy in Louisville, KY | 15 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /faq/ | 200 | migrate-verbatim | Mental Health Services FAQ in Louisville, KY | Faq | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /faq/about-lmhg/ | 200 | migrate-verbatim | About Louisville Mental Health Group \| Therapy and Support | About Lmhg | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /faq/cost/ | 200 | migrate-verbatim | Therapy Cost in Louisville, KY | Cost | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /faq/our-approach/ | 200 | migrate-verbatim | Louisville Mental Health Group Approach | Our Approach | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /group-therapy/ | 200 | migrate-verbatim | Group Therapy in Louisville, KY | Group Therapy in Louisville, KY | 9 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /individual-counseling/ | 200 | migrate-verbatim | Individual Therapy in Louisville, KY | Individual Therapy in Louisville, KY | 15 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /insurance/ | 200 | migrate-verbatim | Medicaid Mental Health Services in Louisville, KY | Insurance | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /jefferson-county-ky/ | 200 | migrate-verbatim | Mental Health Services in Jefferson County, KY | Jefferson County Ky | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /locations/ | 200 | migrate-verbatim | Mental Health Services Near Louisville, KY | Locations | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /locations/community/ | 200 | migrate-verbatim | Community-Based Mental Health Care in Louisville, KY | Community | 16 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /locations/in-home/ | 200 | migrate-verbatim | In-Home Mental Health Services in Louisville, KY | In Home | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /locations/in-person/ | 200 | migrate-verbatim | In-Person Counseling in Louisville, KY | In Person | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /locations/online/ | 200 | migrate-verbatim | Online Therapy in Kentucky | Online | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /locations/school/ | 200 | migrate-verbatim | School-Based Mental Health Support in Louisville, KY | School | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /louisville-ky/ | 200 | migrate-verbatim | Louisville Mental Health Services and Counseling | Louisville Ky | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /meet-the-team/ | 200 | migrate-verbatim | Mental Health Providers in Louisville, KY | Meet The Team | 10 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /oldham-county-ky/ | 200 | migrate-verbatim | Mental Health Services in Oldham County, KY | Oldham County Ky | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /parenting-support/ | 200 | migrate-verbatim | Parenting Support in Louisville, KY | Parenting Support in Louisville, KY | 21 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /play-therapy/ | 200 | migrate-verbatim | Play Therapy in Louisville, KY | Play Therapy in Louisville, KY | 21 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /privacy-policy/ | 404 | requires-verbatim-decision | Privacy Policy for Louisville Mental Health Group | Page Not Found | 7 | 0 | status 404 != staging 200; title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /relationship-counseling/ | 200 | migrate-verbatim | Relationship Counseling in Louisville, KY | Relationship Counseling in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /reviews/ | 200 | migrate-verbatim | Louisville Mental Health Group Reviews \| Client Feedback | Reviews | 7 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /services/ | 200 | migrate-verbatim | Mental Health Services in Louisville, KY | Services | 31 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /specialties/ | 200 | migrate-verbatim | Mental Health Specialties in Louisville, KY | Specialties | 37 | 0 | title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /terms-of-use/ | 404 | requires-verbatim-decision | Terms of Use for Louisville Mental Health Group | Page Not Found | 7 | 0 | status 404 != staging 200; title mismatch; h1 mismatch; visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /therapy-in-your-home/ | 200 | migrate-verbatim | In-Home Therapy in Louisville, KY | In-Home Therapy in Louisville, KY | 18 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
| /trauma-therapy/ | 200 | migrate-verbatim | Trauma Therapy in Louisville, KY | Trauma Therapy in Louisville, KY | 12 | 0 | visible text hash mismatch; missing staging X-Robots-Tag noindex; missing staging robots meta noindex |
