<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDatabaseIfNotExists extends Command
{
    protected $signature = 'db:create-if-not-exists';
    protected $description = 'Create the database if it does not exist';

    public function handle(): int
    {
        $config = config('database.connections.mysql');
        $database = $config['database'];
        $host = $config['host'];
        $username = $config['username'];
        $password = $config['password'] ?? '';

        try {
            $conn = new \mysqli($host, $username, $password);
            if ($conn->connect_error) {
                $this->error('MySQL connection failed: ' . $conn->connect_error);
                return 1;
            }

            $conn->query("CREATE DATABASE IF NOT EXISTS `$database`");
            $this->info("Database '$database' is ready.");
            $conn->close();
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
