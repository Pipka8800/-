<?php
require_once 'auth.php';
require_once 'functions.php';

// Проверяем доступ только для администратора
checkAccess('admin');

// Определяем роли пользователя
$isAdmin = hasRole('admin');
$isCook = hasRole('cook');
$isWaiter = hasRole('waiter');

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'role' => $_POST['role'],
                    'phone' => $_POST['phone']
                ];
                if (addEmployee($data)) {
                    $_SESSION['success'] = 'Сотрудник успешно добавлен';
                }
                break;
                
            case 'edit':
                $data = [
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'role' => $_POST['role'],
                    'phone' => $_POST['phone']
                ];
                if (updateEmployee($_POST['id'], $data)) {
                    $_SESSION['success'] = 'Данные сотрудника обновлены';
                }
                break;
                
            case 'delete':
                if (deleteEmployee($_POST['id'])) {
                    $_SESSION['success'] = 'Сотрудник удален';
                }
                break;

            case 'toggle_status':
                if (toggleEmployeeStatus($_POST['id'])) {
                    $_SESSION['show_success'] = true;
                    header('Location: employees.php');
                    exit;
                }
                break;
        }
        header('Location: employees.php');
        exit;
    }
}

// Получаем список сотрудников
$employees = getEmployees();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сотрудники - Система управления кафе</title>
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
                <a href="employees.php" class="nav-item active">
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
                <h1 class="text-2xl font-bold text-gray-800">Сотрудники</h1>
                <button onclick="showModal('addModal')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-plus"></i> Добавить сотрудника
                </button>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ФИО</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Роль</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Телефон</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $roles = [
                                    'admin' => 'Администратор',
                                    'cook' => 'Повар',
                                    'waiter' => 'Официант'
                                ];
                                echo $roles[$employee['role']] ?? $employee['role'];
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['phone']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteEmployee(<?php echo $employee['id']; ?>)" class="text-red-600 hover:text-red-900 mr-3">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                    <button type="submit" class="<?php echo $employee['status'] === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                                        <i class="fas <?php echo $employee['status'] === 'active' ? 'fa-lock' : 'fa-lock-open'; ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Уведомления -->
    <div id="notifications" class="fixed bottom-4 right-4 z-50 space-y-2">
        <?php if (isset($_SESSION['success'])): ?>
        <div id="successAlert" class="notification bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded-lg shadow-lg">
            <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
        </div>
        <?php 
        unset($_SESSION['success']);
        endif; 
        ?>
        
        <?php if (isset($_SESSION['show_success'])): ?>
        <div id="operationAlert" class="notification bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded-lg shadow-lg">
            <span class="block sm:inline">Операция успешно выполнена</span>
        </div>
        <?php 
        unset($_SESSION['show_success']);
        endif; 
        ?>
    </div>

    <!-- Модальное окно добавления сотрудника -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Добавить сотрудника</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">ФИО:</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Пароль:</label>
                    <input type="text" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Роль:</label>
                    <select name="role" class="form-input" required>
                        <option value="admin">Администратор</option>
                        <option value="cook">Повар</option>
                        <option value="waiter">Официант</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Телефон:</label>
                    <input type="text" name="phone" class="form-input" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('addModal')" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования сотрудника -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Редактировать сотрудника</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label class="form-label">ФИО:</label>
                    <input type="text" name="name" id="editName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" id="editEmail" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Роль:</label>
                    <select name="role" id="editRole" class="form-input" required>
                        <option value="admin">Администратор</option>
                        <option value="cook">Повар</option>
                        <option value="waiter">Официант</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Телефон:</label>
                    <input type="text" name="phone" id="editPhone" class="form-input" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('editModal')" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно удаления сотрудника -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h2 class="text-xl font-bold mb-4">Удалить сотрудника</h2>
            <p class="mb-4">Вы действительно хотите удалить этого сотрудника?</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('deleteModal')" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Удалить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Закрытие модального окна при клике вне его области
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        function editEmployee(employee) {
            document.getElementById('editId').value = employee.id;
            document.getElementById('editName').value = employee.name;
            document.getElementById('editEmail').value = employee.email;
            document.getElementById('editRole').value = employee.role;
            document.getElementById('editPhone').value = employee.phone;
            showModal('editModal');
        }

        function deleteEmployee(id) {
            document.getElementById('deleteId').value = id;
            showModal('deleteModal');
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