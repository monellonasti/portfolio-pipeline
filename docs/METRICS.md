# How the numbers were measured

Every figure in the README comes from one of four sources:

- **Session logs**: the Claude Code transcripts of the sessions involved (timestamps of our own messages). They are private; the times and counts below are copied from them.
- **Git history** of the website repository, for the plugin versions.
- **File times** of the skills and of the `.portfolio/` packages.
- **The packages themselves**: `EVIDENCE.md`, `REVIEW.md`, `pipeline.json`, screenshots.

All times are CEST (UTC+2).

## Before: the first case study (9 August 2026)

Project: Look And Feel Better, a Shopify theme. Method: a 20 KB prompt pasted into Claude Code, followed by our corrections in chat.

| Time | Event |
| --- | --- |
| 16:25 | First message: the prompt |
| 17:04 | Fact: we also digitized the client's catalog; request to take the screenshots |
| 17:18 | Fact: our role and the timeline |
| 17:35 | Correction: remove the AI-style dashes, explain the palette and where the accents are placed |
| 17:49 | Correction: remove the dashes (second time) |
| 18:03 | Fact: the full scope of our role (account setup, payments, shipping) |
| 18:21 | Correction: screenshots redone to show badges, filter navigation and the announcement bar |
| 19:12 | Correction: rename the image files with the project name, to avoid collisions in the gallery |

Elapsed: 2 h 47 min. Follow-up messages: 7 (3 facts, 4 corrections).

The prompt already asked for evidence behind every selling point and ended with a verification section. What it did not have was a separate numbered ledger, confidence levels, or a second reviewer.

## Building the automation (9 and 10 August 2026)

| Time | Event | Source |
| --- | --- | --- |
| 9 Aug, 17:05 | Plugin 1.6.0: fields for in-depth case studies, filled by hand | git |
| 9 Aug, 17:22 | Plugin 1.7.0: import of the case study from a Markdown file | git |
| 9 Aug, 17:52 | Plugin 1.8.0: design section, section images, lightbox | git |
| 9 Aug, 18:17 | Plugin 1.9.0: AI translation, one language at a time | git |
| 9 Aug, 19:16 | We ask to turn the process into reusable skills | session log |
| 9 Aug, 19:18 to 19:25 | The four skills are written | file times |
| 9 Aug, 19:26 | Plugin 1.10.0: translation panel with progress and stop | git |
| 9 Aug, 19:44 | First automated run, on another project | session log |
| 10 Aug, 23:51 | Our feedback: the automated output is not as detailed as the hand-made one | session log |
| 10 Aug, 23:53 | `quality-bar.md` written and wired into `/portfolio` and `/portfolio-review` | file times |

Later plugin releases added structured data on case studies (1.11.0) and the rule that a translated version is published only when every field filled in Italian has a complete translation (1.11.3).

## After: a pipeline run (23 September 2026)

Project: an in-house Shopify app. Its case study is approved but not yet published, so it is not named here.

| Time | Event | Source |
| --- | --- | --- |
| 11:04 | `/portfolio-pipeline` | session log |
| 11:42 | Our only message: the business problem the product solves, which the code cannot tell | session log |
| 11:43 | Package complete, review updated with our context: PASS WITH FIXES, approved | `REVIEW.md` file time |
| 17:48 | We ask to prepare the publication | session log |
| 17:54 | Import bundle ready: Markdown file, 9 images, list of manual fields | file times |

Elapsed to an approved package: 39 min. Package: 44 evidence records, 11 screenshots. Review scores, before and after the reviewer's fixes:

| Area | Before fixes | After fixes |
| --- | --- | --- |
| Accuracy | 84 | 92 |
| Evidence | 83 | 90 |
| Portfolio | 80 | 86 |
| Technical | 91 | 93 |
| Visual Story | 62 | 74 |
| Copy Quality | 80 | 88 |
| Privacy | 94 | 97 |
| **Overall** | **81** | **88** |

The import file was checked by running the plugin's importer code on it: it fills 18 fields, 3 design blocks, 5 capabilities and 4 technical deep dives.

## All packages

Counted on 28 September 2026 from the `.portfolio/` folders.

| Project | Evidence records | Review | Live on digifluss.com |
| --- | --- | --- | --- |
| Frequency School | 92 | 91/100, approved | yes, six languages |
| TOPTOUR Portugal | 88 | 69/100 on the first pass; approved on 22 Sep 2026, after its fixes and our confirmation of the live site | yes, six languages |
| In-house Shopify app | 44 | 88/100, approved | not yet |
| Five other projects | 26, 35, 43, 46, 50 | not reviewed yet | no |
| **Total** | **424** | | |

The hand-made reference, Look And Feel Better, predates the pipeline and is live in six languages.

## Translations (28 September 2026)

| Run | Plugin | Calls | Right on the first call | Em and en dashes in the saved translations |
| --- | --- | --- | --- | --- |
| Frequency School and TOPTOUR Portugal | 1.11.6 | 10 | 8 | Frequency School: 40 en, 49 de, 27 fr, 2 es, 2 pt. TOPTOUR: 11 en, 21 de, 5 fr, 0 es, 0 pt |
| All three case studies | 1.11.7 | 15 | 15 | 0 in every language |

- **The two failures on 1.11.6.** The model copied straight quotes from the Italian ("doposcuola Casalnuovo") without escaping them, so its JSON did not parse and the plugin rejected the language. An extra prompt instruction did not help: both retries failed again. The two answers were repaired (7 and 14 quote fixes, each checked, same structure as the Italian fields) and saved.
- **The dashes.** The Italian copy has none, because the writing rules forbid them, but the translation prompt did not say so. Look And Feel Better, translated in August, had 28 in English, 20 in German, 16 in French, 15 in Spanish and 17 in Portuguese.
- **Plugin 1.11.7** asks the API for structured output (a JSON schema built from the case study's fields) and carries the no-dash rule. Per language: 71 to 168 s; 26 min for all 15.
- **Checked on the live site** after the cache purge: 18 pages (3 case studies in 6 languages) answer 200, list six languages plus x-default in hreflang and contain no em or en dash in the page body.
- **Not translated:** the post title, which the plugin treats as the project name, and the gallery captions, which come from the media library. Frequency School's post title used to carry an Italian descriptor ("sito WordPress su misura") that stayed Italian in every language; on 28 September it was renamed to the plain project name, and the plugin now builds each language's `<title>` from the translated category (for example "Frequency School: Custom WordPress Theme").

## Caveats

- The before and after runs are different projects, and the first one also designed the method. The times are indicative, not a benchmark.
- The 39 minutes are mostly machine time. Our input was one message plus reading the result; we did not time the reading.
- Translations run on request from wp-admin. Since 28 September 2026 all three live case studies are in six languages.
