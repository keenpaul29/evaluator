#!/bin/bash
set -e

cat > /etc/systemd/system/laravel-worker.service <<'EOF'
[Unit]
Description=Laravel queue worker
After=network.target

[Service]
User=webapp
Group=webapp
WorkingDirectory=/var/app/current
ExecStart=/usr/bin/php /var/app/current/artisan queue:work --queue=evaluations,default --sleep=3 --tries=3 --timeout=600 --max-time=3600
Restart=always
RestartSec=5
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable laravel-worker.service
systemctl restart laravel-worker.service
echo "queue worker started"