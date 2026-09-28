"""Generic portfolio screenshot capturer (Playwright + system Chrome, headless).

Usage:
    python capture_screenshots.py <shotlist.json> [--out <dir>]

Shot list format (JSON):
{
  "baseUrl": "https://example.com",
  "outputDir": ".portfolio/screenshots",        // optional, --out overrides
  "slugPrefix": "myproject",                     // prepended to filenames
  "shots": [
    {
      "name": "01-homepage-hero-desktop",        // filename without prefix/ext
      "url": "/",                                // absolute or relative to baseUrl
      "viewport": "desktop",                     // "desktop" (1440x900) | "mobile" (390x844)
      "waitMs": 2000,                            // extra settle time (default 1800)
      "scrollTo": "#programmes",                 // optional: scroll element to center
      "scrollToTop": "#partner",                 // optional: align element top to viewport top
      "element": ".product-card",                // optional: element screenshot instead of viewport
      "elementIndex": 0,                         // nth match for "element" (default 0)
      "hasText": "Aloe",                         // optional: filter "element" matches by text
      "unionClip": [".groups", ".benefits"],     // optional: clip = union of these elements' boxes
      "clipTop": 160,                            // optional: clip = top strip of given height
      "openDetails": ".filters details",         // optional: set open=true on matching <details>
      "type": {"selector": "input.search", "text": "formula"},   // optional: type to trigger UI
      "click": ".quick-add button[type=submit]", // optional: click before shooting
      "waitForSelector": ".toast.is-visible"     // optional: wait for element after actions
    }
  ]
}

Every unknown key is ignored, so shot lists stay forward-compatible.
"""
import argparse
import json
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

DESKTOP = {"viewport": {"width": 1440, "height": 900}, "device_scale_factor": 2}
MOBILE = {
    "viewport": {"width": 390, "height": 844},
    "device_scale_factor": 2,
    "is_mobile": True,
    "has_touch": True,
    "user_agent": (
        "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) "
        "AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1"
    ),
}


def settle(page, ms):
    page.wait_for_load_state("load")
    try:
        page.wait_for_load_state("networkidle", timeout=8000)
    except Exception:
        pass
    dismiss_cookie_banner(page)
    page.wait_for_timeout(ms)


def dismiss_cookie_banner(page):
    for sel in (
        "#shopify-pc__banner button[id*='decline']",
        "button:has-text('Decline all')",
        "button:has-text('Reject all')",
        "button:has-text('Rifiuta')",
    ):
        try:
            el = page.locator(sel).first
            if el.is_visible(timeout=600):
                el.click()
                page.wait_for_timeout(400)
                return
        except Exception:
            pass


def union_clip(page, selectors, viewport_width):
    rects = page.evaluate(
        """(sels) => sels.map(s => {
            const el = document.querySelector(s);
            if (!el) return null;
            const r = el.getBoundingClientRect();
            return {x: r.x, y: r.y, width: r.width, height: r.height};
        }).filter(Boolean)""",
        selectors,
    )
    if not rects:
        return None
    x1 = min(r["x"] for r in rects)
    y1 = min(r["y"] for r in rects)
    x2 = max(r["x"] + r["width"] for r in rects)
    y2 = max(r["y"] + r["height"] for r in rects)
    pad = 12
    return {
        "x": max(0, x1 - pad),
        "y": max(0, y1 - pad),
        "width": min(viewport_width, x2 - x1 + 2 * pad),
        "height": y2 - y1 + 2 * pad,
    }


def run_shot(page, shot, out_dir, prefix, viewport_width):
    name = shot["name"]
    filename = f"{prefix}-{name}.png" if prefix else f"{name}.png"
    path = out_dir / filename

    if shot.get("openDetails"):
        page.evaluate(
            "(sel) => document.querySelectorAll(sel).forEach(d => d.open = true)",
            shot["openDetails"],
        )
        page.wait_for_timeout(600)

    if shot.get("scrollTo"):
        page.eval_on_selector(
            shot["scrollTo"], "el => el.scrollIntoView({block: 'center', behavior: 'instant'})"
        )
        page.wait_for_timeout(1200)
    if shot.get("scrollToTop"):
        page.eval_on_selector(
            shot["scrollToTop"],
            "el => { const y = el.getBoundingClientRect().top + window.scrollY; window.scrollTo(0, Math.max(0, y - 10)); }",
        )
        page.wait_for_timeout(1200)

    if shot.get("type"):
        target = page.locator(shot["type"]["selector"]).first
        target.click()
        target.type(shot["type"]["text"], delay=80)
        page.wait_for_timeout(800)

    if shot.get("click"):
        page.locator(shot["click"]).first.click()

    if shot.get("waitForSelector"):
        page.wait_for_selector(shot["waitForSelector"], timeout=10000)
        page.wait_for_timeout(600)

    if shot.get("element"):
        loc = page.locator(shot["element"])
        if shot.get("hasText"):
            loc = page.locator(shot["element"], has_text=shot["hasText"])
        el = loc.nth(int(shot.get("elementIndex", 0)))
        el.scroll_into_view_if_needed()
        page.wait_for_timeout(800)
        el.screenshot(path=str(path))
    elif shot.get("unionClip"):
        clip = union_clip(page, shot["unionClip"], viewport_width)
        if clip is None:
            print(f"SKIP {filename}: unionClip selectors not found")
            return None
        page.screenshot(path=str(path), clip=clip)
    elif shot.get("clipTop"):
        page.screenshot(
            path=str(path),
            clip={"x": 0, "y": 0, "width": viewport_width, "height": int(shot["clipTop"])},
        )
    else:
        page.screenshot(path=str(path))

    print(f"captured {filename}")
    return filename


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("shotlist")
    parser.add_argument("--out", default=None)
    args = parser.parse_args()

    spec = json.loads(Path(args.shotlist).read_text(encoding="utf-8"))
    base = spec.get("baseUrl", "").rstrip("/")
    prefix = spec.get("slugPrefix", "")
    out_dir = Path(args.out or spec.get("outputDir", ".portfolio/screenshots"))
    out_dir.mkdir(parents=True, exist_ok=True)

    failures = []
    with sync_playwright() as p:
        try:
            browser = p.chromium.launch(channel="chrome", headless=True)
        except Exception:
            browser = p.chromium.launch(headless=True)

        contexts = {}
        try:
            for shot in spec["shots"]:
                vp = shot.get("viewport", "desktop")
                if vp not in contexts:
                    contexts[vp] = browser.new_context(**(MOBILE if vp == "mobile" else DESKTOP))
                page = contexts[vp].new_page()
                url = shot.get("url", "/")
                if url.startswith("/"):
                    url = base + url
                try:
                    page.goto(url, wait_until="domcontentloaded")
                    settle(page, int(shot.get("waitMs", 1800)))
                    run_shot(
                        page, shot, out_dir, prefix,
                        390 if vp == "mobile" else 1440,
                    )
                except Exception as exc:
                    failures.append((shot["name"], str(exc)))
                    print(f"FAILED {shot['name']}: {exc}")
                finally:
                    page.close()
        finally:
            for ctx in contexts.values():
                ctx.close()
            browser.close()

    if failures:
        print(f"\n{len(failures)} shot(s) failed")
        return 1
    print("\nall shots captured")
    return 0


if __name__ == "__main__":
    sys.exit(main())
