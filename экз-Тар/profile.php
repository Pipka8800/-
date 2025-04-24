<?php
require_once 'auth.php';
require_once 'functions.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Получаем текущего пользователя
$currentUser = getCurrentUser();
$isAdmin = hasRole('admin');
$isCook = hasRole('cook');
$isWaiter = hasRole('waiter');

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $data = [
                    'name' => $_POST['name'],
                    'email' => $_POST['email']
                ];
                
                // Если введен новый пароль
                if (!empty($_POST['new_password'])) {
                    if (empty($_POST['current_password'])) {
                        $_SESSION['error'] = 'Для изменения пароля необходимо ввести текущий пароль';
                    } else {
                        // Проверяем текущий пароль
                        if (login($currentUser['email'], $_POST['current_password'])) {
                            $data['password'] = $_POST['new_password'];
                        } else {
                            $_SESSION['error'] = 'Неверный текущий пароль';
                        }
                    }
                }
                
                if (!isset($_SESSION['error'])) {
                    if (updateEmployee($currentUser['id'], $data)) {
                        $_SESSION['success'] = 'Профиль успешно обновлен';
                        $_SESSION['user_name'] = $data['name'];
                        $_SESSION['user_initials'] = getInitials($data['name']);
                        $currentUser = getCurrentUser(); // Обновляем данные пользователя
                    } else {
                        $_SESSION['error'] = 'Ошибка при обновлении профиля';
                    }
                }
                break;
        }
        header('Location: profile.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - Система управления кафе</title>
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
                        <?php echo htmlspecialchars($_SESSION['user_initials']); ?>
                    </div>
                    <div>
                        <h2 class="font-semibold logo-text"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
                        <p class="text-sm text-gray-500 nav-text">
                            <?php
                            if ($isAdmin) {
                                echo 'Администратор';
                            } elseif ($isCook) {
                                echo 'Повар';
                            } elseif ($isWaiter) {
                                echo 'Официант';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Главная</span>
                </a>
                <?php if ($isAdmin): ?>
                <a href="employees.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Сотрудники</span>
                </a>
                <?php endif; ?>
                <?php if ($isAdmin || $isWaiter): ?>
                <a href="shifts.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-text">Смены</span>
                </a>
                <?php endif; ?>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Заказы</span>
                </a>
                <a href="profile.php" class="nav-item active">
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
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold text-gray-800 mb-6">Профиль</h1>
                        
                        <!-- Уведомления -->
                        <div id="notifications" class="mb-4">
                            <?php if (isset($_SESSION['error'])): ?>
                            <div class="notification bg-red-100 border border-red-400 text-red-700 px-6 py-3 rounded-lg shadow-lg">
                                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                            </div>
                            <?php 
                            unset($_SESSION['error']);
                            endif; 
                            ?>
                            
                            <?php if (isset($_SESSION['success'])): ?>
                            <div class="notification bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded-lg shadow-lg">
                                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                            </div>
                            <?php 
                            unset($_SESSION['success']);
                            endif; 
                            ?>
                        </div>
                        
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label for="name" class="form-label">ФИО</label>
                                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required
                                       class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required
                                       class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label for="role" class="form-label">Роль</label>
                                <input type="text" id="role" value="<?php
                                    if ($isAdmin) echo 'Администратор';
                                    elseif ($isCook) echo 'Повар';
                                    else echo 'Официант';
                                ?>" disabled
                                       class="form-input bg-gray-100">
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h2 class="text-lg font-medium text-gray-900 mb-4">Смена пароля</h2>
                                
                                <div class="space-y-4">
                                    <div class="form-group">
                                        <label for="current_password" class="form-label">Текущий пароль</label>
                                        <input type="password" name="current_password" id="current_password"
                                               class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password" class="form-label">Новый пароль</label>
                                        <input type="password" name="new_password" id="new_password"
                                               class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                                        <input type="password" name="confirm_password" id="confirm_password"
                                               class="form-input">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="showConfirmModal()" class="btn-secondary">
                                    Отмена
                                </button>
                                <button type="submit" class="btn-primary">
                                    Сохранить изменения
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Модальное окно подтверждения отмены -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideConfirmModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Подтверждение</h2>
            <p class="mb-4">Вы действительно хотите отменить изменения? Все несохраненные данные будут потеряны.</p>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="hideConfirmModal()" class="btn-secondary">Нет</button>
                <button type="button" onclick="window.location.href='profile.php'" class="btn-primary">Да</button>
            </div>
        </div>
    </div>

    <script>
        // Функции для работы с модальными окнами
        function showConfirmModal() {
            document.getElementById('confirmModal').style.display = 'block';
        }
        
        function hideConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
        
        // Закрытие модального окна при клике вне его области
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Валидация паролей
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Новый пароль и подтверждение не совпадают');
            }
        });
        
        // Анимация уведомлений
        function handleNotifications() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(function(notification) {
                notification.style.transition = 'all 0.5s ease-in-out';
                notification.style.transform = 'translateY(0)';
                notification.style.opacity = '1';
                
                setTimeout(function() {
                    notification.style.transform = 'translateY(100%)';
                    notification.style.opacity = '0';
                    
                    setTimeout(function() {
                        notification.remove();
                    }, 500);
                }, 3000);
            });
        }
        
        // Запускаем обработку уведомлений при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            handleNotifications();
        });
    </script>
</body>
</html> 