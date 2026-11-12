---
paths:
  - "tools/coa/**"
  - "storage/app/source-review/coa/**"
---

# COA Source Collection

Corrections from the live collection on "26-11-12":

- COA's tree shifts while a year or region loads. Wait for loading to finish, read fresh state and confirm the intended category expanded before selecting its child. Scope selections to observed category IDs; a generic visible Cagayan link can select the city branch instead of municipalities.
- Bind every popup to the observed file ID. Its actual download URL must equal the inventoried official PDF URL before saving. A generic popup link returned an adjacent ZIP once; never count that ZIP as an Executive Summary.
- Rounded KB/MB labels are not exact advertised byte counts. Preserve the label, record a rounded-display check, and compute the saved file's exact byte count and SHA-256 separately.
- Preserve COA's original title and URL. A mislabeled entry becomes an Executive Summary only after inspecting the PDF, with its identifying page evidence recorded.
- Browser saves can replace ñ with n or change punctuation. Use only unique normalized filename matches; never guess among ambiguous candidates. Keep checkpoints per file and validation every 12, including a final partial flush.

# COA Parsing

Corrections from the 26-11-12 accuracy work ([record](../../docs/plan/archive/26-11-12-coa-accuracy-and-differences.md)):

- Every value change in the `composer data:finances:extract` diff is checked against its PDF page before importing. A passing suite is not evidence that amounts are right.
- A finding is attributed to the report (`report` family) only when the printed page proves it. Split rows, damaged text and reader disagreements are `reading`. Never say which side of a contradiction is wrong (for example a heading versus its amounts) without another report proving it.
- Never loosen the hardening tests in `tools/coa/tests/test_evidence.py` to pass a change; change the code. Repairs to text (joining split digits, restoring stale headings) need independent proof: the other reader plus the row's arithmetic, or the neighbouring report.
- Messages are formal and complete: line, years, report and amounts in pesos (`peso()`, `lineLabel()`). One-decimal amounts are not a finding.
- Edit Python with Unicode or regex escapes through the Edit tool or a script file, not shell heredocs.
