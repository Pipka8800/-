<?php
session_start();
require_once 'config.php';

// Функция для проверки авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для проверки роли пользователя
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['user_role'] === $role;
}

// Функция для входа в систему
function login($email, $password) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_initials'] = getInitials($user['name']);
        return true;
    }
    return false;
}

// Функция для выхода из системы
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Функция для получения инициалов
function getInitials($name) {
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        $initials .= mb_substr($part, 0, 1, 'UTF-8');
    }
    return $initials;
}

// Функция для проверки доступа
function checkAccess($requiredRole) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    if (!hasRole($requiredRole)) {
        header('Location: index.php');
        exit;
    }
}

// Функция для получения данных текущего пользователя
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?> 