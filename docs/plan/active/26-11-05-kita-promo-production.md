---
title: "Kita Promo Production"
created: "26-11-05"
updated: "26-11-06"
status: in-review
branch: "feat/finance-report-redesign"
base: staging
predecessor: "../archive/26-11-05-showcase-fast-cut.md"
---

# Kita Promo Production

## Objective

Produce the user's flagship product film and companion cuts from the Kita Promo Video Master Prompt supplied on "26-11-05". The attached brief supersedes earlier treatments: actual live app footage only, real committed geometry, official artwork, exact tokens, and original compositions with interaction sound.

## Scope and Acceptance

- [x] Resolve the section 15 credit placeholders. User requested their removal; omit team/adviser credits and the closing URL. Retain factual source and music attribution. Omit awards and local photography unless supplied with usable rights.
- [x] Resolve editorial direction: a quick showcase with coherent flow covering the whole built product, music and state sounds with captions, no voiceover, ending with Kita and the challenge name only.
- [x] Verify the current showable inventory against the running app. Omit unavailable features and unsupported claims.
- [x] Record fresh wide and phone-width interactions, retaining Simulated data and Sample Data labels. Crop the final record-review shot to its Review card and the profile shot to its birthday controls.
- [x] Produce the 16:9 flagship at 60 fps (172.5 seconds), hero (63.75 seconds), teaser (15 seconds), and separately composed 9:16 cut (41.25 seconds).
- [x] Supply burned-in English captions and SRT files, a poster, thumbnail and six key frames.
- [x] Supply editable source, source and license manifest, on-screen copy and actual decoded media verification under ignored storage/app/kita-promo. Master name: kita-promo-flagship.
- [x] Add and verify quieter stage variants of the flagship and hero, retaining their encoded picture samples.
- [x] Replace the rejected song in all four cuts and both stage variants with the user-selected driving drums and electric bass direction. Deliver the new full-length soundtrack and editable composition with its instrument license.
- [ ] Resolve the strict native 60 fps source-capture requirement. The browser was asked for 60 fps but produced variable-frame-rate VP9 capture. Outputs are independently verified constant 60 fps; actual source packet counts remain disclosed. Do not represent these review exports as fully satisfying native 60 fps capture.

## Deferred or Excluded

Application feature work, committing unrelated changes, deployment and publication are outside this production. Do not reset the shared development database or run setup on the active environment merely to obtain footage. A proposed terminal shot does not justify a destructive reset or a fabricated successful run. No commit or push solely for a staged terminal scene.

## Decisions and Sources

- "26-11-05": Follow the attached master brief and docs/product/promo-video-prompt.md, with the running app as final authority for built features. Preserve official public/brand SVGs and committed boundaries.
- "26-11-05": User explicitly forbids all reuse from prior reels. Record, compose, animate and score this production from scratch. Do not reuse old captures, assets, layouts, music or rendering sources. Official repository brand artwork, real geometry and application source remain the authorized primary product sources.
- "26-11-05": Existing application changes on the current work branch belong to ongoing work and must be preserved.
- "26-11-05": User replied "remove that one. ask more questions." Omit the requested team/adviser and URL credit block. Asked about first review cut, voiceover, emphasis and closing invitation. No names or awards inferred from Git authorship or account details.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-06" | Completed the new original score Groundwork using sampled acoustic drums, fingered electric bass, muted guitar, organ and short brass accents; replaced all interaction foley | User selected driving live drums and bass with sharp rhythmic accents. Composition and arrangement in music-v2/compose.py and per-cut event JSON, with GeneralUser GS license and source credits supplied. |
| "26-11-06" | Replaced audio in all four cuts and regenerated both stage mixes; verified all six after decoding | New social mixes measure about minus 14.01 LUFS and stage mixes about minus 16.01 LUFS, with decoded peaks below 0.57. All six picture payload hashes match the pre-replacement videos and all six complete file hashes changed. |
| "26-11-05" | User rejected the entire soundtrack and selected driving live drums and bass with sharp rhythmic accents | Replace composition, instrumentation, arrangement and interaction sound. No rejected musical phrases or audio reused. Preserve the existing picture edits while replacing audio in all cuts and stage variants. |
| "26-11-05" | Read the complete attached brief, previous reel history, production assets and project workflow | Establish new production scope and avoid rejected treatments |
| "26-11-05" | Ran composer doctor with local service access | All checks pass, including Docker, PostGIS, test database, migrations and summary tables |
| "26-11-05" | Started the existing CI check and production build | Obtain current evidence rather than reuse historical test counts |
| "26-11-05" | Production build passed; current CI has passed frontend checks, TypeScript, frontend tests, Pint and PHPStan so far | Full PHP suite still running; no green CI claim yet |
| "26-11-05" | Removed closing personal credits and URL requirement and asked editorial questions | User's latest instruction |
| "26-11-05" | Confirmed fast full-product showcase, music/SFX/captions and logo/challenge close; prohibited all prior reel reuse | User's replies and annotation correction |
| "26-11-05" | Recorded fresh public, Capitol and 375 px phone footage; authored a new geometry reveal, scene compositions, actual-code sequence, original score and event sounds | No earlier reel footage, artwork, layouts, score or rendering source reused |
| "26-11-05" | Omitted Add a Business because both configured demo accounts were denied access; retained registry and Review card | Brief directs the live app to be final authority and unavailable scenes to be dropped |
| "26-11-05" | Exported all four cuts plus stage variants; corrected a decoded AAC audio peak in the teaser and reverified it | Actual H.264 frame counts, decoded playback and audio checks in the handoff |
| "26-11-05" | Preserved the source-capture limitation and current application CI failure in the verification note | Source tab recordings are VFR; FinanceImportTest reports 12 unsigned imported figures. No native constant 60 fps source or CI-green claim |

## Verification and Handoff

Review exports and editable handoff are under `storage/app/kita-promo/`. The soundtrack was entirely replaced with Groundwork on "26-11-06", using the selected acoustic drums and electric bass direction. Every MP4 was inspected for constant 60 fps and checked after AAC decoding. New social mixes measure about minus 14.01 LUFS and stage variants about minus 16.01 LUFS, with decoded peaks below 0.57. Picture payload hashes match the pre-replacement videos. The complete song is also supplied as `kita-groundwork-flagship.wav`; its composition, arrangement events, instrument bank, license and credits accompany the editable handoff. No rejected musical phrase, arrangement or rendered audio reused.

Production build, frontend checks, TypeScript, frontend tests, Pint and PHPStan passed. Full CI reports 358 of 359 PHP tests passed, with the existing finance import sign-off test failing on 12 figures. Parser tests: 88 passed. No application source, package, Docker or government-data changes, commits or pushes were made for this production. Normal protected-record viewing wrote its expected view log.

This plan remains active for review because the strict native 60 fps capture criterion is unresolved. The requested output files are constant 60 fps; the source tab recordings are variable frame rate. See `capture-audit.json`, `container-audit.json` and `handoff/verification-note.txt` for actual evidence and omissions. Do not archive as fully compliant without resolving or explicitly accepting that difference.
