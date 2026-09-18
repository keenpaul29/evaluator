#!/bin/bash
set -e

mkdir -p /etc/nginx/conf.d/elasticbeanstalk

SOCK="/var/run/php-fpm/www.sock"
if [ ! -S "$SOCK" ]; then
    SOCK=$(find /run /var/run -maxdepth 2 -name 'www.sock' 2>/dev/null | head -1)
fi
if [ -z "$SOCK" ]; then
    echo "ERROR: php-fpm socket not found"
    exit 1
fi

cat > /etc/nginx/conf.d/elasticbeanstalk/00_application.conf <<EOF
location / {
    root /var/app/current/public;
    try_files \$uri \$uri/ /index.php?\$query_string;
}

location ~ \\.php\$ {
    root /var/app/current/public;
    fastcgi_pass unix:${SOCK};
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    include fastcgi_params;
}
EOF

nginx -t
systemctl reload nginx
echo "nginx reloaded, php-fpm socket: ${SOCK}"