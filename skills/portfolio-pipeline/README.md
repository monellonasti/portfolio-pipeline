# Portfolio Pipeline

Version: 1.0.0 (single source of truth: `VERSION` file in this directory)

A reusable Claude Code skills pipeline that turns any software project into an evidence-based portfolio case study, reviews it, and publishes it into the portfolio website codebase.

```
CODEBASE + DOCS + LIVE PRODUCT + USER CONTEXT
        -> /portfolio            (create .portfolio/ package)
        -> /portfolio-review     (independent QA, scoring, approval)
        -> /portfolio-publish    (integrate into portfolio site repo)
        -> /portfolio-pipeline   (orchestrate everything with checkpoints)
```

## Installation

Skills live in `~/.claude/skills/`:

```
portfolio/SKILL.md
portfolio-review/SKILL.md
portfolio-publish/SKILL.md
portfolio-pipeline/            <- orchestrator + all shared resources
├── SKILL.md
├── README.md                  <- this file
├── VERSION
├── references/                <- evidence-policy, writing-rules, screenshot-playbook,
│                                 privacy-policy, publishing-policy, project-schema
├── schemas/project.schema.json
├── templates/                 <- project.json, pipeline.json, CONTEXT.md, OVERRIDES.md
└── scripts/                   <- capture_screenshots.py, validate_project.py,
                                  export_portfolio_skills.py
```

Persistent configuration: `~/.claude/portfolio/config.json`.

## Commands

Run from the root of any software project:

```
/portfolio
/portfolio https://example.com
/portfolio https://cliente.it | Role: Full-stack developer | Year: 2026 | Language: Italian
/portfolio-review
/portfolio-publish
/portfolio-pipeline
/portfolio-pipeline https://example.com
```

Everything after the command name is user context: URLs get verified, `Key: value` pairs map onto project fields, free text becomes `user-context` evidence.

## Outputs (`.portfolio/` inside the analyzed project)

```
CASE-STUDY.md      human-readable case study (fixed section structure)
project.json       canonical machine-readable data (schemaVersion 1, validated)
EVIDENCE.md        numbered evidence ledger (E001...) backing every claim
SCREENSHOTS.md     shot list with priorities, purposes, statuses
REVIEW.md          written by /portfolio-review (placeholder until then)
CONTEXT.md         yours: role, brief, metrics, confidentiality notes; never overwritten
OVERRIDES.md       yours: editorial decisions that beat automation; never overwritten
pipeline.json      resumable pipeline state
screenshots/       captured PNGs, filenames prefixed with the project slug
```

## Chrome / browser

Screenshot capture uses Playwright driving the system Chrome (`channel="chrome"`, headless), so no extra browser download is needed; it falls back to bundled Chromium. Requirements: Python 3 + `pip install playwright`. In-app browser tools or the Claude in Chrome extension, when connected, are used for exploration and live verification; PNG production on disk goes through `scripts/capture_screenshots.py` with a shot-list JSON (format documented in the script header).

## Configuration (publish target)

`~/.claude/portfolio/config.json` stores where the portfolio site lives (`portfolioLocalPath`, data/assets directories, route pattern, language, brand, publishMode). Values start as `null`; `/portfolio-publish` fills them by auto-detection or by asking once, then reuses them. Edit the file by hand anytime.

## Recovery

State is on disk, not in the conversation. If a run is interrupted at any point, rerun the same command:

- `/portfolio` re-reads `.portfolio/` and updates instead of resetting (CONTEXT.md and OVERRIDES.md are never touched).
- `/portfolio-review` refreshes the review in place.
- `/portfolio-publish` is idempotent by slug: an existing entry is updated, never duplicated.
- `/portfolio-pipeline` reads `pipeline.json` and continues from `lastStep`.

If the package gets into a confusing state, the safe reset is deleting everything in `.portfolio/` EXCEPT `CONTEXT.md`, `OVERRIDES.md` and `screenshots/`, then rerunning `/portfolio`.

## Export / backup

```
python ~/.claude/skills/portfolio-pipeline/scripts/export_portfolio_skills.py [output.zip]
```

Exports the four skills plus a sanitized config example (machine-local paths stripped, no credentials). Restore by unzipping `skills/` into `~/.claude/skills/`.

## Safety model

- Evidence first: no claim without a ledger entry; runtime beats code beats docs.
- Approval gate: publishing requires `verification.approvedForPublish = true` set by the reviewer.
- Privacy: filtering at creation time and again at publish time; concrete doubt = don't publish.
- Never: destructive actions on analyzed products, production deploys without explicit request, invented information.
