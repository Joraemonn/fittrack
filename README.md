# FitTrack

FitTrack is a PHP + MySQL fitness tracking website for logging workouts, meals, and running progress in one place.

## Setup

1. Create a MySQL database and import [`sql/schema.sql`](sql/schema.sql).
2. Update environment variables or edit [`config/config.php`](config/config.php) if your local DB credentials differ.
3. Serve the project from a local PHP stack such as XAMPP or WAMP.
4. Visit `index.php` to register an account and begin logging data.

## Features

- Registration, login, logout, and session-protected pages
- Dashboard with weekly summaries and progress charts
- Workout, meal, and running log forms with history tables
- Profile editing with optional image upload
- Contact form that stores messages in the database
