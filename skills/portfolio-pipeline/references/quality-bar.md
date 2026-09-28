# Quality Bar

The reference standard is the LookAndFeelBetter case study (August 2026): a Shopify theme project whose final package took several feedback rounds to reach. This file encodes that end state so every future run starts there instead of rediscovering it. A package that reads like a good README summary has failed; the target is a document a potential client reads and thinks "these people understand what they built, down to the pixel".

## Depth minimums (mid-size project; scale up for bigger ones, justify explicitly if smaller)

Analysis:
- Open and actually read the significant source files, not just names and READMEs. Cite evidence as `path:line` or `path` + mechanism. If you cannot describe HOW a differentiator works (the actual mechanism: which file, which data, which comparison), you have not analyzed enough to claim it.
- Count what can be counted: files, sections, components, lines of custom code, products, price points, accordion items, filter groups. Concrete numbers are the register of the whole document.
- 15+ evidence records; every HIGH differentiator backed by at least two source types, including runtime when the product is reachable.

CASE-STUDY.md:
- Full section structure from writing-rules.md, no skipped sections.
- "The Product" is a walk-through of what a real user sees and does, screen by screen, not a feature list.
- "Design & Visual Identity" is mandatory whenever the studio authored the design: name the exact palette values, give every color a role ("il verde guida l'azione, il crema fa respirare, l'oro segnala il valore"), list WHERE each accent appears (which badges, which eyebrows, which borders), explain the photography/background direction and why. Ground every claim in design tokens, CSS, or the live product.
- Each capability block 30-70 words with the actual mechanism named. Each engineering highlight has a real Challenge (what goes wrong without this) and a real Solution (how it works), not restated marketing.
- Verified Facts: 15+ dated, checkable statements. Also list standard features deliberately NOT highlighted: it is what makes the rest credible.

Screenshots:
- 5-8 page views following the narrative arc AND, whenever the UI has designed components, 4-7 close-up detail shots (single card with its badge hierarchy, open filter panel, announcement bar, accordion stack, a row of cards showing the system across items). The detail shots are what proves craft; a package with only full-page views is below the bar.
- Read every produced image and retake anything cropped mid-component or showing an unintended state.

## Calibration examples

Below the bar (process ran, depth missing):
> "Il tema include un sistema di badge configurabile e un carrello moderno e veloce."

At the bar:
> "Il badge sconto è sempre calcolato dal confronto tra compare_at_price e price a livello di variante (snippets/badge-sale-dynamic.liquid): non può andare fuori sincrono con il checkout. I badge editoriali derivano da una tassonomia di tag a 6 gruppi (status:, goal:, role:, programme:, support:, type:) orchestrata da un unico snippet, con massimo 2 badge per card."

Below the bar:
> "Design curato con una palette naturale."

At the bar:
> "Usiamo due verdi con ruoli diversi: il verde scuro (#0F3D22) incornicia il sito su header, footer e heading; il verde naturale (#2E7D32) è riservato all'azione (CTA, Add to Cart, link attivi). L'oro (#C79A2B) segnala il valore e compare solo in otto punti precisi: il 35% della hero, il badge Most Popular, gli eyebrow, la sottolineatura dei titoli, la quote del partner, i bordi delle CTA secondarie, il dot attivo dello slider e i focus."

## Self-check before closing (answer honestly; any "no" means go back)

1. Could a competent developer reconstruct each HIGH differentiator's mechanism from my description alone?
2. Does every number in the copy come from something I counted or verified, with an evidence ID?
3. Did I verify the live product where reachable, and does the copy reflect what is actually visible today (disabled features described as capabilities)?
4. Are there detail screenshots proving craft up close, verified image by image?
5. Does the design section explain WHY (roles, placements) rather than list adjectives?
6. Would the LookAndFeelBetter package and this one feel like the same studio's standard? If this one is visibly thinner, the run is not finished.
