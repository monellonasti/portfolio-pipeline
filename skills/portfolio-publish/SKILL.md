---
name: portfolio-publish
description: Publish an approved portfolio package into the user's portfolio website codebase (card + case study page + optimized screenshots), adapting to the site's existing content model. Use whenever the user says /portfolio-publish, asks to add/publish a project to their portfolio site, or wants the case study integrated into the website repository. Publishes to the codebase only, never deploys to production.
---

# /portfolio-publish — Publisher

Integrate an approved `.portfolio/` package into the portfolio website repository. "Publish" means PUBLISH TO PORTFOLIO CODEBASE; production deploy happens only if the user explicitly asks in this conversation.

Read `../portfolio-pipeline/references/publishing-policy.md` and `privacy-policy.md` before acting. Validate with `../portfolio-pipeline/scripts/validate_project.py`.

## Gates (check in order, stop at the first failure)

1. `.portfolio/` exists in the current project.
2. `project.json` valid against the schema.
3. `REVIEW.md` exists and is a real review, not the placeholder.
4. `verification.approvedForPublish` is `true`.
5. Portfolio target known (config or discovery below).

On failure, tell the user exactly which gate failed and the command that fixes it (usually `/portfolio` or `/portfolio-review`).

## Target configuration

Persistent config: `~/.claude/portfolio/config.json`:

```json
{
  "portfolioRepository": null,
  "portfolioLocalPath": null,
  "projectDataDirectory": null,
  "projectAssetsDirectory": null,
  "projectRoutePattern": null,
  "defaultLanguage": "it",
  "brandName": null,
  "publishMode": "repository"
}
```

If values are null, try to detect the portfolio site automatically (a local repo whose content matches a personal/studio portfolio). If detection fails, ask the user only for the indispensable values (typically the local path), write them to the config, and reuse them in every future session. Never invent values.

## Discovery before writing

Analyze the target repo first: stack, content model, data model for projects, routes, how cards and case study pages are built, asset/image pipeline, localization, components, build process. If a projects/case-studies model exists, map `project.json` onto it; do not build a parallel system. If none exists, propose the minimal addition consistent with the site's conventions and get the user's ok before scaffolding.

## Publish

Follow the steps in `publishing-policy.md`: map data, copy and optimize screenshots, create/update card and case study page reusing existing components, idempotent by slug (existing slug = update in place, never duplicate), run formatter/linter, run the build (a failing build means not published), run relevant tests, verify the page locally when possible (browser tools or local server).

Immediately before writing anything into the target: final privacy sweep (PII, credentials, internal data, reserved screenshots, sensitive URLs, unauthorized names, unverified claims). Concrete doubt = leave that piece out and flag it.

## Bookkeeping

On success update the source project's `project.json` (`publication.published = true`, `target`, `publishedAt`) and `pipeline.json` (`publishedToRepository`, `portfolioBuildPassed`, `lastStep`, `updatedAt`). Report to the user: what was created/updated in the target repo, build result, how to preview, and that no production deploy was performed.
