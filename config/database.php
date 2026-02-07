<?php

try {
    $pdo = new PDO('pgsql:host=localhost;dbname=vanilla', 'postgres', 'postgres');
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

$pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $pdo->prepare("INSERT INTO users (username, password) 
    VALUES (:username, :password) 
    ON CONFLICT (username) DO NOTHING");

$stmt->execute([
    'username' => 'admin',
    'password' => password_hash('admin', PASSWORD_BCRYPT)
]);