"""Export the portfolio pipeline (skills + non-sensitive config) to a portable zip.

Usage:
    python export_portfolio_skills.py [output.zip]

Includes the four skill directories. Includes ~/.claude/portfolio/config.json only
after stripping fields that look sensitive (none are expected, but paths private to
this machine are replaced with null so the archive is transferable).
"""
import json
import sys
import zipfile
from pathlib import Path

SKILLS = ["portfolio", "portfolio-review", "portfolio-publish", "portfolio-pipeline"]
SKILLS_ROOT = Path.home() / ".claude" / "skills"
CONFIG_PATH = Path.home() / ".claude" / "portfolio" / "config.json"
EXCLUDE_SUFFIXES = {".pyc"}
EXCLUDE_DIRS = {"__pycache__", ".git"}


def main():
    out = Path(sys.argv[1]) if len(sys.argv) > 1 else Path.cwd() / "portfolio-pipeline-export.zip"
    count = 0
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as zf:
        for skill in SKILLS:
            root = SKILLS_ROOT / skill
            if not root.exists():
                print(f"warning: missing skill {skill}")
                continue
            for path in root.rglob("*"):
                if path.is_dir():
                    continue
                if path.suffix in EXCLUDE_SUFFIXES or any(part in EXCLUDE_DIRS for part in path.parts):
                    continue
                zf.write(path, f"skills/{skill}/{path.relative_to(root)}")
                count += 1
        if CONFIG_PATH.exists():
            config = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))
            for key in ("portfolioLocalPath",):
                if key in config:
                    config[key] = None
            zf.writestr("portfolio/config.example.json", json.dumps(config, indent=2))
            count += 1
    print(f"exported {count} files -> {out}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
