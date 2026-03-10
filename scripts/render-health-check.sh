#!/bin/bash

# E-Lib Render Health Check Script
# Monitors the health of your deployed application

set -e

# Configuration
APP_URL="${1:-}"

if [ -z "$APP_URL" ]; then
    echo "Usage: $0 <app-url>"
    echo "Example: $0 https://elib-web.onrender.com"
    exit 1
fi

# Remove trailing slash
APP_URL="${APP_URL%/}"

echo "🏥 E-Lib Health Check"
echo "===================="
echo "URL: $APP_URL"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Function to check endpoint
check_endpoint() {
    local endpoint=$1
    local expected_status=$2
    local description=$3
    
    echo -n "Checking $description... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL$endpoint" || echo "000")
    
    if [ "$response" = "$expected_status" ]; then
        echo -e "${GREEN}✅ OK ($response)${NC}"
        return 0
    else
        echo -e "${RED}❌ FAILED ($response, expected $expected_status)${NC}"
        return 1
    fi
}

# Function to check JSON response
check_json_endpoint() {
    local endpoint=$1
    local description=$2
    
    echo -n "Checking $description... "
    
    response=$(curl -s "$APP_URL$endpoint")
    status=$(echo "$response" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
    
    if [ "$status" = "healthy" ] || [ "$status" = "ok" ]; then
        echo -e "${GREEN}✅ OK${NC}"
        return 0
    else
        echo -e "${RED}❌ FAILED${NC}"
        echo "Response: $response"
        return 1
    fi
}

# Run checks
echo "Running health checks..."
echo ""

failed=0

# Check main page
check_endpoint "/" "302" "Main page redirect" || ((failed++))

# Check login page
check_endpoint "/login.php" "200" "Login page" || ((failed++))

# Check health endpoint
check_json_endpoint "/healthcheck.php" "Health endpoint" || ((failed++))

# Check static assets
check_endpoint "/assets/css/style.css" "200" "CSS assets" || ((failed++))
check_endpoint "/assets/js/main.js" "200" "JS assets" || ((failed++))

echo ""
echo "===================="

if [ $failed -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed!${NC}"
    echo "Your application is healthy and running."
    exit 0
else
    echo -e "${RED}❌ $failed check(s) failed${NC}"
    echo "Please review the errors above."
    exit 1
fi
