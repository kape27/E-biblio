#!/bin/bash

# E-Lib Render Logs Viewer
# Quick access to Render logs

SERVICE_NAME="${1:-elib-web}"

echo "📋 Viewing logs for: $SERVICE_NAME"
echo "=================================="
echo ""

if command -v render &> /dev/null; then
    # Use Render CLI if available
    render logs -s "$SERVICE_NAME" -f
else
    echo "⚠️  Render CLI not installed"
    echo ""
    echo "To install Render CLI:"
    echo "  npm install -g @render/cli"
    echo ""
    echo "Or view logs in the dashboard:"
    echo "  https://dashboard.render.com"
fi
