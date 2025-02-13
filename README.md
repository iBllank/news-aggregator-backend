# News Aggregator Backend

## Overview
This project is a Laravel-based news aggregator (Backend) that fetches and stores articles from multiple sources. It supports scheduled fetching, user preferences, authentication, and caching using Redis.

## Features
- Fetch news from different APIs (News API, The Guardian, NYT, etc.).
- Store articles in a MySQL database.
- Implemented user authentication and User Preferences Management to personalize content filtering.
- Optimized Performance with Redis caching.
- Automated News Fetching via Laravel scheduled tasks.
- API endpoints for frontend.
- Secure Authentication using Laravel Sanctum for API token-based authentication.

## Requirements
- PHP 8+
- Laravel 11
- Docker & Laravel Sail
- MySQL
- Redis

## Code Quality & Best Practices  

This project follows industry-standard coding principles to ensure maintainability, scalability, and efficiency:  

- **DRY (Don't Repeat Yourself):** Common functionalities are abstracted into reusable services and helpers.  
- **KISS (Keep It Simple, Stupid):** The code remains clean and straightforward, avoiding unnecessary complexity.  
- **SOLID Principles:** The code is structured using single-responsibility controllers, dependency injection, and interface-driven design.  
- **Efficient Querying:** Eloquent relationships and caching (Redis) are used to optimize database queries.  
- **Security Best Practices:** Laravel Sanctum is used for authentication, and environment variables handle sensitive configurations.  
- **Scalable & Modular:** The architecture allows easy feature expansion and modification. 

## Installation
```sh
# Clone the repository
git clone https://github.com/iBllank/news-aggregator-backend.git
cd news-aggregator-backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Start Docker containers
sail up -d

# Generate new APP_KEY
sail artisan key:generate

# Run database migrations
sail artisan migrate
```

## Running the Application
```sh
# Start the Laravel Sail environment
sail up -d

# Fetch news manually
sail artisan news:fetch

# Run the scheduler (Alternative to system cron job)
sail artisan schedule:work
```

## API Endpoints
### Authentication
- `POST /api/register` - Register a new user.
- `POST /api/login` - Authenticate a user.

### Articles
- `GET /api/articles` - Fetch stored articles and search, filter and user preferences.
- `GET /api/filters` - Return distinct, non-empty filters from data in DB.
  
### User Preferences
- `POST /api/preferences` - Save user preferences.
- `GET /api/preferences` - Retrieve user preferences.

## Running Tests
```sh
# Run unit and feature tests
sail artisan test
```

## Scheduling Tasks
In a development environment, use:
```sh
sail artisan schedule:work
```

For production, set up a cron job:
```sh
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Project Structure
```
news-aggregator-backend/
├── app/
│   ├── Console/Commands/FetchNewsCommand.php
│   ├── Http/Controllers/Api/ArticleController.php
|   ├── Http/Controllers/Api/AuthController.php
|   ├── Http/Controllers/Api/PreferenceController.php
│   ├── Services/NewsService.php
├── database/
│   ├── factories/
│   ├── migrations/
├── routes/
│   ├── api.php
│   ├── console.php
├── config/
│   ├── app.php
│   ├── cache.php
│   ├── database.php
├── .env
├── docker-compose.yml
├── README.md
```

## Environment Variables (.env)
```env
APP_NAME=NewsAggregatorBackend
APP_KEY=base64:YOUR_APP_KEY_HERE
DB_DATABASE=news-aggregator
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
CACHE_STORE=redis
CACHE_PREFIX=
REDIS_CLIENT=phpredis
NEWS_API_KEY=YOUR_NEWS_API_KEY
GUARDIAN_API_KEY=YOUR_GUARDIAN_API_KEY
NYT_API_KEY=YOUR_NYT_API_KEY
```

## Deployment
- Ensure `.env` is set up correctly.
- Use a cron job for scheduling tasks.
- Configure database and cache settings for production.

