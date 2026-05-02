# Firebase notifications setup

## Backend (.env)

Add these variables to backend `.env`:

- `FIREBASE_PROJECT_ID` = your Firebase project ID
- `FIREBASE_SERVICE_ACCOUNT_PATH` = absolute path to service account JSON file

Example:

`FIREBASE_SERVICE_ACCOUNT_PATH=D:\WORK\secrets\optitime-firebase-service-account.json`

## Frontend (.env)

Add these variables to frontend `.env`:

- `VITE_FIREBASE_API_KEY`
- `VITE_FIREBASE_AUTH_DOMAIN`
- `VITE_FIREBASE_PROJECT_ID`
- `VITE_FIREBASE_STORAGE_BUCKET`
- `VITE_FIREBASE_MESSAGING_SENDER_ID`
- `VITE_FIREBASE_APP_ID`
- `VITE_FIREBASE_VAPID_KEY`

## Frontend service worker

Edit `Code/optitime-frontend/public/firebase-messaging-sw.js` and replace:

- `__FIREBASE_API_KEY__`
- `__FIREBASE_AUTH_DOMAIN__`
- `__FIREBASE_PROJECT_ID__`
- `__FIREBASE_STORAGE_BUCKET__`
- `__FIREBASE_MESSAGING_SENDER_ID__`
- `__FIREBASE_APP_ID__`

with the same values from frontend `.env`.

## Firebase console steps

1. Enable **Cloud Messaging**.
2. In **Project settings > Cloud Messaging > Web configuration**, generate a Web Push certificate key (VAPID).
3. In **Project settings > Service accounts**, generate a private key JSON and place it on the backend machine.
