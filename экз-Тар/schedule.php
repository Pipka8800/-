<?php
require_once 'auth.php';
require_once 'functions.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Проверяем права администратора
if (!hasRole('admin')) {
    header('Location: index.php');
    exit;
}

// Получаем данные пользователя из сессии
$userName = $_SESSION['user_name'] ?? 'Пользователь';
$userRole = $_SESSION['user_role'] ?? 'student';
$userInitials = $_SESSION['user_initials'] ?? 'П';

// Получаем расписание на текущую неделю
$schedule = getWeekSchedule();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание - Школьные кружки</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Боковая панель -->
        <aside class="sidebar bg-white shadow-lg">
            <div class="p-4 border-b">
                <div class="flex items-center space-x-3">
                    <div class="avatar avatar-blue">
                        <?php echo htmlspecialchars($userInitials); ?>
                    </div>
                    <div>
                        <h2 class="font-semibold logo-text"><?php echo htmlspecialchars($userName); ?></h2>
                        <p class="text-sm text-gray-500 nav-text">Администратор</p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Главная</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Пользователи</span>
                </a>
                <a href="clubs.php" class="nav-item">
                    <i class="fas fa-chalkboard"></i>
                    <span class="nav-text">Кружки</span>
                </a>
                <a href="schedule.php" class="nav-item active">
                    <i class="fas fa-calendar"></i>
                    <span class="nav-text">Расписание</span>
                </a>
                <a href="reviews.php" class="nav-item">
                    <i class="fas fa-star"></i>
                    <span class="nav-text">Отзывы</span>
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
                <h1 class="text-2xl font-bold text-gray-800">Расписание</h1>
                <div class="flex items-center space-x-4">
                    <button class="btn-secondary">
                        <i class="fas fa-chevron-left mr-2"></i>
                        Предыдущая неделя
                    </button>
                    <button class="btn-primary" onclick="showAddScheduleModal()">
                        <i class="fas fa-plus mr-2"></i>
                        Добавить занятие
                    </button>
                    <button class="btn-secondary">
                        Следующая неделя
                        <i class="fas fa-chevron-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- Расписание -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Время</th>
                            <th>Понедельник</th>
                            <th>Вторник</th>
                            <th>Среда</th>
                            <th>Четверг</th>
                            <th>Пятница</th>
                            <th>Суббота</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $timeSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
                        foreach ($timeSlots as $time):
                        ?>
                        <tr>
                            <td class="font-medium"><?php echo $time; ?></td>
                            <?php for ($day = 1; $day <= 6; $day++): ?>
                            <td>
                                <?php
                                if (isset($schedule[$day][$time])):
                                    $lesson = $schedule[$day][$time];
                                ?>
                                <div class="p-2 bg-blue-50 rounded">
                                    <div class="font-medium text-blue-800"><?php echo htmlspecialchars($lesson['club_name']); ?></div>
                                    <div class="text-sm text-blue-600"><?php echo htmlspecialchars($lesson['teacher_name']); ?></div>
                                    <div class="text-xs text-blue-500"><?php echo htmlspecialchars($lesson['room']); ?></div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Модальное окно добавления занятия -->
    <div class="modal-overlay" id="modalOverlay"></div>
    <div id="addScheduleModal" class="modal">
        <div class="modal-content bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Добавить занятие</h2>
            <form id="addScheduleForm" method="POST" action="add_schedule.php">
                <div class="form-group">
                    <label class="form-label" for="club">Кружок</label>
                    <select id="club" name="club_id" class="form-input" required>
                        <option value="">Выберите кружок</option>
                        <?php foreach (getAllClubs() as $club): ?>
                        <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="day">День недели</label>
                    <select id="day" name="day" class="form-input" required>
                        <option value="1">Понедельник</option>
                        <option value="2">Вторник</option>
                        <option value="3">Среда</option>
                        <option value="4">Четверг</option>
                        <option value="5">Пятница</option>
                        <option value="6">Суббота</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="time">Время</label>
                    <select id="time" name="time" class="form-input" required>
                        <?php foreach ($timeSlots as $time): ?>
                        <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="room">Кабинет</label>
                    <input type="text" id="room" name="room" class="form-input" required>
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" class="btn-secondary" onclick="hideAddScheduleModal()">Отмена</button>
                    <button type="submit" class="btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Функции для работы с модальным окном
        function showAddScheduleModal() {
            document.getElementById('modalOverlay').classList.add('show');
            document.getElementById('addScheduleModal').classList.add('show');
        }

        function hideAddScheduleModal() {
            document.getElementById('modalOverlay').classList.remove('show');
            document.getElementById('addScheduleModal').classList.remove('show');
        }

        // Закрытие модального окна при клике вне его
        document.getElementById('modalOverlay').addEventListener('click', hideAddScheduleModal);

        // Предотвращение закрытия при клике на само модальное окно
        document.getElementById('addScheduleModal').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html> 