#!/bin/bash

# E-Lib Render Database Backup Script
# Creates a backup of the Render MySQL database

set -e

DATABASE_NAME="${1:-elib-db}"
BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/render_backup_$TIMESTAMP.sql"

echo "💾 E-Lib Render Database Backup"
echo "==============================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo -e "${BLUE}Creating backup of: $DATABASE_NAME${NC}"
echo "Backup file: $BACKUP_FILE"
echo ""

if command -v render &> /dev/null; then
    # Use Render CLI
    echo "Using Render CLI..."
    render db backup "$DATABASE_NAME"
    echo ""
    echo -e "${GREEN}✅ Backup created on Render${NC}"
    echo ""
    echo "To download the backup:"
    echo "  1. Go to https://dashboard.render.com"
    echo "  2. Select your database: $DATABASE_NAME"
    echo "  3. Go to 'Backups' tab"
    echo "  4. Download the latest backup"
else
    echo "⚠️  Render CLI not installed"
    echo ""
    echo "To create a backup:"
    echo "  1. Go to https://dashboard.render.com"
    echo "  2. Select your database: $DATABASE_NAME"
    echo "  3. Go to 'Backups' tab"
    echo "  4. Click 'Create Backup'"
    echo ""
    echo "To install Render CLI:"
    echo "  npm install -g @render/cli"
fi

echo ""
echo "📖 For more information, see RENDER.md"
