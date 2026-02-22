.PHONY: dev dev-windows dev-ps dev-docker stop-elasticsearch clean help

# Default target
help:
	@echo "UniLearn Development Commands:"
	@echo ""
	@echo "  dev          - Start development server with Elasticsearch (auto-detect OS)"
	@echo "  dev-windows  - Start development server on Windows"
	@echo "  dev-ps       - Start development server with PowerShell"
	@echo "  dev-docker   - Start development server with Docker only"
	@echo "  stop-elasticsearch - Stop Elasticsearch container"
	@echo "  clean        - Clean up containers and cache"
	@echo ""

# Auto-detect OS and run appropriate command
dev:
	@if [[ "$$OSTYPE" == "msys" ]] || [[ "$$OSTYPE" == "cygwin" ]] || [[ "$$OSTYPE" == "win32" ]]; then \
		make dev-windows; \
	elif [[ "$$OSTYPE" == "darwin"* ]]; then \
		make dev-macos; \
	else \
		make dev-linux; \
	fi

# Windows development
dev-windows:
	@echo "🚀 Starting UniLearn on Windows..."
	@if exist start-dev.bat \
		start-dev.bat; \
	else \
		echo "❌ start-dev.bat not found"; \
		exit 1; \
	fi

# PowerShell development
dev-ps:
	@echo "🚀 Starting UniLearn with PowerShell..."
	@if exist start-dev.ps1 \
		powershell -ExecutionPolicy Bypass -File start-dev.ps1; \
	else \
		echo "❌ start-dev.ps1 not found"; \
		exit 1; \
	fi

# macOS/Linux development
dev-macos dev-linux:
	@echo "🚀 Starting UniLearn on Unix..."
	@docker run -d --name unilearn-elasticsearch -p 9200:9200 -e "discovery.type=single-node" -e "xpack.security.enabled=false" elasticsearch:8.11.0 || echo "Elasticsearch already running"
	@sleep 5
	@php bin/console fos:elastica:populate
	@php bin/console server:run

# Docker only
dev-docker:
	@echo "🐳 Starting Elasticsearch with Docker..."
	docker run -d --name unilearn-elasticsearch -p 9200:9200 -e "discovery.type=single-node" -e "xpack.security.enabled=false" elasticsearch:8.11.0 || echo "Elasticsearch already running"
	@echo "⏳ Waiting for Elasticsearch to be ready..."
	@sleep 10
	@php bin/console fos:elastica:populate
	@echo "✅ Ready! Symfony server can be started with: php bin/console server:run"

# Stop Elasticsearch
stop-elasticsearch:
	@echo "🛑 Stopping Elasticsearch..."
	@docker stop unilearn-elasticsearch || echo "Elasticsearch container not running"
	@docker rm unilearn-elasticsearch || echo "Elasticsearch container not found"

# Clean up
clean:
	@echo "🧹 Cleaning up..."
	@make stop-elasticsearch
	@php bin/console cache:clear
	@docker system prune -f
	@echo "✅ Cleanup complete!"
