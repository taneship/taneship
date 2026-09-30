# Contributing

Thank you for helping. Open a discussion or an issue before a change larger than a small fix, so that we agree on it before you write it.

## Conventions

[`AGENTS.md`](AGENTS.md) holds the conventions every contribution follows. The ones a review checks first:

- Business logic lives in actions, written test-first. Controllers and React components only translate between people and the actions.
- Names follow the naming rules. When a name is debatable, the commit body gives the reasoning.
- User-facing text goes through the translation files, and every page has a browser test in light and in dark mode.
- No gate gets a baseline, an ignore comment or a lowered threshold.
- A new dependency, a new kind of folder or a change to quality configuration is discussed first.

## Commits

- Conventional Commits, `type(scope): subject`, where the scope is a module id, `deps` or `docs`. The commit-msg hook checks every commit, and so does CI.
- One logical change per commit, and every commit passes `composer check`. A change to a page also passes `composer test:browser`.
- Only final commits are pushed: squash `fixup!` and `squash!` commits before you push, since CI rejects them.

## Pull requests

1. Fork the repository and create a branch from `main`.
2. Push your final commits and open a pull request against `main`. CI runs every gate on it.
3. Keep the branch up to date by rebasing it on `main`, never by merging `main` into it: `main` holds no merge commit.
4. Once CI passes and the change is accepted, `main` is fast-forwarded to your branch. Your commits reach `main` as they are, with their messages and hashes.
