# Docker CI/CD Manual Guide

## What is CI/CD?

- **CI (Continuous Integration)**: Automatically build, test, and validate code on every push
- **CD (Continuous Deployment)**: Automatically deploy to production after passing tests

---

## Manual Docker CI/CD Workflow

### 1. LOCAL DEVELOPMENT - Build Image Locally

```powershell
# Build image with tag
docker build -t course-enrollment:latest .

# View built image
docker images | findstr course-enrollment

# Run container from image
docker run -d `
  --name course-app `
  -p 8080:9000 `
  -e DB_HOST=host.docker.internal `
  -e DB_NAME=course_enrollment `
  course-enrollment:latest

# Check container status
docker ps
docker logs course-app

# Test the app
curl http://localhost:8080
```

---

### 2. TAG IMAGE FOR REGISTRY

```powershell
# Tag for Docker Hub
docker tag course-enrollment:latest yourusername/course-enrollment:latest
docker tag course-enrollment:latest yourusername/course-enrollment:v1.0.0

# Tag for GitHub Container Registry (ghcr.io)
docker tag course-enrollment:latest ghcr.io/yourusername/course-enrollment:latest
docker tag course-enrollment:latest ghcr.io/yourusername/course-enrollment:v1.0.0

# View tags
docker images | findstr course-enrollment
```

---

### 3. PUSH TO REGISTRY (DockerHub)

```powershell
# Login to Docker Hub
docker login

# Push image
docker push yourusername/course-enrollment:latest
docker push yourusername/course-enrollment:v1.0.0

# Verify on hub.docker.com
```

---

### 4. PUSH TO REGISTRY (GitHub Container Registry)

```powershell
# Create GitHub Personal Access Token (PAT)
# Go to: Settings > Developer settings > Personal access tokens
# Scopes needed: write:packages, read:packages, delete:packages

# Login to ghcr.io
echo $env:GITHUB_TOKEN | docker login ghcr.io -u yourusername --password-stdin

# Push image
docker push ghcr.io/yourusername/course-enrollment:latest

# Make private repo public or use PAT to pull:
docker login ghcr.io -u yourusername -p $env:GITHUB_TOKEN
docker pull ghcr.io/yourusername/course-enrollment:latest
```

---

### 5. DEPLOY WITH docker-compose

```powershell
# Pull latest image from registry
docker-compose pull

# Start services (builds locally if no pull)
docker-compose up -d

# View running containers
docker-compose ps

# View logs
docker-compose logs -f app
docker-compose logs -f nginx

# Stop services
docker-compose down

# Stop and remove volumes
docker-compose down -v

# Rebuild specific service
docker-compose build app
docker-compose up -d app
```

---

### 6. AUTOMATED TESTING IN CI

```powershell
# Lint PHP files
Get-ChildItem -Path . -Filter "*.php" -Recurse | ForEach-Object {
    php -l $_.FullName
}

# Run unit tests (if you have them)
docker run --rm `
  -v ${PWD}:/app `
  phpunit/phpunit tests/

# Security audit
docker run --rm `
  -v ${PWD}:/app `
  composer audit
```

---

### 7. MULTI-STAGE BUILD OPTIMIZATION

The Dockerfile included uses multi-stage builds:
- **Stage 1 (builder)**: Install dev dependencies, run Composer
- **Stage 2 (production)**: Only copy needed files, smaller final image

```powershell
# Build specific stage
docker build -t course-enrollment:dev --target builder .

# View layer sizes
docker history course-enrollment:latest

# Compare sizes
docker images | findstr course-enrollment
# Result: ~200MB (prod) vs ~500MB (with dev deps)
```

---

### 8. GITHUB ACTIONS SETUP

1. **Create secrets in GitHub repo:**
   - Settings > Secrets and variables > Actions > New secret
   - Required secrets:
     - `DEPLOY_KEY`: SSH private key
     - `DEPLOY_HOST`: Production server IP/domain
     - `DEPLOY_USER`: SSH user
     - `SLACK_WEBHOOK`: (optional) for notifications

2. **Workflow triggers automatically on:**
   - Push to `main` or `develop` branches
   - Pull requests to `main`

3. **Jobs run in this order:**
   ```
   build (CI) → quality (CI) → deploy (CD, only on main)
   ```

---

### 9. MANUAL CI/CD SIMULATION (No GitHub)

#### Step 1: Test locally
```powershell
docker build -t course-enrollment:test .
docker run --rm course-enrollment:test php -l config/config.php
```

#### Step 2: Push to registry
```powershell
docker build -t myregistry/course-enrollment:v1.0.0 .
docker push myregistry/course-enrollment:v1.0.0
```

#### Step 3: Deploy to production server
```powershell
# SSH into production server
ssh user@production-server

# Pull new image
docker pull myregistry/course-enrollment:v1.0.0

# Update docker-compose.yml image tag
vim docker-compose.yml
# Change: image: myregistry/course-enrollment:v1.0.0

# Restart services
docker-compose up -d
docker-compose exec app php artisan migrate --force

# Verify
curl http://localhost/health.php
```

---

### 10. HEALTH CHECK & MONITORING

```powershell
# Check container health
docker inspect course-enrollment-app | findstr -A 10 "Health"

# View health status
docker ps
# STATUS shows: Up 5 minutes (healthy)

# Manual health test
docker exec course-enrollment-app curl http://localhost:9000/health.php

# View real-time stats
docker stats course-enrollment-app
```

---

### 11. ROLLBACK ON FAILURE

```powershell
# Stop current container
docker-compose down

# Run previous version
docker run -d `
  --name course-app-rollback `
  -p 8080:9000 `
  myregistry/course-enrollment:v0.9.0

# Test
curl http://localhost:8080

# If works, update compose file and re-deploy
```

---

### 12. CLEANUP

```powershell
# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# Remove unused networks
docker network prune

# Remove all (CAUTION)
docker system prune -a --volumes
```

---

## Full CI/CD Pipeline Flow

```
Developer pushes code to GitHub main branch
    ↓
GitHub Actions triggered
    ↓
Build stage:
  └─ Build Docker image
  └─ Run PHP linting
  └─ Check security (Composer audit)
  └─ Push to GHCR
    ↓
Deploy stage (only on main):
  └─ SSH to production server
  └─ Pull new image
  └─ Run docker-compose up -d
  └─ Run migrations
  └─ Health check
  └─ Slack notification
    ↓
Production running new version
```

---

## Environment Variables

Create `.env` file:
```
DB_HOST=db
DB_NAME=course_enrollment
DB_USER=root
DB_PASS=secure_password
APP_ENV=production
```

Then use in docker-compose:
```yaml
environment:
  - DB_HOST=${DB_HOST}
  - DB_PASS=${DB_PASS}
```

---

## Commands Cheat Sheet

| Command | Purpose |
|---------|---------|
| `docker build -t name:tag .` | Build image |
| `docker run -d image` | Run container |
| `docker push image` | Push to registry |
| `docker-compose up -d` | Start services |
| `docker logs container` | View logs |
| `docker exec container cmd` | Run command in container |
| `docker inspect container` | View details |
| `docker rm container` | Delete container |
| `docker rmi image` | Delete image |

---

## Troubleshooting

**Image won't build:**
```powershell
docker build -t name:tag . --no-cache
docker build -t name:tag . --progress=plain
```

**Container won't start:**
```powershell
docker logs container_name
docker inspect container_name
```

**Port already in use:**
```powershell
netstat -ano | findstr :8080
taskkill /PID processid /F
```

**Push fails:**
```powershell
docker login  # Re-login
docker push image --verbose
```
