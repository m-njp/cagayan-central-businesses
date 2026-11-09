---
title: "Kita Showcase Reel"
created: "26-11-05"
updated: "26-11-05"
status: superseded
branch: "feat/finance-report-redesign"
base: staging
predecessor: null
---

# Kita Showcase Reel

## Objective

Superseded on "26-11-05" by [Showcase Motion Rebuild](26-11-05-showcase-motion-rebuild.md). The user rejected the screenshot-led creative treatment and soundtrack despite the technical checks below.

Create the requested one to two minute marketing reel using Kita's existing identity and real application screens.

## Scope and Acceptance

- [x] Landscape 1920 by 1080 video lasting 96 seconds at 60 frames per second.
- [x] Instrumental soundtrack, as selected by the user on "26-11-05".
- [x] Animate the official Kita mark, including its magnifying glass.
- [x] Highlight implemented maps, government finances, business directory, community wishes and Capitol tools.
- [x] Use Geist and Geist Mono, project colors, halftone geography and concise copy without gradients, middots or long dashes.
- [x] Inspect representative frames and validate encoded video duration, frame count, playback and audio.

## Deferred or Excluded

No application feature changes, publishing, commits or modifications to existing uncommitted work. No protected business records, resident text, credentials or single-business sales in the reel. Unbuilt workflows are excluded.

## Decisions and Sources

- "26-11-05": User selected landscape and instrumental audio. Produce an original instrumental score with no third-party music dependency.
- "26-11-05": Reuse official SVG artwork from public/brand, fonts from resources/fonts, and design rules from docs/engineering/design.md. Dots retain equal size and geographic meaning.
- "26-11-05": Working question: What can citizens and Capitol staff understand and do through Kita? Show the official animated identity followed by real product screens.
- "26-11-05": Store video, captures and editable rendering source under ignored storage/app/showcase. This media deliverable does not alter application screens or their local-selection behavior.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-05" | Started reel production and identity review | User request and audio/aspect selections |
| "26-11-05" | Completed the reel and original 100 BPM instrumental | Real application captures with no application code changes |

## Verification and Handoff

- Deliverable: `storage/app/showcase/kita-showcase.mp4`, 38,144,074 bytes. Editable compositor and encoder source remain alongside it in the ignored artifact folder.
- Independent MP4 container inspection: 5,760 H.264 samples, time scale 60,000, sample duration 1,000, exactly 60 fps and 96 seconds. Fast-start metadata precedes media. AAC stereo has 4,500 packets and exactly 96 seconds at 48 kHz.
- Browser playback reports 1920 by 1080 and 96 seconds. Decoded seeks checked at 3, 13, 28, 38, 52, 61, 65, 72, 81, 91 and 95.8 seconds; presented times matched within one to two frames. Corrected the local verification server to support byte-range seeking before accepting these checks.
- Reviewed the eleven decoded stills and their contact sheet. Corrected clipped table and wish-panel text before the final render.
- Decoded soundtrack: two channels, 48 kHz, 96 seconds, peak 0.624 and RMS 0.115. Stereo channels differ and the signal is below clipping.
- Design and privacy review: official logo paths and colors retained; project fonts and CSS tokens used; halftone dots represent geographic areas and the official eye. Reel copy scan found no gradients, middots or long dashes. Public simulated records retain their visible label, curated wishes retain Sample Data, and no protected business fields, resident reasons or individual sales are shown.
- Existing application changes were preserved. Application checks were not rerun for this media-only deliverable. Plan files pass `git diff --check`.
