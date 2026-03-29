# OptiTime Frontend

Clean Vue 3 + Vite frontend rebuild for OptiTime, focused on a production-ready authentication flow and maintainable feature-first structure.

## Setup

```sh
npm install
npm run dev
```

## Scripts

- `npm run dev` starts the Vite dev server
- `npm run build` creates a production build
- `npm run preview` serves the production build locally
- `npm run lint` checks JavaScript and Vue files with ESLint
- `npm run lint:fix` auto-fixes lint issues where possible
- `npm run format` formats the project with Prettier
- `npm run check` runs lint, format check, and build

## Environment

Create a local env file when needed. The app ships with safe mock defaults:

```env
VITE_APP_NAME=OptiTime
VITE_AUTH_DELAY_MS=1000
```

## Demo Credentials

- Email: `demo@optitime.com`
- Password: `password123`

## Structure

```text
src/
  assets/styles/      Global theme and layout styles
  components/base/    Reusable UI primitives
  composables/        Shared Vue logic
  features/auth/      Auth-specific schema and form orchestration
  pages/              Route-level views
  router/             Vue Router setup and guards
  services/           API and business logic
  stores/             Pinia stores
  types/              JSDoc typedef modules
  utils/              Small framework-agnostic helpers
```
