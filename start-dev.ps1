# UniLearn Development Server Starter
# PowerShell script to start Symfony server with Elasticsearch

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Starting UniLearn Development Server" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Check if Docker is available
try {
    docker --version | Out-Null
    Write-Host "✅ Docker is available" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker is not installed or not running" -ForegroundColor Red
    Write-Host "Please install Docker Desktop first" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Check if Elasticsearch container is already running
$elasticsearchContainer = docker ps -f name=unilearn-elasticsearch --format "{{.Names}}" | Select-String "unilearn-elasticsearch"
if ($elasticsearchContainer) {
    Write-Host "✅ Elasticsearch is already running" -ForegroundColor Green
} else {
    Write-Host "🐳 Starting Elasticsearch..." -ForegroundColor Yellow
    
    try {
        docker run -d --name unilearn-elasticsearch -p 9200:9200 -e "discovery.type=single-node" -e "xpack.security.enabled=false" elasticsearch:8.11.0 | Out-Null
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Elasticsearch started successfully" -ForegroundColor Green
        } else {
            Write-Host "❌ Failed to start Elasticsearch" -ForegroundColor Red
            Read-Host "Press Enter to exit"
            exit 1
        }
        
        # Wait for Elasticsearch to be ready
        Write-Host "⏳ Waiting for Elasticsearch to be ready..." -ForegroundColor Yellow
        $maxAttempts = 30
        $attempt = 0
        
        while ($attempt -lt $maxAttempts) {
            try {
                $response = Invoke-RestMethod -Uri "http://localhost:9200/_cluster/health" -TimeoutSec 2 -ErrorAction Stop
                Write-Host "✅ Elasticsearch is ready!" -ForegroundColor Green
                break
            } catch {
                $attempt++
                Start-Sleep -Seconds 2
            }
        }
        
        if ($attempt -ge $maxAttempts) {
            Write-Host "⚠️ Elasticsearch took too long to start, but continuing..." -ForegroundColor Yellow
        }
    } catch {
        Write-Host "❌ Error starting Elasticsearch: $_" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}

# Clear cache
Write-Host "🧹 Clearing cache..." -ForegroundColor Blue
php bin/console cache:clear

# Populate Elasticsearch
Write-Host "📊 Populating Elasticsearch index..." -ForegroundColor Blue
php bin/console fos:elastica:populate

# Start Symfony server
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "🚀 Starting Symfony Development Server" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Server will be available at: http://localhost:8000" -ForegroundColor White
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan

# Start Symfony server in the current process
php bin/console server:run
