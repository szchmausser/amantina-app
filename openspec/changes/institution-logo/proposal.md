# Proposal: Institution Logo

## Intent

Replace hardcoded "Laravel Starter Kit" with the institution's own name and logo, uploaded via existing settings UI. Logo also serves as dynamic favicon.

## Scope

### In Scope

- Media Library `logo` collection on Institution (single-file, `thumb` + `favicon` conversions)
- Logo upload/remove endpoints on InstitutionController (replicating User::avatar)
- Institution data (name, logo_url) shared globally via HandleInertiaRequests
- `app-logo.tsx` renders institution name + logo
- Logo upload widget in institution settings form
- Dynamic favicon via dedicated route

### Out of Scope

- Multi-tenant branding (single-institution system)
- Image cropping/editing UI, watermarking, advanced transforms

## Capabilities

### New Capabilities

- `institution-logo`: Logo upload, display, removal, favicon serving

### Modified Capabilities

- None

## Approach

1. **Model**: `HasMedia`/`InteractsWithMedia` on Institution, single-file `logo` collection with `thumb` (150x150) and `favicon` (32x32) conversions
2. **Controller**: `updateLogo()` + `removeLogo()` on InstitutionController, same pattern as ProfileController::updateAvatar/removeAvatar
3. **Routes**: `POST|DELETE /settings/institution/logo`
4. **Shared data**: `institution` with `name`, `logo_url`, `favicon_url` in `HandleInertiaRequests::share()`
5. **Frontend**: Rewrite `app-logo.tsx` to consume shared institution; add logo upload with preview to institution settings page
6. **Favicon**: Route returning logo media with cache headers

## Affected Areas

| Area | Impact | Summary |
|------|--------|---------|
| `app/Models/Institution.php` | Modified | Media traits + logo collection |
| `app/Http/.../InstitutionController.php` | Modified | Logo upload/remove methods |
| `routes/settings.php` | Modified | Logo POST/DELETE routes |
| `app/.../HandleInertiaRequests.php` | Modified | Share institution globally |
| `resources/js/components/app-logo.tsx` | Modified | Institution name + logo |
| `resources/js/Pages/settings/institution.tsx` | Modified | Logo upload widget + preview |
| `routes/` + new controller | New | Dynamic favicon endpoint |
| `tests/` | New | Feature & Browser tests |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Conversion fails without GD/Imagick | Low | Already proven by User::avatar |
| Browser caches old favicon | Medium | Version string on favicon URL |
| Institution null before seeding | Low | Null-safe handling in share + components |
| Form enctype for file upload | Low | Separate upload route (same as avatar pattern) |

## Rollback Plan

Revert Institution model, remove logo routes/controller methods, restore `app-logo.tsx` to hardcoded, remove institution from HandleInertiaRequests, delete favicon route, rebuild assets.

## Dependencies

- `spatie/laravel-medialibrary`, GD (already installed)
- No new packages required

## Success Criteria

- [ ] Upload logo → persists on reload, displays in sidebar/header
- [ ] Remove logo → image gone, name fallback shown
- [ ] Favicon reflects uploaded logo
- [ ] All existing tests pass (700+)
