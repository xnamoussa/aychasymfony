<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$url = $_ENV['DATABASE_URL'];
$parsed = parse_url($url);
$host = $parsed['host'];
$db   = str_replace('/', '', $parsed['path']);
$user = $parsed['user'];
$pass = $parsed['pass'];

$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
echo "Structure of posts table:\n";
$stmt = $pdo->query("DESCRIBE posts");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nStructure of comments table:\n";
$stmt = $pdo->query("DESCRIBE comments");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
