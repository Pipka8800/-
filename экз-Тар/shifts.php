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

// Если не администратор и не официант - редирект на главную
if (!$isAdmin && !$isWaiter) {
    header('Location: index.php');
    exit;
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if ($isAdmin) {
                    $data = [
                        'date' => $_POST['date'],
                        'cook_id' => $_POST['cook_id'],
                        'waiter_id' => $_POST['waiter_id']
                    ];
                    if (addShift($data)) {
                        header('Location: shifts.php?success=1');
                        exit;
                    }
                }
                break;
                
            case 'edit':
                if ($isAdmin) {
                    $data = [
                        'date' => $_POST['date'],
                        'cook_id' => $_POST['cook_id'],
                        'waiter_id' => $_POST['waiter_id'],
                        'status' => $_POST['status']
                    ];
                    if (updateShift($_POST['id'], $data)) {
                        header('Location: shifts.php?success=1');
                        exit;
                    }
                }
                break;
                
            case 'delete':
                if ($isAdmin) {
                    if (deleteShift($_POST['id'])) {
                        header('Location: shifts.php?success=1');
                        exit;
                    }
                }
                break;
        }
    }
}

// Получаем список смен с учетом роли
$shifts = getShifts();

// Получаем список поваров и официантов для администратора
$cooks = $isAdmin ? getEmployeesByRole('cook') : [];
$waiters = $isAdmin ? getEmployeesByRole('waiter') : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Смены - Система управления кафе</title>
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
                <a href="shifts.php" class="nav-item active">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-text">Смены</span>
                </a>
                <?php endif; ?>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Заказы</span>
                </a>
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
                <h1 class="text-2xl font-bold text-gray-800">Смены</h1>
                <?php if ($isAdmin): ?>
                <button onclick="showModal('addShiftModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-plus"></i> Добавить смену
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Уведомления -->
            <div id="notifications" class="fixed bottom-4 right-4 z-50 space-y-2">
                <?php if (isset($_GET['success'])): ?>
                <div id="successAlert" class="notification bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded-lg shadow-lg">
                    <span class="block sm:inline">Операция успешно выполнена</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Повар</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Официант</th>
                            <?php if ($isAdmin): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($shifts as $shift): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d.m.Y', strtotime($shift['date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch($shift['status']) {
                                    case 'active':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $statusText = 'Активна';
                                        break;
                                    case 'completed':
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        $statusText = 'Завершена';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'bg-red-100 text-red-800';
                                        $statusText = 'Отменена';
                                        break;
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($shift['cook_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($shift['waiter_name']); ?></td>
                            <?php if ($isAdmin): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editShift(<?php echo htmlspecialchars(json_encode($shift)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteShift(<?php echo $shift['id']; ?>)" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Модальное окно добавления смены -->
    <div id="addShiftModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addShiftModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Добавить смену</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Дата:</label>
                    <input type="date" name="date" id="date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Повар:</label>
                    <select name="cook_id" id="cook_id" class="form-input" required>
                        <?php foreach ($cooks as $cook): ?>
                        <option value="<?php echo $cook['id']; ?>"><?php echo htmlspecialchars($cook['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Официант:</label>
                    <select name="waiter_id" id="waiter_id" class="form-input" required>
                        <?php foreach ($waiters as $waiter): ?>
                        <option value="<?php echo $waiter['id']; ?>"><?php echo htmlspecialchars($waiter['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideModal('addShiftModal')" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования смены -->
    <div id="editShiftModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('editShiftModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Редактировать смену</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Дата:</label>
                    <input type="date" name="date" id="edit_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Статус:</label>
                    <select name="status" id="edit_status" class="form-input" required>
                        <option value="active">Активна</option>
                        <option value="completed">Завершена</option>
                        <option value="cancelled">Отменена</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Повар:</label>
                    <select name="cook_id" id="edit_cook_id" class="form-input" required>
                        <?php foreach ($cooks as $cook): ?>
                        <option value="<?php echo $cook['id']; ?>"><?php echo htmlspecialchars($cook['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Официант:</label>
                    <select name="waiter_id" id="edit_waiter_id" class="form-input" required>
                        <?php foreach ($waiters as $waiter): ?>
                        <option value="<?php echo $waiter['id']; ?>"><?php echo htmlspecialchars($waiter['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideModal('editShiftModal')" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно удаления смены -->
    <div id="deleteShiftModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('deleteShiftModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Удалить смену</h2>
            <p class="mb-4">Вы действительно хотите удалить эту смену?</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideModal('deleteShiftModal')" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Удалить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Закрытие модального окна при клике вне его области
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        function editShift(shift) {
            document.getElementById('edit_id').value = shift.id;
            document.getElementById('edit_date').value = shift.date;
            document.getElementById('edit_cook_id').value = shift.cook_id;
            document.getElementById('edit_waiter_id').value = shift.waiter_id;
            document.getElementById('edit_status').value = shift.status || 'active';
            showModal('editShiftModal');
        }

        function deleteShift(id) {
            document.getElementById('delete_id').value = id;
            showModal('deleteShiftModal');
        }

        // Обновленная функция для анимации уведомлений
        function handleNotifications() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(function(notification) {
                // Добавляем начальную анимацию появления
                notification.style.transition = 'all 0.5s ease-in-out';
                notification.style.transform = 'translateY(0)';
                notification.style.opacity = '1';

                // Устанавливаем таймер на скрытие
                setTimeout(function() {
                    notification.style.transform = 'translateY(100%)';
                    notification.style.opacity = '0';
                    
                    // Удаляем элемент после завершения анимации
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