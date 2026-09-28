# Evidence Policy

The whole pipeline exists to produce case studies a client can challenge without the studio losing face. Every meaningful claim must survive the question "how do you know?". This file defines how.

## The evidence ledger

Before writing any promotional copy, build `.portfolio/EVIDENCE.md`: a table of numbered evidence records.

```markdown
| ID   | Claim | Confidence | Source type | Source location | Notes |
| ---- | ----- | ---------- | ----------- | --------------- | ----- |
| E001 | Discount badges are computed from compare_at_price vs price at render time | confirmed | code | snippets/badge-sale-dynamic.liquid | Also verified live on /collections/all |
```

IDs are sequential (`E001`, `E002`, ...) and never reused. When a record turns out to be wrong, mark it `retracted` in Notes instead of deleting it, so review runs can see what changed.

## Confidence levels

- `confirmed`: verified in at least two independent ways (e.g. code + live product), or directly observed at runtime, or explicitly stated by the user.
- `strong`: clearly present in current code or configuration, but not verified at runtime.
- `tentative`: inferred, documented only in possibly stale docs, or present but possibly unused.

## Source types

`code`, `configuration`, `schema`, `documentation`, `git`, `runtime`, `screenshot`, `user-context`.

`user-context` covers anything the user states explicitly (in the command arguments, in `.portfolio/CONTEXT.md`, or in conversation). It counts as confirmed for facts only the user can know (role, timeline, client agreements), but it does not override what the code or the live product demonstrably shows.

## Priority when sources conflict

```
CURRENT PRODUCT BEHAVIOR
> CURRENT CODE
> CURRENT CONFIGURATION / SCHEMA
> RECENT DOCUMENTATION
> HISTORICAL DOCUMENTATION
> INFERENCE
```

If the docs promise a feature the live product does not show, the live product wins and the discrepancy goes into EVIDENCE.md notes plus `missingInformation` if worth asking the user.

## What must never appear without evidence

Business metrics, user counts, conversion rates, revenue, performance numbers, team size, development time, the client's original problem, and the user's personal role. When one of these is plausible but unverifiable, record it as a question in `missingInformation`, never as a fact in the copy.

## Rules for copy

- Any HIGH-value differentiator must rest on `confirmed` or `strong` evidence. A differentiator backed only by `tentative` evidence gets downgraded or cut.
- A feature that exists in code but is disabled or unused in the live product is described as a capability ("attivabile", "configurabile"), never as a visible element of the site.
- Distinguish explicitly between: implemented, partially implemented, configured, planned, documented-but-unverified.
- Installed dependency ≠ used technology. The stack lists only what shapes the product.
