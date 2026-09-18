#!/bin/bash
set -e

ENV_FILE=/var/app/current/.env

cat > "$ENV_FILE" <<'ENVEOF'
APP_NAME="ColoredCow Evaluator"
APP_ENV=production
APP_KEY=base64:l0URUVnQ4ri7Wk7wDLxbrmjRSaX/k9lbqJInrr5pMmw=
APP_DEBUG=false
APP_URL=https://evaluator-env.eba-gjaax96g.us-east-1.elasticbeanstalk.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=evaluator-db.cqrw4e2m4ilq.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=evaluator
DB_USERNAME=admin
DB_PASSWORD=mGlrJCUHLv
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=file
GITHUB_API_TOKEN=
AI_PROVIDER=gemini
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash
ENVEOF

chown webapp:webapp "$ENV_FILE"
chmod 644 "$ENV_FILE"
echo "Wrote $ENV_FILE"