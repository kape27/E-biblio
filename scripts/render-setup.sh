#!/bin/bash

# E-Lib Render Setup Script
# This script helps configure the project for Render deployment

set -e

echo "🚀 E-Lib Render Setup"
echo "===================="
echo ""

# Check if git is initialized
if [ ! -d .git ]; then
    echo "📦 Initializing Git repository..."
    git init
    git add .
    git commit -m "Initial commit for Render deployment"
    echo "✅ Git repository initialized"
else
    echo "✅ Git repository already exists"
fi

# Check if render.yaml exists
if [ ! -f render.yaml ]; then
    echo "❌ render.yaml not found!"
    echo "Please ensure render.yaml is in the project root"
    exit 1
else
    echo "✅ render.yaml found"
fi

# Check if Dockerfile exists
if [ ! -f Dockerfile ]; then
    echo "❌ Dockerfile not found!"
    exit 1
else
    echo "✅ Dockerfile found"
fi

# Check if healthcheck.php exists
if [ ! -f healthcheck.php ]; then
    echo "❌ healthcheck.php not found!"
    exit 1
else
    echo "✅ healthcheck.php found"
fi

echo ""
echo "📝 Next steps:"
echo ""
echo "1. Push your code to GitHub/GitLab/Bitbucket:"
echo "   git remote add origin <your-repo-url>"
echo "   git push -u origin main"
echo ""
echo "2. Go to Render Dashboard: https://dashboard.render.com"
echo ""
echo "3. Create a new Blueprint:"
echo "   - Click 'New +' → 'Blueprint'"
echo "   - Connect your Git repository"
echo "   - Render will detect render.yaml automatically"
echo "   - Click 'Apply'"
echo ""
echo "4. Wait for deployment (5-10 minutes)"
echo ""
echo "5. Access your app at the provided URL"
echo "   Default credentials: admin / admin123"
echo ""
echo "6. IMPORTANT: Change the admin password immediately!"
echo ""
echo "📖 Full documentation: See RENDER.md"
echo ""
echo "✨ Setup complete! Ready for Render deployment."
