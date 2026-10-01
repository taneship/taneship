# Taneship

A Laravel starter kit on Inertia and React, rendered on the server. Business logic lives in actions, static analysis runs at its highest level, architecture tests hold the structure in place, and the conventions are written for developers and coding agents alike.

## Requirements

- PHP 8.5, with PCOV or Xdebug for the coverage gate
- Composer 2.10
- Node.js 24, with npm 11.16 or later

No third-party key is needed: the application runs on SQLite, the database queue and the `log` mailer.

## Create a project

Create a repository from this template, with "Use this template" on GitHub or with the GitHub CLI:

```bash
gh repo create my-app --template taneship/taneship --private --clone
```

Then install and start it:

```bash
cd my-app
composer setup
composer dev
```

The application answers on http://localhost:8000. Sign in as the demo user, `demo@example.com` with the password `password`. The seeders create it outside production only.

The project starts from a single commit and owns every file from then on: change any of them. Taneship tags its releases `vX.Y.Z` on `main`, each with a [GitHub Release](https://github.com/taneship/taneship/releases) whose notes describe the changes and link to their diff. Take what you want from a release by hand.

## Commands

```bash
composer setup          # install dependencies, create .env, generate the key, migrate, seed demo data, build the client and server bundles
composer dev            # run php artisan dev: web server, queue worker, log tail, and the Vite dev server, which also renders pages on the server
composer check          # run the quality gates that need neither a browser nor the network
composer fix            # apply the fixes the tools can make on their own
composer test           # run the Pest suite in parallel, browser tests excluded
composer test:browser   # build the bundles, start the SSR server, then run the browser tests
```

`npm install` installs the git hooks: before a commit, they fix the staged files, and on a commit they check its message. Set `VP_GIT_HOOKS=0` to turn them off.

## Coding agents

[`AGENTS.md`](AGENTS.md) holds the conventions of the project: stack, structure, code style, testing and boundaries. Claude Code, Codex, Cursor, Copilot, Gemini CLI and Junie read it. Add your own conventions to it as the project grows.

Claude Code reads `AGENTS.md` only while the project has no `CLAUDE.md`. If you add one, start it with this line, so that it keeps reading the conventions:

```
@AGENTS.md
```

[Laravel Boost](https://laravel.com/docs/boost) gives agents an MCP server that reads the application: routes, database schema, logs and documentation. Install its configuration, then approve the `laravel-boost` server in your agent:

```bash
php artisan boost:install --mcp
```

The files it generates are git-ignored, and `boost:install` creates them again on another machine.

## Production

Requirements: PHP 8.5, Node.js 24 for server-side rendering, and SQLite, PostgreSQL or MySQL.

Build the application:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
npm prune --omit=dev
php artisan migrate --force
php artisan optimize
```

`npm run build` builds the client bundle into `public/build` and the server bundle into `bootstrap/ssr`. The server bundle imports React and the other runtime packages from `node_modules`, which `npm prune --omit=dev` keeps.

Serve `public/` through a web server that compresses its responses, such as nginx or Caddy, and supervise three processes:

```bash
php artisan inertia:start-ssr   # renders pages on the server. While it is down, pages render in the browser
php artisan queue:work          # runs queued jobs
php artisan schedule:work       # runs scheduled tasks, or run php artisan schedule:run every minute from cron
```

## Pro and Teams

Taneship Pro and Taneship Teams start from this edition for applications that need more. [See what they add](https://github.com/taneship).

## Contributing and security

Read [`CONTRIBUTING.md`](CONTRIBUTING.md) before opening a pull request. Report a vulnerability privately, as [`SECURITY.md`](SECURITY.md) describes.

Taneship is open source under the MIT license.
