# FitTrack

FitTrack is a fitness tracking web application for recording workouts, monitoring nutrition, and reviewing running progress in one place. It combines a PHP and MySQL application with exercise API data, photo-based meal recognition, and a local AI running coach.

## At a Glance

| Area | Technologies |
| --- | --- |
| Web application | PHP, MySQL, HTML, CSS, and vanilla JavaScript |
| Data visualisation | Chart.js |
| Exercise catalogue | API Ninjas, accessed through PHP endpoints |
| Food recognition | Separate Python FastAPI service, served with Uvicorn |
| Running coach | Ollama using `llama3.2:3b` |

This repository contains the web application. The Python food recognition service, its dependencies, and trained model files are maintained separately in the `fittrackCV` project.

## Features

### Dashboard

- Weekly activity summaries for workouts, meals, and running.
- Charts for reviewing logged activity over time.

### Workouts

- Search exercises and filter by muscle group.
- Browse API Ninjas exercise data with a local catalogue fallback when the API is unavailable.
- Create custom exercises and log sets, repetitions, weight, and notes.
- Use an hours/minutes/seconds duration picker for plank exercises.
- Review saved sessions in Workout History.

### Meals

- Enter meals and nutrition information manually.
- Capture a meal with the camera or upload a photo for food recognition.
- Review a predicted food and nutrition information, adjust the serving size, and confirm the entry before saving.
- View daily calorie and macronutrient totals.
- Browse meal history by date, with labels distinguishing manual entries from AI scans.
- Continue with manual entry when the scanning service is unavailable.

### Running

- Log distance, duration, and calories, with calculated pace.
- View total runs, latest distance, longest distance, and fastest pace.
- Review running history with longest-run and personal-best pace badges.
- Compare distance and duration on a progress chart.
- Receive a recap and next-run suggestion when the optional Ollama service is available.

### Accounts and Profiles

- Register, log in, and access session-protected pages.
- Edit profile information and upload a profile image.
- Select measurement units and a timezone.
- Submit messages through the contact form.

## Architecture

```text
Browser: HTML / CSS / JavaScript
    |
    +-- PHP application --------------------> MySQL
    |       |
    |       +-- Exercise endpoints ---------> API Ninjas
    |       |                                 (local catalogue fallback)
    |       |
    |       +-- Running coach --------------> Ollama
    |
    +-- Meal photo -------------------------> Python FastAPI service
            |
            +-- Confirmed nutrition --------> PHP save endpoint --> MySQL
```

PHP renders the pages and handles authentication, validation, and database operations. JavaScript manages interactive controls, charts, meal scanning, and asynchronous requests.

The food scanner and running coach are separate services. The web application can be explored without starting either AI service.

## Run Locally

### Prerequisites

- PHP 8.x with `pdo_mysql`, `curl`, and `fileinfo` enabled.
- MySQL and a web server capable of running PHP, such as the Apache server bundled with XAMPP.
- A modern browser.
- Internet access for CDN-loaded charts, icons, fonts, and live exercise API requests.
- Git, if cloning the repository rather than downloading a ZIP.

### 1. Download the Application

For a typical Windows XAMPP installation, clone into the web root:

```bat
cd /d C:\xampp\htdocs
git clone https://github.com/Joraemonn/fittrack.git fittrack
```

Alternatively, extract the downloaded repository into `C:\xampp\htdocs\fittrack`, ensuring that `index.php` is directly inside that folder.

If the repository is private, GitHub access is required to clone or download it.

### 2. Start Apache and MySQL

Start both services from the XAMPP Control Panel. The Python scanner and Ollama are optional and are configured separately below.

### 3. Set Up the Database

Open phpMyAdmin, typically at `http://localhost/phpmyadmin`.

1. Import [sql/schema.sql](sql/schema.sql). It creates the `fittrack` database and the base tables.
2. Select the `fittrack` database and import [sql/add_meal_scan_metadata.sql](sql/add_meal_scan_metadata.sql) **once**. This adds the fields used to retain scan details and distinguish scanned meals from manual entries.
3. For running coach support, check whether the `running_logs` table has an `ai_insight` column. The base schema currently does not include it. If it is absent, run:

```sql
USE fittrack;
ALTER TABLE running_logs ADD COLUMN ai_insight TEXT NULL;
```

Do not repeat an `ADD COLUMN` migration when those columns already exist. Older migration files in `sql/` are for upgrading earlier installations; several of their fields are already present in the current base schema.

### 4. Configure the Application

Review [config/config.php](config/config.php). Configuration reads environment variables with local defaults:

| Variable | Purpose | Local default |
| --- | --- | --- |
| `APP_URL` | Application base URL | `http://localhost/fittrack` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_NAME` | Database name | `fittrack` |
| `DB_USER` | Database user | `root` |
| `DB_PASS` | Database password | Empty |
| `API_NINJAS_KEY` | Live exercise API access | Supply your own key |
| `OLLAMA_API_URL` | Running coach service URL | `http://localhost:11434` |

Environment variables must be available to the PHP web server process. Alternatively, adjust your local configuration file to match your installation. The application does not automatically load a `.env` file.

Ensure `uploads/profiles/` is writable by the web server so profile uploads can be saved.

### 5. Open FitTrack

Visit `http://localhost/fittrack/`, register a new account, and start logging activity.

The SQL schema does not seed a demo account or sample fitness records. New accounts begin with empty histories; the developer's local database is not included.

## Optional AI Services

### Food Recognition

The Meals page integrates with a separate Python service using FastAPI. The integration specification describes a PyTorch ResNet-18 classifier covering Food-101 categories and additional Singapore food categories.

**The Python source code and model weights are not included in this repository.** To enable scanning, first obtain and configure the separate `fittrackCV` project using its own setup instructions.

From that project's directory, after activating its Python environment and installing its dependencies, start the API:

```sh
python -m uvicorn api:app --host 127.0.0.1 --port 8000
```

The Meals page defaults to `http://<website-hostname>:8000/predict`. For a localhost installation, this is `http://localhost:8000/predict`. The service must allow the website's origin through its CORS configuration.

A different endpoint can be supplied through `window.FITTRACK_FOOD_API` before `assets/js/app.js` loads.

See [API_INTEGRATION_SPEC.md](API_INTEGRATION_SPEC.md) for the request and response contract, and [test_scan.html](test_scan.html) for a standalone integration test page.

[start_fittrack_ai.bat](start_fittrack_ai.bat) is a Windows convenience launcher. It currently references the developer's absolute `fittrackCV` path and `venv` directory; update those paths before using it on another machine.

Without this service, photo recognition is unavailable and meals can still be entered manually. Predictions should be reviewed before saving; nutrition totals are calculated from the supplied nutrition data and selected serving size.

### Running Coach

The Running page calls Ollama's `/api/generate` endpoint using the `llama3.2:3b` model.

With Ollama installed, download the model:

```sh
ollama pull llama3.2:3b
```

Ensure the Ollama service is running at `http://localhost:11434`, or set `OLLAMA_API_URL` to its address. If a server is not already running, start it with `ollama serve`.

After a run is saved, the application sends its details, recent runs, and personal bests to the model to generate a short recap and next-run suggestion. Responses are stored in `running_logs.ai_insight`.

If Ollama cannot be reached, the run can still be logged without a new coaching response. Local inference may add a delay when saving a run, especially when the model first loads.

## Suggested Review Walkthrough

1. Register an account and explore the dashboard.
2. Log a workout, then select a plank exercise to try the duration picker.
3. Create a custom exercise and check the saved workout history.
4. Enter a meal manually and use Meal History to view entries for a chosen date.
5. Log several runs to compare pace, longest distance, and chart trends.
6. Edit the profile and try a profile image upload.
7. With the optional services running, scan a meal and generate a running recap.

## Engineering Details

- Account passwords are hashed and checked with PHP's password functions.
- Database access uses PDO and prepared statements for user-supplied values.
- Account pages require a logged-in session, and activity queries are scoped to the current user.
- Exercise API calls are handled by PHP endpoints, keeping the API credential out of browser requests.
- Plank durations are stored as total seconds in the existing `workouts.reps` column and displayed as time for those exercises.
- Confirmed scans are saved through [save_scanned_meal.php](save_scanned_meal.php); historical meal data is loaded through [meals_by_date.php](meals_by_date.php).

## Repository Structure

```text
fittrack/
|-- api/                         Exercise API and custom exercise endpoints
|-- assets/                      Styles, JavaScript, and image assets
|-- config/                      Application and database configuration
|-- includes/                    Shared layout, helpers, and exercise catalogue
|-- sql/                         Base database schema and upgrade scripts
|-- uploads/profiles/            Profile image storage
|-- dashboard.php                Activity summaries
|-- workouts.php                 Workout logging and history
|-- meals.php                    Manual entry and food scanning interface
|-- meals_by_date.php            Date-based meal history endpoint
|-- save_scanned_meal.php        Confirmed scan persistence
|-- running.php                  Run logging, charts, and coach integration
|-- profile.php                  Profile and display preferences
|-- API_INTEGRATION_SPEC.md      Separate food service integration reference
|-- start_fittrack_ai.bat        Developer-specific Python service launcher
|-- test_scan.html               Food API integration test page
```

## Troubleshooting

| Symptom | Check |
| --- | --- |
| Database connection fails | MySQL is running, the schema is imported, and the database settings match your installation. |
| Duplicate-column error during import | The migration may already have been applied; inspect the table before running it again. |
| Food scanning is unavailable | The separate Python service is running on the expected host and port, and its CORS settings allow the website origin. |
| Scanned entries lose their source label | Apply the meal scan metadata migration to the correct database. |
| Running coach produces no new recap | Ollama is reachable, `llama3.2:3b` is installed, and `running_logs.ai_insight` exists. |
| Profile upload fails | The profile upload directory is writable and PHP's upload limits allow the selected file. |
| Charts or icons are missing | The browser can access the external CDN resources. |

## Hosting Notes

Cloning this repository sets up source files for a local installation; it does not publish a live website. A hosted version needs a PHP-capable server and MySQL database, plus reachable AI services if those features are enabled.

GitHub Pages serves static content and cannot run this PHP/MySQL application. Remote visitors also cannot access services running on the developer's `localhost`; deployed integrations need reachable service URLs.

Keep real credentials out of published source code and use environment-based configuration for hosting.
