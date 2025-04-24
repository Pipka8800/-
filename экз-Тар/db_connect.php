<?php
// Параметры подключения к базе данных
$host = 'localhost';
$dbname = 'pipka';
$username = 'root';
$password = '';

try {
    // Создаем подключение к базе данных
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Устанавливаем режим обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // В случае ошибки выводим сообщение
    echo "Ошибка подключения к базе данных: " . $e->getMessage();
    die();
}
?> 