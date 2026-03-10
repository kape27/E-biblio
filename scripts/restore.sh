#!/bin/bash
# E-Lib Restore Script
# Restores database and uploaded files from backup

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

# Check if backup file is provided
if [ -z "$1" ]; then
    echo -e "${RED}Usage: $0 <backup_file.tar.gz>${NC}"
    echo -e "Available backups:"
    ls -lh backups/elib_backup_*.tar.gz 2>/dev/null || echo "No backups found"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "${BACKUP_FILE}" ]; then
    echo -e "${RED}Backup file not found: ${BACKUP_FILE}${NC}"
    exit 1
fi

echo -e "${YELLOW}⚠️  WARNING: This will overwrite current data!${NC}"
echo -e "${YELLOW}Press Ctrl+C to cancel, or Enter to continue...${NC}"
read

TEMP_DIR=$(mktemp -d)
trap "rm -rf ${TEMP_DIR}" EXIT

echo -e "${BLUE}Extracting backup...${NC}"
tar -xzf "${BACKUP_FILE}" -C "${TEMP_DIR}"

# Find SQL file
SQL_FILE=$(find "${TEMP_DIR}" -name "*.sql" | head -n 1)
UPLOADS_FILE=$(find "${TEMP_DIR}" -name "*_uploads.tar.gz" | head -n 1)

if [ -z "${SQL_FILE}" ]; then
    echo -e "${RED}No SQL file found in backup${NC}"
    exit 1
fi

# Restore database
echo -e "${BLUE}Restoring database...${NC}"
docker-compose exec -T db mysql \
    -u elib_user \
    -pelib_password \
    elib_database < "${SQL_FILE}"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database restored${NC}"
else
    echo -e "${RED}✗ Database restore failed${NC}"
    exit 1
fi

# Restore uploaded files
if [ -n "${UPLOADS_FILE}" ]; then
    echo -e "${BLUE}Restoring uploaded files...${NC}"
    tar -xzf "${UPLOADS_FILE}" -C .
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Files restored${NC}"
    else
        echo -e "${RED}✗ Files restore failed${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}✓ Restore completed successfully!${NC}"
