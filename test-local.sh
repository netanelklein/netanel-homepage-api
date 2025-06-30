#!/bin/bash

# Quick Local API Testing Script
# Run the API using PHP's built-in server

echo "🚀 Starting Netanel Portfolio API for local testing..."
echo "📍 API will be available at: http://localhost:8000"
echo "🧪 Test endpoints:"
echo "   - Health Check: http://localhost:8000/api/health"
echo "   - Portfolio: http://localhost:8000/api/portfolio/personal-info"
echo "   - Projects: http://localhost:8000/api/portfolio/projects"
echo ""
echo "Press Ctrl+C to stop the server"
echo "----------------------------------------"

# Create basic .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating basic .env file..."
    cat > .env << 'EOF'
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_PORT=3306
DB_NAME=netanel_portfolio
DB_USERNAME=root
DB_PASSWORD=
SESSION_SECRET=local-test-secret-key
CACHE_ENABLED=false
EOF
    echo "✅ .env file created"
fi

# Start PHP built-in server
php -S localhost:8000 -t . index.php
