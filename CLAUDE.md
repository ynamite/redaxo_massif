# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The parent workspace `CLAUDE.md` (at `/Users/yvestorres/Repositories/viterex/CLAUDE.md`) covers the multi-repo Viterex roadmap and the relationship between `viterex_addon`, `redaxo-massif`, and `viterex-installer`. This file is the addon-specific supplement.

## What this repo is

A Redaxo 5 addon (`package: massif`, namespace `Ynamite\Massif\` → `lib/`) by MASSIF Web Studio. It has **two halves**:

1. **Backend tools** that work standalone on any Redaxo install (media helpers with AVIF/WebP + lazy-load + breakpoint sets, navigation builder, YForm list/form helpers, MASSIF Settings, R4→R5 converter, MarkItUp/Redactor parsing, file uploads, a `MASSIF Auto-Effekt` media manager effect).
2. **Frontend stubs** under `frontend/` (templates, modules, asset sources, npm dep declarations) that are scaffolded into the user's project root by `viterex_addon`'s `StubsInstaller`. Only activates when `viterex_addon` is available.

**Hard rule from the workspace CLAUDE.md:** redaxo-massif must remain usable *without* viterex_addon. The backend half must keep working standalone; the frontend half degrades gracefully (the `MASSIF Frontend` settings page hides itself, `install.php` no-ops).

## How the addon boots

- `boot.php` registers API functions (`massif_meta_get`, `massif_image_get`, `upload_files`), backend CSS/JS, fragment dirs, the `rex_effect_auto` media manager effect, YForm template path (gated on `yform`), and several `PAGES_PREPARED` / `OUTPUT_FILTER` extension points that re-bucket addon pages into `high_addons` / `low_addons` / `z_addons` blocks.
- `install.php` runs on **first activation only** — gated by both `rex_addon::get('viterex_addon')->isAvailable()` and the idempotency marker `rex_config('massif','frontend_installed_at')`. It scaffolds `frontend/` via `StubsInstaller::installFromDir(...)`, appends live-reload globs (`src/addons/massif/{fragments,lib}/**/*.php`) to viterex's `refresh_globs`, then sets the marker. Failures are logged via `rex_logger::logException`; activation must NOT fail because the backend half doesn't depend on the frontend.
- `boot.php` subscribes to **`VITEREX_INSTALL_STUBS`** so that when the user clicks "Install stubs" inside viterex's settings, this addon's frontend is re-installed in the same operation. Bypasses the marker — the click is the consent.

## Frontend stub installation flow

```
frontend/stubs-map.php  →  walks frontend/{templates,modules,assets}/
                           resolves target prefixes via Ynamite\ViteRex\Config
                           (so paths land correctly in modern / classic / classic+theme)

frontend/package-deps.json →  npm deps merged additively (higher-version-wins)
                              into the project's package.json by viterex_addon
```

Three buckets, three target prefixes:
- `templates/* → src/templates/*` (developer-addon convention)
- `modules/*   → src/modules/*` (developer-addon convention)
- `assets/*    → <viterex.assets_source_dir>/*` (e.g. `src/assets/*`)

Folders matching `Foo [N]` (e.g. `Default [1]`, `Meta [5]`) carry the Redaxo template/module ID in brackets — **don't rename or renumber these**, they're consumed by the developer addon to pin DB IDs across upgrades.

## Page template architecture

`frontend/templates/` ships **7 templates**, each composing the next via `REX_TEMPLATE[key="..."]`:

```
Default [1]   → outer shell: Config → Meta → <body>{Header, Main, Footer}</body></html>
├ Config [3]  → required preamble — sets $lang, $pageClass, useragent flags, URL-manager state
├ Meta [5]    → <!doctype><html><head>… — all meta tags + favicons + REX_VITE placeholder (single source of asset injection)
├ Header [8]  → <header> with logo (Assets::inline('img/...')) and skip link
│ └ Menu [12] → desktop + mobile nav
├ Main [2]    → <main> wrapper around $this->getArticle(1)
└ Footer [7]  → <footer>
```

Asset injection contract (per workspace CLAUDE.md): a single `REX_VITE` placeholder in `Meta` emits preload + CSS + JS + HMR client. Do **not** reintroduce the legacy `Assets::get()` / `Server::getImg|Css|Js|Font|Assets()` API in templates.

Server-side swup `req-with` / `HTTP_X_REQUESTED_WITH` hooks were intentionally removed: `Config` no longer sets the `req-with` rex property and `Default` always emits `</html>`. The swup *JS* integration is still present across `frontend/assets/js/swup/` — only remove if dropping swup entirely.

## Re-install paths (explicit)

Two ways to re-run the install after first activation:

1. **Backend page** *AddOns → MASSIF → MASSIF Frontend* (`pages/frontend.php`) — CSRF-protected form with an "Overwrite existing files" checkbox. Overwritten files are backed up as `<file>.bak.<timestamp>` before being replaced. Hidden when viterex_addon isn't available.
2. **viterex_addon's "Install stubs" button** — fires `VITEREX_INSTALL_STUBS`, the boot.php hook re-installs and merges results into viterex's success summary.

Both paths bypass the `frontend_installed_at` marker because the click itself is consent.

## Common conventions in this codebase

- PSR-4 root: `Ynamite\Massif\` → `lib/` (per `composer.json`); subpackages like `Ynamite\Massif\Media`, `Ynamite\Massif\Nav`, `Ynamite\Massif\BE`, `Ynamite\Massif\Form`, `Ynamite\Massif\Utils`, `Ynamite\Massif\Redactor`, `Ynamite\Massif\MarkItUp`, `Ynamite\Massif\Converter`.
- Non-namespaced legacy classes live under `lib/api/`, `lib/effects/`, `lib/yform/`, `lib/command/` (loaded by Redaxo's autoloader, not Composer's).
- `lib/Media/Media.php` is intentionally empty — the abstract base lives at `lib/Media/Abstract.php` (`abstract class Media`). Subclasses are `Media\Image\Image` and `Media\Video\Video`. Use the `Media::factory(...)` static for source-driven instantiation.
- Backend pages declared in `package.yml` under `page.subpages.*` — supports `customPage: true|false`. The `template` subpage is `hidden: true` and used as a generic YForm-table host (rendered via `lib/BE/Package.php`).
- Console command: `console massif:exec-php "<code>"` — guarded by `isSecure()` in `lib/command/exec-php.php`. Allowed in: `REDAXO_DEV_MODE=1` env, `var/dev_mode` file, CLI from localhost, or when ydeploy reports `!isDeployed()`. The handler also rejects code matching a long deny-list of `eval`/`exec`/`file_*`/`$_SERVER` patterns. Treat it as dev-only.
- `installer_ignore` in `package.yml` skips `.DS_Store`, `.git`, `.gitignore`, `node_modules` so they don't leak into Redaxo's installer ZIP.
- Lang file is German only (`lang/de_de.lang`); README is German.

## What this repo does NOT have

- No `package.json` / `vite.config.js` / build pipeline at the repo root — those are owned by `viterex_addon`'s stubs and only exist in the consuming Redaxo project after scaffolding.
- No test suite, no linter config, no CI. Verification is done by activating in a real Redaxo install (e.g. `~/Herd/primobau/src` per the workspace CLAUDE.md) and exercising the backend pages.
- No `.cursor/`, no `.github/copilot-instructions.md`.

## Versioning notes

Current `package.yml` is `version: 2.0.0`. The 2.0.0 release was the merge of the standalone `redaxo-frontend-assets` repo into `frontend/` plus the auto-install path; see `CHANGELOG.md` for the migration steps (notably: existing projects must manually delete the obsolete `HTML Head [5]`, `HTML Meta [6]`, `HTML Favicon [11]`, `HTML Scripts [2]` template folders before re-running install with overwrite). Active branch as of writing is `main` (the `v3-frontend-assets` branch was merged via PR #1).
