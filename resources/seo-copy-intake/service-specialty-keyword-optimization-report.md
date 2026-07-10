# Services And Specialties Keyword Optimization Report

Status: complete
Baseline reviewed: `0ed158e` (`docs: record completed live copy baseline`)
Keyword map: `bf6112b` (`Add services and specialties keyword map`)
Rewrite date: 2026-07-10
Scope: all 23 Services and Specialties pages on the `resources` branch

## What Changed

- Reviewed the latest reusable copy drafts against the approved primary and secondary keyword map.
- Added full drafts for the `services` and `specialties` hubs.
- Rewrote every detail-page opening so the main intent is plain to a potential client.
- Put each primary keyword and the selected secondary keyword in visitor-facing copy at least once.
- Kept local phrases natural. The copy does not repeat `Louisville, KY` in each section.
- Kept each page between 475 and 750 visitor-facing words.
- Kept each page at Flesch-Kincaid grade 6.5 or lower with the repo verifier.
- Kept service limits, payment notes, privacy rules, court limits, and team roles from the owner-approved intake.
- Used a calm next step. Each page lets a reader use the form or call without a hard sales push.

## EMDR Source Check

The EMDR draft now explains the back-and-forth movement or sound used while a person holds a hard memory in mind. It also notes that the World Health Organization lists EMDR as a treatment that may be considered for adults with PTSD. The page does not promise a cure or say EMDR fits each person.

Sources:

- U.S. Department of Veterans Affairs, National Center for PTSD: [Eye Movement Desensitization and Reprocessing (EMDR) for PTSD](https://www.ptsd.va.gov/understand_tx/emdr.asp)
- World Health Organization: [Post-traumatic stress disorder (PTSD): psychological interventions for adults](https://www.who.int/teams/mental-health-and-substance-use/treatment-care/mental-health-gap-action-programme/evidence-centre/conditions-related-to-stress/posttraumatic-stress-disorder-%28ptsd%29--psychological-interventions---adults)

## Verification

Run from the resources worktree:

```bash
node resources/seo-copy-intake/scripts/verify-service-specialty-copy.mjs
```

Expected final line:

```text
Verified 23 Services and Specialties drafts.
```

This work changes reusable copy on `resources`. It does not promote the drafts into deployable WordPress files or the live site.
