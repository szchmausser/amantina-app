# Admin User Show — Photo Gallery Specification

## Purpose

Administrators viewing a student's detail page can inspect photo evidence attached to activities in the Hours tab, using the same MediaGallery lightbox pattern from the admin field-session show page.

## Requirements

### Requirement: Hour history activities include photo metadata

The `UserController::show()` response MUST include a `photos` array on each activity within `hourHistory[].activities[]`.

| Field | Type | Description |
|-------|------|-------------|
| `id` | `number` | Media record ID |
| `url` | `string` | Absolute URL via `getUrl()` |
| `name` | `string` | Original file name |

#### Scenario: Admin sees photo count on activity badges

- GIVEN an admin user viewing a student who has photos on attendance activities
- WHEN the Hours tab renders
- THEN each activity Badge with `photos.length > 0` SHALL display a camera icon and photo count
- AND activities without photos SHALL NOT display the icon

#### Scenario: Clickable badge opens MediaGallery

- GIVEN an admin viewing the Hours tab
- WHEN they click an activity Badge that has photos
- THEN the MediaGallery lightbox SHALL open with that activity's photos
- AND the admin can navigate between photos with prev/next controls and keyboard

#### Scenario: Badge without photos is non-clickable

- GIVEN a student with no photos on a specific activity
- WHEN the admin views that activity's Badge
- THEN the Badge SHALL NOT be clickable and SHALL NOT show a camera icon

### Requirement: Photos added via Eloquent `getMedia()`

The `UserController::show()` MUST use `$act->getMedia('evidence_photos')` to fetch photos for each activity in the `attendanceActivities` relation (already eager-loaded).

#### Scenario: N+1 is avoided

- GIVEN the `attendanceActivities` relation is eager-loaded on the Attendance query
- WHEN `getMedia()` is called per activity
- THEN Spatie's media library cache prevents additional queries per model

### Requirement: Gallery state added to component

The admin user show component MUST include three state variables: `galleryOpen` (boolean), `galleryItems` (array of `{id, url, name}`), and `galleryIndex` (number). The `<MediaGallery>` component MUST be rendered at the bottom of the component tree.

#### Scenario: Gallery renders at component bottom

- GIVEN the admin user show page loads
- WHEN the Hours tab is active and a badge with photos is clicked
- THEN the MediaGallery SHALL render and display the selected activity's photos

## Scenarios Summary

| Type | Count |
|------|-------|
| Happy path | 2 |
| Edge cases | 2 |
| Error states | 0 |
