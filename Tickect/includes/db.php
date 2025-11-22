<?php
// includes/db.php

$host = 'localhost';
$dbname = 'sistema_tickets'; // Cambia este nombre según tu base de datos
$user = 'root';              // Usuario de tu MySQL
$pass = '';                  // Contraseña (en XAMPP suele estar vacía)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
