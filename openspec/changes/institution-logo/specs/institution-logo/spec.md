# Delta for Institution Logo

## ADDED Requirements

### Requirement: Logo Media Collection

The Institution model MUST implement `HasMedia` with `InteractsWithMedia`. MUST register a single-file `logo` collection with `thumb` (150×150) and `favicon` (32×32) conversions. MUST accept `image/jpeg`, `image/png`, `image/webp`. SHOULD accept `image/gif`.

#### Scenario: Logo upload persists

- GIVEN an institution record exists
- WHEN a valid image is uploaded to the `logo` collection
- THEN the media is stored with conversions and retrievable on reload

#### Scenario: Upload replaces existing logo

- GIVEN the institution already has a logo
- WHEN a new logo is uploaded
- THEN the previous media is deleted before storing

### Requirement: Computed Logo Attributes

The Institution model MUST append `logo_url` (thumb, fallback to original). SHOULD append `favicon_url` (favicon conversion). Both MUST return `null` when no logo exists.

### Requirement: Upload Endpoint

`POST /settings/institution/logo` (named `institution.logo.update`). Validates: `logo` required, image, MIMEs `jpeg,png,webp,gif`, max 2MB. Replaces existing logo. Returns `back()` with success flash.

#### Scenario: Valid image upload

- GIVEN authenticated user on institution settings
- WHEN a valid image under 2MB is submitted
- THEN logo is stored and success message shown

#### Scenario: Oversized file

- GIVEN authenticated user
- WHEN a file >2MB is submitted
- THEN validation error returned

#### Scenario: Invalid file type

- GIVEN authenticated user
- WHEN a non-image file is submitted
- THEN validation error returned

### Requirement: Remove Endpoint

`DELETE /settings/institution/logo` (named `institution.logo.remove`). Deletes existing logo; no-op if none. Returns `back()` with success flash.

#### Scenario: Remove existing logo

- GIVEN institution has a logo
- WHEN remove endpoint called
- THEN media deleted, `logo_url` null, fallback text shown

#### Scenario: Remove when none exists

- GIVEN institution has no logo
- WHEN remove endpoint called
- THEN no-op, request succeeds

### Requirement: Global Shared Data

`HandleInertiaRequests::share()` MUST include `institution { name, logo_url, favicon_url }`. MUST be `null` when no institution record exists.

#### Scenario: Institution exists

- GIVEN a seeded institution
- WHEN any page loads
- THEN `page.props.institution` has `name`, `logo_url`, `favicon_url`

#### Scenario: No institution record

- GIVEN no institution record seeded
- WHEN any page loads
- THEN `page.props.institution` is `null`

### Requirement: AppLogo Component

`app-logo.tsx` MUST display logo (thumb) when available. Fallback: institution name text. Fallback: "Amantina App" when no institution record.

### Requirement: Dynamic Favicon

Dedicated route serving logo favicon conversion (32×32) with `Cache-Control: public, max-age=86400`. URL includes version param for cache busting. Falls back to default favicon when no logo.

#### Scenario: Favicon serves logo

- GIVEN institution has a logo
- WHEN browser requests favicon
- THEN 32×32 conversion returned with cache headers

#### Scenario: Cache busting

- GIVEN browser cached old favicon
- WHEN logo updated or removed
- THEN favicon URL version changes, forcing re-fetch

### Requirement: Logo Upload UI

Institution settings page MUST include logo upload (UserAvatar pattern): preview on select, Save/Cancel buttons, remove button when logo exists. Shows accepted formats.

#### Scenario: Upload widget

- GIVEN user on institution settings
- WHEN they select a file
- THEN preview shown with Guardar/Cancelar; on save, `forceFormData: true`
