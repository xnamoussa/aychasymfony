<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load('.env');

$url = $_ENV['DATABASE_URL'];
$config = parse_url($url);
$host = $config['host'];
$port = $config['port'] ?? 3306;
$user = $config['user'];
$pass = $config['pass'] ?? '';
$db = trim($config['path'], '/');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Users in database:\n";
    $stmt = $pdo->query("SELECT id, email, role FROM users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Email: {$row['email']} | Role: {$row['role']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
