# Institution Logo Specification

## Purpose

Define how the institution manages its logo: upload, display, removal, and dynamic favicon serving. Single-file media collection on the Institution model, shared globally via Inertia.

## Requirements

### Requirement: Logo Media Collection

The Institution model MUST implement `HasMedia` with `InteractsWithMedia` trait. MUST register a single-file `logo` collection with `thumb` (150×150, fit cover) and `favicon` (32×32, fit cover) conversions. MUST accept MIME types `image/jpeg`, `image/png`, `image/webp`. SHOULD also accept `image/gif`.

#### Scenario: Logo persists after upload

- GIVEN an institution record exists
- WHEN a valid image file is uploaded to the `logo` collection
- THEN the media is stored, conversions are generated, and the record is retrievable on next page load

#### Scenario: Upload replaces previous logo

- GIVEN the institution already has a logo
- WHEN a new logo is uploaded
- THEN the previous media is deleted before storing the new one

### Requirement: Computed Logo Attributes

The Institution model MUST append `logo_url` returning the `thumb` conversion URL (fallback to original URL). SHOULD append `favicon_url` returning the `favicon` conversion URL.

#### Scenario: Logo URL returns thumb

- GIVEN a logo exists
- WHEN `$institution->logo_url` is accessed
- THEN it returns the `thumb` conversion URL, not the original

#### Scenario: No logo returns null

- GIVEN no logo has been uploaded
- WHEN `logo_url` or `favicon_url` is accessed
- THEN both return `null`

### Requirement: Upload Endpoint

The system MUST provide `POST /settings/institution/logo` (named `institution.logo.update`). MUST validate: `logo` required, image, MIMEs `jpeg,png,webp,gif`, max 2MB. MUST delete existing logo before storing. MUST return `back()` with success flash.

#### Scenario: Valid image upload

- GIVEN the user is authenticated and on the institution settings page
- WHEN a valid JPG/PNG/WEBP/GIF file under 2MB is submitted
- THEN the logo is stored, conversions generated, and a success message is shown

#### Scenario: Oversized file rejected

- GIVEN the user is authenticated
- WHEN a file larger than 2MB is submitted
- THEN validation fails with an appropriate error message

#### Scenario: Invalid file type rejected

- GIVEN the user is authenticated
- WHEN a non-image file (PDF, EXE, etc.) is submitted
- THEN validation fails with an appropriate error message

### Requirement: Remove Endpoint

The system MUST provide `DELETE /settings/institution/logo` (named `institution.logo.remove`). MUST delete existing logo media if present. MUST be a no-op if no logo exists. MUST return `back()` with success flash.

#### Scenario: Remove existing logo

- GIVEN the institution has a logo
- WHEN the remove endpoint is called
- THEN the media is deleted, `logo_url` returns null, and fallback text is shown

#### Scenario: Remove when no logo exists

- GIVEN the institution has no logo
- WHEN the remove endpoint is called
- THEN the request succeeds (no-op, no error)

### Requirement: Global Shared Data

`HandleInertiaRequests::share()` MUST include `institution` object with `name`, `logo_url`, `favicon_url`. MUST be null-safe: returns `null` when no institution record exists.

#### Scenario: Institution record exists

- GIVEN a seeded institution record
- WHEN any page loads
- THEN `page.props.institution` contains `name`, `logo_url`, `favicon_url`

#### Scenario: Institution record does not exist

- GIVEN no institution record has been seeded
- WHEN any page loads
- THEN `page.props.institution` is `null`

### Requirement: AppLogo Component

The `app-logo.tsx` component MUST display the institution logo (thumb URL) when available. MUST fall back to institution name text when no logo. MUST fall back to "Amantina App" when no institution record exists.

#### Scenario: Logo available

- GIVEN the institution has a logo uploaded
- WHEN the sidebar or header renders
- THEN the logo image is displayed instead of hardcoded icon

#### Scenario: No logo, institution exists

- GIVEN the institution exists but has no logo
- WHEN the sidebar renders
- THEN the institution name is shown as text (first letter or initials)

#### Scenario: No institution record

- GIVEN no institution record exists
- WHEN the sidebar renders
- THEN the fallback text "Amantina App" is shown

### Requirement: Dynamic Favicon

The system MUST serve the institution logo as the browser favicon via a dedicated route. Response MUST include `Cache-Control: public, max-age=86400` headers. URL MUST include a version parameter for cache busting. MUST fall back to default Laravel favicon when no logo.

#### Scenario: Favicon serves uploaded logo

- GIVEN the institution has a logo
- WHEN a browser requests the favicon endpoint
- THEN the favicon conversion (32×32) is returned with cache headers

#### Scenario: Cache busting on logo change

- GIVEN a browser has cached the old favicon
- WHEN the logo is updated or removed
- THEN the favicon URL version string changes, forcing a re-fetch

### Requirement: Logo Upload UI

The institution settings page MUST include a logo upload section following the `UserAvatar` pattern (preview on selection, Save/Cancel buttons, remove button when logo exists). MUST show accepted formats and size limit.

#### Scenario: Upload widget interaction

- GIVEN the user is on the institution settings page
- WHEN they select a file
- THEN a preview is shown with Guardar/Cancelar buttons; on save, the upload endpoint is called with `forceFormData: true`
