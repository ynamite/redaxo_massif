# Changelog

## **Version 3.1.0**

### Added

- **`multi_file_upload` yform value** — generic Dropzone multi-file upload (subclass of `mupload`) with configurable `zip_name` prefix and `upload_folder`; the dataset list links downloads through the new gated API.
- **`massif_download` API** (`Form\Api\Download`) — backend-session-gated download for `mupload`/`multi_file_upload` values (`?rex-api-call=massif_download&table_name=…&field=…&data_id=…`). Resolves the path from the field's `upload_folder` element with a realpath containment check.

### Changed

- **`mupload`** — zip filename prefix is configurable via the new `zip_name` element (default stays `bewerbungsunterlagen`); `handleUploads()` now calls `$this->zipUploads()` so subclasses can override the zip step.
- **`value.mupload.tpl.php`** — inline SVG for the remove icon instead of the project-level `icons/icon` fragment (removed a hidden addon→project dependency); the backend view links the stored file through `massif_download` instead of the session-scoped tmp URL and no longer renders the broken delete link.
- **`Form::send_yform_email_template()`** — `multi_file_upload` added to the default `skip_field_types`.


## **Version 3.0.0**

Breaking release. The `frontend/` half — MASSIF's page templates, content modules and asset sources — is extracted out of this addon. It now lives in the standalone **`viterex-massif-preset`** repo and is installed by `create-viterex`'s "Install preset frontend" pipeline task. This addon is backend-only again (media helpers, navigation builder, YForm extensions, MASSIF Settings, R4→R5 converter, MarkItUp/Redactor parsing, file uploads, the `MASSIF Auto-Effekt` media manager effect).

Decoupling the frontend also fixes an ordering bug: it was previously scaffolded PHP-side on addon activation, *after* the project's package-manager install had run, so the frontend's npm deps were missing from the first `vite build`. `create-viterex` now installs it before the dependency step.

### Removed

- **`frontend/` folder** — templates, modules, asset sources, `stubs-map.php`, `package-deps.json`. Moved to the `viterex-massif-preset` repo.
- **`pages/frontend.php`** — the *MASSIF Frontend* settings subpage, plus its `frontend` entry in `package.yml` and the `massif_frontend_*` keys in `lang/de_de.lang`.
- **`VITEREX_INSTALL_STUBS` handler** in `boot.php` — the addon no longer contributes stubs to viterex_addon's "Install stubs" operation.
- **Frontend auto-install in `install.php`** — the `StubsInstaller::installFromDir()` call and the `rex_config('massif','frontend_installed_at')` idempotency marker.

### Changed

- **`install.php`** — slimmed to a single `StubsInstaller::appendRefreshGlobs()` call (the addon's own `fragments/` + `lib/` live-reload globs), still gated on `viterex_addon` being available.

### Migration

For existing MASSIF projects:

1. The scaffolded frontend already lives in the project (`src/templates`, `src/modules`, `src/assets`, tracked by git and the `developer` addon) — it is unaffected.
2. Updating the `redaxo_massif` submodule to v3.0.0 removes the now-obsolete `src/addons/massif/frontend/` source tree; harmless.
3. The *MASSIF Frontend* backend page is gone. Re-scaffold the frontend by running `create-viterex` with the `viterex-massif-preset` preset.
4. `rex_config('massif','frontend_installed_at')` becomes an unused orphan and can be left as-is.

## **Version 2.0.0**

Breaking release. Merges the previously standalone `redaxo-frontend-assets` repository into this addon as a `frontend/` folder, and adds an auto-install path that scaffolds those assets into the user's project root when `viterex_addon` is available.

The backend tools (image generation, MJML mail, YForm extensions, MASSIF settings, etc.) continue to function on a fresh Redaxo install without `viterex_addon`. The frontend portion only activates when `viterex_addon` is also installed.

### Added

- **`frontend/` folder** — bundles the MASSIF page templates (`Config`, `Default`, `Meta`, `Header`, `Menu`, `Main`, `Footer`), modules (`Text-Block`, `Swiper`, `Kontaktformular`), and asset sources (CSS, JS, fonts, images).
- **`install.php`** — auto-installs `frontend/` into the user's project on first activation iff `viterex_addon` is available and the idempotency marker `rex_config('massif','frontend_installed_at')` is unset. Sets the marker on success. Failure is logged but does not block addon activation.
- **`pages/frontend.php`** — backend Settings subpage at *AddOns → MASSIF → MASSIF Frontend*. Shows install status, offers an explicit re-install button with an "Overwrite existing files" checkbox (overwritten files are backed up with a `.bak.<timestamp>` suffix). Bypasses the idempotency marker — explicit click = explicit consent. Hidden when `viterex_addon` is unavailable.
- **`VITEREX_INSTALL_STUBS` extension-point handler** in `boot.php` — when the user clicks "Install stubs" on viterex's Settings page, the MASSIF frontend is re-installed in the same operation, with the user's overwrite choice respected and results merged into the success summary.
- **`frontend/stubs-map.php`** — declarative source-to-destination map. Walks `frontend/{templates,modules,assets}/` and resolves target prefixes via `Ynamite\ViteRex\Config` so files land at the right locations regardless of project structure (modern, classic, classic+theme).
- **`frontend/package-deps.json`** — npm dependencies the MASSIF frontend stack needs on top of viterex_addon's stub `package.json` (alpinejs + 4 plugins, swiper, gsap, glightbox, dropzone, `@iconify/tailwind4`, `@iconify-json/lucide`). Merged additively into the project's `package.json` via viterex_addon's `installFromDir($packageDeps)` argument.
- Live-reload globs `src/addons/massif/fragments/**/*.php` and `src/addons/massif/lib/**/*.php` are appended (idempotent) to viterex's `refresh_globs` setting on install, so Vite reloads when MASSIF's PHP files change.

### Changed

- **Templates consolidated** per the design in CLAUDE.md §2: 9 sub-folders → 8.
  - **New `Meta [5]`** — merge of `HTML Head [5]` + `HTML Meta [6]` + `HTML Favicon [11]`. Opens `<!doctype html><html><head>…</head>` with all meta tags, favicon links, view-transition style, and the v3 `REX_VITE` placeholder (which now emits preload + CSS + JS scripts + HMR client in one block — replaces the previous `Assets::get()` calls). Reuses ID 5.
  - **New `Main [2]`** — extracted from `Default`'s previously-inline `<main>…$this->getArticle(1)…</main>` block. Reuses ID 2 (freed by `HTML Scripts` removal).
  - **`Default [1]`** — outer shell now: `REX_TEMPLATE[config]` → `REX_TEMPLATE[meta]` → `<body>` → `REX_TEMPLATE[header]` → `REX_TEMPLATE[main]` → `REX_TEMPLATE[footer]` → `</body>` → conditional `</html>` (preserved swup partial-request behavior).
  - **`Header [8]`** — already migrated in B1: `Server::getImg('massif-logo.svg')` → `Assets::inline('img/massif-logo.svg')`.
- **Removed**: `HTML Head [5]/`, `HTML Meta [6]/`, `HTML Favicon [11]/`, `HTML Scripts [2]/` template folders — folded into the consolidated `Meta` and the simpler `REX_VITE` contract.
- **Removed `MJML-Mail [10]/`** — no longer used.
- **Removed swup `req-with` / `HTTP_X_REQUESTED_WITH` server-side hooks** — `Config` no longer sets the `req-with` rex property; `Default` always emits the closing `</html>` tag. (The swup *JS* integration is still imported across many `frontend/assets/js/*` files; remove separately if/when swup is dropped entirely.)
- **`package.yml`** — adds `requires.redaxo: ^5.13.0` and `requires.php: >=8.1`. Adds the `frontend` subpage. Adds `installer_ignore` for `.DS_Store`, `.git`, `.gitignore`, `node_modules`.
- **`lang/de_de.lang`** — adds `massif_frontend_*` keys for the new Settings page.

### Removed

- `redaxo-frontend-assets` repository — merged into this addon. Archive the standalone repo after upgrading.
- `localhost+2*.pem` files (mkcert certs) — generate locally via viterex_addon's `npm run setup-https`.
- Project-level `package.json` and `vite.config.js` from the bundled assets — those files are owned by viterex_addon's stubs.

### Migration

For existing MASSIF projects:

1. Update viterex_addon to v3.1.0 first (provides the public `StubsInstaller::installFromDir()` API and the `VITEREX_INSTALL_STUBS` extension point this addon's hook depends on).
2. Update redaxo-massif to v2.0.0.
3. The marker `rex_config('massif','frontend_installed_at')` is unset after the upgrade, so the next activation auto-installs the frontend (with `overwrite=false` — won't touch existing files). Use the new Settings page with "Overwrite existing files" checked to refresh existing scaffold files.
4. **Manual cleanup of obsolete template folders** — before re-running install with overwrite, delete `src/templates/HTML Head [5]/`, `src/templates/HTML Meta [6]/`, `src/templates/HTML Favicon [11]/`, and `src/templates/HTML Scripts [2]/` from the project. Their content is folded into the new `Meta [5]` and the v3 `REX_VITE` contract. Articles aren't affected — they reference `Default [1]` and `MJML-Mail [10]`, both of which keep their IDs.

## **Version 1.0.0**

- Initial private release. Backend tools for MASSIF projects (image generation, MJML mail, YForm extensions, MASSIF Settings, MarkItUp/Redactor integration, navigation builder, R4-to-R5 converter).
