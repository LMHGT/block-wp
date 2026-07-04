# Service Queue Review

Updated: 2026-06-30T13:44:30.501Z

Status: planning/review only. No WordPress writes are approved by this sheet.

## Confirmed / Revised So Far

- `/services/` is the Services hub, not the homepage.
- Homepage is currently identified in source data as `/` with title `Mental Health Clinic in Louisville, KY`; final homepage title remains a later decision.
- `/child-counseling/` should be renamed in visible/core-service labeling to `Child Therapy in Louisville, KY`; slug/path is not changed unless explicitly approved later.
- The Services menu should be nested: each core service may own zero or more specialty pages.
- Other top-level navigation pages are out of scope for this pass.

## Services Nested Menu

- Services (`/services/`)
  - Individual Counseling (`/individual-counseling/`)
    - Adult Counseling (`/adult-counseling/`)
    - Anxiety and Depression Therapy (`/anxiety-depression-therapy/`)
  - Child Therapy (`/child-counseling/`)
    - Adolescent Counseling (`/adolescent-counseling/`)
    - Play Therapy (`/play-therapy/`)
    - Child Behavioral Intervention (`/child-behavioral-intervention/`)
  - Family Therapy (`/family-therapy/`)
    - Attachment Therapy (`/attachment-therapy/`)
    - Parenting Support (`/parenting-support/`)
  - Couples Counseling (`/couples-counseling/`)
    - Couples Conflict Resolution (`/couples-conflict-resolution/`)
    - Relationship Counseling (`/relationship-counseling/`)
  - Court Ordered Services (`/court-ordered/`)
    - Co-Parenting (`/co-parenting/`)
    - Family Reunification (`/family-reunification/`)
  - Community Based Services (`/community-based-services/`)
    - Case Management (`/case-management/`)
    - Community Support (`/community-support/`)
  - Group Therapy (`/group-therapy/`)
  - Trauma Therapy (`/trauma-therapy/`)
    - EMDR Therapy (`/emdr-therapy/`)

## Core Service Rows

| Path | Menu Label | Working Page Title | Specialty Children |
| --- | --- | --- | --- |
| /individual-counseling/ | Individual Counseling | Individual Therapy in Louisville, KY | /adult-counseling/; /anxiety-depression-therapy/ |
| /child-counseling/ | Child Therapy | Child Therapy in Louisville, KY | /adolescent-counseling/; /play-therapy/; /child-behavioral-intervention/ |
| /family-therapy/ | Family Therapy | Family Therapy in Louisville, KY | /attachment-therapy/; /parenting-support/ |
| /couples-counseling/ | Couples Counseling | Couples Counseling in Louisville, KY | /couples-conflict-resolution/; /relationship-counseling/ |
| /court-ordered/ | Court Ordered Services | Court-Ordered Services in Louisville, KY | /co-parenting/; /family-reunification/ |
| /community-based-services/ | Community Based Services | Community-Based Mental Health Services in Louisville, KY | /case-management/; /community-support/ |
| /group-therapy/ | Group Therapy | Group Therapy in Louisville, KY |  |
| /trauma-therapy/ | Trauma Therapy | Trauma Therapy in Louisville, KY | /emdr-therapy/ |

## Default Read

- Metadata: clean except Child Therapy title revision.
- Page type/schema: candidate-mostly-clean for service rows; schema remains separate from navigation structure.
- Relationships: user-confirmed for the Services menu only.
- Design/Gutenberg implementation: requires user design-transplant decision.

## Gutenberg Basics Starting Pattern

Broad service pages should start as fresh Gutenberg structures using core blocks: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, and CTA. Specialty children should keep their specialty-page template while being linked under the relevant core service in the Services menu. No Astro/Starwind code, CSS, template files, visual assets, or old block markup should be imported.
