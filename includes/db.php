<?php
$host = 'localhost';
$dbname = 'balcao';
$username = 'root'; // Altere conforme sua configuração
$password = '';     // Altere conforme sua configuração


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
$pdo->exec("SET NAMES utf8mb4");
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
?>