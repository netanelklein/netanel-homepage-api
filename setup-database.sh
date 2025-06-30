#!/bin/bash

# Setup script for local MariaDB database
# Run this after creating the database user with create-db-user.sh

echo "🔧 Setting up Portfolio API database schema..."

# Database configuration from .env
DB_NAME="netanel_portfolio"
DB_USER="portfolio_api"
DB_PASS="portfolio_2025_secure"

echo "� Importing database schema to: $DB_NAME"

# Import schema using the dedicated API user
mysql -u$DB_USER -p$DB_PASS $DB_NAME < database/schema.sql

echo "✅ Database schema imported successfully!"
echo ""
echo "🔍 Verifying database setup..."
mysql -u$DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES;"

echo ""
echo "🚀 You can now test the API endpoints:"
echo "   php test-all-endpoints.php"
