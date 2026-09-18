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

location ~ \.php\$ {
    root /var/app/current/public;
    fastcgi_pass unix:${SOCK};
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    fastcgi_param QUERY_STRING \$query_string;
    fastcgi_param REQUEST_METHOD \$request_method;
    fastcgi_param CONTENT_TYPE \$content_type;
    fastcgi_param CONTENT_LENGTH \$content_length;
    fastcgi_param SCRIPT_NAME \$fastcgi_script_name;
    fastcgi_param REQUEST_URI \$request_uri;
    fastcgi_param DOCUMENT_URI \$document_uri;
    fastcgi_param DOCUMENT_ROOT \$document_root;
    fastcgi_param SERVER_PROTOCOL \$server_protocol;
    fastcgi_param REQUEST_SCHEME \$scheme;
    fastcgi_param HTTPS \$https if_not_empty;
    fastcgi_param GATEWAY_INTERFACE CGI/1.1;
    fastcgi_param SERVER_SOFTWARE nginx/\$nginx_version;
    fastcgi_param REMOTE_ADDR \$remote_addr;
    fastcgi_param REMOTE_PORT \$remote_port;
    fastcgi_param SERVER_ADDR \$server_addr;
    fastcgi_param SERVER_PORT \$server_port;
    fastcgi_param SERVER_NAME \$server_name;
    fastcgi_param REDIRECT_STATUS 200;
}
EOF

nginx -t
systemctl reload nginx
echo "nginx reloaded, php-fpm socket: ${SOCK}"