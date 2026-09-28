"""Validate a .portfolio/project.json against the pipeline schema.

Usage:
    python validate_project.py <path-to-project.json>

Uses the `jsonschema` package when available; otherwise falls back to a
built-in structural check covering the invariants publishing depends on.
Exit code 0 = valid, 1 = invalid, 2 = usage/IO error.
"""
import json
import re
import sys
from pathlib import Path

SCHEMA_PATH = Path(__file__).resolve().parent.parent / "schemas" / "project.schema.json"

REQUIRED_TOP = [
    "schemaVersion", "language", "project", "summary", "stack",
    "features", "differentiators", "screenshots", "verification", "publication",
]
SLUG_RE = re.compile(r"^[a-z0-9]+(-[a-z0-9]+)*$")
EVIDENCE_RE = re.compile(r"^E[0-9]{3,}$")
VALUE_ENUM = {"HIGH", "MEDIUM", "LOW"}
IMPL_ENUM = {"implemented", "partial", "configured", "planned", "legacy-unused", "documented-unverified"}
PRIORITY_ENUM = {"HERO", "HIGH", "MEDIUM"}
SHOT_STATUS_ENUM = {"captured", "planned", "unavailable"}
STATUS_ENUM = {"live", "demo", "development", "archived", "unknown"}


def fallback_validate(data):
    errors = []
    for key in REQUIRED_TOP:
        if key not in data:
            errors.append(f"missing top-level key: {key}")
    if errors:
        return errors

    if data["schemaVersion"] != 1:
        errors.append(f"schemaVersion must be 1, got {data['schemaVersion']!r}")

    project = data["project"]
    slug = project.get("slug")
    if slug is not None and not SLUG_RE.match(str(slug)):
        errors.append(f"invalid slug: {slug!r}")
    if project.get("status") not in STATUS_ENUM:
        errors.append(f"invalid project.status: {project.get('status')!r}")

    for i, feat in enumerate(data["features"]):
        if feat.get("value") not in VALUE_ENUM:
            errors.append(f"features[{i}].value invalid: {feat.get('value')!r}")
        if feat.get("implementationStatus") not in IMPL_ENUM:
            errors.append(f"features[{i}].implementationStatus invalid: {feat.get('implementationStatus')!r}")
        for eid in feat.get("evidence", []):
            if not EVIDENCE_RE.match(str(eid)):
                errors.append(f"features[{i}] bad evidence id: {eid!r}")

    for i, shot in enumerate(data["screenshots"]):
        if not shot.get("filename"):
            errors.append(f"screenshots[{i}] missing filename")
        if shot.get("priority") not in PRIORITY_ENUM:
            errors.append(f"screenshots[{i}].priority invalid: {shot.get('priority')!r}")
        if shot.get("status") not in SHOT_STATUS_ENUM:
            errors.append(f"screenshots[{i}].status invalid: {shot.get('status')!r}")

    ver = data["verification"]
    for flag in ("runtimeVerified", "reviewed", "approvedForPublish"):
        if not isinstance(ver.get(flag), bool):
            errors.append(f"verification.{flag} must be boolean")

    if not isinstance(data["publication"].get("published"), bool):
        errors.append("publication.published must be boolean")

    heroes = [s for s in data["screenshots"] if s.get("priority") == "HERO"]
    if len(heroes) > 1:
        errors.append(f"expected at most one HERO screenshot, found {len(heroes)}")

    return errors


def main():
    if len(sys.argv) != 2:
        print(__doc__)
        return 2
    path = Path(sys.argv[1])
    if not path.exists():
        print(f"file not found: {path}")
        return 2
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc:
        print(f"INVALID JSON: {exc}")
        return 1

    try:
        import jsonschema  # type: ignore

        schema = json.loads(SCHEMA_PATH.read_text(encoding="utf-8"))
        validator = jsonschema.Draft202012Validator(schema)
        errors = [f"{'/'.join(map(str, e.path)) or '<root>'}: {e.message}" for e in validator.iter_errors(data)]
        mode = "jsonschema"
    except ImportError:
        errors = fallback_validate(data)
        mode = "builtin"

    if errors:
        print(f"INVALID ({mode}, {len(errors)} error(s)):")
        for err in errors:
            print(f"  - {err}")
        return 1
    print(f"VALID ({mode}): {path}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
