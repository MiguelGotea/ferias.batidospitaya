<?php
require_once 'config.php';

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'u839374897_ferias';
$username = 'u839374897_ferias';
$password = 'FerPitHaya2025$';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}