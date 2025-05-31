# Netanel Klein Portfolio API

A vanilla PHP REST API backend for the portfolio website and admin panel, optimized for Oracle Cloud Infrastructure deployment with Kubernetes support.

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
- **Kubernetes-ready** with OKE deployment
- **Container-optimized** with Docker support
- **OCI-integrated** with Oracle Cloud services

## 📋 Requirements

### Local Development
- PHP 8.4 or higher
- MySQL 8.0 or higher
- Docker & Docker Compose
- 2GB+ RAM

### Production (OKE)
- Oracle Cloud Infrastructure account
- OKE cluster (Kubernetes 1.28+)
- MySQL HeatWave instance
- OCIR (Oracle Container Registry)
- 1 CPU + 6GB RAM (VM.Standard.A1.Flex)

## 🛠️ Quick Start

### Option 1: Local Development with Docker

1. **Clone and setup**
   ```bash
   git clone https://github.com/netanelklein/netanel-homepage-api.git
   cd netanel-homepage-api
   cp .env.example .env
   ```

2. **Start development environment**
   ```bash
   docker-compose up -d
   ```

3. **Initialize database**
   ```bash
   docker-compose exec app php database/init.php
   ```

4. **Access the API**
   - API: http://localhost:8080
   - phpMyAdmin: http://localhost:8081
   - Redis Commander: http://localhost:8082

### Option 2: Oracle Cloud Infrastructure (OKE)

1. **Setup OCI infrastructure**
   ```bash
   # Configure OCI CLI first
   oci setup config
   
   # Set environment variables
   export OCI_REGION="us-ashburn-1"
   export OCI_COMPARTMENT_ID="your-compartment-ocid"
   export OKE_CLUSTER_NAME="netanel-portfolio-cluster"
   
   # Create complete infrastructure
   ./manage-oci.sh setup-all
   ```

2. **Deploy to OKE**
   ```bash
   # Build and deploy
   ./deploy-oke.sh
   ```

3. **Validate deployment**
   ```bash
   ./validate-deployment.sh
   ```

## 🐳 Docker Development

The development environment includes:
- **PHP-FPM 8.4** with Nginx
- **MySQL 8.0** database
- **Redis 7** for caching
- **phpMyAdmin** for database management
- **Redis Commander** for cache management

### Docker Commands
```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec app bash

# Stop all services
docker-compose down

# Rebuild containers
docker-compose build --no-cache
```

## ☸️ Kubernetes Deployment

### Prerequisites
1. **OCI CLI configured** with proper authentication
2. **kubectl installed** and configured for your OKE cluster
3. **Docker** for building container images
4. **OCI Infrastructure** (VCN, OKE cluster, MySQL HeatWave)

### Deployment Process

1. **Setup OCI infrastructure**
   ```bash
   ./manage-oci.sh setup-all
   ```

2. **Build and deploy**
   ```bash
   ./deploy-oke.sh
   ```

3. **Monitor deployment**
   ```bash
   ./monitor-oke.sh status
   ```

4. **Validate everything works**
   ```bash
   ./validate-deployment.sh
   ```

### Kubernetes Resources Created
- **Namespace**: `netanel-api`
- **Deployment**: Multi-replica PHP application
- **Service**: LoadBalancer with OCI integration
- **Ingress**: Nginx with SSL termination
- **ConfigMap**: Application configuration
- **Secret**: Database credentials and API keys
- **PVC**: Persistent storage for uploads and logs
- **HPA**: Horizontal Pod Autoscaler

## 📚 API Documentation

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api` | API information and documentation |
| GET | `/api/health` | Health check endpoint |
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

```javascript
// Login
POST /api/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "your-password"
}

// Use session cookie for subsequent requests
GET /api/admin/messages
Cookie: PHPSESSID=your-session-id
```

### Response Format

All endpoints return JSON responses:

```javascript
// Success response
{
  "success": true,
  "data": { ... },
  "timestamp": "2024-01-15T10:30:00Z"
}

// Error response
{
  "success": false,
  "error": "Error message",
  "code": 400,
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Rate Limiting

- **Public endpoints**: 100 requests per minute per IP
- **Admin endpoints**: 60 requests per minute per session
- **Contact form**: 5 submissions per hour per IP

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1610704200
```

## 🔧 Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```bash
# Database Configuration
DB_HOST=localhost
DB_NAME=netanel_portfolio
DB_USER=root
DB_PASSWORD=

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Security
ADMIN_USERNAME=admin
ADMIN_PASSWORD=
JWT_SECRET=
RATE_LIMIT_ENABLED=true

# OCI Configuration (for production)
OCI_REGION=us-ashburn-1
OCI_TENANCY_ID=
OCI_COMPARTMENT_ID=
OKE_CLUSTER_ID=
MYSQL_HOST=
MYSQL_PASSWORD=

# OCIR Configuration
OCIR_REGION=us-ashburn-1
OCIR_NAMESPACE=
OCIR_REPO=netanel-portfolio-api
```

### Database Schema

The database schema is automatically created from:
- `database/migrations/001_initial_schema.sql`
- `database/seeds/initial_data.sql`

Key tables:
- `personal_info`: Basic profile information
- `projects`: Portfolio projects
- `skills`: Technical skills with categories
- `experience`: Work experience
- `education`: Educational background
- `contact_messages`: Contact form submissions

## 🔍 Monitoring and Troubleshooting

### Monitoring Tools

1. **Deployment Status**
   ```bash
   ./monitor-oke.sh status
   ```

2. **Application Logs**
   ```bash
   ./monitor-oke.sh logs
   ```

3. **Resource Usage**
   ```bash
   ./monitor-oke.sh resources
   ```

4. **Debug Session**
   ```bash
   ./monitor-oke.sh debug
   ```

### Health Checks

- **Kubernetes**: Liveness and readiness probes
- **Application**: `/api/health` endpoint
- **Database**: Connection test in health check
- **Cache**: Redis connection test

### Troubleshooting Common Issues

1. **Pod not starting**
   ```bash
   kubectl describe pod -n netanel-api
   kubectl logs -n netanel-api -l app=netanel-api
   ```

2. **Database connection issues**
   ```bash
   ./monitor-oke.sh debug
   # Inside container:
   php -r "mysqli_connect('$DB_HOST', '$DB_USER', '$DB_PASSWORD', '$DB_NAME') or die('Connection failed');"
   ```

3. **LoadBalancer not getting IP**
   ```bash
   kubectl get events -n netanel-api
   kubectl describe service netanel-api -n netanel-api
   ```

4. **SSL certificate issues**
   ```bash
   kubectl describe ingress netanel-api -n netanel-api
   kubectl logs -n ingress-nginx deployment/ingress-nginx-controller
   ```

## 📊 Performance Optimization

### Caching Strategy
- **File-based caching** for database queries
- **Redis caching** for session data
- **HTTP caching** headers for static content
- **Database query optimization**

### Resource Limits
```yaml
resources:
  requests:
    memory: "256Mi"
    cpu: "250m"
  limits:
    memory: "512Mi"
    cpu: "500m"
```

### Horizontal Pod Autoscaling
- **CPU threshold**: 70%
- **Memory threshold**: 80%
- **Min replicas**: 2
- **Max replicas**: 10

## 🔒 Security Features

### Application Security
- **Input validation** for all endpoints
- **SQL injection protection** with prepared statements
- **XSS protection** with output encoding
- **CSRF protection** for admin endpoints
- **Rate limiting** to prevent abuse
- **Security headers** (X-Frame-Options, X-Content-Type-Options, etc.)

### Infrastructure Security
- **Private subnets** for database
- **Security groups** with minimal required access
- **Kubernetes RBAC** with least privilege
- **Secrets management** with OCI Vault
- **Network policies** for pod-to-pod communication

## 🚀 Deployment Automation

### Scripts Overview

1. **`deploy-oke.sh`**: Complete deployment automation
2. **`manage-oci.sh`**: OCI infrastructure management
3. **`monitor-oke.sh`**: Monitoring and troubleshooting
4. **`test-deployment.sh`**: Deployment testing
5. **`validate-deployment.sh`**: Comprehensive validation

### CI/CD Integration

The scripts can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Deploy to OKE
  run: |
    ./deploy-oke.sh
    ./validate-deployment.sh
```

## 💰 Cost Optimization

### Oracle Cloud Always Free Tier
- **OKE Control Plane**: FREE
- **Worker Nodes**: 2x VM.Standard.A1.Flex (ARM) - FREE
- **MySQL HeatWave**: 50GB - FREE
- **Block Storage**: 200GB - FREE
- **Load Balancer**: ~$10-20/month

### Estimated Monthly Cost
- **Production**: $11-25/month (mostly Load Balancer)
- **Development**: $0 (using Always Free tier)

## 📖 Additional Resources

### Documentation
- [OCI Setup Guide](./OCI-SETUP.md) - Complete infrastructure setup
- [API Reference](./docs/api-reference.md) - Detailed endpoint documentation
- [Database Schema](./docs/database-schema.md) - Database design and relationships

### Oracle Cloud Infrastructure
- [OKE Documentation](https://docs.oracle.com/en-us/iaas/Content/ContEng/home.htm)
- [MySQL HeatWave](https://docs.oracle.com/en-us/iaas/mysql-database/)
- [Container Registry](https://docs.oracle.com/en-us/iaas/Content/Registry/home.htm)

### Development Tools
- [Docker Documentation](https://docs.docker.com/)
- [Kubernetes Documentation](https://kubernetes.io/docs/)
- [kubectl Cheat Sheet](https://kubernetes.io/docs/reference/kubectl/cheatsheet/)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Development Guidelines
- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation
- Test on both local and OKE environments

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Netanel Klein**
- Website: [netanelk.com](https://netanelk.com)
- Email: netanel@netanelk.com
- LinkedIn: [netanel-klein](https://linkedin.com/in/netanel-klein)

---

*This API is part of a portfolio project demonstrating modern cloud-native PHP development with Oracle Cloud Infrastructure.*
