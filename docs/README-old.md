# Netanel Klein Portfolio API

A vanilla PHP REST API backend for the portfolio website and admin panel.

## 🚀 Features

- **RESTful API** with clean endpoint structure
- **Vanilla PHP** implementation (no Composer dependencies)
- **MySQL database** with optimized schema
- **File-based caching** for performance
- **Rate limiting** and security middleware
- **Admin authentication** with session management
- **Contact form** with spam protection
- **Dynamic CV generation** and download
- **CORS support** for frontend integration

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite (or Nginx)
- 1GB+ RAM (optimized for OCI VM.Standard.A1.Flex)

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/netanelklein/netanel-homepage-api.git
   cd netanel-homepage-api
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database and configuration settings
   ```

3. **Set up database**
   ```bash
   mysql -u root -p
   CREATE DATABASE netanel_portfolio;
   USE netanel_portfolio;
   SOURCE database/schema.sql;
   ```

4. **Set permissions**
   ```bash
   chmod 755 storage/
   chmod 755 storage/cache/
   chmod 755 storage/logs/
   chmod 755 storage/uploads/
   ```

5. **Configure web server**
   
   **Apache**: The included `.htaccess` file handles URL rewriting
   
   **Nginx**: Add this to your server configuration:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   
   location ~ \.php$ {
       fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
       fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
       include fastcgi_params;
   }
   ```

## 📚 API Documentation

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api` | API information and documentation |
| GET | `/api/health` | Health check |
| GET | `/api/portfolio/personal-info` | Get personal information |
| GET | `/api/portfolio/projects` | Get projects list |
| GET | `/api/portfolio/skills` | Get skills grouped by category |
| GET | `/api/portfolio/experience` | Get work experience |
| GET | `/api/portfolio/education` | Get education background |
| GET | `/api/cv/download` | Download CV (PDF) |
| POST | `/api/contact/submit` | Submit contact form |

### Admin Endpoints (Authentication Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Admin login |
| POST | `/api/auth/logout` | Admin logout |
| GET | `/api/admin/messages` | Get contact messages |
| PUT | `/api/admin/personal-info` | Update personal info |
| POST/PUT/DELETE | `/api/admin/projects/{id?}` | Manage projects |
| POST/PUT/DELETE | `/api/admin/skills/{id?}` | Manage skills |
| POST/PUT/DELETE | `/api/admin/experience/{id?}` | Manage experience |
| POST/PUT/DELETE | `/api/admin/education/{id?}` | Manage education |

### Authentication

Admin endpoints require session-based authentication:

1. Login with POST to `/api/auth/login`:
   ```json
   {
     "username": "admin",
     "password": "your-password"
   }
   ```

2. Include session cookie in subsequent requests

### Rate Limiting

- Contact form: 5 requests per minute per IP
- Admin login: 3 attempts per 5 minutes per IP
- General endpoints: 60 requests per minute per IP

### Response Format

All endpoints return JSON with consistent structure:

**Success Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "error": true,
  "message": "Error description",
  "errors": { ... } // Optional validation errors
}
```

### CV Generation Endpoints

**Download CV**
```bash
# PDF format (default)
curl "https://api.netanelk.com/api/cv/download"

# HTML format
curl "https://api.netanelk.com/api/cv/download?format=html"

# JSON data format
curl "https://api.netanelk.com/api/cv/download?format=json"
```

**CV Statistics**
```bash
curl "https://api.netanelk.com/api/cv/stats"
```

### Admin Endpoints

**Dashboard Analytics**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://api.netanelk.com/api/admin/dashboard"
```

**Contact Messages Management**
```bash
# Get messages with pagination
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://api.netanelk.com/api/admin/messages?page=1&limit=20&status=unread"

# Update message status
curl -X PUT \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"is_read": true}' \
     "https://api.netanelk.com/api/admin/messages/1/status"

# Delete message
curl -X DELETE \
     -H "Authorization: Bearer YOUR_TOKEN" \
     "https://api.netanelk.com/api/admin/messages/1"
```

**Content Management (Projects, Skills, Experience)**
```bash
# Get admin content (includes hidden items)
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://api.netanelk.com/api/admin/projects"

# Create new project
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "New Project",
       "short_description": "Brief description",
       "long_description": "Detailed description",
       "technologies": "PHP, MySQL, JavaScript",
       "project_url": "https://example.com",
       "github_url": "https://github.com/user/repo",
       "is_featured": true,
       "is_visible": true
     }' \
     "https://api.netanelk.com/api/admin/projects"

# Update project
curl -X PUT \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"title": "Updated Project Title"}' \
     "https://api.netanelk.com/api/admin/projects/1"

# Delete project
curl -X DELETE \
     -H "Authorization: Bearer YOUR_TOKEN" \
     "https://api.netanelk.com/api/admin/projects/1"
```

## 🔧 Development

### Project Structure

```
api/
├── config/           # Configuration files
├── database/         # Database schema and migrations
├── routes/           # API route definitions
├── src/
│   ├── core/         # Core framework classes
│   ├── controllers/  # Request handlers
│   ├── middleware/   # HTTP middleware
│   ├── models/       # Data models
│   └── services/     # Business logic services
├── storage/
│   ├── cache/        # File-based cache
│   ├── logs/         # Application logs
│   └── uploads/      # File uploads
├── .htaccess         # Apache configuration
├── .env.example      # Environment template
└── index.php         # Application entry point
```

### Adding New Endpoints

1. Define route in `routes/api.php`
2. Create controller method
3. Add any required middleware
4. Update documentation

### Caching

The API uses file-based caching for performance:

- Personal info: 1 hour TTL
- Projects: 30 minutes TTL
- Skills: 1 hour TTL
- Experience: 1 hour TTL
- Education: 1 hour TTL

Cache is automatically invalidated when data is updated through admin endpoints.

## 🔒 Security

- Input validation and sanitization
- SQL injection protection with prepared statements
- XSS protection with output encoding
- CSRF protection for admin actions
- Rate limiting for abuse prevention
- Secure session management
- Security headers configuration

## 🧪 Testing

### Automated Testing
Run the comprehensive API test suite:

```bash
# Make script executable
chmod +x test_api.sh

# Run all tests
./test_api.sh

# Test specific localhost setup
API_BASE="http://localhost:8000/api" ./test_api.sh
```

### Manual Testing
Test individual endpoints:

```bash
# Test public endpoints
curl "http://localhost/api/portfolio/personal-info"
curl "http://localhost/api/portfolio/projects"
curl "http://localhost/api/cv/download?format=html"

# Test contact form
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"name":"Test","email":"test@example.com","subject":"Test","message":"Hello"}' \
     "http://localhost/api/contact/submit"

# Test authentication
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"your_password"}' \
     "http://localhost/api/auth/login"
```

## 🚀 Deployment

### Quick Deployment
Use the automated deployment script for Oracle Cloud Infrastructure:

```bash
# On your server
sudo ./deploy.sh
```

### Manual Deployment

1. **Server Requirements**
   ```bash
   # Install dependencies
   sudo apt update
   sudo apt install nginx php8.4-fpm php8.4-mysql php8.4-curl php8.4-mbstring mysql-server
   ```

2. **Clone Repository**
   ```bash
   git clone https://github.com/netanelklein/netanel-homepage-api.git /var/www/api.netanelk.com
   cd /var/www/api.netanelk.com
   ```

3. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/api.netanelk.com
   sudo chmod -R 755 /var/www/api.netanelk.com
   sudo chmod -R 775 /var/www/api.netanelk.com/storage
   ```

4. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials and settings
   nano .env
   ```

5. **Setup Database**
   ```bash
   mysql -u root -p
   CREATE DATABASE portfolio_api;
   CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON portfolio_api.* TO 'api_user'@'localhost';
   FLUSH PRIVILEGES;
   exit

   # Import schema
   mysql -u api_user -p portfolio_api < database/schema.sql
   ```

6. **Configure Nginx**
   ```bash
   sudo cp nginx.conf /etc/nginx/sites-available/api.netanelk.com
   sudo ln -s /etc/nginx/sites-available/api.netanelk.com /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

7. **Setup SSL (Optional but Recommended)**
   ```bash
   sudo apt install certbot python3-certbot-nginx
   sudo certbot --nginx -d api.netanelk.com
   ```

8. **Test Deployment**
   ```bash
   ./test_api.sh
   ```

### Environment Variables

Key environment variables in `.env`:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.netanelk.com

# Database
DB_HOST=localhost
DB_NAME=portfolio_api
DB_USER=api_user
DB_PASS=secure_password

# Security
SESSION_LIFETIME=86400
RATE_LIMIT_ENABLED=true

# External Services
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
```

## 📊 Monitoring

- Application logs in `storage/logs/`
- Error tracking and debugging
- Performance monitoring
- Cache statistics via admin panel

## 🤝 Contributing

This is a personal portfolio project, but suggestions and improvements are welcome!

## 📄 License

This project is proprietary and confidential.

---

**Author**: Netanel Klein  
**Email**: netanel@netanelk.com  
**Website**: https://netanelk.com

## 🚀 Recent Updates

### Phase 3 Completion (Latest)
- ✅ **CV Generation Controller** - Dynamic PDF/HTML CV generation with multiple output formats
- ✅ **Admin Panel Controller** - Complete CRUD operations for all content types
- ✅ **Enhanced Middleware** - Completed RateLimit middleware with endpoint categorization
- ✅ **Extended Models** - Added all missing CRUD methods and admin operations
- ✅ **Testing Suite** - Created comprehensive API testing script
- ✅ **Deployment Ready** - Nginx configuration and automated deployment script

### Core Features Implemented
- 🔐 **Session-based Authentication** with secure admin access
- 📊 **Portfolio Data Management** (projects, skills, experience, education)
- 📧 **Contact Form Processing** with spam detection and rate limiting
- 📄 **Dynamic CV Generation** (PDF, HTML, JSON formats)
- 🛡️ **Comprehensive Security** (CORS, rate limiting, input validation, logging)
- 📈 **Admin Dashboard** with analytics and content management
- ⚡ **Performance Optimized** with file-based caching system
- 🔄 **Production Ready** with deployment automation
