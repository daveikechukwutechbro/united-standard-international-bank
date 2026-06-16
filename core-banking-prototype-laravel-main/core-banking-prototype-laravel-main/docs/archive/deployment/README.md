# Deployment Guide

This guide explains how to deploy the Finaegis Core Banking platform.

## Deployment Methods

### 1. GitHub Actions (Recommended)

The project uses GitHub Actions for automated deployments:

- **Automatic deployment to demo**: Every push to `main` branch (if demo server is configured)
- **Manual deployment to production**: Via GitHub Actions UI or when creating a release tag (if production server is configured)

**Note**: Deployments will be automatically skipped if the corresponding server variables are not configured in GitHub. This allows you to use the same workflow even if you haven't set up all environments yet.

#### Required GitHub Configuration

##### GitHub Variables (non-sensitive configuration)
Configure these variables in your repository settings under Settings > Secrets and variables > Actions > Variables:

```
DEMO_SERVER          # Demo server hostname/IP
DEMO_USER            # SSH username for demo
DEMO_PATH            # Deployment path on demo server
DEMO_URL             # Demo site URL

PRODUCTION_SERVER    # Production server hostname/IP
PRODUCTION_USER      # SSH username for production
PRODUCTION_PATH      # Deployment path on production server
PRODUCTION_URL       # Production site URL
```

##### GitHub Secrets (sensitive data)
Configure these secrets in your repository settings under Settings > Secrets and variables > Actions > Secrets:

```
DEMO_SSH_PRIVATE_KEY        # SSH private key for demo server
DEMO_SSH_KNOWN_HOSTS        # SSH known_hosts entry for demo

PRODUCTION_SSH_PRIVATE_KEY  # SSH private key for production
PRODUCTION_SSH_KNOWN_HOSTS  # SSH known_hosts entry for production
```

### 2. Laravel Envoy

For manual deployments or when GitHub Actions is not available:

```bash
# Deploy to demo
envoy run deploy

# Deploy to production
envoy run deploy-production

# Rollback if needed
envoy run rollback --on=production
```

## Deployment Process

1. **Pre-deployment checks**
   - Validates composer files
   - Checks for security vulnerabilities
   - Runs critical unit tests

2. **Build artifacts**
   - Installs production dependencies
   - Builds frontend assets
   - Optimizes Laravel caches

3. **Deployment**
   - Transfers files to server
   - Runs database migrations
   - Clears and rebuilds caches
   - Restarts queue workers

4. **Post-deployment**
   - Runs health checks
   - Clears CDN cache
   - Updates monitoring

## Server Requirements

- PHP 8.4+ with extensions:
  - GMP (required for cryptographic operations)
  - BCMath, Intl, MB String, PDO
  - Redis, OPCache, SOAP, Imagick
- MariaDB 10.11+ or PostgreSQL 14+
- Redis 7+
- Node.js 20+
- Nginx
- Supervisor

See [SERVER_SETUP.md](./SERVER_SETUP.md) for detailed server configuration.

## Environment Configuration

Production environment variables:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## Deployment Checklist

Before deploying to production:

- [ ] All tests passing
- [ ] Security scan completed
- [ ] Database migrations reviewed
- [ ] Environment variables updated
- [ ] Backup strategy in place
- [ ] Monitoring configured
- [ ] SSL certificate valid

## Rollback Procedure

If deployment fails:

1. **Via GitHub Actions**: Re-run previous successful deployment
2. **Via Envoy**: `envoy run rollback --on=production`
3. **Manual**: SSH to server and symlink previous release

## Monitoring

After deployment:

- Check application health: `https://your-domain.com/health`
- Monitor error logs: `/srv/finaegis/storage/logs/`
- Check queue workers: `sudo supervisorctl status`
- Verify scheduled tasks: `crontab -l`

## Troubleshooting

Common issues:

1. **Permission errors**: Ensure `storage` and `bootstrap/cache` are writable
2. **Queue not processing**: Check supervisor logs
3. **Assets not loading**: Run `php artisan storage:link`
4. **Database errors**: Verify migrations ran successfully

For detailed troubleshooting, check the logs in `/srv/finaegis/storage/logs/`.