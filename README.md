# Ah Ho Fruits - WordPress E-commerce

Premium fresh fruits delivery website for Singapore, built with WordPress and WooCommerce.

## Quick Start (Local Development)

### Prerequisites
- Docker Desktop
- Git

### Start Local Environment

```bash
# Start WordPress and MySQL containers
docker-compose up -d

# Open in browser
open http://localhost:8080
```

### Stop Environment

```bash
docker-compose down
```

## Project Structure

```
ah-ho-fruits/
├── .github/
│   └── workflows/
│       └── deploy.yml          # GitHub Actions deployment
├── wp-content/
│   ├── themes/
│   │   └── ah-ho-fruits/       # Custom theme
│   └── plugins/
│       └── ah-ho-custom/       # Custom functionality
├── docker-compose.yml          # Local development
├── deploy.sh                   # Manual deployment script
└── README.md
```

## Deployment

### Automatic (via GitHub Actions)
Push to `main` branch - changes in `wp-content/` trigger automatic deployment.

### Manual
```bash
./deploy.sh
```

## GitHub Secrets Required

| Secret | Description |
|--------|-------------|
| `VODIEN_HOST` | SSH hostname |
| `VODIEN_USER` | SSH username |
| `VODIEN_SSH_KEY` | Private key contents |
| `VODIEN_PATH` | Document root path |
| `VODIEN_PORT` | SSH port (usually 22) |

## Theme Features

- WooCommerce optimized
- Singapore-focused (SGD, GST, shipping)
- Mobile responsive
- WhatsApp integration
- Custom product badges
- Sticky header
- Mini cart with AJAX updates

## Development Workflow

1. Make changes in `wp-content/themes/ah-ho-fruits/`
2. Test locally at `http://localhost:8080`
3. Commit and push to trigger deployment

---
*Ah Ho Fruits - Fresh fruits delivered to your doorstep*
