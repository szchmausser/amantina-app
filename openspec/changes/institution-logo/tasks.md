# Tasks: Institution Logo

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~520 (320 backbone + 200 frontend) |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (backbone) → PR 2 (frontend) |
| Delivery strategy | auto-chain |
| Chain strategy | feature-branch-chain |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Backbone: model, controller, routes, favicon, types, tests | PR 1 | Base = `feature/institution-logo`. Serves logo data via Inertia; no UI changes. |
| 2 | Frontend: upload component, logo display, blade, tests | PR 2 | Base = PR 1 branch. Depends on PR 1 for shared data + routes. Pure UI layer. |

## PR 1: Backbone (back-end)

- [x] 1.1 **Migration** — Create `add_soft_deletes_to_institution` migration adding `deleted_at` to `institution` table.
- [x] 1.2 **Model** — Add `HasMedia`, `InteractsWithMedia`, `SoftDeletes` to `Institution.php`. Register single-file `logo` collection with `thumb` (150×150) + `favicon` (32×32) conversions, MIME accept list. Append computed `logo_url` and `favicon_url` attributes (both nullable).
- [x] 1.3 **Controller** — Add `updateLogo()` + `removeLogo()` to `InstitutionController.php` (inline validation, same as ProfileController::updateAvatar/removeAvatar).
- [x] 1.4 **Routes** — Add `POST|DELETE /settings/institution/logo` in `routes/settings.php` (auth+verified). Add `GET /favicon` in `routes/web.php` pointing to `FaviconController`.
- [x] 1.5 **FaviconController** — Create `FaviconController` with `__invoke()`. Returns logo favicon conversion (32×32) with cache headers, or redirects to `/favicon.ico` fallback.
- [x] 1.6 **Shared data** — Add `institution { name, logo_url, favicon_url }` to `HandleInertiaRequests::share()`. Null-safe when no institution record exists.
- [x] 1.7 **Types** — Add `institution` to `SharedData` in `resources/js/types/index.ts` and `InertiaConfig` in `resources/js/types/global.d.ts`.
- [x] 1.8 **Feature tests** — Create `tests/Feature/Settings/InstitutionLogoTest.php` covering: upload valid (persists + replaces), upload oversized (validation error), upload bad type (validation error), remove existing logo, remove with none, favicon serves logo, favicon fallback to default, shared data includes institution.

## PR 2: Frontend (UI)

- [x] 2.1 **Upload component** — Create `resources/js/components/institution-logo-upload.tsx` mirroring `user-avatar.tsx`: FileReader preview, Guardar/Cancelar buttons, remove button when logo exists, accepted formats text.
- [x] 2.2 **Settings page** — Integrate `InstitutionLogoUpload` into `resources/js/pages/settings/institution.tsx` above the info form. Pass `logoUrl`, `institutionName`, Wayfinder route URLs.
- [x] 2.3 **AppLogo** — Rewrite `resources/js/components/app-logo.tsx` to consume `page.props.institution` from shared data. Render logo (thumb) when available, fallback to institution name, fallback to "Amantina App" when null.
- [x] 2.4 **Dynamic favicon** — Update `resources/views/app.blade.php`: replace static `favicon.ico` link with dynamic URL using `{{ ($page['props']['institution']['favicon_url'] ?? null) ?: asset('favicon.ico') }}?v={{ time() }}` for cache-busting.
- [x] 2.5 **Browser tests** — Create `tests/Browser/Settings/InstitutionLogoSettingsTest.php` covering: logo section visible on settings page, logo preview visible when logo exists, remove logo via UI, AppLogo displays logo image in sidebar.

## Dependency Graph

```
1.1 → 1.2 → 1.3 → 1.4 → 1.5 → 1.6 → 1.7 → 1.8
                                                  ↓
                                          2.1 → 2.2
                                          2.3 → 2.4
                                          2.5 (depends on 2.1+2.2+2.3)
```
