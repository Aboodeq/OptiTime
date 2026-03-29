# OptiTime Frontend

Auth-only rebuild aligned with the old copy's login design and structure, with the auth flow cleaned up into smaller feature modules and AR/EN i18n support.

## Setup

```sh
npm install
npm run dev
```

## Demo Access

- Form login: `demo@optitime.com` / `password123`
- Quick demo: use any role button on the login page

## Structure

```text
src/
  app/                 Router and app-level plugins
  assets/              Global styles
  components/common/   Small shared UI helpers
  constants/           Storage keys
  features/auth/       Auth api, model, and UI
  i18n/                AR/EN locale files
  store/               Auth and locale stores
  utils/               Shared helpers
  views/               Route-level wrappers
```
