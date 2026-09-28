---
name: portfolio-pipeline
description: End-to-end orchestrator for the portfolio system. Runs discover, create, verify, screenshot, review, fix, validate and prepare-publish in sequence with checkpoints, resuming from .portfolio/pipeline.json instead of redoing completed work. Use whenever the user says /portfolio-pipeline, wants the full portfolio flow for the current project in one go, or asks to take a project "from repo to portfolio". Arguments are passed through to the creator phase.
---

# /portfolio-pipeline — Orchestrator

Run the whole flow for the project in the current working directory:

```
DISCOVER -> CREATE -> VERIFY -> SCREENSHOT -> REVIEW -> FIX -> VALIDATE -> PREPARE PUBLISH
                                                             (then, if configured and approved)
                                          -> PUBLISH TO PORTFOLIO REPOSITORY -> BUILD -> VISUAL VERIFY
```

No production deploy unless the user explicitly asks. This skill owns sequencing and state; the actual work is defined by the three sibling skills. Execute each phase by invoking the corresponding skill (`portfolio`, `portfolio-review`, `portfolio-publish`) via the Skill tool, passing this command's arguments through to `portfolio`. If skill invocation is unavailable mid-run, read the sibling `SKILL.md` files (`../portfolio/SKILL.md`, etc.) and follow them directly.

## State and resume

State lives in `.portfolio/pipeline.json` (template: `templates/pipeline.template.json`). Before starting, read it: flags already true mean work already done; skip those phases unless the user asked for a redo or the underlying inputs changed (e.g. the repo changed significantly since `updatedAt`). After each phase, update the relevant flags plus `lastStep` and `updatedAt`. This makes reruns cheap and interruptions recoverable: rerunning `/portfolio-pipeline` after a crash continues from `lastStep`.

## Checkpoints (verify before moving on)

- A, PROJECT UNDERSTOOD: product type, core functionality, architecture and main differentiators are known. Else stay in discovery.
- B, EVIDENCE READY: no HIGH differentiator without confirmed/strong evidence in EVIDENCE.md.
- C, VISUALS READY: screenshots captured, or a complete plan exists with statuses (`captured`/`planned`/`unavailable`).
- D, REVIEW PASSED: `verification.approvedForPublish = true` after `/portfolio-review` (including its fix loop; one re-review after fixes is normal).
- E, PORTFOLIO BUILD PASSED: only relevant when publishing; the target repository builds.

If a checkpoint cannot be satisfied because information only the user has is missing, pause, report precisely what is needed (pointing at `missingInformation` and `CONTEXT.md`), and continue with what can proceed.

## Publish decision

After checkpoint D: if the portfolio target is configured (`~/.claude/portfolio/config.json` or auto-detected) AND the package is approved, proceed to `/portfolio-publish` and require checkpoint E plus a local visual verification of the resulting page. Otherwise stop after PREPARE PUBLISH and tell the user what remains (configure target, or resolve review blockers).

## Reporting

At the end, give one compact status report: phases run vs skipped (with why), checkpoint results, review score, publish outcome, and the exact next command if anything is pending. Keep the user able to interrupt at any phase without losing work: state is on disk, not in the conversation.
