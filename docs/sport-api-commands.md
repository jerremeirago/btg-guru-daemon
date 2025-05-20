# Sport API Commands Documentation

## Overview

This document outlines the Artisan commands available for fetching sports data from RapidAPI. These commands allow you to retrieve fresh data for different sports with specific date parameters.

## Available Commands

### Sport-Specific Commands

#### Baseball Data

```bash
php artisan api:baseball --day=<day> --month=<month> --year=<year>
```

Fetches baseball match data for the specified date.

**Parameters:**
- `--day`: Day of the month (1-31)
- `--month`: Month (1-12)
- `--year`: Year (e.g., 2025)

**Example:**
```bash
php artisan api:baseball --day=1 --month=5 --year=2025
```

#### Football Data

```bash
php artisan api:football --day=<day> --month=<month> --year=<year>
```

Fetches football (soccer) match data for the specified date.

**Parameters:**
- `--day`: Day of the month (1-31)
- `--month`: Month (1-12)
- `--year`: Year (e.g., 2025)

**Example:**
```bash
php artisan api:football --day=1 --month=5 --year=2025
```

#### Basketball Data

```bash
php artisan api:basketball --day=<day> --month=<month> --year=<year>
```

Fetches basketball match data for the specified date.

**Parameters:**
- `--day`: Day of the month (1-31)
- `--month`: Month (1-12)
- `--year`: Year (e.g., 2025)

**Example:**
```bash
php artisan api:basketball --day=1 --month=5 --year=2025
```

### Combined Command

```bash
php artisan api:sports --sport=<sport> --day=<day> --month=<month> --year=<year>
```

Fetches data for one or all sports for the specified date.

**Parameters:**
- `--sport`: Sport type (options: `football`, `baseball`, `basketball`, `all`)
- `--day`: Day of the month (1-31)
- `--month`: Month (1-12)
- `--year`: Year (e.g., 2025)

**Example:**
```bash
# Fetch data for all sports
php artisan api:sports --sport=all --day=1 --month=5 --year=2025

# Fetch data for a specific sport
php artisan api:sports --sport=football --day=1 --month=5 --year=2025
```

## Implementation Details

### Fresh Data Retrieval

All commands are configured to bypass the cache and fetch fresh data from RapidAPI. This ensures that you always get the most up-to-date information when running these commands.

```php
$matches = $this->baseballApiService->getMatchesByDate(
    (int) $day,
    (int) $month,
    (int) $year,
    true // bypass cache
);
```

### Data Storage

The fetched data is automatically normalized and stored in the database. The system:

1. Creates or updates league records
2. Creates or updates team records
3. Creates or updates match records with all relevant details

### Error Handling

The commands include robust error handling:

- API request failures are caught and logged
- Database transaction errors are handled gracefully
- Detailed error messages are displayed in the console

### Output Format

The commands display:

1. The URL being fetched
2. A formatted table of matches with:
   - Match ID
   - Status
   - Home team
   - Away team
   - Score

## Integration with Scheduled Tasks

These commands can be integrated with Laravel's scheduler to automatically fetch data at regular intervals:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Fetch today's data for all sports every hour
    $schedule->command('api:sports --sport=all --day=' . now()->day . ' --month=' . now()->month . ' --year=' . now()->year)
             ->hourly();
    
    // Fetch tomorrow's scheduled matches once a day
    $schedule->command('api:sports --sport=all --day=' . now()->addDay()->day . ' --month=' . now()->addDay()->month . ' --year=' . now()->addDay()->year)
             ->dailyAt('00:01');
}
```

## Requirements

To use these commands, ensure:

1. Your `.env` file contains the required RapidAPI credentials:
   ```
   RAPIDAPI_KEY=your-api-key
   RAPIDAPI_HOST=allsportsapi2.p.rapidapi.com
   RAPIDAPI_BASE_URL=https://allsportsapi2.p.rapidapi.com
   ```

2. The database is properly configured (PostgreSQL is recommended)

3. You have run all migrations:
   ```bash
   php artisan migrate
   ```

## Troubleshooting

If you encounter issues:

1. Check your RapidAPI credentials
2. Verify your database connection
3. Check the Laravel logs in `storage/logs/laravel.log`
4. Ensure you have sufficient RapidAPI quota remaining
