<?php

// Database configuration from environment variables
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '5432';
$dbName = getenv('DB_NAME') ?: 'vanilla';
$dbUser = getenv('DB_USER') ?: 'postgres';
$dbPassword = getenv('DB_PASSWORD') ?: 'postgres';

try {
    $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
} catch (\Throwable $th) {
    error_log('Database connection failed: ' . $th->getMessage());
    die('Database connection error. Please contact support.');
}

// Error Mode
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS properties (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    lot_number VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    area_sqm DECIMAL(10,2),
    property_type VARCHAR(50) DEFAULT 'house',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $pdo->prepare("INSERT INTO users (username, password) 
    VALUES (:username, :password) 
    ON CONFLICT (username) DO NOTHING");

$stmt->execute([
    'username' => 'admin',
    'password' => password_hash('admin', PASSWORD_BCRYPT)
]);