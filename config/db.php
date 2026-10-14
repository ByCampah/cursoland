<?php
$host = 'localhost';
$db   = 'cursoland';
$user = 'root';
$pass = ''; // Si tu phpMyAdmin en XAMPP tiene clave, ponela acá
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Error en la conexión con la base de datos: " . $e->getMessage());
}
?>