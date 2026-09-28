# Screenshot Playbook

Screenshots tell the story of the project. They are curated, not harvested: 5-8 page views plus, when the project rewards it, a handful of close-up detail shots. Never capture every page.

## Narrative arc

```
IDENTITY  ->  CORE EXPERIENCE  ->  DIFFERENTIATOR #1  ->  DIFFERENTIATOR #2  ->  OPERATIONS / COMPLEXITY  ->  MOBILE (if relevant)
```

The homepage is NOT automatically the hero. A configurator, a dashboard, a matching result, a 3D scene, an operational workflow, or a data visualization often says more about the work. Pick the single screen that condenses what the project is.

## Detail shots ("artigianalità")

From a distance every well-built site looks "normal"; the craft shows up close. When the project has designed components, add element-level shots: a single product card with its badge hierarchy, an open filter panel, an announcement bar, a metafield-driven accordion stack, a row of cards showing a system working across items. These routinely become the most persuasive images in the case study.

## Viewports

- Desktop: 1440 x 900
- Mobile: 390 x 844 (mobile user agent, touch enabled)
- Capture at device_scale_factor 2 for crisp images.

## Naming

Prefix every file with the project slug so galleries never collide across projects:

```
<slug>-01-homepage-hero-desktop.png
<slug>-02-checkout-mobile.png
```

Number files in narrative order, not navigation order. Save to `.portfolio/screenshots/`.

## Capture technique

Preferred: the bundled script `scripts/capture_screenshots.py` (Playwright driving system Chrome via channel="chrome", headless). It supports full-viewport shots, element shots by selector, clip regions, scroll-to-selector, typing to trigger predictive UI, and clicking to trigger toasts. Write a shot-list JSON and run the script; verify every produced image by reading it before accepting it.

If in-app browser tools or the Chrome extension are connected, they can be used for exploration and verification; for producing PNG files on disk the script is the reliable path.

Common fixes learned the hard way:
- `scrollIntoView` on a text selector can match a header/logo element; scroll to the section's id or class instead.
- After scrolling, wait 1-2s for lazy images before shooting.
- Element screenshots are tight; for grouped content compute a union clip of the relevant bounding boxes with padding.
- Retake any shot that is cut mid-component; a cropped card reads as sloppiness in a portfolio about craft.

## Record keeping

For every shot (captured or only planned), record in `.portfolio/SCREENSHOTS.md` and in `project.json` -> `screenshots[]`:

```
filename, page/screen, route, viewport, priority (HERO | HIGH | MEDIUM), purpose, evidence IDs, status (captured | planned | unavailable)
```

## Exclusions

Never include: passwords, tokens, API keys, customer data, private emails, financial information, PII, DevTools/console, accidental errors, unintended loading skeletons, OS notifications, cookie banners (dismiss first, choosing the privacy-preserving option), temporary or admin-only URLs. If the live product currently hides a feature (e.g. a floating button disabled in settings), do not fake it; note it and shoot what is really there.
