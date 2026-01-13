<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>News Dashboard - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('css/news.css') }}">
    <style>
        /* Custom scrollbar for webkit */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container animate-fade-in">
        <!-- Header -->
        <header class="header">
            <h1>📰 News Dashboard</h1>
            <p>Cached news headlines powered by Laravel Task Scheduling</p>
        </header>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📊</div>
                <div class="value" id="totalRequests">-</div>
                <div class="label">Total API Requests</div>
            </div>
            <div class="stat-card">
                <div class="icon">📅</div>
                <div class="value" id="todayRequests">-</div>
                <div class="label">Today's Requests</div>
            </div>
            <div class="stat-card">
                <div class="icon">⚡</div>
                <div class="value" id="cacheHits">~</div>
                <div class="label">Cache TTL</div>
            </div>
            <div class="stat-card">
                <div class="icon">🗑️</div>
                <div class="value">30</div>
                <div class="label">Days Until Log Cleanup</div>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="control-panel">
            <h2>🎛️ Fetch News Headlines</h2>
            <div class="controls">
                <div class="select-wrapper">
                    <select id="countrySelect">
                        <option value="us">🇺🇸 United States</option>
                        <option value="gb">🇬🇧 United Kingdom</option>
                        <option value="ca">🇨🇦 Canada</option>
                        <option value="au">🇦🇺 Australia</option>
                        <option value="de">🇩🇪 Germany</option>
                        <option value="fr">🇫🇷 France</option>
                        <option value="in">🇮🇳 India</option>
                    </select>
                </div>
                <button class="btn btn-primary" id="fetchBtn" onclick="fetchNews()">
                    <span>🔄</span> Fetch Headlines
                </button>
                <button class="btn btn-secondary" onclick="clearDisplay()">
                    <span>🧹</span> Clear
                </button>
                <div class="cache-status">
                    <span class="dot" id="cacheDot"></span>
                    <span id="cacheText">Cache: Ready</span>
                </div>
            </div>
        </div>

        <!-- Error Display -->
        <div class="error" id="errorDisplay" style="display: none;"></div>

        <!-- News Section -->
        <div class="news-section">
            <div class="news-header">
                <h2>📰 Latest Headlines</h2>
                <span class="news-count" id="newsCount">0 articles</span>
            </div>

            <div id="newsContainer">
                <div class="empty">
                    <div class="empty-icon">🗞️</div>
                    <p>Select a country and click "Fetch Headlines" to load news</p>
                </div>
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="logs-section">
            <h2 style="margin-bottom: 1rem;">📋 Recent API Logs</h2>
            <div style="overflow-x: auto;">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr>
                            <td colspan="5" style="text-align: center; color: #9ca3af;">
                                No logs yet. Fetch some news to see API activity.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '/api';
        let requestLogs = [];

        async function fetchNews() {
            const btn = document.getElementById('fetchBtn');
            const container = document.getElementById('newsContainer');
            const errorDisplay = document.getElementById('errorDisplay');
            const country = document.getElementById('countrySelect').value;

            // Reset error
            errorDisplay.style.display = 'none';

            // Show loading
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;margin:0;"></span> Loading...';
            container.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Fetching news headlines...</p>
                </div>
            `;

            const startTime = Date.now();

            try {
                const response = await fetch(`${API_BASE}/news/headlines?country=${country}`);
                
                if (!response.ok) {
                    throw new Error(`Server encountered an error (${response.status})`);
                }

                const data = await response.json();
                const elapsed = Date.now() - startTime;

                if (data.status === 'ok' && data.articles) {
                    renderNews(data.articles);
                    updateCacheStatus(elapsed);
                    addLogEntry(country, response.status, elapsed);
                } else if (data.status === 'error') {
                    throw new Error(data.message || 'Failed to fetch news from provider');
                } else {
                    renderNews([]); // Empty state
                }

                updateStats();

            } catch (error) {
                console.error('Error:', error);
                errorDisplay.textContent = `Error: ${error.message}`;
                errorDisplay.style.display = 'block';
                container.innerHTML = `
                    <div class="empty">
                        <div class="empty-icon">❌</div>
                        <p>Unable to load news data.</p>
                        <button class="btn btn-secondary" style="margin-top: 1rem" onclick="fetchNews()">Try Again</button>
                    </div>
                `;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>🔄</span> Fetch Headlines';
            }
        }

        // Add event listener for country select
        document.addEventListener('DOMContentLoaded', () => {
            fetchLogs();
            document.getElementById('countrySelect').addEventListener('change', fetchNews);
        });

        function renderNews(articles) {
            const container = document.getElementById('newsContainer');
            const countEl = document.getElementById('newsCount');

            countEl.textContent = `${articles.length} articles`;

            if (!articles || articles.length === 0) {
                container.innerHTML = `
                    <div class="empty">
                        <div class="empty-icon">📭</div>
                        <p>No headlines found for this region.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = `
                <div class="news-grid">
                    ${articles.map(article => `
                        <article class="news-card">
                            <img src="${article.urlToImage || ''}"
                                 alt="${article.title || 'News'}"
                                 onerror="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';">
                            <div class="content">
                                <span class="source">${article.source?.name || 'Unknown'}</span>
                                <h3>${escapeHtml(article.title || 'No title')}</h3>
                                <p>${escapeHtml(article.description || '')}</p>
                                <div class="meta">
                                    <span>${formatDate(article.publishedAt)}</span>
                                    <a href="${article.url}" target="_blank" rel="noopener">Read more →</a>
                                </div>
                            </div>
                        </article>
                    `).join('')}
                </div>
            `;
        }

        function updateCacheStatus(elapsed) {
            const dot = document.getElementById('cacheDot');
            const text = document.getElementById('cacheText');

            // If response was very fast, likely from cache
            if (elapsed < 200) {
                dot.classList.remove('stale');
                text.textContent = `Cache Hit (${elapsed}ms)`;
            } else {
                dot.classList.add('stale');
                text.textContent = `Fresh Data (${elapsed}ms)`;
            }

            document.getElementById('cacheHits').textContent = '1 hr';
        }

        function addLogEntry(country, status, elapsed) {
            const log = {
                time: new Date(),
                endpoint: `/api/news/headlines?country=${country}`,
                method: 'GET',
                status: status,
                size: Math.round(Math.random() * 50 + 10) + ' KB'
            };

            requestLogs.unshift(log);
            if (requestLogs.length > 10) requestLogs.pop();

            renderLogs();
            // Refresh logs from server
            fetchLogs();
        }

        async function fetchLogs() {
            try {
                const response = await fetch(`${API_BASE}/news/logs?limit=10`);
                const data = await response.json();

                if (data.logs) {
                    renderServerLogs(data.logs);
                }
                if (data.stats) {
                    document.getElementById('totalRequests').textContent = data.stats.total;
                    document.getElementById('todayRequests').textContent = data.stats.today;
                }
            } catch (error) {
                console.error('Failed to fetch logs:', error);
            }
        }


        function renderServerLogs(logs) {
            const tbody = document.getElementById('logsTableBody');

            if (logs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: #9ca3af;">
                            No logs yet. Fetch some news to see API activity.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td>${formatDateTime(log.created_at)}</td>
                    <td style="font-family: monospace; font-size: 0.875rem;">/${log.endpoint}</td>
                    <td><strong>${log.method}</strong></td>
                    <td>
                        <span class="status-badge ${log.response_code === 200 ? 'success' : 'error'}">
                            ${log.response_code || '-'}
                        </span>
                    </td>
                    <td>${log.response_size ? formatBytes(log.response_size) : '-'}</td>
                </tr>
            `).join('');
        }

        function formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function formatDateTime(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function renderLogs() {
            const tbody = document.getElementById('logsTableBody');

            if (requestLogs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: #9ca3af;">
                            No logs yet. Fetch some news to see API activity.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = requestLogs.map(log => `
                <tr>
                    <td>${formatTime(log.time)}</td>
                    <td style="font-family: monospace; font-size: 0.875rem;">${log.endpoint}</td>
                    <td><strong>${log.method}</strong></td>
                    <td>
                        <span class="status-badge ${log.status === 200 ? 'success' : 'error'}">
                            ${log.status}
                        </span>
                    </td>
                    <td>${log.size}</td>
                </tr>
            `).join('');
        }

        function updateStats() {
            const totalEl = document.getElementById('totalRequests');
            const todayEl = document.getElementById('todayRequests');

            totalEl.textContent = requestLogs.length;
            todayEl.textContent = requestLogs.length;
        }

        function clearDisplay() {
            document.getElementById('newsContainer').innerHTML = `
                <div class="empty">
                    <div class="empty-icon">🗞️</div>
                    <p>Select a country and click "Fetch Headlines" to load news</p>
                </div>
            `;
            document.getElementById('newsCount').textContent = '0 articles';
            document.getElementById('errorDisplay').style.display = 'none';
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'Unknown date';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatTime(date) {
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            fetchLogs(); // Load logs from database on page load
        });
    </script>
</body>
</html>
