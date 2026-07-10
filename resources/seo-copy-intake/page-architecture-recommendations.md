# Service And Specialty Page Architecture Recommendations

Status: Recommendation only. No page was added, removed, redirected, or changed on deployable `main`.

Evidence used:

- Direct owner answers stored in `brand-and-page-copy-intake.json`.
- Current WordPress page inventory and parent relationships.
- DataForSEO Google SERPs checked on 2026-07-09 and 2026-07-10.
- Google Search Console was attempted after DataForSEO, but the connector is not authenticated in this session. Traffic and query overlap must be checked before any redirect or deletion.

## Recommended Decisions

| Priority | Current page | Recommendation | Reason | Confidence |
| --- | --- | --- | --- | --- |
| 1 | `/relationship-counseling/` | Merge into `/couples-counseling/` unless LMHG confirms a separate audience or service. Preserve useful copy and use a 301 redirect only after GSC and backlink checks. | Relationship counseling, couples counseling, couples therapy, and marriage counseling share nearly the same Louisville search intent. The current draft can be a strong section on the parent page. | Medium-high |
| 2 | `/couples-conflict-resolution/` | Replace the couples-only framing with `Conflict Resolution Counseling`. Consider `/conflict-resolution-counseling/` and reparenting under Family Therapy, with links from Couples Counseling. | The owner already confirmed that this service can include parents and children, siblings, parents and grandparents, and blended families. Broader DataForSEO results support family and counseling intent; the couples-only phrase leans toward mediation. | High |
| 3 | `/therapy-in-your-home/` and `/locations/in-home/` | Choose one primary in-home page. The current structure favors keeping `/locations/in-home/` as the care-setting page and folding the therapy-specific copy into it. Redirect the other URL only after traffic and backlink checks. | Both pages answer closely related in-home mental health intent. The plugin already removes the old In-Home specialty classification, which points toward a care-setting rather than specialty model. | Medium |
| 4 | `/attachment-therapy/` | If the service is only parent-child work, rename it `Parent-Child Attachment Therapy` and keep it under Family Therapy. Add an adult attachment page only if LMHG truly offers a separate adult service with provider depth. | Current LMHG copy is parent-child focused, but the Louisville SERP for attachment therapy leans toward adult attachment patterns and romantic relationships. The present title sets the wrong expectation for many searchers. | High after scope confirmation |

## Keep And Deepen

Keep the other service and specialty pages as separate pages for now. They have a clear parent-child relationship, audience, service boundary, or search intent. This includes:

- Individual Counseling, Adult Counseling, and Anxiety and Depression Therapy;
- Child Therapy, Teen Therapy, Child Behavioral Therapy, and Play Therapy;
- Family Therapy and Parenting Support;
- Court-Ordered Services, Co-Parenting Services, and Family Reunification Services;
- Community-Based Services, Case Management, and Community Support;
- Trauma Therapy and EMDR Therapy; and
- Group Therapy.

Case Management should not be split into a second Targeted Case Management page. LMHG already ranks for the main Case Management phrase, and a second thin page would create avoidable overlap.

## Conditional Addition

Do not add a new service page solely for SEO at this time. The only reasonable conditional addition is an adult attachment therapy page, and only if LMHG confirms that it is a real, distinct service with enough provider expertise and copy to support it.

## Required Checks Before A Merge Or Deletion

1. Restore Google Search Console access and compare query-page pairs for at least 90 days.
2. Check backlinks and referring pages for every URL being considered for a redirect.
3. Choose the stronger destination based on intent, clicks, impressions, links, and the real service model.
4. Move useful copy and internal links before adding a 301 redirect.
5. Confirm the old URL leaves the sitemap and that the destination is indexable and canonical.
