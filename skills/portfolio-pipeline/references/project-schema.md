# Project Data Contract

`project.json` is the canonical machine-readable representation of a portfolio project. Markdown files are for humans; publishing reads from the JSON.

- Schema file: `../schemas/project.schema.json` (JSON Schema 2020-12), `schemaVersion: 1`.
- Validate with `../scripts/validate_project.py <path-to-project.json>` (uses `jsonschema` if installed, otherwise a built-in structural check).
- Unknown values stay `null` (or empty arrays). Never invent a value to fill a field.
- Every feature, differentiator, engineering highlight and screenshot carries `evidence: ["E00x", ...]` pointing into EVIDENCE.md.
- `verification` is owned by `/portfolio-review`; `/portfolio` initializes it with `reviewed: false, approvedForPublish: false`.
- `publication` is owned by `/portfolio-publish`.
- Backward compatibility: additive changes only within schemaVersion 1. A breaking change bumps schemaVersion and must keep the validator able to read version 1 files.

Sibling state file `.portfolio/pipeline.json` (template in `../templates/pipeline.template.json`) tracks pipeline progress so reruns skip completed work. Skills update their own flags and `lastStep`/`updatedAt` after each phase.
