#!/bin/bash
# E-Lib Backup Script
# Creates full backup of database and uploaded files

set -e

# Configuration
BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="elib_backup_${TIMESTAMP}"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}Starting E-Lib backup...${NC}"

# Create backup directory
mkdir -p "${BACKUP_DIR}"

# Backup database
echo -e "${BLUE}Backing up database...${NC}"
docker-compose exec -T db mysqldump \
    -u elib_user \
    -pelib_password \
    --single-transaction \
    --routines \
    --triggers \
    elib_database > "${BACKUP_DIR}/${BACKUP_NAME}.sql"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database backup created${NC}"
else
    echo -e "${RED}✗ Database backup failed${NC}"
    exit 1
fi

# Backup uploaded files
echo -e "${BLUE}Backing up uploaded files...${NC}"
tar -czf "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz" \
    uploads/books \
    uploads/covers \
    2>/dev/null || true

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Files backup created${NC}"
else
    echo -e "${RED}✗ Files backup failed${NC}"
    exit 1
fi

# Create combined archive
echo -e "${BLUE}Creating combined archive...${NC}"
tar -czf "${BACKUP_DIR}/${BACKUP_NAME}_complete.tar.gz" \
    -C "${BACKUP_DIR}" \
    "${BACKUP_NAME}.sql" \
    "${BACKUP_NAME}_uploads.tar.gz"

# Cleanup individual files
rm -f "${BACKUP_DIR}/${BACKUP_NAME}.sql"
rm -f "${BACKUP_DIR}/${BACKUP_NAME}_uploads.tar.gz"

# Calculate size
BACKUP_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}_complete.tar.gz" | cut -f1)

echo -e "${GREEN}✓ Backup completed successfully!${NC}"
echo -e "Backup file: ${BACKUP_DIR}/${BACKUP_NAME}_complete.tar.gz"
echo -e "Size: ${BACKUP_SIZE}"

# Cleanup old backups (keep last 7 days)
echo -e "${BLUE}Cleaning up old backups...${NC}"
find "${BACKUP_DIR}" -name "elib_backup_*.tar.gz" -mtime +7 -delete
echo -e "${GREEN}✓ Cleanup completed${NC}"
