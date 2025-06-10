#!/bin/bash

# Load environment variables from .env file for local testing
export $(grep -v '^#' .env | xargs)

# Start PHP server with environment variables
echo "Starting PHP server with loaded environment variables..."
echo "DB_HOST: $DB_HOST"
echo "DB_NAME: $DB_NAME" 
echo "DB_USERNAME: $DB_USERNAME"

php -S localhost:8000
