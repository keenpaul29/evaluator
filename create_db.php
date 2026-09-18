<?php
$host = 'evaluator-db.cqrw4e2m4ilq.us-east-1.rds.amazonaws.com';
$user = 'admin';
$pass = 'mGlrJCUHLv';
$db = 'evaluator';

echo "Connecting to MySQL at $host...\n";
$conn = @new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
    exit(1);
}

echo "Connected. Creating database...\n";
$conn->query("CREATE DATABASE IF NOT EXISTS `$db`");
echo "Database '$db' created/verified.\n";
$conn->close();
echo "Done.\n";
