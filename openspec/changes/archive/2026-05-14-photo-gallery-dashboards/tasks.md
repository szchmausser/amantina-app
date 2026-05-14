# Tasks: Photo Gallery on Dashboards

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~420-450 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1: Backend → PR 2: Student + Admin views → PR 3: Profile page → PR 4: Tests |
| Delivery strategy | ask-on-risk |
| Chain strategy | feature-branch-chain |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Backend: photo data in all 3 payloads | PR 1 | Base: `feature/photo-gallery-dashboards` tracker branch. Backend foundation + types. ~50-60 lines |
| 2 | Student dashboard + Admin user show | PR 2 | Base: PR 1 branch. Reuse same 3-state gallery + clickable badge pattern. ~100 lines |
| 3 | Profile page: new hourHistory rendering section | PR 3 | Base: PR 2 branch. Most complex — new JSX section + gallery wiring. ~80 lines |
| 4 | Feature + browser tests | PR 4 | Base: PR 3 branch. 3 feature tests + 3 browser tests. ~200 lines |

## Phase 1: Backend — Photo Data in Payloads

- [x] 1.1 `app/Services/HourAccumulatorService.php`: Add `id` to `attendance_activities` SELECT; add photos sub-query JOIN (model_type = `App\Models\AttendanceActivity`) grouped by `attendance_id`; map `photos[]` into `sessionHistory[].activities[]`
- [x] 1.2 `app/Http/Controllers/Settings/ProfileController.php`: Add `->with('attendanceActivities.media')` to attendance query; map `$act->getMedia('evidence_photos')` as `photos[]` in activity mapping
- [x] 1.3 `app/Http/Controllers/Admin/UserController.php`: Same as 1.2 — add eager load + `photos[]` via `getMedia('evidence_photos')`

## Phase 2: TypeScript Interfaces

- [x] 2.1 `resources/js/types/dashboard.ts`: Add optional `photos?: { id: number; url: string; name: string }[]` to `sessionHistory[].activities[]` interface; also `admin/users/show.tsx` and `settings/profile.tsx` HourHistoryActivity interfaces

## Phase 3: Student Dashboard — Clickable Badges

- [ ] 3.1 `resources/js/pages/student/dashboard.tsx`: Import `MediaGallery` from `@/components/media-gallery`; add `galleryOpen`, `galleryItems`, `galleryIndex` state (useState); replace `<span>` badge (line 451) with `<Badge>` component using conditional `cursor-pointer` + camera SVG icon when `photos.length > 0`; onClick sets gallery items/index and opens; add `<MediaGallery>` at component bottom (before closing `</TooltipProvider>`)

## Phase 4: Admin User Show — Clickable Badges

- [ ] 4.1 `resources/js/pages/admin/users/show.tsx`: Add `photos?: { id: number; url: string; name: string }[]` to `HourHistoryActivity` interface; import `MediaGallery`; add gallery state; make activity `<Badge>` (line 1400) conditional clickable with camera icon when photos present; add `<MediaGallery>` before closing `</TabsContent>` of the "horas" tab

## Phase 5: Profile Page — Hour History Rendering

- [ ] 5.1 `resources/js/pages/settings/profile.tsx`: Add `photos?` to `HourHistoryActivity` interface; import `MediaGallery`; add gallery state; add a new `{isAlumno && hourHistory && (...)}` tab/panel under existing tabs rendering sessions grouped with name/date/location + activity badges; make badges clickable with camera icon when `photos.length > 0`; add `<MediaGallery>` at component bottom; show "No has registrado asistencia aún" when history empty

## Phase 6: Tests

- [x] 6.1 Feature test: `HourAccumulatorService` — create attendance with activity + media photo, call `getStudentDashboard()`, assert `sessionHistory[0].activities[0].photos` is non-empty array with expected shape; also empty photos case triangulation
- [x] 6.2 Feature test: `ProfileController::edit()` and `Admin/UserController::show()` — assert `hourHistory[].activities[].photos` is present when alumno has evidence photos
- [ ] 6.3 Browser test: Student dashboard — visit as alumno with photo evidence, click badge, assert MediaGallery opens; visit alumno without photos, assert badge is not clickable (no `cursor-pointer`)
- [ ] 6.4 Browser test: Admin user show — visit Hours tab, click badge with photos, assert gallery opens; badge without photos is inert
- [ ] 6.5 Browser test: Profile page — visit profile as alumno, verify hour history section renders, click badge to open gallery; non-alumno sees no hour history section
