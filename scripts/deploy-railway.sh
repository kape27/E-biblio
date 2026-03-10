#!/bin/bash

# E-Lib Railway Deployment Script
# Quick deployment helper for Railway.app

set -e

echo "🚂 E-Lib Railway Deployment Helper"
echo "=================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo -e "${RED}❌ Git is not installed${NC}"
    exit 1
fi

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo -e "${YELLOW}📦 Initializing Git repository...${NC}"
    git init
    echo -e "${GREEN}✅ Git initialized${NC}"
fi

# Check for required files
echo -e "${BLUE}🔍 Checking required files...${NC}"
required_files=("Dockerfile" "healthcheck.php" "railway.toml")
missing_files=()

for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -ne 0 ]; then
    echo -e "${RED}❌ Missing required files:${NC}"
    printf '%s\n' "${missing_files[@]}"
    exit 1
fi

echo -e "${GREEN}✅ All required files present${NC}"
echo ""

# Check if remote is configured
if ! git remote | grep -q 'origin'; then
    echo -e "${YELLOW}⚠️  No Git remote configured${NC}"
    echo ""
    echo "Please add your Git repository:"
    echo -e "${BLUE}git remote add origin <your-repo-url>${NC}"
    echo ""
    read -p "Enter your Git repository URL (or press Enter to skip): " repo_url
    
    if [ -n "$repo_url" ]; then
        git remote add origin "$repo_url"
        echo -e "${GREEN}✅ Remote added${NC}"
    else
        echo -e "${YELLOW}⚠️  Skipping remote configuration${NC}"
    fi
fi

echo ""
echo -e "${BLUE}📝 Preparing deployment...${NC}"

# Add all files
git add .

# Check if there are changes to commit
if git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}⚠️  No changes to commit${NC}"
else
    # Commit changes
    echo ""
    read -p "Enter commit message (default: 'Deploy to Railway'): " commit_msg
    commit_msg=${commit_msg:-"Deploy to Railway"}
    
    git commit -m "$commit_msg"
    echo -e "${GREEN}✅ Changes committed${NC}"
fi

echo ""
echo -e "${BLUE}🚀 Ready to push to Git${NC}"
echo ""
read -p "Push to Git now? (y/n): " push_confirm

if [ "$push_confirm" = "y" ] || [ "$push_confirm" = "Y" ]; then
    # Get current branch
    current_branch=$(git branch --show-current)
    
    if [ -z "$current_branch" ]; then
        current_branch="main"
        git branch -M main
    fi
    
    echo -e "${BLUE}Pushing to branch: $current_branch${NC}"
    
    if git push -u origin "$current_branch"; then
        echo -e "${GREEN}✅ Code pushed successfully${NC}"
    else
        echo -e "${RED}❌ Push failed${NC}"
        echo "You may need to pull first: git pull origin $current_branch"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠️  Skipping push${NC}"
fi

echo ""
echo -e "${GREEN}✨ Deployment preparation complete!${NC}"
echo ""
echo -e "${BLUE}📋 Next steps:${NC}"
echo ""
echo "1. Go to Railway: https://railway.app"
echo ""
echo "2. Create a new project:"
echo "   • Click 'Start a New Project'"
echo "   • Select 'Deploy from GitHub repo'"
echo "   • Choose your repository 'E-biblio'"
echo "   • Railway will detect the Dockerfile automatically"
echo ""
echo "3. Add MySQL database:"
echo "   • In your project → 'New' → 'Database' → 'Add MySQL'"
echo "   • Variables are configured automatically"
echo ""
echo "4. Wait for deployment (3-5 minutes)"
echo ""
echo "5. Access your app at the provided Railway URL"
echo "   Default credentials: admin / admin123"
echo ""
echo "6. IMPORTANT: Change admin password immediately!"
echo ""
echo -e "${BLUE}📖 Full documentation: RAILWAY.md${NC}"
echo ""
echo -e "${GREEN}🚂 Ready for Railway deployment!${NC}"