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
            case 'create':
                if ($isAdmin || $isWaiter) {
                    $data = [
                        'table_number' => $_POST['table_number'],
                        'waiter_id' => $isAdmin ? $_POST['waiter_id'] : $currentUser['id'],
                        'status' => 'new'
                    ];
                    if (createOrder($data)) {
                        $_SESSION['success'] = 'Заказ успешно создан';
                    }
                }
                break;
                
            case 'edit':
                if ($isAdmin || $isWaiter) {
                    $data = [
                        'table_number' => $_POST['table_number'],
                        'waiter_id' => $isAdmin ? $_POST['waiter_id'] : $currentUser['id'],
                        'status' => $_POST['status']
                    ];
                    if (updateOrder($_POST['id'], $data)) {
                        $_SESSION['success'] = 'Заказ успешно обновлен';
                    }
                }
                break;
                
            case 'delete':
                if ($isAdmin || $isWaiter) {
                    if (deleteOrder($_POST['id'])) {
                        $_SESSION['success'] = 'Заказ успешно удален';
                    }
                }
                break;
                
            case 'update_status':
                if ($isAdmin || $isCook || $isWaiter) {
                    if (updateOrderStatus($_POST['id'], $_POST['status'])) {
                        $_SESSION['show_success'] = true;
                        header('Location: orders.php');
                        exit;
                    }
                }
                break;
        }
        header('Location: orders.php');
        exit;
    }
}

// Получаем список заказов с учетом роли
$orders = getOrders();

// Получаем список официантов для администратора
$waiters = $isAdmin ? getEmployeesByRole('waiter') : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы - Система управления кафе</title>
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
                <a href="orders.php" class="nav-item active">
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
                <h1 class="text-2xl font-bold text-gray-800">
                    <?php if ($isCook): ?>
                        Заказы на кухню
                    <?php elseif ($isWaiter): ?>
                        Заказы столов
                    <?php else: ?>
                        Заказы
                    <?php endif; ?>
                </h1>
                <?php if ($isAdmin): ?>
                <button onclick="showCreateModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>Создать заказ
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Таблица заказов -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">№ стола</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Официант</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Время создания</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($order['table_number']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($order['waiter_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch($order['status']) {
                                        case 'new':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'in_progress':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'ready':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'served':
                                            echo 'bg-purple-100 text-purple-800';
                                            break;
                                        case 'paid':
                                            echo 'bg-gray-100 text-gray-800';
                                            break;
                                    }
                                    ?>">
                                    <?php
                                    switch($order['status']) {
                                        case 'new':
                                            echo 'Новый';
                                            break;
                                        case 'in_progress':
                                            echo 'В работе';
                                            break;
                                        case 'ready':
                                            echo 'Готов';
                                            break;
                                        case 'served':
                                            echo 'Подано';
                                            break;
                                        case 'paid':
                                            echo 'Оплачено';
                                            break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <?php if ($isAdmin): ?>
                                    <!-- Администратор видит все кнопки -->
                                    <button onclick="editOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteOrder(<?php echo $order['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php elseif ($isWaiter): ?>
                                    <!-- Официант видит только кнопку редактирования -->
                                    <button onclick="editOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php elseif ($isCook && in_array($order['status'], ['new', 'in_progress'])): ?>
                                    <!-- Повар видит кнопку смены статуса для новых и текущих заказов -->
                                    <button onclick="showStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
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

    <!-- Модальное окно создания заказа -->
    <?php if ($isAdmin || $isWaiter): ?>
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideCreateModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Создать заказ</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Номер стола:</label>
                    <input type="number" name="table_number" id="table_number" class="form-input" required min="1">
                </div>
                <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label class="form-label">Официант:</label>
                    <select name="waiter_id" id="waiter_id" class="form-input" required>
                        <option value="">Выберите официанта</option>
                        <?php foreach ($waiters as $waiter): ?>
                        <option value="<?php echo $waiter['id']; ?>">
                            <?php echo htmlspecialchars($waiter['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideCreateModal()" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Модальное окно изменения статуса -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideStatusModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Изменить статус заказа</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="status_order_id">
                <div class="form-group">
                    <label class="form-label">Новый статус:</label>
                    <select name="status" id="status" class="form-input" required>
                        <?php if ($isCook): ?>
                        <option value="in_progress">В работе</option>
                        <option value="ready">Готов</option>
                        <?php endif; ?>
                        <?php if ($isWaiter): ?>
                        <option value="served">Подано</option>
                        <option value="paid">Оплачено</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideStatusModal()" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования заказа -->
    <div id="editOrderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideEditOrderModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Редактировать заказ</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_order_id">
                <?php if ($isAdmin || ($isWaiter && $order['waiter_id'] == $currentUser['id'])): ?>
                <div class="form-group">
                    <label class="form-label">Номер стола:</label>
                    <input type="number" name="table_number" id="edit_table_number" class="form-input" required min="1">
                </div>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label class="form-label">Официант:</label>
                    <select name="waiter_id" id="edit_waiter_id" class="form-input" required>
                        <option value="">Выберите официанта</option>
                        <?php foreach ($waiters as $waiter): ?>
                        <option value="<?php echo $waiter['id']; ?>">
                            <?php echo htmlspecialchars($waiter['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Статус:</label>
                    <select name="status" id="edit_status" class="form-input" required>
                        <?php if ($isAdmin): ?>
                        <option value="new">Новый</option>
                        <option value="in_progress">В работе</option>
                        <option value="ready">Готов</option>
                        <option value="served">Подано</option>
                        <option value="paid">Оплачено</option>
                        <?php elseif ($isWaiter): ?>
                        <option value="new">Новый</option>
                        <option value="in_progress">В работе</option>
                        <option value="ready">Готов</option>
                        <option value="served">Подано</option>
                        <option value="paid">Оплачено</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideEditOrderModal()" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно удаления заказа -->
    <div id="deleteOrderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideDeleteOrderModal()">&times;</span>
            <h2 class="text-xl font-bold mb-4">Удалить заказ</h2>
            <p class="mb-4">Вы действительно хотите удалить этот заказ?</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_order_id">
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideDeleteOrderModal()" class="btn-secondary">Отмена</button>
                    <button type="submit" class="btn-primary">Удалить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Функции для работы с модальными окнами
        function showCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }
        
        function hideCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }
        
        function showStatusModal(orderId, currentStatus) {
            document.getElementById('status_order_id').value = orderId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function hideStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        function editOrder(order) {
            document.getElementById('edit_order_id').value = order.id;
            document.getElementById('edit_table_number').value = order.table_number;
            if (document.getElementById('edit_waiter_id')) {
                document.getElementById('edit_waiter_id').value = order.waiter_id;
            }
            document.getElementById('edit_status').value = order.status;
            showEditOrderModal();
        }

        function deleteOrder(id) {
            document.getElementById('delete_order_id').value = id;
            showDeleteOrderModal();
        }

        function showEditOrderModal() {
            document.getElementById('editOrderModal').style.display = 'block';
        }

        function hideEditOrderModal() {
            document.getElementById('editOrderModal').style.display = 'none';
        }

        function showDeleteOrderModal() {
            document.getElementById('deleteOrderModal').style.display = 'block';
        }

        function hideDeleteOrderModal() {
            document.getElementById('deleteOrderModal').style.display = 'none';
        }

        // Закрытие модального окна при клике вне его области
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
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