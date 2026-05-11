# Design: Institution Logo

## Technical Approach

Single-file `logo` Media Library collection on Institution (mimicking `User::avatar` exactly). Upload/remove via POST/DELETE routes on InstitutionController. Institution data (`name`, `logo_url`, `favicon_url`) shared globally via `HandleInertiaRequests`. Favicon served from a dedicated public route with cache-busting version param. Frontend: `app-logo.tsx` renders shared institution data; institution settings page gains logo upload widget.

## Architecture Decisions

### Decision: Inline validation vs FormRequest

| Option | Tradeoff | Decision |
|--------|----------|----------|
| FormRequest (UploadLogoRequest) | More files, follows AGENTS.md convention | **Skip** — ProfileController::updateAvatar already uses inline validation. Following existing pattern is more important. |
| Inline validation in controller | Fewer files, matches existing pattern | **Accept** — Consistency with proven avatar pattern wins. |

### Decision: LogoUpload as separate component vs inline in institution page

| Option | Tradeoff | Decision |
|--------|----------|----------|
| New `institution-logo-upload.tsx` | Reusable, single responsibility, testable | **Accept** — Mirrors `user-avatar.tsx` structure. |
| Inline in institution.tsx | Fewer files | Skip — clutters the page component. |

### Decision: Favicon cache-busting strategy

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `updated_at` timestamp as version param | Simple, no DB changes | **Accept** — `Institution::first()?->updated_at?->timestamp` is lightweight. |
| Random/hashed version per request | No caching at all | Skip — defeats CDN caching. |
| Media library UUID | Complex for marginal gain | Skip — timestamp is sufficient. |

## Data Flow

```
User uploads logo → InstitutionController::updateLogo()
  → addMediaFromRequest('logo') → media stored
  → redirect back with flash success
  → Inertia re-render → HandleInertiaRequests shares updated institution
  → app-logo.tsx re-renders with new logo_url

Browser requests favicon → GET /favicon?v=<timestamp>
  → FaviconController
  → returns logo favicon conversion (32×32) with Cache-Control
  → falls back to default favicon.ico if no logo
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Models/Institution.php` | Modify | Add `HasMedia`, `InteractsWithMedia`, `SoftDeletes`, `logo` collection, computed attributes |
| `app/Http/Controllers/Settings/InstitutionController.php` | Modify | Add `updateLogo()`, `removeLogo()` (inline validation, same pattern as ProfileController) |
| `app/Http/Controllers/FaviconController.php` | Create | Serve favicon conversion or default, cache-friendly |
| `routes/settings.php` | Modify | Add logo POST/DELETE routes |
| `routes/web.php` | Modify | Add public favicon route |
| `app/Http/Middleware/HandleInertiaRequests.php` | Modify | Share `institution { name, logo_url, favicon_url }` |
| `database/migrations/xxxx_xx_xx_xxxxxx_add_soft_deletes_to_institution.php` | Create | Add `deleted_at` to `institution` table |
| `resources/views/app.blade.php` | Modify | Replace static favicon with dynamic URL + version |
| `resources/js/types/index.ts` | Modify | Add `institution` to `SharedData` |
| `resources/js/types/global.d.ts` | Modify | Add `institution` to `InertiaConfig` |
| `resources/js/components/app-logo.tsx` | Modify | Consume shared institution, render logo/name |
| `resources/js/components/institution-logo-upload.tsx` | Create | Logo upload widget (FileReader preview, Guardar/Cancelar) |
| `resources/js/pages/settings/institution.tsx` | Modify | Add logo section, pass institution props to upload component |
| `tests/Feature/Settings/InstitutionLogoTest.php` | Create | Feature tests for upload, remove, validation |
| `tests/Browser/Settings/InstitutionLogoSettingsTest.php` | Create | Browser tests for UI upload flow |

## Interfaces / Contracts

```typescript
// SharedData addition
interface InstitutionData {
    name: string;
    logo_url: string | null;
    favicon_url: string | null;
}

declare module '@inertiajs/core' {
    sharedPageProps: {
        institution: InstitutionData | null;
        // ...existing
    };
}

// InstitutionLogoUpload props
interface InstitutionLogoUploadProps {
    logoUrl: string | null;
    logoUpdateUrl: string;
    logoRemoveUrl: string;
    institutionName: string;
}
```

```php
// Institution model computed attributes
public function getLogoUrlAttribute(): ?string
{
    return $this->getFirstMediaUrl('logo', 'thumb')
        ?: $this->getFirstMediaUrl('logo');
}

public function getFaviconUrlAttribute(): ?string
{
    return $this->getFirstMediaUrl('logo', 'favicon')
        ?: $this->getFirstMediaUrl('logo');
}
```

## Media Conversions

| Name | Width | Height | Fit | Purpose |
|------|-------|--------|-----|---------|
| `thumb` | 150 | 150 | cover | Sidebar/header logo |
| `favicon` | 32 | 32 | cover | Browser tab icon |

Accepted MIMEs: `image/jpeg`, `image/png`, `image/gif`, `image/webp`. Max size: 2MB.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | Upload valid logo → persists, replaces existing | `post(route('institution.logo.update'), ['logo' => $file])` |
| Feature | Upload oversized file → validation error | Assert `logo` validation fails |
| Feature | Upload invalid type → validation error | Assert `logo` validation fails |
| Feature | Remove logo → collection empty | `delete(route('institution.logo.remove'))` |
| Feature | Remove when none → no-op, success | Assert 302 with success session |
| Feature | Favicon serves logo conversion | Assert response has image content-type |
| Feature | Favicon fallback when no logo | Assert redirects to /favicon.ico |
| Feature | Shared data includes institution | Assert `$response->original->getData()['page']['props']['institution']` |
| Browser | Upload logo via UI → preview shown, persisted | See `user-avatar.tsx` browser test pattern |

## PR Boundary

All changes fit within a single PR (~500 lines total). **No chaining needed**, but borderline — decide at `sdd-tasks`:
- PR 1 (backbone, ~300 lines): Model + migration + controller + routes + shared data + favicon + feature tests
- PR 2 (frontend, ~200 lines): app-logo + upload widget + institution form + Browser tests + layout favicon

If merged into one PR, tag `size:exception` due to test volume pushing past 400-line budget.

## Migration / Rollout

- Run `php artisan migrate` (adds `deleted_at` to `institution` table)
- Run `php artisan storage:link` if not already linked (media library images)
- If rolling back: revert migration, revert model, remove routes, rebuild assets
- Favicon route is public — no auth dependency

## Open Questions

- None — pattern is fully proven by User::avatar. No unknowns.

---

## Design Created

**Change**: institution-logo
**Location**: `openspec/changes/institution-logo/design.md` + Engram `sdd/institution-logo/design`

### Summary
- **Approach**: Replicate User::avatar Media Library pattern on Institution model with `logo` collection, `thumb` + `favicon` conversions. Upload/remove endpoints, global shared data, dynamic favicon.
- **Key Decisions**: 4 documented (inline validation, separate component, timestamp cache-busting, single/concurrent PR)
- **Files Affected**: 14 files (6 modified, 4 new, 4 modified frontend/types)
- **Testing Strategy**: 7 Feature tests + 1 Browser test covering upload, remove, validation, favicon, shared data

### Open Questions
None

### Next Step
Ready for sdd-tasks.
