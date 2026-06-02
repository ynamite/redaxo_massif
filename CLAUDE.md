# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The parent workspace `CLAUDE.md` (at `/Users/yvestorres/Repositories/viterex/CLAUDE.md`) covers the multi-repo Viterex roadmap and the relationship between `viterex_addon`, `redaxo-massif`, and `viterex-installer`. This file is the addon-specific supplement.

## What this repo is

A Redaxo 5 addon (`package: massif`, namespace `Ynamite\Massif\` → `lib/`) by MASSIF Web Studio. It is a **backend tooling addon**: media helpers with AVIF/WebP + lazy-load + breakpoint sets, navigation builder, YForm list/form helpers, MASSIF Settings, R4→R5 converter, MarkItUp/Redactor parsing, file uploads, and a `MASSIF Auto-Effekt` media manager effect.

It works standalone on any Redaxo install and has **no hard dependency on `viterex_addon`** — the one optional integration (`install.php`, below) is gated on `viterex_addon` being available and degrades to a no-op otherwise.

> **History:** until v2.x this addon also shipped a `frontend/` half — MASSIF's page templates, modules and asset sources, auto-scaffolded into the project on activation. As of **v3.0.0** that frontend was extracted into the standalone **`viterex-massif-preset`** repo and is now installed by `create-viterex`'s "Install preset frontend" pipeline task. This addon is backend-only again. See `CHANGELOG.md`.

## How the addon boots

- `boot.php` registers API functions (`massif_meta_get`, `massif_image_get`, `upload_files`), backend CSS/JS, fragment dirs, the `rex_effect_auto` media manager effect, YForm template path (gated on `yform`), and several `PAGES_PREPARED` / `OUTPUT_FILTER` extension points that re-bucket addon pages into `high_addons` / `low_addons` / `z_addons` blocks.
- `install.php` — when `viterex_addon` is available, appends live-reload globs (`src/addons/massif/{fragments,lib}/**/*.php`) to viterex's `refresh_globs` so Vite reloads when MASSIF's PHP files change. Gated on `viterex_addon`; failures are logged via `rex_logger::logException` and never block activation.

## Common conventions in this codebase

- PSR-4 root: `Ynamite\Massif\` → `lib/` (per `composer.json`); subpackages like `Ynamite\Massif\Media`, `Ynamite\Massif\Nav`, `Ynamite\Massif\BE`, `Ynamite\Massif\Form`, `Ynamite\Massif\Utils`, `Ynamite\Massif\Redactor`, `Ynamite\Massif\MarkItUp`, `Ynamite\Massif\Converter`.
- Non-namespaced legacy classes live under `lib/api/`, `lib/effects/`, `lib/yform/`, `lib/command/` (loaded by Redaxo's autoloader, not Composer's).
- `lib/Media/Media.php` is intentionally empty — the abstract base lives at `lib/Media/Abstract.php` (`abstract class Media`). Subclasses are `Media\Image\Image` and `Media\Video\Video`. Use the `Media::factory(...)` static for source-driven instantiation.
- Backend pages declared in `package.yml` under `page.subpages.*` — supports `customPage: true|false`. The `template` subpage is `hidden: true` and used as a generic YForm-table host (rendered via `lib/BE/Package.php`).
- Console command: `console massif:exec-php "<code>"` — guarded by `isSecure()` in `lib/command/exec-php.php`. Allowed in: `REDAXO_DEV_MODE=1` env, `var/dev_mode` file, CLI from localhost, or when ydeploy reports `!isDeployed()`. The handler also rejects code matching a long deny-list of `eval`/`exec`/`file_*`/`$_SERVER` patterns. Treat it as dev-only.
- `installer_ignore` in `package.yml` skips `.DS_Store`, `.git`, `.gitignore`, `node_modules` so they don't leak into Redaxo's installer ZIP.
- Lang file is German only (`lang/de_de.lang`); README is German.

## What this repo does NOT have

- No `frontend/`, `package.json`, `vite.config.js` or build pipeline — the MASSIF frontend lives in the `viterex-massif-preset` repo, and the Vite chain is owned by `viterex_addon`'s stubs. Both only exist in the consuming Redaxo project after scaffolding.
- No test suite, no linter config, no CI. Verification is done by activating in a real Redaxo install (e.g. `~/Herd/primobau/src` per the workspace CLAUDE.md) and exercising the backend pages.
- No `.cursor/`, no `.github/copilot-instructions.md`.

## Versioning notes

Current `package.yml` is `version: 3.0.0`. The 3.0.0 release extracted the bundled `frontend/` half (templates, modules, asset sources, `stubs-map.php`, `package-deps.json`), the `MASSIF Frontend` settings page, and the `VITEREX_INSTALL_STUBS` subscription — the addon is backend-only again. See `CHANGELOG.md`. The frontend now lives in `viterex-massif-preset` and is installed by `create-viterex`.
