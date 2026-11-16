# TelePinoy

TelePinoy is a Philippine TV streaming app concept built with Laravel (API), Vue 3 + Quasar (frontend), and Capacitor (Android wrapper). It lets users browse available channels, view details, watch live streams, save favorites, and revisit recently watched items.

## Project structure

- backend/ - Laravel REST API and MySQL-friendly database setup
- frontend/ - Vue 3 + Quasar app for web and Android packaging
- frontend/android/ - Capacitor Android project

## Tech stack

- Backend: Laravel, PHP, MySQL/SQLite for local development, Composer
- Frontend: Vue 3, Quasar, TypeScript, Vite, Pinia, Axios
- Mobile: Capacitor + Android Studio
- Streaming: HTML5 video + HLS.js

## Local setup

1. Start the Laravel API

   cd backend
   php artisan serve --host=127.0.0.1 --port=8000

2. Start the frontend in a second terminal

   cd frontend
   npm install
   npm run dev -- --host 0.0.0.0 --port 9000

3. Open the app

   http://localhost:9000

## Database

The current project uses SQLite for local development so it can run without a full MySQL server. The database is seeded with sample Philippine channels.

## Admin sign-in and playlist import

Set `TELEPINOY_ADMIN_EMAIL` and `TELEPINOY_ADMIN_PASSWORD` in `backend/.env` (that file is ignored by Git), then run:

   cd backend
   php artisan config:clear
   php artisan migrate --seed

Open `/admin/login` and sign in with those environment-configured credentials. The admin dashboard at `/admin` shows channel totals and monthly playback starts; `/admin/channels` manages the Live, Imported, and Custom sections. Admin channel management endpoints require a Sanctum bearer token; the browser keeps it only for the current tab session.

In Channels, select **Import playlist** and paste an HTTPS URL from a host in `TELEPINOY_PLAYLIST_HOSTS` (default: `iptv-org.github.io`). Names, category, logo URL, TVG ID, and stream URL are imported into the database as unpublished channels. Use **Create channel** for custom entries. Preview a stream in the channel manager; only after HLS media buffers can you select **Publish to Live**. Published playback starts are counted for the monthly dashboard statistic; it counts playback starts, not unique people. The current player supports HLS `.m3u8`; MPEG-DASH `.mpd` streams are not enabled. Stream accessibility does not establish permission to rebroadcast; only use streams you are authorized to use.

## Android setup

1. Build the frontend

   cd frontend
   npm run build

2. Open Android project

   npx cap open android

3. Run the project in Android Studio.

## Notes

- Sample streams are placeholder HLS test URLs for demonstration.
- Replace them with authorized live channels before production deployment.
- Use the Admin page in the app to add or edit channel entries while developing the project.
