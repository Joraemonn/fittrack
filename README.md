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

## Exercise Data

FitTrack uses API Ninjas as the main workout exercise API. API Ninjas provides exercise text data such as name, muscle group, type, equipment, and instructions.

The workout picker is intentionally text-first: it shows the exercise name and target muscle group only. Browsing and searching use the PHP API Ninjas proxy, including an all-muscles browse mode that combines API results across the supported muscle categories. The local exercise catalogue is only used as a fallback if the API cannot be reached.
