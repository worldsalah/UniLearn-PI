@echo off
echo ========================================
echo Starting UniLearn Development Server
echo ========================================

:: Check if Docker is running
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not installed or not running
    echo Please install Docker Desktop first
    pause
    exit /b 1
)

:: Check if Elasticsearch container is already running
docker ps -f name=unilearn-elasticsearch --format "table {{.Names}}" | findstr "unilearn-elasticsearch" >nul
if %errorlevel% equ 0 (
    echo ✅ Elasticsearch is already running
) else (
    echo 🐳 Starting Elasticsearch...
    docker run -d --name unilearn-elasticsearch -p 9200:9200 -e "discovery.type=single-node" -e "xpack.security.enabled=false" elasticsearch:8.11.0
    
    if %errorlevel% equ 0 (
        echo ✅ Elasticsearch started successfully
    ) else (
        echo ❌ Failed to start Elasticsearch
        pause
        exit /b 1
    )
    
    :: Wait for Elasticsearch to be ready
    echo ⏳ Waiting for Elasticsearch to be ready...
    :wait_loop
    timeout /t 2 >nul
    curl -s http://localhost:9200/_cluster/health >nul 2>&1
    if %errorlevel% equ 0 (
        echo ✅ Elasticsearch is ready!
    ) else (
        goto wait_loop
    )
)

:: Clear cache and populate Elasticsearch
echo 🧹 Clearing cache...
php bin/console cache:clear

echo 📊 Populating Elasticsearch index...
php bin/console fos:elastica:populate

:: Start Symfony server
echo ========================================
echo 🚀 Starting Symfony Development Server
echo ========================================
echo Server will be available at: http://localhost:8000
echo Press Ctrl+C to stop the server
echo ========================================

php bin/console server:run
