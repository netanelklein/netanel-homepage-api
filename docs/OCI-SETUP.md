# Oracle Cloud Infrastructure (OCI) Setup Guide

## 🚀 Phase 1: Kubernetes Learning with OKE

This guide walks you through deploying the Portfolio API to Oracle Container Engine for Kubernetes (OKE) for learning purposes, before migrating to a cost-optimized VM deployment.

## 📋 Prerequisites

### 1. OCI Account Setup
- Sign up for Oracle Cloud Account (Always Free tier)
- Complete identity verification
- Note your tenancy OCID and region

### 2. Required Tools Installation

```bash
# Install OCI CLI
curl -L https://raw.githubusercontent.com/oracle/oci-cli/master/scripts/install/install.sh | bash

# Install kubectl
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"
sudo install -o root -g root -m 0755 kubectl /usr/local/bin/kubectl

# Install Docker (if not already installed)
sudo apt-get update
sudo apt-get install docker.io
sudo usermod -aG docker $USER
```

### 3. OCI CLI Configuration

```bash
# Configure OCI CLI
oci setup config

# Test connection
oci iam user list
```

## 🏗️ OCI Infrastructure Setup

### 1. Create VCN (Virtual Cloud Network)

```bash
# Create VCN
oci network vcn create \
    --compartment-id <your-compartment-id> \
    --display-name "portfolio-vcn" \
    --cidr-block "10.0.0.0/16"

# Create Internet Gateway
oci network internet-gateway create \
    --compartment-id <your-compartment-id> \
    --vcn-id <vcn-id> \
    --display-name "portfolio-igw" \
    --is-enabled true

# Create Route Table
oci network route-table create \
    --compartment-id <your-compartment-id> \
    --vcn-id <vcn-id> \
    --display-name "portfolio-rt" \
    --route-rules '[{
        "destination": "0.0.0.0/0",
        "destinationType": "CIDR_BLOCK",
        "networkEntityId": "<internet-gateway-id>"
    }]'

# Create Security List
oci network security-list create \
    --compartment-id <your-compartment-id> \
    --vcn-id <vcn-id> \
    --display-name "portfolio-sl" \
    --ingress-security-rules '[{
        "source": "0.0.0.0/0",
        "protocol": "6",
        "tcpOptions": {
            "destinationPortRange": {
                "min": 80,
                "max": 80
            }
        }
    }, {
        "source": "0.0.0.0/0",
        "protocol": "6",
        "tcpOptions": {
            "destinationPortRange": {
                "min": 443,
                "max": 443
            }
        }
    }]'

# Create Subnet for OKE
oci network subnet create \
    --compartment-id <your-compartment-id> \
    --vcn-id <vcn-id> \
    --display-name "oke-subnet" \
    --cidr-block "10.0.1.0/24" \
    --route-table-id <route-table-id> \
    --security-list-ids '["<security-list-id>"]'
```

### 2. Create OKE Cluster

```bash
# Create OKE Cluster
oci ce cluster create \
    --compartment-id <your-compartment-id> \
    --name "portfolio-oke-cluster" \
    --vcn-id <vcn-id> \
    --kubernetes-version "v1.28.2" \
    --service-lb-subnet-ids '["<subnet-id>"]' \
    --endpoint-subnet-id <subnet-id> \
    --endpoint-is-public-ip-enabled true

# Create Node Pool
oci ce node-pool create \
    --cluster-id <cluster-id> \
    --compartment-id <your-compartment-id> \
    --name "portfolio-node-pool" \
    --kubernetes-version "v1.28.2" \
    --node-shape "VM.Standard.A1.Flex" \
    --node-shape-config '{
        "memoryInGBs": 6,
        "ocpus": 1
    }' \
    --subnet-ids '["<subnet-id>"]' \
    --size 2 \
    --node-source-details '{
        "sourceType": "IMAGE",
        "imageId": "<oracle-linux-image-id>"
    }'
```

### 3. Setup MySQL HeatWave

```bash
# Create DB System
oci mysql db-system create \
    --compartment-id <your-compartment-id> \
    --display-name "portfolio-mysql" \
    --shape-name "MySQL.VM.Standard.E3.1.8GB" \
    --subnet-id <subnet-id> \
    --admin-username "admin" \
    --admin-password "YourSecurePassword123!" \
    --data-storage-size-in-gb 50 \
    --availability-domain <availability-domain>
```

## 🐳 Container Registry Setup

### 1. Create Repository in OCIR

```bash
# Login to OCIR
docker login <region>.ocir.io -u <tenancy-namespace>/<username>

# Create repository (will be created automatically on first push)
# Format: <region>.ocir.io/<tenancy-namespace>/netanel-portfolio-api
```

### 2. Generate Auth Token

1. Go to OCI Console → Identity & Security → Users
2. Click your username
3. Go to Auth Tokens
4. Generate New Token
5. Copy token (use as Docker password)

## 🚀 Deployment Process

### 1. Update Configuration

1. **Update deployment script variables:**
   ```bash
   # Edit deploy-oke.sh
   OCI_REGION="iad"  # your region
   OCI_TENANCY_NAMESPACE="your-tenancy-namespace"
   OCI_COMPARTMENT_ID="ocid1.compartment.oc1..your-compartment-id"
   OKE_CLUSTER_ID="ocid1.cluster.oc1.iad.your-cluster-id"
   ```

2. **Update Kubernetes secrets:**
   ```bash
   # Encode your database credentials
   echo -n "your-db-username" | base64
   echo -n "your-db-password" | base64
   
   # Update k8s/secret.yaml with encoded values
   ```

3. **Update ConfigMap:**
   ```bash
   # Edit k8s/configmap.yaml
   # Update DB_HOST with your MySQL HeatWave endpoint
   ```

### 2. Deploy to OKE

```bash
# Make deployment script executable
chmod +x deploy-oke.sh

# Full deployment
./deploy-oke.sh deploy

# Or step by step
./deploy-oke.sh build    # Build image only
./deploy-oke.sh push     # Build and push to OCIR
./deploy-oke.sh status   # Check deployment status
```

### 3. Configure DNS

1. Get Load Balancer IP:
   ```bash
   kubectl get services -n netanel-portfolio
   ```

2. Update DNS records:
   ```
   api.netanelk.com → A record → <load-balancer-ip>
   ```

### 4. SSL Certificate (Let's Encrypt)

```bash
# Install cert-manager
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.13.0/cert-manager.yaml

# Create ClusterIssuer for Let's Encrypt
kubectl apply -f - <<EOF
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: your-email@gmail.com
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
EOF
```

## 📊 Monitoring and Management

### 1. Check Deployment Status

```bash
# Pods status
kubectl get pods -n netanel-portfolio

# Services and ingress
kubectl get services,ingress -n netanel-portfolio

# Logs
kubectl logs -f deployment/portfolio-api -n netanel-portfolio

# Resource usage
kubectl top pods -n netanel-portfolio
```

### 2. Scaling

```bash
# Manual scaling
kubectl scale deployment portfolio-api --replicas=3 -n netanel-portfolio

# Auto-scaling is configured via HPA (70% CPU, 80% memory)
kubectl get hpa -n netanel-portfolio
```

### 3. Updates

```bash
# Build and deploy new version
./deploy-oke.sh deploy

# Rolling update
kubectl set image deployment/portfolio-api api=<new-image> -n netanel-portfolio
kubectl rollout status deployment/portfolio-api -n netanel-portfolio

# Rollback if needed
kubectl rollout undo deployment/portfolio-api -n netanel-portfolio
```

## 💰 Cost Optimization

### Always Free Tier Limits
- **OKE**: 1 managed cluster (always free)
- **Compute**: 2 AMD VMs (1 OCPU + 6GB RAM each) or 4 ARM VMs (1 OCPU + 4GB RAM each)
- **Block Storage**: 200GB
- **Load Balancer**: 1 (always free)
- **MySQL**: 50GB (HeatWave always free)

### Resource Configuration
- Node pool: 2 x VM.Standard.A1.Flex (1 OCPU + 6GB RAM)
- API pods: 2 replicas with resource limits
- HPA: Scale 2-10 based on demand
- Storage: 15GB total (10GB + 5GB logs)

## 🎯 Phase 2: Migration to VM

After learning Kubernetes, migrate to VM deployment:

1. **Export data** from OKE MySQL to VM MySQL
2. **Update DNS** to point to VM IP
3. **Use existing `deploy.sh`** for VM deployment
4. **Decommission OKE** resources to save costs

This approach gives you hands-on Kubernetes experience while maintaining cost efficiency for long-term hosting.

## 🔧 Troubleshooting

### Common Issues

1. **Pod not starting:**
   ```bash
   kubectl describe pod <pod-name> -n netanel-portfolio
   kubectl logs <pod-name> -n netanel-portfolio
   ```

2. **Database connection:**
   - Check security list allows port 3306
   - Verify MySQL endpoint in ConfigMap
   - Check credentials in Secret

3. **Load Balancer not getting IP:**
   - Verify subnet has internet gateway
   - Check security list allows 80/443
   - OCI limits may apply

4. **Image pull errors:**
   - Verify OCIR login
   - Check image name in deployment.yaml
   - Ensure repository exists

### Support Resources
- [OKE Documentation](https://docs.oracle.com/en-us/iaas/Content/ContEng/home.htm)
- [OCI CLI Reference](https://docs.oracle.com/en-us/iaas/tools/oci-cli/latest/oci_cli_docs/)
- [Kubernetes Documentation](https://kubernetes.io/docs/)
