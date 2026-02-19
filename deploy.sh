#!/bin/bash
# Manual deployment script for Ah Ho Fruit
# Usage: ./deploy.sh

# Configuration - UPDATE THESE VALUES
VODIEN_USER="contactl"
VODIEN_HOST="sh00017.vodien.com"
VODIEN_PORT="22"
VODIEN_PATH="/home/contactl/public_html"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Ah Ho Fruit - Manual Deployment Script${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""

# Check if rsync is installed
if ! command -v rsync &> /dev/null; then
    echo -e "${RED}Error: rsync is not installed${NC}"
    exit 1
fi

# Check if deploy key exists
if [ ! -f "deploy-key" ]; then
    echo -e "${RED}Error: deploy-key not found${NC}"
    echo "Run 'ssh-keygen -t ed25519 -f deploy-key -N \"\"' to generate one"
    exit 1
fi

# Ensure correct permissions on deploy key
chmod 600 deploy-key

echo -e "${GREEN}Starting deployment...${NC}"
echo "Deploying wp-content to ${VODIEN_HOST}..."
echo ""

# Deploy using rsync
rsync -avzr --delete \
    --exclude='.git*' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    -e "ssh -p ${VODIEN_PORT} -i deploy-key -o StrictHostKeyChecking=no" \
    ./wp-content/ \
    ${VODIEN_USER}@${VODIEN_HOST}:${VODIEN_PATH}/wp-content/

# Check if deployment was successful
if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}Deployment completed successfully!${NC}"
    echo -e "${GREEN}========================================${NC}"
else
    echo ""
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}Deployment failed!${NC}"
    echo -e "${RED}========================================${NC}"
    exit 1
fi
