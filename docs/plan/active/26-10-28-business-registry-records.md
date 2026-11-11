---
title: "Business Registry Records"
created: "26-10-28"
updated: "26-11-10"
status: in-progress
branch: "feat/business-registry"
base: staging
predecessor: "../archive/26-10-28-category-hierarchy.md"
---

# Business Registry Records

## Objective

In the user's words: put the registered businesses in the Capitol, with the range of their renewal, the BIR registration, address, owner, type, images, PDFs, computation and expiration. On 26-10-29 the user added a seeded presentation set with obvious business trends and a colored simulated indicator, a profile-style record ("like LinkedIn, but formal"), a form with a map pin, and a public view that engages residents and owners.

## Scope and Acceptance

Agreed 26-10-29 (D40, D41):

- [x] Staff add and edit records for their assigned towns, with Save Draft or Submit for Review. Admin and Super Admin approve, or return for resubmission with a required reason. Editing an approved record sends it back for review.
- [x] Form: business, business contact (public), owner, DTI SEC or CDA registration, TIN and RDO, business permit, documents; Town, Barangay, street address and a MapLibre pin, checked inside the barangay with PostGIS. No geocoder.
- [x] Permits per year (`business_permits`) and M32 Permit Status in SQL.
- [x] Documents on the private disk with SHA-256, type and size limits from config, served through an authorized, throttled route to Admins and Staff of the town.
- [x] Full record view writes one `record_view_logs` row first (P9). TIN masked.
- [x] BIR Registered badge: our own badge when an approved record has a COR on file.
- [x] Public directory and profile: approved records only (P15), public fields plus business contact, storefront pins only, local filters.
- [x] Presentation seeder: food near schools and online businesses grow every year, one barangay declines (requirement 7), records in every review state, permits and placeholder documents; amber Simulated data tag.
- [x] Polish from the user's screenshots (26-10-29): mm-dd-yyyy dates; profile header aligned with a back link, no category icon, no New or Valid; ruled field grid; Achievements medals (M34 Operating Milestone, M33 streak, BIR); directory filter row with search icon, name-only rows with fixed mark slots, one fixed-height list and map panel, solid barangay outline with name tag; seeder capped at 20 per barangay with unique local-style names and street-style pins; Economy without percentages, accounting parentheses, compact charts, counts-only Business Trends, sticky map and Community Wishes bars.
- [ ] Browser check at desktop and 375px, light and dark, and the Staff to Admin walkthrough.
- [ ] Privacy review across P1 to P15.

## Deferred or Excluded

- Processing permit applications, fees or issuing and renewing permits stays with the city. Computation of fees or taxes needs a decision and a source.
- Pending-change versioning: an edit to an approved record withdraws it from public view until it is approved again.
- Live BIR verification and the owner's government ID number. The BIR badge is not a tax compliance claim.
- Prefilling the wish form from a barangay, and a "Growing Here" panel from M28, proposed for the public view.

## Decisions and Sources

- D40 Business Registry and D41 Contact Number Formats in `decisions.md`; P15 and the field classes in `privacy-rules.md`; M32 in `metrics.md`; "Business Registry" in `design.md`.
- BIR: RR 15-2024 (COR at the place of business), RMC 38-2026 (Registration Seal Badge), RMC 113-2026 (online posting suspended on 26-10-26). Secondary sources are cited in `mvp-scope.md`; a team member still verifies the official texts.
- Document kinds follow the Tuguegarao new permit list in `mvp-scope.md`, section 4. RDO 013 is Tuguegarao.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-10-28" | Deferred plan written | The user asked that the future registry shape this plan's design |
| "26-10-29" | Plan started and approved with the user's answers: public name, badges and business contact; Staff prepare and Admins review; pin first; own BIR badge; trend story; permit, DTI SEC CDA, TIN and RDO fields | User request for the seeded presentation set and the Registered Businesses UI |
| "26-10-29" | Slices 1 to 4 built: schema and seeder, Capitol record and review, form with pin and uploads, public directory and profile | This branch |
| "26-10-29" | Businesses and Economy polish; Declared Sales and Share of Town removed from Business Trends (query, Data, Help); word cloud and wishView removed; M34 added | User screenshot review; plan check-the-images-in-steady-teacup |

## Verification and Handoff

26-10-29: the seeder, directory, profile, form and public test files pass. The full PHP suite passed 324 of 325 before the type count fix; the taxonomy test then passed. PHPStan reports no errors. `npm run check`, `npm run types:check` and `npm run build` pass. The browser check and privacy review are still open.

26-10-29 polish: seeder, public directory, privacy business trends and Economy tests pass (new M34 and seeder cap tests); frontend unit tests, `npm run check`, `npm run types:check` and `npm run build` pass. Browser checked desktop dark and 375px light for directory, profile and Economy, with zero app requests for local filters and Back. Full `composer ci:check` not run, at the user's request.

### Achievements Redesign "26-10-30"

Question: What operating milestones and registration recognition has this business earned? The user rejected the custom medals and asked for package-based visuals. Replace hand-drawn hexagons and ribbons with the installed lucide-react Award, Flame and ShieldCheck icons, registered through lib/icons.ts. Use one consistent responsive set of compact bordered panels inside the existing Achievements card. Keep the same eligibility, values, dates, empty-state omission, profile placement and directory marks. No new dependencies or data changes. Final verification pending.

### Illustrated Achievements Correction "26-10-30"

The user rejected the Lucide panels, requested designed badge assets, and approved the detailed previous illustrated pack direction. This supersedes the preceding Achievements Redesign proposal. The shared component now uses the five selected trophy/medal PNGs from previous illustrated pack's free pack. Milestone artwork maps existing awarded values 5, 10 and 20 to progressively detailed trophy illustrations; this mapping is presentation only and changes no eligibility rules. Streak and BIR use distinct illustrations with the same factual labels and dates. The card is three centered badges without nested panels. No package added. Source, original filenames and license are recorded in public/brand/achievements/LICENSE.txt. Official freebie license permits websites and applications; assets are bundled only as part of the app. Final verification pending.

Illustrated achievements verification "26-10-30": npm run check, npm run types:check and npm run build passed (existing large-chunk advisory only). Browser verified the public profile at 1440px dark and 375px light: all three illustrations loaded from local files at 112px/80px, no horizontal overflow, no console errors. Shared public/Capitol callers and empty-card omission were inspected; Capitol was not separately browser-tested. Design Check found no must-fix issue in the achievement changes. No metric, data, route, authorization or eligibility changes; no new behavior tests needed. Full CI was not repeated after its earlier unreadable tools/coa/.pytest_cache failure. git diff --check passed. Overall registry acceptance and privacy review remain open. No commit or push.

### Kenney Medals Correction "26-10-30"

The user rejected the previous illustrated pack and requested the other researched option, Kenney Medals. Removed all five rejected PNGs and replaced their license with Kenney's original CC0 license. New filenames prevent cached rejected artwork from appearing. Bronze/silver/gold illustrate the existing 5/10/20-year milestones, a gold star medal represents Renewal Streak, and a silver seal represents BIR Registered. No eligibility, metric, data or route changes. Source: https://kenney.nl/assets/medals. Earlier illustrated-pack implementation and verification are superseded. Final verification pending.
### Original Achievements Restored "26-10-30"

The latest user instruction rejects both external asset directions and asks to keep the original design with a small enhancement. Restored the original hexagonal SVG medals, tails, colors, labels and wrapping layout. The inner ring is continuous rather than dashed; the outer outline/ribbon opacity and number spacing are slightly quieter. Both downloaded asset sets and their bundled license files are removed from the project, and the empty achievement asset directory is removed. Earlier icon-panel and asset-pack proposals are superseded. The app's original brand logos remain untouched. No packages, eligibility, metrics, data or route changes. Final checks pending.

Restoration verification "26-10-30": npm run check, npm run types:check and npm run build pass, with only the existing bundle-size advisory. Browser refreshed after build: original medals render on the public profile; zero external achievement images; 375px layout has no horizontal overflow. Design Check found no must-fix finding in the small refinements. git diff --check passed. No new behavior tests or repeated full CI run for this styling-only restoration; the earlier Python-cache permission blocker remains. Overall registry checks remain open.

### Public Businesses Layout "26-11-10"

Which businesses match my filters, and where are they? Keep the filters, list and map together in the first screenful. Use one divided filter card, reduce the gap above the results, and use a 56 px public navbar with no divider beside the appearance switch. Align the shared profile detail grid to the surface padding on both public and Capitol profiles.

Verified in the local browser at 1440 px and 375 px. The directory uses the card surface in both themes, leaves 16 px above the results, and provides 44 px dropdown targets on phones. The public About and Contact grids meet the inside edge of their borders and align their labels to the headings. Town selection works from the keyboard. The Capitol profile uses the same grid primitive and was reviewed in source. The production build and `composer ci:check` passed on PostGIS with 378 PHP tests.
