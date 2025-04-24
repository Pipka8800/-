<?php
require_once 'auth.php';
require_once 'functions.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователя из сессии
$userName = $_SESSION['user_name'] ?? 'Пользователь';
$userRole = $_SESSION['user_role'] ?? 'waiter';
$userInitials = $_SESSION['user_initials'] ?? 'П';

// Определяем роль пользователя
$isAdmin = hasRole('admin');
$isCook = hasRole('cook');
$isWaiter = hasRole('waiter');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система управления кафе</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Боковая панель -->
        <aside class="sidebar bg-white shadow-lg w-64">
            <div class="p-4 border-b">
                <div class="flex items-center space-x-3">
                    <div class="avatar avatar-blue">
                        <?php echo htmlspecialchars($userInitials); ?>
                    </div>
                    <div>
                        <h2 class="font-semibold logo-text"><?php echo htmlspecialchars($userName); ?></h2>
                        <p class="text-sm text-gray-500 nav-text">
                            <?php
                            switch($userRole) {
                                case 'admin':
                                    echo 'Администратор';
                                    break;
                                case 'cook':
                                    echo 'Повар';
                                    break;
                                case 'waiter':
                                    echo 'Официант';
                                    break;
                                default:
                                    echo 'Пользователь';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4">
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Главная</span>
                </a>
                <?php if ($isAdmin): ?>
                <a href="employees.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Сотрудники</span>
                </a>
                <a href="shifts.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-text">Смены</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Заказы</span>
                </a>
                <?php endif; ?>

                <?php if ($isCook): ?>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-text">Заказы</span>
                </a>
                <?php endif; ?>

                <?php if ($isWaiter): ?>
                <a href="shifts.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-text">Смены</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Заказы</span>
                </a>
                <?php endif; ?>

                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span class="nav-text">Профиль</span>
                </a>
                <a href="logout.php" class="nav-item text-red-500">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Выход</span>
                </a>
            </nav>
        </aside>

        <!-- Основной контент -->
        <main class="content flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    <?php
                    $currentPage = basename($_SERVER['PHP_SELF']);
                    switch($currentPage) {
                        case 'employees.php':
                            echo 'Сотрудники';
                            break;
                        case 'shifts.php':
                            echo 'Смены';
                            break;
                        case 'orders.php':
                            echo 'Заказы';
                            break;
                        case 'profile.php':
                            echo 'Профиль';
                            break;
                        default:
                            echo 'Главная';
                    }
                    ?>
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="Поиск..." class="search-input pl-10 pr-4 py-2 border rounded-lg">
                    </div>
                    <button class="relative p-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="notification-badge"></span>
                    </button>
                </div>
            </div>
            
            <!-- Содержимое страницы -->
            <div class="flex-1 overflow-y-auto p-6">
                <?php if ($isAdmin): ?>
                <!-- Контент для администратора -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Сотрудники</h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Всего: <?php echo getEmployeesCount(); ?></span>
                        </div>
                        <p class="text-gray-600 mb-4">Управление персоналом кафе</p>
                        <a href="employees.php" class="btn-primary inline-block">Перейти</a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Смены</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Активных: <?php echo getActiveShiftsCount(); ?></span>
                        </div>
                        <p class="text-gray-600 mb-4">Управление рабочими сменами</p>
                        <a href="shifts.php" class="btn-primary inline-block">Перейти</a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Заказы</h3>
                            <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded">Активных: <?php echo getNewOrdersCount() + getInProgressOrdersCount(); ?></span>
                        </div>
                        <p class="text-gray-600 mb-4">Просмотр и управление заказами</p>
                        <a href="orders.php" class="btn-primary inline-block">Перейти</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($isCook): ?>
                <!-- Контент для повара -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Заказы</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Активных: <?php echo getNewOrdersCount() + getInProgressOrdersCount(); ?></span>
                        </div>
                        <p class="text-gray-600 mb-4">Управление заказами на кухне</p>
                        <a href="orders.php" class="btn-primary inline-block">Перейти</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($isWaiter): ?>
                <!-- Контент для официанта -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Смены</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Активных: <?php echo getActiveShiftsCount(); ?></span>
                        </div>
                        <p class="text-gray-600 mb-4">Информация о текущей смене</p>
                        <a href="shifts.php" class="btn-primary inline-block">Перейти</a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Заказы</h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Новых: <?php echo getNewOrdersCount(); ?></span>
                        </div>
                        <p class="text-gray-600 mb-4">Создание и управление заказами</p>
                        <a href="orders.php" class="btn-primary inline-block">Перейти</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Скрипты -->
    <script>
        // Функция для сворачивания/разворачивания боковой панели
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            const navTexts = document.querySelectorAll('.nav-text');
            const logoText = document.querySelector('.logo-text');
            
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-20');
            content.classList.toggle('ml-64');
            content.classList.toggle('ml-20');
            
            navTexts.forEach(text => {
                text.classList.toggle('hidden');
            });
            
            logoText.classList.toggle('hidden');
            
            // Сохраняем состояние в localStorage
            const isCollapsed = sidebar.classList.contains('w-20');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Восстанавливаем состояние боковой панели при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                const sidebar = document.querySelector('.sidebar');
                const content = document.querySelector('.content');
                const navTexts = document.querySelectorAll('.nav-text');
                const logoText = document.querySelector('.logo-text');
                
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                content.classList.remove('ml-64');
                content.classList.add('ml-20');
                
                navTexts.forEach(text => {
                    text.classList.add('hidden');
                });
                
                logoText.classList.add('hidden');
            }
        });
    </script>
</body>
</html>