# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger                                                                                                                        | Skill                | Path                                                                 |
| ------------------------------------------------------------------------------------------------------------------------------ | -------------------- | -------------------------------------------------------------------- |
| When creating a GitHub issue, reporting a bug, or requesting a feature                                                         | issue-creation       | C:\Users\pprch\.config\opencode\skills\issue-creation\SKILL.md       |
| When creating a pull request, opening a PR, or preparing changes for review                                                    | branch-pr            | C:\Users\pprch\.config\opencode\skills\branch-pr\SKILL.md            |
| When user says "judgment day", "judgment-day", "review adversarial", "dual review", "doble review", "juzgar", "que lo juzguen" | judgment-day         | C:\Users\pprch\.config\opencode\skills\judgment-day\SKILL.md         |
| When user asks to create a new skill, add agent instructions, or document patterns for AI                                      | skill-creator        | C:\Users\pprch\.config\opencode\skills\skill-creator\SKILL.md        |
| When writing Go tests, using teatest, or adding test coverage                                                                  | go-testing           | C:\Users\pprch\.config\opencode\skills\go-testing\SKILL.md           |
| When writing guides, READMEs, RFCs, onboarding docs, architecture docs, or review-facing documentation                         | cognitive-doc-design | C:\Users\pprch\.config\opencode\skills\cognitive-doc-design\SKILL.md |
| When drafting or posting feedback, review comments, maintainer replies, Slack messages, or GitHub comments                     | comment-writer       | C:\Users\pprch\.config\opencode\skills\comment-writer\SKILL.md       |
| When a PR would exceed 400 changed lines, when planning chained PRs, stacked PRs, or reviewable slices                         | chained-pr           | C:\Users\pprch\.config\opencode\skills\chained-pr\SKILL.md           |
| When implementing a change, preparing commits, splitting PRs, or planning chained or stacked PRs                               | work-unit-commits    | C:\Users\pprch\.config\opencode\skills\work-unit-commits\SKILL.md    |

## Project-Level Skills

| Trigger                                                                                                                                                                                                                                                                                 | Skill                          | Path(s)                                                                                          |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------ | ------------------------------------------------------------------------------------------------ |
| Whenever referencing backend routes in frontend components, importing from @/actions or @/routes, calling Laravel routes from TypeScript, or working with Wayfinder route functions                                                                                                     | wayfinder-development          | .claude/skills/wayfinder-development/SKILL.md                                                    |
| When creating React pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions React with Inertia                                                                                               | inertia-react-development      | .claude/skills/inertia-react-development/SKILL.md                                                |
| When adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes | tailwindcss-development        | .claude/skills/tailwindcss-development/SKILL.md                                                  |
| When implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications                                   | fortify-development            | .claude/skills/fortify-development/SKILL.md                                                      |
| When working with spatie/laravel-medialibrary features including associating files with Eloquent models, defining media collections and conversions, generating responsive images, and retrieving media URLs and paths                                                                  | medialibrary-development       | .claude/skills/medialibrary-development/SKILL.md                                                 |
| When working with Spatie Laravel Permission features, including roles, permissions, middleware, policies, teams, and Blade directives                                                                                                                                                   | laravel-permission-development | .claude/skills/laravel-permission-development/SKILL.md                                           |
| When building Inertia page components, handling forms with useForm, managing shared data, or implementing persistent layouts. Triggers on tasks involving Inertia.js, page props, form handling, or Laravel React integration                                                           | laravel-inertia-react          | .claude/skills/laravel-inertia-react/SKILL.md, skills/laravel-inertia-react/SKILL.md             |
| When user asks to build web components, pages, artifacts, posters, or applications (websites, landing pages, dashboards, React components, HTML/CSS layouts, or when styling/beautifying any web UI)                                                                                    | frontend-design                | .claude/skills/frontend-design/SKILL.md, skills/frontend-design/SKILL.md                         |
| When working with shadcn/ui, component registries, presets, --preset codes, or any project with a components.json file. Also for "shadcn init", "create an app with --preset", or "switch to --preset"                                                                                  | shadcn                         | .claude/skills/shadcn/SKILL.md                                                                   |
| When user asks "how do I do X", "find a skill for X", "is there a skill that can...", or expresses interest in extending agent capabilities                                                                                                                                             | find-skills                    | .claude/skills/find-skills/SKILL.md, skills/find-skills/SKILL.md                                 |
| When writing, reviewing, or refactoring React/Next.js code to ensure optimal performance patterns. Triggers on tasks involving React components, Next.js pages, data fetching, bundle optimization, or performance improvements                                                         | vercel-react-best-practices    | .claude/skills/vercel-react-best-practices/SKILL.md, skills/vercel-react-best-practices/SKILL.md |
| When creating Laravel models, setting up queue workers, implementing Sanctum auth flows, building Livewire components, optimising Eloquent queries, or writing Pest/PHPUnit tests for Laravel features                                                                                  | laravel-specialist             | .claude/skills/laravel-specialist/SKILL.md, skills/laravel-specialist/SKILL.md                   |

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

### chained-pr

- Split large changes into chained or stacked PRs protecting reviewer focus
- Stay within Gentle AI's 400-line cognitive review budget per PR
- First PR: infrastructure (migrations, models, interfaces). Second+: features
- Each PR must be independently reviewable and mergeable

### work-unit-commits

- Structure commits as deliverable work units, not file-type batches
- Tests and docs stay beside the code they verify
- Each commit should answer "what does this deliver?" not "what files changed?"

### vercel-react-best-practices

- Eliminate waterfalls: use Promise.all() for independent operations
- Avoid barrel file imports (200-800ms import cost)
- Use dynamic imports for heavy components
- Prefer derived state over effects for computed values
- Use functional setState updates to prevent stale closures
- Use toSorted() over sort() for immutability with React state
- Config after() for non-blocking operations after response

### laravel-specialist

- Create Eloquent models with proper relationships and return type hints
- Implement Form Request classes for validation
- Build RESTful APIs with API resources
- Use Pest/PHPUnit for feature testing
- Configure queues with ShouldQueue interface

### cognitive-doc-design

- Use progressive disclosure — start with overview, add detail as needed
- Chunk information: one idea per section, short paragraphs
- Use tables for comparisons, checklists for procedures
- Lead with recognition (what they know) before recall (what they need to learn)
- Signpost sections clearly; use consistent terminology throughout

### comment-writer

- Warm and direct, not sarcastic or cold
- Validate the effort before correcting the approach
- Explain WHY something is wrong with technical reasoning, not opinion
- CAPS for emphasis of important points, not for shouting
- Use analogies only when they genuinely clarify the point

## Project Conventions

| File                                 | Path                                                            | Notes                                                                                                  |
| ------------------------------------ | --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| AGENTS.md                            | C:\Desarrollo\amantina-app\AGENTS.md                            | Main convention file — contains Laravel Boost guidelines, testing rules, and project-specific patterns |
| CLAUDE.md                            | C:\Desarrollo\amantina-app\CLAUDE.md                            | Duplicate of AGENTS.md with Laravel Boost guidelines                                                   |
| GEMINI.md                            | C:\Desarrollo\amantina-app\GEMINI.md                            | Duplicate of AGENTS.md with Laravel Boost guidelines                                                   |
| ia_docs/bitacora_especificaciones.md | C:\Desarrollo\amantina-app\ia_docs\bitacora_especificaciones.md | Business specs and requirements for Bitácora Socioproductiva                                           |
| ia_docs/amantina_implementacion.md   | C:\Desarrollo\amantina-app\ia_docs\amantina_implementacion.md   | Implementation milestones and architecture decisions                                                   |
| fresh-setup.md                       | C:\Desarrollo\amantina-app\fresh-setup.md                       | Fresh local setup instructions with CompleteTestDataSeeder                                             |
| openspec/config.yaml                 | C:\Desarrollo\amantina-app\openspec\config.yaml                 | Existing SDD config from previous initialization                                                       |

Read the convention files listed above for project-specific patterns and rules.
