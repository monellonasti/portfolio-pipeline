# Publishing Policy

`/portfolio-publish` means PUBLISH TO PORTFOLIO CODEBASE, never DEPLOY TO PRODUCTION. Production deploys happen only on an explicit user request in the current conversation.

## Preconditions (hard gates)

1. `.portfolio/` exists in the source project.
2. `project.json` is valid against the schema (`scripts/validate_project.py`).
3. `REVIEW.md` exists and is not the not-reviewed placeholder.
4. `verification.approvedForPublish` is `true`.
5. The portfolio target is configured (`~/.claude/portfolio/config.json`) or discoverable.

If any gate fails, stop and tell the user which one and how to satisfy it (usually: run `/portfolio-review`).

## Discover before touching

Never assume the portfolio site's structure. Before writing anything, analyze the target repository: stack, content model (markdown? JSON? CMS? collections?), data model for projects, routing pattern, how project cards and case study pages are built, how images are stored and optimized, localization, design components, build process.

If the site already has a projects/case-studies model, adapt `project.json` to it. Do not build a parallel second system. If the site has no such model yet, propose the minimal addition that fits its existing conventions and confirm with the user before scaffolding it.

## Publish steps

1. Map `project.json` fields onto the target's content model.
2. Copy screenshots into the target's asset location; optimize (resize/compress/convert) following the site's existing image pipeline.
3. Create or update the project card and the case study page, reusing existing components and styles.
4. Idempotency by slug: if the slug already exists in the target, update that entry in place. Never create duplicates.
5. Run the site's formatter/linter if configured.
6. Run the build. A failing build means the publish is not done; fix or roll back.
7. Run relevant tests if the site has them.
8. When possible, open the built page locally and visually verify card and case study.
9. Update the source project's `project.json` (`publication.published`, `publication.target`, `publication.publishedAt`) and `pipeline.json`.

## Final safety sweep

Immediately before writing into the target repo, re-scan everything being copied for: PII, credentials, tokens, internal data, reserved admin screenshots, sensitive URLs, internal comments, unauthorized names, claims not backed by evidence. Concrete doubt = do not publish that piece; flag it instead.

## Git conduct in the target repo

Work on the current branch unless the repo's conventions or the user say otherwise. Commit only when the user asks. Never force-push. Never touch deploy workflows.
