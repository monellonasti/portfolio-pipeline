# Privacy Policy (Pipeline)

A portfolio entry is public marketing. Anything that enters `.portfolio/` may eventually reach the public site, so filtering happens at creation time, not only at publish time.

## Never include, anywhere in the package

- Credentials of any kind: passwords, API keys, tokens, secrets, connection strings, `.env` values.
- Customer or end-user data: names, emails, phone numbers, addresses, orders, payments, health data, any PII.
- Financial information of the client or the studio.
- Internal URLs that grant access (admin panels, signed URLs, preview tokens, staging with basic-auth strings).
- Internal comments, TODOs or code excerpts that reveal unfixed vulnerabilities.
- Client names or metrics the user has flagged as confidential in CONTEXT.md or OVERRIDES.md.

## Screenshots

Review every image before accepting it. If a real name, email, order, or internal tool sneaks into a frame, retake or crop. Demo data is preferable to real data; if only real data exists, pick records that are clearly non-sensitive or ask the user.

## Repository hygiene

The analysis may read config files that contain secrets; that is fine for understanding, but no secret value is ever copied into EVIDENCE.md, CASE-STUDY.md, project.json, or any other artifact. Reference the file path, not the value.

## When in doubt

If there is a concrete doubt about whether something is publishable, do not publish it. Record the question in `missingInformation` and tell the user. An entry can always be enriched later; a leak cannot be unpublished.
