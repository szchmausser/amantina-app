# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger                                                                                                                        | Skill          | Path                                                  |
| ------------------------------------------------------------------------------------------------------------------------------ | -------------- | ----------------------------------------------------- |
| When creating a GitHub issue, reporting a bug, or requesting a feature                                                         | issue-creation | C:\Users\pprch\.claude\skills\issue-creation\SKILL.md |
| When creating a pull request, opening a PR, or preparing changes for review                                                    | branch-pr      | C:\Users\pprch\.claude\skills\branch-pr\SKILL.md      |
| When user asks to create a new skill, add agent instructions, or document patterns for AI                                      | skill-creator  | C:\Users\pprch\.claude\skills\skill-creator\SKILL.md  |
| When writing Go tests, using teatest, or adding test coverage                                                                  | go-testing     | C:\Users\pprch\.claude\skills\go-testing\SKILL.md     |
| When user says "judgment day", "judgment-day", "review adversarial", "dual review", "doble review", "juzgar", "que lo juzguen" | judgment-day   | C:\Users\pprch\.claude\skills\judgment-day\SKILL.md   |

## Project-Level Skills

| Trigger                                                                                                                                                     | Skill                          | Path                                                                              |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------ | --------------------------------------------------------------------------------- |
| When user asks to build web components, pages, artifacts, posters, or applications                                                                          | frontend-design                | C:\Desarrollo\amantina-app\.agents\skills\frontend-design\SKILL.md                |
| When working with shadcn/ui, component registries, presets                                                                                                  | shadcn                         | C:\Desarrollo\amantina-app\.agents\skills\shadcn\SKILL.md                         |
| Triggers on tasks involving Inertia.js, page props, form handling, or Laravel React integration                                                             | laravel-inertia-react          | C:\Desarrollo\amantina-app\.agents\skills\laravel-inertia-react\SKILL.md          |
| When user asks "how do I do X", "find a skill for X", "is there a skill for X"                                                                              | find-skills                    | C:\Desarrollo\amantina-app\.agents\skills\find-skills\SKILL.md                    |
| Build and work with Spatie Laravel Permission features                                                                                                      | laravel-permission-development | C:\Desarrollo\amantina-app\.agents\skills\laravel-permission-development\SKILL.md |
| Build and work with spatie/laravel-medialibrary features                                                                                                    | medialibrary-development       | C:\Desarrollo\amantina-app\.agents\skills\medialibrary-development\SKILL.md       |
| Laravel Fortify headless authentication backend development                                                                                                 | fortify-development            | C:\Desarrollo\amantina-app\.agents\skills\fortify-development\SKILL.md            |
| When adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders | tailwindcss-development        | C:\Desarrollo\amantina-app\.agents\skills\tailwindcss-development\SKILL.md        |
| When creating React pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching                         | inertia-react-development      | C:\Desarrollo\amantina-app\.agents\skills\inertia-react-development\SKILL.md      |
| Whenever referencing backend routes in frontend components, importing from @/actions or @/routes, calling Laravel routes from TypeScript                    | wayfinder-development          | C:\Desarrollo\amantina-app\.agents\skills\wayfinder-development\SKILL.md          |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### laravel-permission-development

- Users have Roles, Roles have Permissions, Apps check Permissions (not Roles)
- Direct permissions on users are anti-pattern; assign permissions to roles instead
- Use `$user->can('permission-name')` for all authorization checks (supports Super Admin via Gate)
- The `HasRoles` trait (which includes `HasPermissions`) is added to User models

### medialibrary-development

- Use spatie/laravel-medialibrary to associate files with Eloquent models
- Activate when working with file uploads, media attachments, or image processing in Laravel
- Supports image/video conversions, responsive images, multiple collections, and various storage disks

### fortify-development

- Fortify is a headless authentication backend for Laravel applications
- Check config/fortify.php for all options including features, guards, rate limiters
- Look in app/Actions/Fortify/ for customizable business logic
- Use search-docs for detailed Laravel Fortify patterns and documentation

### wayfinder-development

- Activate whenever referencing backend routes in frontend components
- Import from `@/actions/` (controllers) or `@/routes/` (named routes)
- Use `.form()` with `<Form>` component or `form.submit(store())` with useForm

### tailwindcss-development

- Use Tailwind CSS v4 utilities for styling
- Always use search-docs tool for version-specific Tailwind CSS documentation
- Check and follow existing Tailwind conventions in the project before introducing new patterns

### inertia-react-development

- Create React page components in resources/js/pages directory
- Use useForm hook for form handling
- Use search-docs for detailed Inertia v2 React patterns and documentation
- v2 features: deferred props, prefetching, WhenVisible, InfiniteScroll, once props, flash data, polling

### laravel-inertia-react

- Use useForm hook for all form handling in React
- Type props using TypeScript interfaces extending PageProps
- Use route() helper from Ziggy, never hardcode URLs
- Display Laravel validation errors inline with proper UX

### frontend-design

- Commit to a BOLD aesthetic direction before coding
- Choose distinctive typography, avoid generic fonts
- Compose components rather than reinvent; use existing UI patterns
- Production-grade, visually striking, and memorable

### shadcn

- Use existing components first; check registry before writing custom UI
- Compose, don't reinvent; Settings = Tabs + Card + form controls
- Use built-in variants before custom styles (variant="outline", size="sm")
- Use semantic colors (bg-primary, text-muted-foreground)

### go-testing

- Use table-driven tests for multiple test cases
- Use teatest for Bubbletea TUI testing
- Use golden file testing for complex outputs
- Built-in support for integration tests with net/http/httptest

### issue-creation

- Blank issues are disabled — MUST use a template (bug report or feature request)
- Every issue gets status:needs-review automatically
- A maintainer MUST add status:approved before any PR can be opened
- Questions go to Discussions, not issues

### branch-pr

- Every PR MUST link an approved issue — no exceptions
- Every PR MUST have exactly one type:\* label
- Automated checks must pass before merge is possible
- Blank PRs without issue linkage will be blocked by GitHub Actions

### skill-creator

- Create a skill when: pattern used repeatedly, project-specific conventions differ, complex workflows need step-by-step instructions
- Don't create a skill when: documentation already exists, pattern trivial, one-off task

### judgment-day

- Launch two independent blind judge sub-agents simultaneously
- Iterate until both pass or escalate after 2 iterations
- Use for: high-confidence review of code/features/architecture

## Project Conventions

| File      | Path                                 | Notes                                                                                                  |
| --------- | ------------------------------------ | ------------------------------------------------------------------------------------------------------ |
| AGENTS.md | C:\Desarrollo\amantina-app\AGENTS.md | Main convention file — contains Laravel Boost guidelines, testing rules, and project-specific patterns |

Read the convention files listed above for project-specific patterns and rules. All referenced paths have been extracted — no need to read index files to discover more.
