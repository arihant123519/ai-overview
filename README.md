# AIO — AI Overview & Keyword Analysis Platform

A Laravel application for tracking and analyzing Google **AI Overviews (AIO)** and search performance across multiple clients and domains. It combines Google Search Console, Google Ads Keyword Planner, and AI models (OpenAI / Gemini) to research keywords, generate analysis prompts, monitor AI Overview visibility, and keep results synced on a schedule.

## Features

### Client & Domain Management
- Manage multiple **clients** and their **domain properties** (CRUD).
- Configure per-property settings including sync **frequency** (`DD:HH:MM` format).
- Dynamic Google Ads **customer ID** and **manager ID** handling per client.

### Keyword Research & Analysis
- Create and edit **keyword requests** per client property.
- **Keyword cluster analysis** and grouping.
- Auto-fetch keywords and pull suggestions from **Google Ads Keyword Planner**.
- Enrich keywords with **Google Search Console (GSC)** data (clicks, impressions, URLs).
- Parent/child keyword relationships with status checks.
- **Median** calculation and display for keyword metrics.
- Background queue processing for bulk keyword jobs, with status polling.

### AI Overview (AIO) Tracking
- Fetch and extract **AI Overview results** for keywords.
- Sync AIO data on demand or automatically and check AI status.
- **AIO cluster analysis** and cached result retrieval.
- Store organic results, related questions, and related searches.

### AI Prompt Generation & Analysis
- Generate and update **AIO analysis prompts** per property/keyword.
- Specialized prompt types: **brand-neutral**, **visibility tracking**, and **competitor trigger**.
- Run single-prompt analysis through **Gemini**, **ChatGPT/OpenAI**, and a **Search API**.
- Store and display generated prompts and their responses.

### AI Similarity & Comparison
- **AI similarity analysis** between results.
- Side-by-side **comparison analysis** per client/keyword.

### History & Logging
- **History Log** of analysis runs per domain/property/keyword.
- Drill down into individual run logs and their extracted AIO results.
- Priority flagging for sync ordering.

### Scheduled Sync (Cron)
- `AutoSyncAIOforclient:send` command re-runs AIO syncs based on each property's configured frequency.
- Reachable via the `/AutoSyncAIOforclient` route or scheduled as a cron job.
- `CleanupStuckKeywordJobs` command clears stalled queue jobs.

### Authentication
- User registration/login via **Laravel UI** auth scaffolding.
- **Google OAuth** integration and user profile management.

## Tech Stack

- **PHP** ^8.1, **Laravel** ^10
- **Livewire** ^3.5
- **Google API Client**, **Google Ads PHP SDK** (Keyword Planner, Search Console)
- **OpenAI PHP Client** + **Gemini** API
- **MySQL** database with **database** queue driver
- **Vite** + Blade for the frontend

## External Integrations

Configured via `.env` (see `.env.example`):

| Service | Env keys |
|---|---|
| Google Ads / Keyword Planner | `GOOGLE_ADS_KEY_PATH`, `DEVELOPER_TOKEN`, `MANAGER_CUSTOMER_ID`, `CUSTOMER_ID` |
| Google Search Console | `GOOGLE_APPLICATION_CREDENTIALS` |
| OpenAI | `OPENAI_API_KEY` |
| Google Gemini | `GEMINI_KEY`, `GEMINI_MODEL_ID` |
| AI Overview API | `AIO_TOKEN` |
| YouTube Data API | `YOUTUBE_API_KEY` |

## Getting Started

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
# fill in DB credentials and the API keys listed above

# 3. Database
php artisan migrate

# 4. Build assets & serve
npm run dev
php artisan serve

# 5. Run the queue worker (required for keyword/AIO jobs)
php artisan queue:work
```

### Scheduled syncing

Run the auto-sync command on a schedule (e.g. via cron or `php artisan schedule:work`):

```bash
php artisan AutoSyncAIOforclient:send
```

## Project Structure

- `app/Http/Controllers/` — Domain, keyword, AIO prompt, similarity, history, and auth controllers
- `app/Models/` — Clients, properties, keyword requests, AI overviews, prompts, history logs, etc.
- `app/Services/` — Google Ads, Keyword Planner, and Search Console service wrappers
- `app/Console/Commands/` — Scheduled sync and queue cleanup commands
- `app/Jobs/` — Background keyword/AIO processing jobs
- `routes/web.php` — Application routes
- `resources/views/` — Blade templates (clients, keyword analysis, AIO prompts, history log, median, etc.)
