#!/bin/bash

# Setup script for creating dedicated MariaDB user for Portfolio API
# Run this script with sudo to create the database user and database

echo "🔧 Setting up dedicated MariaDB user for Portfolio API..."

# Database configuration
DB_NAME="netanel_portfolio"
DB_USER="portfolio_api"
DB_PASS="portfolio_2025_secure"

echo "👤 Creating database user: $DB_USER"

# Create user and database using sudo mysql
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

echo "✅ Database user and database created successfully!"
echo ""
echo "📋 Database configuration:"
echo "   Database: $DB_NAME"
echo "   User: $DB_USER"
echo "   Password: $DB_PASS"
echo ""
echo "🔄 Next steps:"
echo "   1. Update .env file with new credentials"
echo "   2. Run ./setup-database.sh to import schema"
echo "   3. Test API endpoints"
