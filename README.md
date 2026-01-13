# Laravel Task Scheduling & Caching Assignment

This project demonstrates the implementation of **Task Scheduling**, **Caching**, and **API Logging** in a Laravel application, fulfilling the requirements for the "Implementing Task Scheduling and Caching in Laravel" assignment.

## 📋 Assignment Objectives

1.  **Database Logging**: Store logs of frequent API requests.
2.  **Scheduler**: Automatically delete logs older than 30 days.
3.  **Caching**: Cache external API responses (NewsAPI) for faster retrieval.
4.  **Cache Invalidation**: Ensure cache is refreshed every hour.
5.  **Artisan Commands**: Demonstrate custom commands for managing these processes.

---

## 🚀 Implementation Details

### 1. External Service Integration
We integrated the **[NewsAPI](https://newsapi.org/)** service using the `jcobhams/newsapi` library.
- **Service Class**: `App\Services\NewsApiService.php`
- **Logic**: Fetches top headlines based on country. Robustly handles errors and ensures only valid responses are processed.

### 2. Caching Strategy
To improve performance and reduce API usage, responses are cached using Laravel's Cache facade (File/Redis/Memcached compatible).
- **Cache Key**: `news.headlines.v3.{country}`
- **TTL (Time To Live)**: 1 Hour (3600 seconds).
- **Implementation**: The service checks for existing cache before making an external request.

### 3. Automated Task Scheduling
The application utilizes Laravel's Scheduler to handle periodic maintenance. defined in `routes/console.php`.

| Task | Schedule | Description |
| :--- | :--- | :--- |
| `logs:clean` | **Daily** | Deletes API logs that are older than 30 days. |
| `cache:clear-news` | **Hourly** | (Optional) Force-clears the news cache to ensure invalidation. |

### 4. Custom Artisan Commands
We created dedicated console commands to manage the system manually or via cron.

- **`php artisan logs:clean`**
    - Source: `App\Console\Commands\CleanOldLogs.php`
    - Action: Removes old database entries from the `api_logs` table.

- **`php artisan cache:clear-news`**
    - Source: `App\Console\Commands\ClearNewsCache.php`
    - Action: Targeted clearing of news-related cache keys without flushing the entire system cache.

### 5. API Endpoints
- **GET** `/api/news/headlines?country=us`
    - Fetches news for a specific country.
    - Logs the request details (User Agent, IP, success status) to the database.
    - Returns cached data if available; otherwise fetches fresh data.

---

## 🛠️ Setup & Usage

### Prerequisites
- PHP 8.1+
- Composer
- A Database (MySQL)
- A generic [NewsAPI Key](https://newsapi.org/)

### Installation
1.  **Clone the repository**
2.  **Install Dependencies**:
    ```bash
    composer install
    npm install && npm run build
    ```
3.  **Configure Environment**:
    Copy `.env.example` to `.env` and set your credentials:
    ```ini
    DB_CONNECTION=mysql
    NEWS_API_KEY=your_api_key_here
    ```
4.  **Run Migrations**:
    ```bash
    php artisan migrate
    ```

### Running the Scheduler
For local development, you can run the scheduler in the foreground:
```bash
php artisan schedule:work
```

 
### Testing the UI
Visit the application in your browser (e.g., `http://localhost:8000/news`).
- **Dashboard**: View latest news with a modern glassmorphism UI.
- **Real-time Logs**: View the API request logs directly on the page.
- **Controls**: Switch countries to see auto-fetching and caching in action.
