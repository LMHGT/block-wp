# Service And Specialty Page Architecture Recommendations

Status: Owner decisions recorded on 2026-07-10. Deployable implementation is tracked separately from this reusable copy record.

Evidence used:

- Direct owner answers stored in `brand-and-page-copy-intake.json`.
- Current WordPress page inventory and parent relationships.
- DataForSEO Google SERPs checked on 2026-07-09 and 2026-07-10.
- Google Search Console was attempted after DataForSEO, but the connector is not authenticated in this session. Traffic and query overlap must be checked before any redirect or deletion.

## Recommended Decisions

| Priority | Current page | Recommendation | Reason | Confidence |
| --- | --- | --- | --- | --- |
| 1 | `/relationship-counseling/` | **Approved.** Merge useful copy into Couples Counseling and redirect the former page to `/couples-counseling/`. | The owner confirmed that relationship counseling and couples counseling are the same LMHG service. | High |
| 2 | `/couples-conflict-resolution/` | **Approved.** Keep `/conflict-resolution-counseling/` under Family Therapy for non-couple family relationships, and redirect the old couples-focused URL to Couples Counseling. | The final owner decision separates romantic-couple work from conflict among other family members. | High |
| 3 | `/therapy-in-your-home/` and `/locations/in-home/` | **Approved.** Keep `/locations/in-home/`, fold the therapy copy into it, and redirect `/therapy-in-your-home/`. | Both pages answer the same in-home intent. The location page is the stronger care-setting home and must remain under Locations. | High |
| 4 | `/attachment-therapy/` | **Approved.** Keep the existing URL, rename the page `Parent-Child Attachment Therapy`, limit it to parent-child work, and keep it under Family Therapy. | The narrower title sets the right service expectation without creating an unnecessary URL change. Do not add an adult attachment page now. | High |

## Keep And Deepen

Keep the other service and specialty pages as separate pages for now. They have a clear parent-child relationship, audience, service boundary, or search intent. This includes:

- Individual Counseling, Adult Counseling, and Anxiety and Depression Therapy;
- Child Therapy, Teen Therapy, Child Behavioral Therapy, Play Therapy, and Parenting Support;
- Family Therapy, Parent-Child Attachment Therapy, and Conflict Resolution Counseling;
- Court-Ordered Services, Co-Parenting Services, and Family Reunification Services;
- Community-Based Services, Case Management, and Community Support;
- Trauma Therapy and EMDR Therapy; and
- Group Therapy.

Case Management should not be split into a second Targeted Case Management page. LMHG already ranks for the main Case Management phrase, and a second thin page would create avoidable overlap.

## No New Pages

Do not add a new service page solely for SEO at this time. Do not add an adult attachment page or a separate Targeted Case Management page.

## Required Implementation Checks

1. Move useful copy and all internal links before adding each 301 redirect.
2. Update page data, menus, service relationships, FAQ relationships, media mappings, schema inputs, hidden inventories, and redirect records.
3. Confirm the old URL leaves the sitemap and that the destination is indexable and canonical.
4. Verify the old route returns one 301 to the exact destination and keeps the query string.
5. Verify the retained route returns 200 and no duplicate published page remains.
6. Route the legacy couples-focused `/couples-conflict-resolution/` URL to Couples Counseling because Conflict Resolution Counseling now serves non-couple family relationships.
