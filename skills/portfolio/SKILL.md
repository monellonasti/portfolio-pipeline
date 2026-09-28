---
name: portfolio
description: Portfolio Project Creator. Analyze the current repository, verify the live product, curate screenshots and generate a complete evidence-based case study package in .portfolio/. Use whenever the user wants to turn the current project into a portfolio entry or case study, says /portfolio, "case study", "aggiungi al portfolio", "presenta questo progetto", or asks to document a finished client project for their studio website. Arguments after the command are user-supplied context (URL, Role, Year, Language, free notes).
---

# /portfolio — Project Creator

Turn the repository in the current working directory into a portfolio package under `.portfolio/`. Everything is evidence-based: the reader of the final case study is a potential client, and every claim must survive "how do you know?".

Shared resources live in the sibling skill directory `../portfolio-pipeline/` (from this file's base directory), i.e. `~/.claude/skills/portfolio-pipeline/`:

- `../portfolio-pipeline/references/quality-bar.md` — the depth standard; read FIRST and self-check against it before closing
- `../portfolio-pipeline/references/evidence-policy.md` — read before writing EVIDENCE.md
- `../portfolio-pipeline/references/writing-rules.md` — read before writing any copy
- `../portfolio-pipeline/references/screenshot-playbook.md` — read before capturing screenshots
- `../portfolio-pipeline/references/privacy-policy.md` — filtering rules, read early
- `../portfolio-pipeline/references/project-schema.md` + `../portfolio-pipeline/schemas/project.schema.json` — data contract
- `../portfolio-pipeline/templates/` — project.json, pipeline.json, CONTEXT.md, OVERRIDES.md starters
- `../portfolio-pipeline/scripts/capture_screenshots.py`, `../portfolio-pipeline/scripts/validate_project.py`

## Arguments

Everything after `/portfolio` is explicit user context (evidence type `user-context`, confidence confirmed for facts only the user can know). Parse liberally: a URL becomes the live URL to verify; `Key: value` pairs (Role, Year, Language, Client, Timeline...) map to project fields; anything else is free context. Example: `/portfolio https://cliente.it | Role: Full-stack developer | Year: 2026 | Language: Italian`.

## Phase 0 — Setup and prior state

1. If `.portfolio/` already exists, read everything in it first. This run is an update, not a reset: never discard `CONTEXT.md`, `OVERRIDES.md`, or manual edits. Overrides win over automation (unless they would force a false statement). Read `pipeline.json` to see what is already done.
2. If `CONTEXT.md` / `OVERRIDES.md` do not exist, create them from the templates so the user can fill them later.
3. Note obvious scoping traps: a directory can contain more than one project (zips, old versions, an unrelated codebase). Identify the real, current project (prefer the live product and the most recent working tree) and say explicitly what you excluded.

## Phase 1 — Repository analysis

Study structure, CLAUDE.md, README, docs, package manifests, dependencies, configuration, frontend, backend, database/migrations, API, auth, storage, queues/workers/cron, CI/CD, Docker, deployment, tests, assets, design system, and git history when relevant. Ignore noise dirs (`node_modules`, `dist`, `build`, `.next`, `.nuxt`, `vendor`, `coverage`, caches) unless genuinely needed. Do not stop at file names: open and understand the files that matter. Do not modify application code; this is analysis, not review (no refactors, no bug fixes).

Reconstruct, only where verifiable: project name, client, industry, target users, primary/secondary product type, status, live/demo URLs, repository, year, role. Unknown stays unknown.

Architecture: frontend, backend, data, infrastructure, integrations, tooling. An installed dependency is NOT a used technology; the stack lists only what shapes the product.

Product: core workflows, user journeys, roles, admin panels, automations, custom logic, main features, planned-but-unbuilt features, legacy unused features. Tag every feature with an implementation status (implemented / partial / configured / planned / legacy-unused / documented-unverified).

## Phase 2 — Evidence ledger

Follow `../portfolio-pipeline/references/evidence-policy.md`. Build `.portfolio/EVIDENCE.md` with numbered records (E001...) before any copy: claim, confidence (confirmed/strong/tentative), source type, source location, notes. When sources conflict: runtime > code > configuration > recent docs > old docs > inference.

## Phase 3 — Feature classification

Classify every presentable feature HIGH / MEDIUM / LOW. HIGH means real engineering, architecture, product thinking, automation, integration or problem solving (configurators, sync engines, queues, real-time, ERP/CRM integration, multi-tenant, roles/permissions, custom pipelines, licensing mechanisms, operational dashboards...). LOW is normal web development (responsive, modern UI, clean code, contact forms, standard CRUD) and is never a selling point without a concrete reason. Select 3-6 HIGH differentiators; each needs confirmed or strong evidence. Also record what you deliberately do NOT highlight: it makes the rest credible.

## Phase 4 — Runtime verification

Try to verify the real product, in order: user-provided URL, production URL found in the repo, verified demo/staging, locally running app, app safely startable locally. Use browser tools when available; the Playwright script works headless with system Chrome. Compare documentation vs code vs runtime; runtime wins.

Never: purchase, delete, modify production data, submit real forms, change configuration, pay, publish. Adding an item to a cart in a fresh session is acceptable; checkout is not. If auth is needed and no safe session exists, mark that area unverifiable. If a feature exists in code but is disabled live, record it and describe it as a capability, not as a visible element.

## Phase 5 — Screenshots

Follow `../portfolio-pipeline/references/screenshot-playbook.md`. Curate 5-8 page views following the narrative arc plus close-up detail shots when the project has designed components (badges, filters, cards, accordions: the craft shows up close). Prefix filenames with the project slug. Capture with `../portfolio-pipeline/scripts/capture_screenshots.py` and a shot-list JSON; visually verify every image and retake anything cropped mid-component. Save to `.portfolio/screenshots/`; record all shots (captured or planned) in `SCREENSHOTS.md` and `project.json`. Apply privacy rules to every frame.

## Phase 6 — Outputs

Create/update in `.portfolio/`:

```
CASE-STUDY.md     # structure and style from references/writing-rules.md
project.json      # from templates/project.template.json, then filled; validate with scripts/validate_project.py
EVIDENCE.md
SCREENSHOTS.md
REVIEW.md         # placeholder "Not reviewed yet. Run /portfolio-review." if absent; never overwrite an existing review
CONTEXT.md        # template if absent; NEVER overwrite user content
OVERRIDES.md      # template if absent; NEVER overwrite user content
pipeline.json     # update: projectAnalyzed, runtimeVerified, screenshotsReady, caseStudyReady, lastStep, updatedAt
screenshots/
```

Set `generatedAt`/`updatedAt` (ISO 8601), compute `verification.evidenceCount` and `confirmedEvidenceCount`, leave `reviewed`/`approvedForPublish` false (they belong to /portfolio-review). Fill `missingInformation` with the questions worth asking the user (max 7, only things you could not derive yourself: original problem, role, team, metrics, year, constraints).

## Phase 7 — Quality self-check (mandatory)

Before closing, re-read `../portfolio-pipeline/references/quality-bar.md` and answer its self-check questions honestly against what you produced. The bar is the LookAndFeelBetter standard: mechanisms named with file paths, counted numbers, a walk-through Product section, a design section with color roles and accent placements, detail screenshots verified image by image, 15+ evidence records and 15+ verified facts. A package visibly thinner than that standard is not finished: go back to the weak phase and deepen it before reporting. Time spent here is the difference between a README summary and a case study.

## Closing

Summarize for the user: what the project is, the chosen differentiators, screenshot status, the self-check outcome, open questions from `missingInformation`, and suggest running `/portfolio-review` next.
