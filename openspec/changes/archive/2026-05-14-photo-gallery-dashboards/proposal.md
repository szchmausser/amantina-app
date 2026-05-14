# Proposal: Photo Gallery on Dashboard Views

## Intent

Clickable photo evidence gallery currently exists only on the admin field-session show page. Replicate the same pattern on 3 additional views so students, representatives, and admins can view evidence photos attached to activity badges — without upload/delete capabilities (those remain permission-protected).

## Scope

### In Scope
1. **HourAccumulatorService** — add `photos` to sessionHistory activities (raw DB, separate media query)
2. **ProfileController** — add `photos` to hourHistory activities via `getMedia('evidence_photos')`
3. **UserController** — add `photos` to hourHistory activities via `getMedia('evidence_photos')`
4. **Student dashboard** (student/dashboard.tsx) — replace `<span>` with clickable badge + photo icon + MediaGallery
5. **Admin user show** (admin/users/show.tsx) — add click handler + photo icon to existing activity `<Badge>` +
   MediaGallery
6. **User profile** (settings/profile.tsx) — add hourHistory section (data already sent, not rendered) with
   clickable badges + MediaGallery
7. **TypeScript types** — extend activity interfaces with `photos: { id, url, name }[]`

### Out of Scope
- Teacher dashboard (aggregated stats, no individual activities)
- Photo upload/delete (already protected in AttendanceActivityController)
- Representative dashboard (hourly data is per-student card, not activity badges)
- PDF reports (StudentPdfController, separate treatment)

## Capabilities

### New Capabilities
- None

### Modified Capabilities
- None

> Pure feature enhancement — no spec-level behavior changes. All controllers/pages already send and render the activity data; we're adding photo metadata and gallery interactivity.

## Approach

**Backend** — 3 changes:
- `HourAccumulatorService`: Query `media` table directly (JOIN on `attendance_activities.id` WHERE `model_type='App\Models\AttendanceActivity'` AND `collection_name='evidence_photos'`), group by `attendance_id`, merge into sessionHistory activities
- `ProfileController`: Add `photos` => `$act->getMedia('evidence_photos')->map(fn($m) => ['id','url','name'])`
- `UserController`: Same pattern as ProfileController

**Frontend** — per-page pattern (same as field-sessions/show.tsx):
1. 3-state: `galleryOpen`, `galleryItems`, `galleryIndex`
2. Clickable badge: `onClick` opens gallery if `act.photos.length > 0`, camera SVG icon with count
3. `<MediaGallery>` at component bottom

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Services/HourAccumulatorService.php` | Modified | Add photos query, merge into sessionHistory activities |
| `app/Http/Controllers/Settings/ProfileController.php` | Modified | Add `photos` to activity map (2 lines) |
| `app/Http/Controllers/Admin/UserController.php` | Modified | Add `photos` to activity map (2 lines) |
| `resources/js/pages/student/dashboard.tsx` | Modified | Gallery state + clickable badges + MediaGallery |
| `resources/js/pages/admin/users/show.tsx` | Modified | Photo icon on Badge + gallery state + MediaGallery |
| `resources/js/pages/settings/profile.tsx` | Modified | HourHistory section rendering + gallery state + MediaGallery |
| `resources/js/types/dashboard.ts` | Modified | Add `photos` to activity shape in sessionHistory |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Profile page missing hourHistory rendering (data exists, no UI) | High | Needs full section render + gallery; largest scope for this page |
| HourAccumulatorService raw query — Eloquent model not loaded | Medium | Separate JOIN on `media` table, same as evidenceCount pattern (line 1264) |
| N+1 photo queries in Eloquent controllers | Low | `attendanceActivities` already eager-loaded, `getMedia()` is cached per model |
| Photo data too large for Inertia props | Low | Photos limited by attendance activity count (typically < 50 props per page) |

## Rollback Plan

Per-file revert: undo controller mapping changes (remove `photos` keys), revert JSX to pre-gallery state (remove `galleryOpen/galleryItems/galleryIndex` state and `<MediaGallery>`). Each page is independent.

## Dependencies

None. Fully self-contained within existing codebase.

## Success Criteria

- [ ] Student dashboard activity badges show photo count icon and open MediaGallery on click
- [ ] Admin user show activity badges show photo count icon and open MediaGallery on click
- [ ] User profile shows hourHistory with clickable photo badges and MediaGallery
- [ ] All existing tests pass (700 tests)
- [ ] Zero regressions in field-sessions gallery (existing reference implementation unchanged)
