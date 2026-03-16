#!/bin/bash
# Auto-configure git credentials for Cowork sessions
# Usage: source .claude/setup-git.sh

CRED_FILE="$(dirname "$0")/.gitcredentials"

if [ -f "$CRED_FILE" ]; then
    git config credential.helper "store --file=$CRED_FILE"
    echo "Git credentials configured from .claude/.gitcredentials"
else
    echo "No credentials file found at $CRED_FILE"
fi
