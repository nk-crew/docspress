# AGENTS

Minimal context for coding agents working on DocsPress.

## Product

WordPress plugin for documentation sites (GPL-2.0). PHP + bundled JS/CSS in `src/`.

## Stack

- PHP (Composer), WPCS-style lint via Composer scripts
- Frontend sources under `src/` (JS/SCSS); build uses project tooling (`wpeg`—see `package.json`)

## Rules

- Keep changes scoped; follow existing structure under `src/`.
- WordPress: sanitize/escape and appropriate capability checks for admin flows.
- Do not embed secrets or perform destructive operations unless explicitly requested.

## Commands

Use **`package.json`** as the source of truth: `npm run build`, `npm run dev`, `npm run lint`, PHP/CSS/JS lint scripts. Do not assume script names without checking the file.
