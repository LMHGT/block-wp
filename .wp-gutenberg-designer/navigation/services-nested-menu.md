# Services Nested Menu Plan

Generated: 2026-06-30T13:44:30.501Z

Status: planning only. No WordPress navigation menu, page parent, template, metadata, or visible surface was changed.

## Scope

- This covers the nested Services menu only.
- Other top-level pages will be handled later.
- Menu labels are allowed to be shorter than full page titles.
- Child Counseling is carried forward as Child Therapy for visible/core-service labeling, while preserving `/child-counseling/` until a routing change is explicitly approved.

## Menu Tree

- Services (`/services/`)
  - Individual Counseling (/individual-counseling/)
    - Adult Counseling (/adult-counseling/)
    - Anxiety and Depression Therapy (/anxiety-depression-therapy/)
  - Child Therapy (/child-counseling/)
    - Adolescent Counseling (/adolescent-counseling/)
    - Play Therapy (/play-therapy/)
    - Child Behavioral Intervention (/child-behavioral-intervention/)
  - Family Therapy (/family-therapy/)
    - Attachment Therapy (/attachment-therapy/)
    - Parenting Support (/parenting-support/)
  - Couples Counseling (/couples-counseling/)
    - Couples Conflict Resolution (/couples-conflict-resolution/)
    - Relationship Counseling (/relationship-counseling/)
  - Court Ordered Services (/court-ordered/)
    - Co-Parenting (/co-parenting/)
    - Family Reunification (/family-reunification/)
  - Community Based Services (/community-based-services/)
    - Case Management (/case-management/)
    - Community Support (/community-support/)
  - Group Therapy (/group-therapy/)
  - Trauma Therapy (/trauma-therapy/)
    - EMDR Therapy (/emdr-therapy/)

## Implementation Notes

- Implement later with a Gutenberg Navigation block in `parts/header.html`.
- Do not import Astro navigation code, Starwind markup, legacy CSS, or old block markup.
- Core service menu items use `templates/service-page.html`; specialty child pages use `templates/specialty-page.html`.
- `/services/` remains the Services hub and uses `templates/services-hub.html`.
