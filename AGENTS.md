# Conventions

This application is built on Taneship. This file holds its conventions, for the developers and the coding agents who work on it. It belongs to the project: add your own conventions to it, in the same terms, as the project grows.

## Stack

| Concern               | Choice                                                                         |
| --------------------- | ------------------------------------------------------------------------------ |
| Runtime               | PHP 8.5                                                                        |
| Framework             | Laravel 13                                                                     |
| Server–client bridge  | Inertia 3 (`inertiajs/inertia-laravel`, `@inertiajs/react`, `@inertiajs/vite`) |
| UI library            | React 19, with the React Compiler                                              |
| Rendering             | Server-side rendering through Inertia, on by default                           |
| Language              | TypeScript 7                                                                   |
| Styling               | Tailwind CSS 4                                                                 |
| Components            | shadcn/ui on Base UI, with Lucide icons                                        |
| Toolchain             | Vite+ 1 (Vite, Vitest, Oxlint, Oxfmt)                                          |
| Typed routes          | Laravel Wayfinder 0.1 (beta)                                                   |
| Tests                 | Pest 5, with the Laravel, browser and type coverage plugins, on Playwright     |
| Static analysis       | Larastan 3, level 10                                                           |
| Formatting            | Laravel Pint                                                                   |
| Automated refactoring | Rector 2, with `driftingly/rector-laravel`                                     |
| Agent tooling         | Laravel Boost, its MCP server only                                             |
| JavaScript runtime    | Node.js 24, with npm 11.16 or later                                            |

- **No Laravel Fortify.** It publishes action classes into `app/` and drives behavior through configuration. Authentication is written in actions and controllers.
- **Dependencies are justified.** Every package earns its place, and packages that publish code into the application are avoided. Upgrades land in dedicated `build(deps)` commits.

## Commands

```bash
composer setup          # install dependencies, create .env, generate the key, migrate, seed demo data, build the client and server bundles
composer dev            # run php artisan dev: web server, queue worker, log tail, and the Vite dev server, which also renders pages on the server
composer check          # run the quality gates that need neither a browser nor the network
composer fix            # apply the fixes the tools can make on their own
composer test           # run the Pest suite in parallel, browser tests excluded
composer test:browser   # build the bundles, start the SSR server, then run the browser tests
```

- Browser tests run against the production build. Stop `composer dev` before `composer test:browser`: the suite refuses to run while the Vite dev server does.
- Wayfinder generates the TypeScript of routes and controllers into `resources/js/actions`, `resources/js/routes` and `resources/js/wayfinder`. The dev server and `composer check` regenerate it; after adding a route elsewhere, run `php artisan wayfinder:generate`.

## Quality Gates

Tools enforce quality; review does not replace them. No gate ever gets a baseline, an ignore comment or a lowered threshold.

`composer check` runs, in this order:

```bash
php artisan config:clear
vendor/bin/pint --test                                                  # PHP style, Laravel preset
vendor/bin/rector process --dry-run --clear-cache                       # PHP 8.5 and Laravel 13 idioms, dead code, missing types
vendor/bin/phpstan analyse                                              # Larastan, level 10
vendor/bin/pest --parallel --exclude-testsuite=Browser --coverage --min=90
vendor/bin/pest --type-coverage --min=100
php artisan wayfinder:generate
npx vp check                                                            # Oxfmt, Oxlint and the TypeScript type check
npx vp test                                                             # Vitest
```

| Where                   | What runs                                                                                                                                                                                              |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `composer check`        | Every gate except the browser tests, Lighthouse, commit messages and dependency audits                                                                                                                 |
| `composer test:browser` | The browser tests: every page and the critical journeys, rendered on the server. They run locally, not in CI                                                                                           |
| Git hooks               | Before a commit, Rector and Pint on staged PHP files and `vp check --fix` on staged TypeScript and CSS. On a commit, the commit message check                                                          |
| CI                      | `composer check` against SQLite, PostgreSQL and MySQL, Lighthouse on the public pages (at least 0.95 on mobile in every category audited), the commit messages, and the dependency audits, also weekly |

`composer check` and `composer fix` clear Rector's cache first. The cache holds one verdict per file, so it still calls a file clean when a change in another file, such as a helper added to `tests/Pest.php`, makes Rector want to rewrite it. The pre-commit hook gives Rector the staged files alone and can miss such a rewrite: `composer check` decides.

The architecture tests in `tests/Unit/Arch/` check the layering rule, the naming rules, `strict_types`, final classes, the absence of ignore and disable comments, the translation keys, that every component and hook is imported somewhere, and that every type of `resources/js/types/` matches its PHP class.

## Project Structure

```
app/                            One folder per kind of class, flat, as Laravel does
├── Actions/                    One business operation per class
├── Console/Commands/
├── Data/                       Immutable structures handed to and returned by actions
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/<Area>/     Resourceful controllers, grouped by URL area
│   ├── Middleware/
│   └── Requests/<Area>/        Mirrors Controllers/
├── Listeners/
├── Models/
├── Notifications/
├── Policies/
└── Providers/
database/                       Laravel conventions: factories, migrations, seeders
lang/
├── en.json                     Interface text, read by the front end. Keys start with the module id
├── en/<module>.php             Server text of a module: flashed messages, mails
└── en/validation.php           Laravel's own files: auth, pagination, passwords, validation
resources/
├── css/app.css                 Design tokens (Tailwind, shadcn/ui)
├── views/app.blade.php         Root template: language and theme of the first response
└── js/
    ├── app.tsx                 Starts the Inertia app: titles, theme, toasts
    ├── pages/<area>/           Inertia pages, grouped by URL area
    ├── components/             Application components
    ├── components/ui/          shadcn/ui primitives
    ├── layouts/
    ├── hooks/
    ├── lib/
    └── types/                  Types of the props pages receive, written by hand
routes/
├── web.php                     The application's first routes, then a require for each module's route file
└── <module>.php                The web routes of one module
tests/
├── Browser/                    Tests that drive a browser
│   ├── Journeys/               Critical journeys, end to end
│   └── Pages/<Area>/           One test per page of resources/js/pages
├── Feature/                    Tests that boot the application
│   ├── Actions/                Mirrors app/Actions
│   ├── Http/<Area>/            Mirrors app/Http/Controllers
│   ├── Providers/              Mirrors app/Providers
│   └── Seeders/                Mirrors database/seeders
└── Unit/                       Tests that need no application
    ├── Arch/                   Architecture, naming and translation rules
    └── GitHooks/               The scripts of .vite-hooks/
.github/workflows/              One CI workflow per concern
.vite-hooks/                    Git hooks: pre-commit, commit-msg
```

- Every class goes in the folder Laravel's generators use for its kind. Actions and Data join them, as in Laravel's own packages. Other kinds of folder appear only when needed.
- Folders are flat. Class names carry the subject (`CancelSubscription`, `SubscriptionStatus`), so no subfolder is needed to tell them apart.
- The HTTP layer and the pages are the exception: they are grouped by URL area, because a controller only makes sense with its URL. The area is the first segment of the path: `/account/profile` belongs to `Account`. Authentication routes, which Laravel keeps at the root (`/login`, `/register`), form the `Auth` area.
- A page with no area, such as `welcome` or `dashboard`, sits at the root of `pages/`.
- Actions never use the delivery layer: `App\Http`, `App\Filament` and `App\Console`. That is the only layering rule.
- A folder exists only when it contains a file. No empty scaffolding.
- Wayfinder output is generated, git-ignored and never edited by hand.
- The shadcn/ui primitives in `components/ui/` are owned code: edited freely, and linted and formatted like the rest. Two rules do not apply to them, because shadcn generates files that break them: `react/only-export-components`, which only protects Fast Refresh, and the `<Component>Props` naming. Every other rule applies to them: a primitive that breaks one is fixed in its file. `shadcn add` never overwrites an existing primitive: run it without `--overwrite`, and answer no when it offers to overwrite a file. A change upstream is ported by hand. They import `cn()` from shadcn's `cn` package, so there is no `lib/utils.ts`.
- A popup renders inside the landmark that holds its trigger, through the `container` prop its primitive forwards to Base UI's portal, as `DropdownMenuContent` does. Base UI portals it to the end of `<body>` by default, outside every landmark, where it fails axe's `region` rule. A dialog keeps that default, sheets and alert dialogs included: axe counts a dialog as a region of its own.

## Modules

A module is a unit of delivery, such as `billing` or `blog`. Its id names its text, its routes and its commits, never a PHP namespace or a front-end folder. No code boundary separates modules: an action may use any model or action it needs.

| File                              | Role                                                                                  |
| --------------------------------- | ------------------------------------------------------------------------------------- |
| `lang/en.json`, keys `<module>.*` | The module's interface text, which pages and components read                          |
| `lang/en/<module>.php`            | The module's server text, when it has any: flashed toasts, validation messages, mails |
| `routes/<module>.php`             | The module's web routes, when it has any. `routes/web.php` requires it                |

Everything else stays in Laravel's place:

- Service providers are listed in `bootstrap/providers.php`, which `make:provider` completes on its own.
- `DatabaseSeeder` calls each seeder explicitly, and calls the seeders of demo data only outside production.
- Commands and schedules go in `routes/console.php`. The first one creates the file, which `bootstrap/app.php` already loads.
- Shared props are added in `HandleInertiaRequests`, and their types in the `SharedProps` interface of `resources/js/types/shared-props.ts`.
- The items of a menu are declared in the component that renders it, such as `components/app-sidebar.tsx`.

## Pages

- A route that only renders a page has no controller: `Route::get('/pricing', fn (): Response => Inertia::render('pricing'))->name('pricing');`, with `Inertia\Response`. `Route::inertia()` is not used: Larastan types it as `mixed`, which level 10 rejects.
- An Inertia page name is its file path: `account/profile` is `resources/js/pages/account/profile.tsx`.
- A page that uses a layout declares it after its component, `Dashboard.layout = AppLayout;`, so the layout's code loads with that page only. `welcome` and `http-error` have none.
- Every page sets its title with `<Head title={translate('<module>.<page>.title')} />`. The application name is appended. A page meant for search engines also sets a meta description; a page that must not be indexed carries `<meta name="robots" content="noindex" />`.
- Pages render on the server first. Never read `window`, `document` or `localStorage` while rendering: do it in an effect or an event handler. A server render that fails fails its browser test.
- Every page receives the shared props: `name`, `theme`, `isSidebarOpen`, `translations` and `errors`.
- Style with the design tokens of `resources/css/app.css`, such as `bg-background`, `text-foreground`, `text-muted-foreground` and `border`. They hold in light and dark mode, where raw colors break the contrast rules. Dark mode is the `dark` class on `<html>`.
- Add a shadcn/ui component with `npx shadcn@latest add <component>` when a page uses it, never in bulk. A package it installs stays only while a component in use needs it.
- Forms are built with shadcn/ui's Field components: a `FieldGroup` holds the fields, and each `Field` holds a control, its `FieldLabel` and its `FieldError`. A field in error carries `data-invalid` on its `Field`, and `aria-invalid` and `aria-describedby`, naming its `FieldError`, on its control. `InputField`, in `components/input-field.tsx`, composes them for an input: it takes the name, the label, the error and the props of the input, and wires the rest. A checkbox sits in a horizontal `Field`, before its label.
- A controller shows a toast by flashing it: `Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.invoice_sent')]);`. The type is `success` or `error`, and the message is server text.

## Translations

- The interface text lives in `lang/en.json`: one flat object whose keys start with their module id, such as `"billing.invoices.title": "Invoices"`. Pages and components read it through `useTranslation()`:

    ```tsx
    const { translate, translateChoice } = useTranslation();

    translate('billing.invoices.greeting', { name: user.name }); // "Hello :name", with :Name and :NAME casing
    translateChoice('billing.invoices.count', invoices.length); // "{0} No invoices|{1} One invoice|[2,*] :count invoices"
    ```

- The text the server sends lives in `lang/en/<module>.php`, read with `__('billing.invoice_sent')`.
- A key sits in one file only. Every literal key passed to `translate()` or `translateChoice()` exists in `lang/en.json`, and every key exists in every locale.
- User-facing text always goes through a translation file, never hard-coded.

## Code Style

### Naming

**Everywhere**

- American English, as in Laravel and Stripe: `canceled`, `color`, `license`.
- Full words. The only abbreviations allowed are `id`, `url` and standard acronyms (`Html`, `Json`, `Uuid`). Write `$subscription`, never `$sub`.
- Booleans read as questions: `$isPublished`, `hasVerifiedEmail()`, `canRefund()`.
- Plural for collections, singular for single items.
- Names describe the business, not the mechanism: `CancelSubscription`, not `SubscriptionService::cancel()`.

**PHP**

- Every file declares `strict_types=1`.
- Classes are `final`. Actions and Data classes are `final readonly`.
- Allowed role suffixes are the ones Laravel or this document require: `Controller`, `Request`, `Middleware`, `Resource`, `Policy`, `Factory`, `Seeder`, `ServiceProvider`, `Observer`, `Exception`, `Data`, `Test`.
- Actions, events, jobs, listeners, mails, notifications and console commands carry no role suffix, as in Laravel's documentation (`InvoicePaid`, `OrderShipped`, `ProcessPodcast`). The namespace tells apart two classes of the same name, such as an action and a notification.
- Banned: the suffixes `Service`, `Manager`, `Helper`, `Util`, and folders named `Services`, `Helpers`, `Utils`, `Common` or `Misc`.
- **Actions**: verb and noun, specific enough to stand alone in a flat folder (`VerifyEmail`, `CancelSubscription`). One public method `handle()`, dependencies injected through the constructor.
- **Events**: noun and past participle (`EmailChanged`, `SubscriptionCanceled`). Where Laravel defines an event for the same fact, such as `Illuminate\Auth\Events\Verified`, dispatch Laravel's instead of creating one: the ecosystem listens to it.
- **Exceptions**: named after the broken rule, suffixed `Exception` as in Laravel, and built through named constructors (`EmailAlreadyVerifiedException::for($user)`).
- **Enums**: singular (`Theme`, `SubscriptionStatus`), cases in PascalCase, backed by lowercase snake_case strings.
- **Controllers**: resourceful methods only (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`). Any other operation becomes a resource of its own, for example `EmailVerificationController@store`.
- **Form requests**: `<Verb><Noun>Request`, exposing `toData()`, which returns what its action expects: a Data object, or the value itself when the action takes a single one (`SendPasswordResetLink::handle(string $email)`).

**Database**

- Tables are plural snake_case, columns are snake_case, foreign keys are `<singular>_id`.
- When the moment matters, state is a nullable timestamp rather than a boolean: `email_verified_at`, `canceled_at`.
- Money is stored as integer minor units with an ISO 4217 `currency` column. Never floats.

**HTTP**

- URIs are kebab-case, with plural resources (`/account/passkeys`).
- Route names follow the path, dotted: `/account/passkeys/{passkey}` is `account.passkeys.destroy`. Authentication routes keep the names Laravel relies on: `login`, `logout`, `password.reset`, `verification.notice`.

**TypeScript and React**

- Files are kebab-case (`profile-form.tsx`, `use-translation.ts`). Components are PascalCase. A hook `useX` lives in `use-x.ts`.
- A page default-exports a function named after the page (`AccountProfile`). Everything else uses named exports.
- Props types are declared with `type` and named `<Component>Props`.
- A Data class or an enum passed to a page has a TypeScript type of the same name, without the `Data` suffix, in `resources/js/types/<name>.ts`: `ProfileData` becomes `Profile`, in `types/profile.ts`. An architecture test checks that the type lists the same properties, or the same values for an enum.
- No `any`, no non-null assertions, no `@ts-ignore` or `@ts-expect-error`.
- Routes are called through Wayfinder, never written as strings: `<Link href={dashboard()}>`, with `import { dashboard } from '@/routes'`.
- Imports list packages first, then `@/` imports. Oxfmt sorts them, and the Tailwind classes too.

### Comments

- A comment is short, and says only what the code cannot: why a choice was made, a constraint, a consequence that is not obvious.
- It never repeats what the context already signals. The namespace, the class, the method and the parameter names are signals.
- Docblocks carry only what PHP types cannot express, such as generics and array shapes.

```php
// No: repeats the class and method names.
/** Handles the verification of the user's email. */
public function handle(User $user, CarbonImmutable $verifiedAt): void

// Yes: says what the code cannot.
// Stripe retries a webhook for up to three days: the same event can arrive twice.
```

### Examples

An action:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\EmailAlreadyVerifiedException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Verified;

final readonly class VerifyEmail
{
    public function handle(User $user, CarbonImmutable $verifiedAt): void
    {
        if ($user->hasVerifiedEmail()) {
            throw EmailAlreadyVerifiedException::for($user);
        }

        $user->email_verified_at = $verifiedAt;
        $user->save();

        event(new Verified($user));
    }
}
```

A controller hands the action typed data and nothing else:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\UpdateProfile;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class ProfileController
{
    public function update(
        UpdateProfileRequest $request,
        #[CurrentUser] User $user,
        UpdateProfile $updateProfile,
    ): RedirectResponse {
        $updateProfile->handle($user, $request->toData());

        return to_route('account.profile.edit');
    }
}
```

An Inertia page:

```tsx
// resources/js/pages/account/profile.tsx
import { Head } from '@inertiajs/react';

import { ProfileForm } from '@/components/profile-form';
import { useTranslation } from '@/hooks/use-translation';
import { AppLayout } from '@/layouts/app-layout';
import type { Profile } from '@/types/profile';

type AccountProfileProps = {
    profile: Profile;
};

export default function AccountProfile({ profile }: AccountProfileProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('account.profile.title')} />
            <ProfileForm profile={profile} />
        </>
    );
}

AccountProfile.layout = AppLayout;
```

### Commits

- Conventional Commits, `type(scope): subject`. Types: `feat`, `fix`, `refactor`, `perf`, `test`, `docs`, `build`, `ci`, `chore`.
- The scope is a module id, `deps` or `docs`. A module id is accepted once `lang/en/<module>.php` exists or a key of `lang/en.json` starts with it, so modules become scopes as they are created.
- The subject is imperative and lowercase, has no trailing period, and is at most 72 characters. The body explains why, not what.
- One logical change per commit, and every commit passes `composer check`.
- `.vite-hooks/commit-msg` checks every commit, locally and in CI. A merge commit keeps the message Git gives it, and a project's first commit the one GitHub gives it. `fixup!` and `squash!` commits pass locally and fail in CI.

## Testing Strategy

| Level          | Location                                                                     | What it proves                                                                                                                                     |
| -------------- | ---------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Architecture   | `tests/Unit/Arch/`                                                           | The layering rule, naming rules, strict types, final classes                                                                                       |
| Action         | `tests/Feature/Actions/`                                                     | Every action and business rule, with the model behavior they rely on. The database is allowed                                                      |
| HTTP           | `tests/Feature/Http/<Area>/`                                                 | Every route: authorization, validation, Inertia component and every prop it receives, shared props included, redirects                             |
| Page           | `tests/Browser/Pages/<Area>/`                                                | Every page, in light and in dark mode, rendered on the server: no accessibility issue at any impact level, no JavaScript error, no console message |
| Journey        | `tests/Browser/Journeys/`                                                    | The critical journeys end to end, rendered on the server, with no JavaScript error and no console message along the way                            |
| Front-end unit | next to the file, `*.test.ts`                                                | Pure functions in `lib/` and `hooks/`                                                                                                              |
| Other code     | `tests/Feature/Providers/`, `tests/Feature/Seeders/`, `tests/Unit/GitHooks/` | Code that no other level covers, one folder per folder under test: `app/Providers/`, `database/seeders/`, `.vite-hooks/`                           |

- `tests/` holds only `Browser/`, `Feature/` and `Unit/`, as in Laravel. `Feature/` holds the tests that boot the application and `Unit/` the tests that do not. Inside them, a test sits in the folder that mirrors what it tests.
- Actions are written test-first.
- Tests never call a third-party API. External services are replaced by in-memory fakes, and incoming webhooks are tested with signed fixture payloads.
- Feature tests run without built assets and without server-side rendering: they assert the Inertia response.
- Browser tests run against the production build with server-side rendering on, as users get it. A page test checks that the page was rendered on the server:

    ```php
    it('renders the invoices page in dark mode', function (): void {
        $this->actingAs(User::factory()->create());

        visit(route('billing.invoices.index'))
            ->inDarkMode()
            ->assertAttribute('#app', 'data-server-rendered', 'true')
            ->assertSee(__('billing.invoices.title'))
            ->assertNoSmoke()
            ->assertNoAccessibilityIssues(level: 3);
    });
    ```

## Boundaries

**Always**

- Run `composer check` before every commit, and commit only when it passes.
- Write actions test-first.
- Keep business logic in actions and models. Controllers and React components only translate between people and the actions.
- Follow the naming rules. When a name is debatable, record the reasoning in the commit body.

**Ask first**

- Adding or removing a dependency, or moving one to a new major version.
- Adding a kind of folder that Laravel's generators do not use.
- Changing quality configuration: `phpstan.neon`, `rector.php`, `pint.json`, `vite.config.ts`, `lighthouserc.json`, git hooks, CI workflows, thresholds.

**Never**

- Commit secrets, real API keys or customer data.
- Add a PHPStan baseline, `@phpstan-ignore`, `@ts-ignore`, `@ts-expect-error` or lint-disable comments, or lower a threshold.
- Call a third-party API from tests.
- Edit `vendor/`, `node_modules/` or generated Wayfinder files.
- Create classes suffixed `Service`, `Manager`, `Helper` or `Util`, or folders named `Services`, `Helpers`, `Utils`, `Common` or `Misc`.
