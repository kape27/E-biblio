.PHONY: help build up down restart logs shell db-shell backup restore clean

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
NC := \033[0m # No Color

help: ## Show this help message
	@echo "$(BLUE)E-Lib Docker Commands$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-15s$(NC) %s\n", $$1, $$2}'

build: ## Build Docker images
	@echo "$(BLUE)Building Docker images...$(NC)"
	docker-compose build --no-cache

up: ## Start all services
	@echo "$(BLUE)Starting E-Lib services...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)E-Lib is running!$(NC)"
	@echo "Web: http://localhost:8080"
	@echo "phpMyAdmin: http://localhost:8081"

down: ## Stop all services
	@echo "$(YELLOW)Stopping E-Lib services...$(NC)"
	docker-compose down

restart: ## Restart all services
	@echo "$(BLUE)Restarting E-Lib services...$(NC)"
	docker-compose restart

logs: ## Show logs (use 'make logs service=web' for specific service)
	@if [ -z "$(service)" ]; then \
		docker-compose logs -f; \
	else \
		docker-compose logs -f $(service); \
	fi

shell: ## Access web container shell
	@echo "$(BLUE)Accessing web container...$(NC)"
	docker-compose exec web bash

db-shell: ## Access database shell
	@echo "$(BLUE)Accessing database...$(NC)"
	docker-compose exec db mysql -u elib_user -pelib_password elib_database

backup: ## Backup database
	@echo "$(BLUE)Creating database backup...$(NC)"
	@mkdir -p backups
	docker-compose exec db mysqldump -u elib_user -pelib_password elib_database > backups/backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Backup created in backups/$(NC)"

restore: ## Restore database (use 'make restore file=backup.sql')
	@if [ -z "$(file)" ]; then \
		echo "$(YELLOW)Usage: make restore file=backup.sql$(NC)"; \
		exit 1; \
	fi
	@echo "$(BLUE)Restoring database from $(file)...$(NC)"
	docker-compose exec -T db mysql -u elib_user -pelib_password elib_database < $(file)
	@echo "$(GREEN)Database restored!$(NC)"

clean: ## Remove all containers, volumes, and images
	@echo "$(YELLOW)⚠️  This will remove all data! Press Ctrl+C to cancel...$(NC)"
	@sleep 5
	docker-compose down -v
	docker system prune -f
	@echo "$(GREEN)Cleanup complete!$(NC)"

status: ## Show status of all services
	@echo "$(BLUE)E-Lib Services Status:$(NC)"
	docker-compose ps

setup: ## Initial setup (build and start)
	@echo "$(BLUE)Setting up E-Lib...$(NC)"
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "$(GREEN)Created .env file$(NC)"; \
	fi
	$(MAKE) build
	$(MAKE) up
	@echo "$(GREEN)Setup complete!$(NC)"
	@echo "Access E-Lib at: http://localhost:8080"
	@echo "Default credentials: admin / admin123"

update: ## Update and rebuild services
	@echo "$(BLUE)Updating E-Lib...$(NC)"
	git pull
	docker-compose build
	docker-compose up -d
	@echo "$(GREEN)Update complete!$(NC)"
