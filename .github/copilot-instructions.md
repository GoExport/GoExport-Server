# GoExport Server - AI Coding Agent Instructions

## Project Overview

Laravel 12 server for GoExport video rendering. Provides API endpoints and admin panel (Orchid Platform) to queue and manage video export jobs processed by a headless CLI binary (`bin/goexport/GoExport_CLI`). Runs in Docker with Xorg virtual display for headless rendering.

## Architecture

### Core Data Flow

1. **API Request** → `api.key` middleware validates `X-API-Key` header → `ExportController::store()` creates `Export` model
2. **Job Dispatch** → `Export` job dispatched to `exports` queue with `pending` status
3. **Job Processing** → Queue worker executes `GoExport_CLI` binary on display `:99`, updates status to `in_progress` → `completed`/`failed`
4. **Output** → Video files stored in `storage/app/public/exports/`, public URL returned

### Key Components

-   **Models**: `Export` (job records), `ExportSetting` (cached config), `ApiKey` (auth), `User` (Orchid users)
-   **Jobs**: `App\Jobs\Export` - executes CLI with Symfony Process, handles all status transitions
-   **API**: REST endpoints at `/api/v1/exports/*` with API key auth
-   **Admin**: Orchid screens at `/admin/*` for managing exports, settings, API keys

## Code Patterns

### API Authentication

All API routes use `api.key` middleware alias. Auth via `X-API-Key` header or `api_key` query param:

```php
// bootstrap/app.php registers the alias
$middleware->alias(['api.key' => \App\Http\Middleware\AuthenticateApiKey::class]);

// routes/api.php
Route::middleware('api.key')->prefix('v1')->group(function () { ... });
```

### Settings Pattern

Use `ExportSetting` model for cached key-value config (1hr TTL):

```php
ExportSetting::get('key', $default);   // Read with cache
ExportSetting::set('key', $value);     // Write and clear cache
ExportSetting::getCliSettings();       // Get all CLI-related settings
ExportSetting::getAspectRatioKeys();   // For validation rules
```

### Orchid Admin Screens

Located in `app/Orchid/Screens/`. Each screen has:

-   `query()` - fetch data
-   `name()`, `description()` - header text
-   `commandBar()` - action buttons
-   `layout()` - form/table layouts using `Orchid\Support\Facades\Layout`

Layouts in `app/Orchid/Layouts/` define reusable field configurations.

### Job Dispatch Pattern

Always include `exportId` and set queue explicitly:

```php
ExportJob::dispatch(
    $export->service,
    $export->userId,
    $export->videoId,
    $export->videoAspectRatio,
    $export->videoResolution,
    $export->videoOutro,
    $export->id  // Required for status updates
)->onQueue('exports');
```

## Development Commands

```bash
# Local dev (requires PHP, Node.js, MySQL)
composer dev              # Runs: php artisan serve + queue:listen + npm run dev

# Docker dev (with VNC debugging)
docker compose -f docker-compose.dev.yml up --build

# Docker production
docker compose up --build -d

# Tests
composer test             # Runs pest tests

# Queue worker
php artisan queue:work --queue=exports
```

## Environment & Configuration

### Critical Environment Variables

-   `QUEUE_CONNECTION=database` - Required for export processing
-   `DISPLAY=:99` - Virtual display for headless rendering
-   `AUTO_MIGRATE=true` - Enable for auto-migrations on Docker startup

### Export Settings (Admin Panel)

Aspect ratios, resolutions, timeouts, and OBS WebSocket config are stored in `export_settings` table. Access at `/admin/settings/export`.

## Testing Notes

-   Tests use Pest PHP framework (see `tests/`)
-   Run `composer test` (clears config cache first)
-   API tests should create `ApiKey` fixtures for authenticated requests

## Docker Architecture

-   **Supervisor** manages: Xorg (:99), Nginx, PHP-FPM, queue worker, optional VNC
-   **Display :99** - Headless display where GoExport renders
-   **Display :1** - Full XFCE desktop (dev only, for debugging)
-   VNC ports: 5999 (display :99), 5901 (desktop), 6080/6099 (noVNC web viewers)

## File Locations

| Purpose         | Path                                       |
| --------------- | ------------------------------------------ |
| CLI Binary      | `bin/goexport/GoExport_CLI`                |
| Export outputs  | `storage/app/public/exports/`              |
| Supervisor logs | `/var/log/supervisor/*.log` (in container) |
| Platform routes | `routes/platform.php`                      |
| API routes      | `routes/api.php`                           |
