#!/bin/bash

# COPY AND PASTE THIS INTO YOUR DIGITAL OCEAN SERVER TERMINAL
# This will automatically deploy everything after you git push

echo "╔════════════════════════════════════════════════════════════╗"
echo "║     RUN THIS ON YOUR DIGITAL OCEAN SERVER                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Navigate to project (adjust path if different)
cd /var/www/ebims || cd /var/www/html/ebims || { echo "❌ Could not find project directory"; exit 1; }

echo "📁 Current directory: $(pwd)"
echo ""

# Pull latest code
echo "📥 Pulling latest code from Git..."
git pull origin master

# Run the quick deploy script
echo ""
echo "🚀 Running deployment..."
chmod +x deployment/quick_deploy.sh
bash deployment/quick_deploy.sh

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                 ALL DONE! ✅                                ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 Test your login now: https://your-domain.com/login"
echo ""
