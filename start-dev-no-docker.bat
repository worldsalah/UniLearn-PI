@echo off
echo ========================================
echo Starting UniLearn Development Server
echo ========================================

:: Check if Elasticsearch is running on localhost:9200
curl -s http://localhost:9200/_cluster/health >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Elasticsearch is already running
) else (
    echo ❌ Elasticsearch is NOT running
    echo.
    echo Please start Elasticsearch manually:
    echo 1. Download Elasticsearch: https://www.elastic.co/downloads/elasticsearch
    echo 2. Extract to: C:\elasticsearch\
    echo 3. Run: C:\elasticsearch\bin\elasticsearch.bat
    echo 4. Wait for it to start (green status at http://localhost:9200)
    echo.
    echo Once Elasticsearch is running, press any key to continue...
    pause >nul
)

:: Clear cache
echo 🧹 Clearing cache...
php bin/console cache:clear

:: Try to populate Elasticsearch
echo 📊 Attempting to populate Elasticsearch...
php bin/console fos:elastica:populate

:: Start Symfony server
echo ========================================
echo 🚀 Starting Symfony Development Server
echo ========================================
echo Server will be available at: http://localhost:8000
echo Press Ctrl+C to stop the server
echo ========================================

php bin/console server:run
