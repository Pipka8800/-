<?php
require_once 'auth.php';
require_once 'config.php';

// Функции для работы с сотрудниками
function getEmployees() {
    $db = connectDB();
    $stmt = $db->query('SELECT * FROM employees ORDER BY name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEmployee($id) {
    $db = connectDB();
    $stmt = $db->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addEmployee($data) {
    $db = connectDB();
    $stmt = $db->prepare('INSERT INTO employees (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, "active")');
    return $stmt->execute([
        $data['name'],
        $data['email'],
        $data['password'],
        $data['role'],
        $data['phone']
    ]);
}

function updateEmployee($id, $data) {
    $db = connectDB();
    $fields = [];
    $values = [];
    
    // Добавляем поля для обновления
    if (isset($data['name'])) {
        $fields[] = 'name = ?';
        $values[] = $data['name'];
    }
    if (isset($data['email'])) {
        $fields[] = 'email = ?';
        $values[] = $data['email'];
    }
    if (isset($data['role'])) {
        $fields[] = 'role = ?';
        $values[] = $data['role'];
    }
    if (isset($data['phone'])) {
        $fields[] = 'phone = ?';
        $values[] = $data['phone'];
    }
    if (isset($data['password'])) {
        $fields[] = 'password = ?';
        $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $values[] = $id;
    $sql = 'UPDATE employees SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
}

function deleteEmployee($id) {
    $db = connectDB();
    $stmt = $db->prepare('DELETE FROM employees WHERE id = ?');
    return $stmt->execute([$id]);
}

function toggleEmployeeStatus($id) {
    $db = connectDB();
    $stmt = $db->prepare('UPDATE employees SET status = CASE WHEN status = "active" THEN "blocked" ELSE "active" END WHERE id = ?');
    return $stmt->execute([$id]);
}

function getEmployeesByRole($role) {
    $db = connectDB();
    $stmt = $db->prepare('SELECT * FROM employees WHERE role = ? AND status = "active" ORDER BY name');
    $stmt->execute([$role]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Функции для работы со сменами
function getShifts($filters = []) {
    $pdo = connectDB();
    $sql = "
        SELECT s.*, 
               c.name as cook_name, 
               w.name as waiter_name 
        FROM shifts s
        LEFT JOIN employees c ON s.cook_id = c.id
        LEFT JOIN employees w ON s.waiter_id = w.id
    ";

    $params = [];
    $where = [];
    
    if (!empty($filters['waiter_id'])) {
        $where[] = "s.waiter_id = ?";
        $params[] = $filters['waiter_id'];
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY s.date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getShift($id) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addShift($data) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("INSERT INTO shifts (date, cook_id, waiter_id) VALUES (?, ?, ?)");
    return $stmt->execute([
        $data['date'],
        $data['cook_id'],
        $data['waiter_id']
    ]);
}

function updateShift($id, $data) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("UPDATE shifts SET date = ?, cook_id = ?, waiter_id = ?, status = ? WHERE id = ?");
    return $stmt->execute([
        $data['date'],
        $data['cook_id'],
        $data['waiter_id'],
        $data['status'],
        $id
    ]);
}

function deleteShift($id) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
    return $stmt->execute([$id]);
}

// Функции для работы с заказами
function getOrders($filters = []) {
    $pdo = connectDB();
    $sql = "
        SELECT o.*, 
               w.name as waiter_name,
               COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN employees w ON o.waiter_id = w.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
    ";
    
    $params = [];
    $where = [];
    
    if (!empty($filters['waiter_id'])) {
        $where[] = "o.waiter_id = ?";
        $params[] = $filters['waiter_id'];
    }
    
    if (!empty($filters['status'])) {
        if (is_array($filters['status'])) {
            $placeholders = str_repeat('?,', count($filters['status']) - 1) . '?';
            $where[] = "o.status IN ($placeholders)";
            $params = array_merge($params, $filters['status']);
        } else {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getOrder($id) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT o.*, 
               w.name as waiter_name,
               GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN employees w ON o.waiter_id = w.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createOrder($data) {
    $pdo = connectDB();
    $pdo->beginTransaction();
    
    try {
        // Создаем заказ
        $stmt = $pdo->prepare("INSERT INTO orders (table_number, waiter_id, status) VALUES (?, ?, 'new')");
        $stmt->execute([$data['table_number'], $data['waiter_id']]);
        $orderId = $pdo->lastInsertId();
        
        // Добавляем позиции заказа
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity) VALUES (?, ?, ?)");
        foreach ($data['items'] as $item) {
            $stmt->execute([$orderId, $item['menu_item_id'], $item['quantity']]);
        }
        
        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateOrderStatus($id, $status) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

function getOrderStatuses() {
    return [
        'new' => 'Новый',
        'in_progress' => 'В работе',
        'ready' => 'Готов',
        'served' => 'Подано',
        'paid' => 'Оплачено'
    ];
}

// Вспомогательные функции
function getActiveShift() {
    $pdo = connectDB();
    $stmt = $pdo->query("SELECT * FROM shifts WHERE date = CURDATE() LIMIT 1");
    return $stmt->fetch();
}

function deleteOrder($id) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting order: " . $e->getMessage());
        return false;
    }
}

function updateOrder($id, $data) {
    $db = connectDB();
    try {
        $sql = "UPDATE orders SET 
                table_number = ?,
                waiter_id = ?,
                status = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $data['table_number'],
            $data['waiter_id'],
            $data['status'],
            $id
        ]);
    } catch (PDOException $e) {
        error_log("Error updating order: " . $e->getMessage());
        return false;
    }
}

function getEmployeesCount() {
    $db = connectDB();
    $stmt = $db->query("SELECT COUNT(*) FROM employees WHERE status != 'deleted'");
    return $stmt->fetchColumn();
}

function getActiveShiftsCount() {
    $db = connectDB();
    $stmt = $db->query("SELECT COUNT(*) FROM shifts WHERE status = 'active'");
    return $stmt->fetchColumn();
}

function getNewOrdersCount() {
    $db = connectDB();
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'new'");
    return $stmt->fetchColumn();
}

function getInProgressOrdersCount() {
    $db = connectDB();
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'in_progress'");
    return $stmt->fetchColumn();
}

function getReadyOrdersCount() {
    $db = connectDB();
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'ready'");
    return $stmt->fetchColumn();
}
?> 