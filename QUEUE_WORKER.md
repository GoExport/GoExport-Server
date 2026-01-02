# Queue Worker Setup

The export functionality uses Laravel's queue system to process export jobs asynchronously. All exports are automatically queued when saved and processed in a dedicated `exports` queue.

## Running the Queue Worker

To process export jobs, you need to run the queue worker specifically for the exports queue:

```bash
php artisan queue:work --queue=exports
```

Or process all queues (including exports):

```bash
php artisan queue:work
```

### Keep Queue Worker Running

For production, you should use a process monitor like:

**Option 1: Supervisor (Linux)**

```bash
sudo apt-get install supervisor
```

**Option 2: PM2 (Cross-platform)**

```bash
npm install -g pm2
pm2 start "php artisan queue:work --queue=exports" --name export-worker
pm2 save
pm2 startup
```

**Option 3: Windows Service**
Use NSSM (Non-Sucking Service Manager) to run the queue worker as a Windows service.

### Queue Worker Options

```bash
# Process only export jobs
php artisan queue:work --queue=exports

# Process jobs with verbose output
php artisan queue:work --queue=exports --verbose

# Process all queues
php artisan queue:work

# Stop after processing all jobs
php artisan queue:work --queue=exports --stop-when-empty

# Restart worker every hour
php artisan queue:work --queue=exports --max-time=3600

# Restart worker after 100 jobs
php artisan queue:work --queue=exports --max-jobs=100

# Run in daemon mode (production)
php artisan queue:work --queue=exports --daemon
```

## Monitoring Exports

You can monitor export jobs in the admin panel at `/admin/exports`

### Export Status Values:

-   **pending**: Waiting to be processed
-   **in_progress**: Currently being processed
-   **completed**: Successfully completed
-   **failed**: Processing failed

## How It Works

The export system is fully automated:

1. **Create an export** → Fill in the form with service, user ID, video ID, aspect ratio, resolution, and outro preference.

2. **Save** → Click "Save" - the export is automatically queued to the `exports` queue with `pending` status.

3. **Job Processing:**

    - Status changes from `pending` to `in_progress`
    - Runs the `goexport_cli` command with your parameters
    - Updates to `completed` with file path on success
    - Updates to `failed` on error
    - All status changes tracked in the database

4. **Monitor Progress:**
    - View current status in the exports table
    - Check file path when completed
    - View/edit exports at any time

## Troubleshooting

### Jobs not processing?

1. Make sure queue worker is running for exports: `php artisan queue:work --queue=exports`
2. Check if the queue worker is running at all: `php artisan queue:work`
3. Check failed jobs: `php artisan queue:failed`
4. Check logs: `storage/logs/laravel.log`
5. Look at the jobs table in database to see pending jobs

### View queued jobs:

```bash
# Check database jobs table
php artisan db:table jobs
```

### Retry failed jobs:

```bash
php artisan queue:retry all
```

### Clear failed jobs:

```bash
php artisan queue:flush
```

### Restart queue worker:

```bash
php artisan queue:restart
```

## Important Notes

-   **Automatic Queuing**: All exports are automatically queued when saved - there's no manual "process" step
-   **Dedicated Queue**: Exports use their own `exports` queue, separate from other application jobs
-   **Status Tracking**: Export status is automatically managed (pending → in_progress → completed/failed)
-   **Database Storage**: Queue jobs are stored in the `jobs` table, export results in the `exports` table
