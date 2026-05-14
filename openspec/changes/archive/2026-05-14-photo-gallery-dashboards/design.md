# Design: Photo Gallery on Dashboards

## Technical Approach

Add clickable photo evidence badges to activity items in 3 existing views by: (1) including `photos` arrays in the backend data payloads, and (2) replicating the React state/gallery pattern from `admin/field-sessions/show.tsx` — 3 pieces of gallery state (`galleryOpen`, `galleryItems`, `galleryIndex`) + `MediaGallery` component + conditional camera icon on badges.

The 3 views have different backend data sources:
- **Student dashboard**: raw DB queries in `HourAccumulatorService::getStudentDashboard()` — needs manual JOIN on `media` table
- **Profile page** and **Admin user show**: Eloquent models — can use `getMedia('evidence_photos')`

## Architecture Decisions

| Decision | Choice | Alternatives | Rationale |
|----------|--------|--------------|-----------|
| Photo fetch strategy | Sub-query JOIN in raw DB; `getMedia()` in Eloquent | Unify all to raw DB | Service already uses raw queries for student dashboard; Eloquent controllers get free N+1 protection via `with()` |
| Gallery state scope | Local `useState` per page, 3 vars | Shared hook/context | Only 3 views, state is trivial. Avoid premature abstraction. |
| Activity `id` inclusion | Add `id` to raw DB query select | Skip and use composite key | `id` is needed for `ActivityBadge key` in React and for photo JOIN matching |

## Data Flow

```
Backend (3 sources):
  HourAccumulatorService   ──→ sessionHistory[].activities[].photos[]
  ProfileController          ──→ hourHistory[].activities[].photos[]
  Admin\UserController       ──→ hourHistory[].activities[].photos[]

Frontend (3 pages):
  student/dashboard.tsx       → clickable badges → setGalleryItems/photos
  settings/profile.tsx        → clickable badges → setGalleryItems/photos
  admin/users/show.tsx        → clickable badges → setGalleryItems/photos

All 3 → <MediaGallery open items initialIndex />
```

### Media JOIN pattern (HourAccumulatorService)

```php
// Fetch photos per activity_id (same pattern as evidenceCount, lines 1264-1271)
$photos = DB::table('media')
    ->where('media.model_type', 'App\Models\AttendanceActivity')
    ->whereIn('media.model_id', $activityIds)
    ->whereNull('media.deleted_at')  // media uses soft deletes
    ->select('media.id', 'media.model_id', 'media.name', 'media.file_name')
    ->get()
    ->groupBy('model_id');
```

Then at mapping time:
```php
'photos' => ($photos->get($act->id) ?? collect())->map(fn ($m) => [
    'id' => $m->id,
    'url' => Storage::url($m->id . '/' . $m->file_name), // or resolve via Media model
    'name' => $m->name,
])->values()->toArray(),
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Services/HourAccumulatorService.php` | Modify | Add `id` to attendance_activities select (line ~1193); add sub-query JOIN for photos; map photos into sessionHistory activities |
| `app/Http/Controllers/Settings/ProfileController.php` | Modify | Add `photos` via `getMedia('evidence_photos')` in attendanceActivities mapping (~lines 139-143) |
| `app/Http/Controllers/Admin/UserController.php` | Modify | Add `photos` via `getMedia('evidence_photos')` in attendanceActivities mapping (~lines 315-319) |
| `resources/js/types/dashboard.ts` | Modify | Add `photos` to `sessionHistory.activities` interface |
| `resources/js/pages/student/dashboard.tsx` | Modify | Add gallery state + import + clickable badges + MediaGallery |
| `resources/js/pages/settings/profile.tsx` | Modify | Add hourHistory rendering with clickable badges + gallery state + MediaGallery import |
| `resources/js/pages/admin/users/show.tsx` | Modify | Add `photos?` to `HourHistoryActivity` interface + gallery state + clickable badges + MediaGallery |

## Interfaces / Contracts

### Student dashboard activity (types/dashboard.ts)

```typescript
interface SessionActivity {
    categoryName: string;
    hours: number;
    photos?: { id: number; url: string; name: string }[];
}
```

### Admin user show HourHistoryActivity & Profile

```typescript
interface HourHistoryActivity {
    id: number;
    hours: number;
    activity_category: string | null;
    photos?: { id: number; url: string; name: string }[];
}
```

### Gallery state (replicated in 3 pages)

```typescript
const [galleryOpen, setGalleryOpen] = useState(false);
const [galleryItems, setGalleryItems] = useState<{ id: number; url: string; name: string }[]>([]);
const [galleryIndex, setGalleryIndex] = useState(0);
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | HourAccumulatorService returns photos in sessionHistory | Create attendance with activity + media, call `getStudentDashboard()`, assert photos array is non-empty |
| Feature | ProfileController returns photos in hourHistory | Auth as alumno, visit profile, assert response has `photos` in activities |
| Feature | Admin UserController returns photos in hourHistory | Auth as admin, visit user show, assert response has `photos` in activities |
| Browser | Click activity badge opens gallery | Create attendance with photo, visit page, click badge, assert MediaGallery is visible |
| Browser | Activity badge without photos is not clickable | Create attendance without photo, visit page, assert no cursor-pointer |

## Migration / Rollout

No migration required. Data is already in `media` table (`model_type = App\Models\AttendanceActivity`). This is purely a read-side change.

## Open Questions

- [ ] Confirm `media.name` is populated (vs using `file_name` as display name)
- [ ] Confirm `media` table uses soft deletes (needs `whereNull('media.deleted_at')`)
- [ ] Profile page hourHistory currently unused in JSX — must add rendering section under the Salud tab or add a new "Historial de Horas" tab for alumnos
