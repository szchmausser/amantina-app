# User Profile — Photo Gallery Specification

## Purpose

Students viewing their own profile can see their hour history (previously sent but not rendered) with clickable photo badges and a MediaGallery lightbox, matching the admin field-session show pattern.

## Requirements

### Requirement: Profile renders hour history section

The profile page MUST render a new section displaying the student's hour history when `isAlumno` is true. The data is already sent from `ProfileController::edit()` as the `hourHistory` prop but was previously unused in JSX.

#### Scenario: Hour history section appears for students

- GIVEN an authenticated alumno user on their profile page
- WHEN the page loads
- THEN a visually distinct section SHALL display attendance sessions grouped by academic year
- AND each session SHALL show its name, date, location, and activity badges with hours

#### Scenario: Non-students do not see hour history

- GIVEN a user without the alumno role
- WHEN viewing their profile
- THEN the hour history section SHALL NOT render

#### Scenario: Empty hour history shows placeholder

- GIVEN an alumno with no attendance records
- WHEN viewing their profile
- THEN the hour history section SHALL display an empty state message: "No has registrado asistencia aún"

### Requirement: Profile hour history activities include photo metadata

The `ProfileController::edit()` response MUST include a `photos` array on each activity within `hourHistory[].activities[]`, added via `$act->getMedia('evidence_photos')`.

| Field | Type | Description |
|-------|------|-------------|
| `id` | `number` | Media record ID |
| `url` | `string` | Absolute URL via `getUrl()` |
| `name` | `string` | Original file name |

#### Scenario: Photo count appears on activity badges

- GIVEN an alumno with photos on some activities
- WHEN the hour history section renders
- THEN each Badge with photos SHALL show a camera icon with the photo count
- AND Badges without photos SHALL NOT show the icon

#### Scenario: HourHistoryActivity interface extended with photos

- GIVEN the profile page's `HourHistoryActivity` TypeScript interface
- WHEN the page receives props
- THEN each activity SHALL have an optional `photos` array typed as `{ id: number; url: string; name: string }[]`

### Requirement: Gallery state and MediaGallery render

The profile page MUST include gallery state (`galleryOpen`, `galleryItems`, `galleryIndex`) and render `<MediaGallery>` at the component bottom.

#### Scenario: Clicking photo badge opens gallery

- GIVEN an alumno viewing their profile hour history
- WHEN they click an activity Badge that has `photos.length > 0`
- THEN the MediaGallery lightbox SHALL open showing that activity's photos
- AND keyboard navigation and close-on-escape SHALL work

#### Scenario: Badge without photos is non-clickable

- GIVEN an activity with zero photos
- WHEN the user clicks the badge
- THEN no gallery SHALL open

## Scenarios Summary

| Type | Count |
|------|-------|
| Happy path | 2 |
| Edge cases | 3 |
| Error states | 0 |
