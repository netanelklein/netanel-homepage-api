# API Service Configuration

This service is part of the centralized Netanel Portfolio infrastructure.

## 🔗 Infrastructure Location

All Kubernetes manifests and deployment scripts have been moved to:
```
../infrastructure/
├── k8s/api/           # API-specific Kubernetes manifests
├── scripts/           # Centralized deployment scripts
└── docker-compose.yml # Local development environment
```

## 🚀 Deployment

### From Infrastructure Directory

```bash
# Deploy just the API
cd ../infrastructure
./scripts/deploy.sh deploy api

# Deploy all services
./scripts/deploy.sh deploy-all

# Monitor API status
./scripts/monitoring/monitor.sh
```

### Local Development

```bash
# From infrastructure directory
cd ../infrastructure
docker-compose up api mysql redis

# Or from API directory (development only)
docker build -t portfolio-api .
docker run -p 8080:80 portfolio-api
```

## 📊 Service Details

- **Service Name**: `api`
- **Namespace**: `netanel-portfolio`
- **Port**: `80` (internal), `8080` (local dev)
- **Health Check**: `/health`
- **API Documentation**: `/docs`

## 🔧 Configuration

Service-specific configurations:
- `Dockerfile` - Container build configuration
- `.env.example` - Environment variables template
- `config/` - Application configuration files

For infrastructure configuration, see `../infrastructure/README.md`.
