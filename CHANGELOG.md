# Changelog

## **Version 2.0.0**

Breaking release. Merges the previously standalone `redaxo-frontend-assets` repository into this addon as a `frontend/` folder, and adds an auto-install path that scaffolds those assets into the user's project root when `viterex_addon` is available.

The backend tools (image generation, MJML mail, YForm extensions, MASSIF settings, etc.) continue to function on a fresh Redaxo install without `viterex_addon`. The frontend portion only activates when `viterex_addon` is also installed.

### Added

- **`frontend/` folder** — bundles the MASSIF page templates (`Config`, `Default`, `Header`, `Menu`, `Footer`, `HTML Head`, `HTML Meta`, `HTML Favicon`, `MJML-Mail`), modules (`Text-Block`, `Swiper`, `Kontaktformular`), and asset sources (CSS, JS, fonts, images).
- **`install.php`** — auto-installs `frontend/` into the user's project on first activation iff `viterex_addon` is available and the idempotency marker `rex_config('massif','frontend_installed_at')` is unset. Sets the marker on success. Failure is logged but does not block addon activation.
- **`pages/frontend.php`** — backend Settings subpage at *AddOns → MASSIF → MASSIF Frontend*. Shows install status, offers an explicit re-install button with an "Overwrite existing files" checkbox (overwritten files are backed up with a `.bak.<timestamp>` suffix). Bypasses the idempotency marker — explicit click = explicit consent. Hidden when `viterex_addon` is unavailable.
- **`VITEREX_INSTALL_STUBS` extension-point handler** in `boot.php` — when the user clicks "Install stubs" on viterex's Settings page, the MASSIF frontend is re-installed in the same operation, with the user's overwrite choice respected and results merged into the success summary.
- **`frontend/stubs-map.php`** — declarative source-to-destination map. Walks `frontend/{templates,modules,assets}/` and resolves target prefixes via `Ynamite\ViteRex\Config` so files land at the right locations regardless of project structure (modern, classic, classic+theme).
- **`frontend/package-deps.json`** — npm dependencies the MASSIF frontend stack needs on top of viterex_addon's stub `package.json` (alpinejs + 4 plugins, swiper, gsap, glightbox, dropzone, `@iconify/tailwind4`, `@iconify-json/lucide`). Merged additively into the project's `package.json` via viterex_addon's `installFromDir($packageDeps)` argument.
- Live-reload globs `src/addons/massif/fragments/**/*.php` and `src/addons/massif/lib/**/*.php` are appended (idempotent) to viterex's `refresh_globs` setting on install, so Vite reloads when MASSIF's PHP files change.

### Changed

- **Templates migrated to viterex_addon v3 API**:
  - `HTML Meta [6]/template.php` — replaces `Assets::get(); echo $assets['preload']; echo $assets['css']` with a single `REX_VITE` placeholder. With v3, `REX_VITE` emits preload + CSS + JS scripts + HMR client all in one block, so the previous separate `HTML Scripts [2]` template is no longer needed.
  - `Header [8]/template.php` — replaces `Server::getImg('massif-logo.svg')` with `Assets::inline('img/massif-logo.svg')` (preserves inline-SVG behavior for `currentColor` theming).
  - `Default [1]/template.php` — drops the `REX_TEMPLATE[key="html-scripts"]` reference.
- **Removed `HTML Scripts [2]/`** — folded into `HTML Meta [6]/` via the new single-placeholder `REX_VITE` contract.
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

## **Version 1.0.0**

- Initial private release. Backend tools for MASSIF projects (image generation, MJML mail, YForm extensions, MASSIF Settings, MarkItUp/Redactor integration, navigation builder, R4-to-R5 converter).
