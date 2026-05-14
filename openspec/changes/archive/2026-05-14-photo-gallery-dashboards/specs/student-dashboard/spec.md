# Student Dashboard — Photo Gallery Specification

## Purpose

Students and representatives viewing the student dashboard can inspect photo evidence attached to activities in the session history, using the same MediaGallery lightbox pattern already available on the admin field-session show page.

## Requirements

### Requirement: Session history activities include photo metadata

The system MUST include a `photos` array on each activity object within `sessionHistory[].activities[]`.

| Field | Type | Description |
|-------|------|-------------|
| `id` | `number` | Media record ID |
| `url` | `string` | Absolute URL to the photo file |
| `name` | `string` | Original file name |

#### Scenario: Student sees photo count on activity badges

- GIVEN a student with photos attached to one or more attendance activities
- WHEN the dashboard loads session history
- THEN each activity badge with `photos.length > 0` SHALL display a camera icon and the photo count
- AND activities with zero photos SHALL NOT display the icon

#### Scenario: Clicking a badge with photos opens the gallery

- GIVEN a student viewing the dashboard
- WHEN they click an activity badge that has `photos.length > 0`
- THEN a full-screen MediaGallery lightbox SHALL open showing the first photo from that activity
- AND the gallery SHALL support keyboard navigation (ArrowLeft/ArrowRight) and Escape to close

#### Scenario: Clicking a badge without photos does nothing

- GIVEN a student viewing the dashboard
- WHEN they click an activity badge that has `photos.length === 0`
- THEN no gallery SHALL open

#### Scenario: Empty photo array is handled gracefully

- GIVEN the student has no photos across any activity
- WHEN the dashboard loads
- THEN no gallery state is triggered and no camera icons appear

### Requirement: Evidence query uses raw DB JOIN

The `HourAccumulatorService::getStudentDashboard()` method MUST query the `media` table directly via a JOIN on `attendance_activities.id` filtered by `model_type = 'App\\Models\\AttendanceActivity'`, grouped by `attendance_id`, and merged into each activity in the session history response.

#### Scenario: Photos are grouped per attendance activity

- GIVEN a student with multiple photos across different activities
- WHEN the dashboard loads
- THEN each activity receives only its own photos, not photos from other activities

### Requirement: Activity interface extended with photos

The TypeScript type for session history activities (`StudentDashboardData.sessionHistory[].activities[]`) MUST include the `photos` field.

#### Scenario: Type definition supports the new field

- GIVEN the `StudentDashboardData` interface
- WHEN the dashboard component receives props
- THEN the `activities` items SHALL have an optional `photos` array typed as `{ id: number; url: string; name: string }[]`

## Scenarios Summary

| Type | Count |
|------|-------|
| Happy path | 2 |
| Edge cases | 3 |
| Error states | 0 |
