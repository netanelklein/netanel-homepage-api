#!/bin/bash

# API Test Script - Quick endpoint validation
# Tests all major API endpoints for basic functionality

API_URL="http://localhost:8000"
echo "🧪 Testing API endpoints at $API_URL"
echo "========================================"

# Function to test an endpoint
test_endpoint() {
    local endpoint=$1
    local description=$2
    
    echo -n "Testing $description... "
    
    response=$(curl -s -w "%{http_code}" -o /tmp/api_response "$API_URL$endpoint")
    http_code=${response: -3}
    
    if [ "$http_code" -eq 200 ]; then
        echo "✅ OK ($http_code)"
        # Show first 100 chars of response
        echo "   Response: $(cat /tmp/api_response | head -c 100)..."
    else
        echo "❌ FAILED ($http_code)"
        echo "   Error: $(cat /tmp/api_response)"
    fi
    echo
}

# Check if server is running
echo "🔍 Checking if API server is running..."
if ! curl -s "$API_URL" > /dev/null 2>&1; then
    echo "❌ API server not running at $API_URL"
    echo "💡 Start it with: ./test-local.sh"
    exit 1
fi

echo "✅ API server is running"
echo

# Test all major endpoints
test_endpoint "/api/health" "Health Check"
test_endpoint "/api/health/status" "Health Status"
test_endpoint "/api/portfolio/personal-info" "Personal Info"
test_endpoint "/api/portfolio/projects" "Projects"
test_endpoint "/api/portfolio/skills" "Skills"
test_endpoint "/api/portfolio/experience" "Experience"
test_endpoint "/api/portfolio/education" "Education"

echo "🏁 API testing complete!"
echo "💡 To start the API server: ./test-local.sh"
