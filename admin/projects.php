<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die('Доступ запрещен');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    header('Location: projects.php');
    exit;
}

$edit_project = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $edit_project = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    $contact_info = trim($_POST['contact_info']);
    $customer_id = (int)$_POST['customer_id'];
    
    if (isset($_POST['project_id']) && !empty($_POST['project_id'])) {
        $id = (int)$_POST['project_id'];
        $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, budget=?, contact_info=?, customer_id=? WHERE id=?");
        $stmt->execute([$title, $description, $budget, $contact_info, $customer_id, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (title, description, budget, contact_info, customer_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $budget, $contact_info, $customer_id]);
    }
    header('Location: projects.php');
    exit;
}

$projects = $pdo->query("
    SELECT p.*, u.name as customer_name 
    FROM projects p 
    JOIN users u ON p.customer_id = u.id 
    ORDER BY p.created_at DESC
")->fetchAll();

$customers = $pdo->prepare("SELECT id, name FROM users WHERE role = 'customer'");
$customers->execute();
$customers = $customers->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Управление проектами</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">Фриланс Биржа</a></h1>
            <nav>
                <ul>
                    <li><a href="../index.php">Главная</a></li>
                    <li><a href="projects.php">Проекты</a></li>
                    <li><a href="responses.php">Отклики</a></li>
                    <li><a href="../logout.php">Выйти</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
        <h2>Управление проектами</h2>
        
        <h3><?php echo $edit_project ? 'Редактировать проект' : 'Добавить новый проект'; ?></h3>
        <form method="POST" class="admin-form">
            <?php if ($edit_project): ?>
                <input type="hidden" name="project_id" value="<?php echo $edit_project['id']; ?>">
            <?php endif; ?>
            <label>Название:</label>
            <input type="text" name="title" required value="<?php echo $edit_project ? htmlspecialchars($edit_project['title']) : ''; ?>">
            <label>Описание:</label>
            <textarea name="description" rows="5" required><?php echo $edit_project ? htmlspecialchars($edit_project['description']) : ''; ?></textarea>
            <label>Бюджет:</label>
            <input type="number" step="0.01" name="budget" value="<?php echo $edit_project ? htmlspecialchars($edit_project['budget']) : ''; ?>">
            <label>Контактная информация:</label>
            <textarea name="contact_info" rows="2"><?php echo $edit_project ? htmlspecialchars($edit_project['contact_info']) : ''; ?></textarea>
            <label>Заказчик:</label>
            <select name="customer_id" required>
                <option value="">-- Выберите --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($edit_project && $edit_project['customer_id'] == $c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit"><?php echo $edit_project ? 'Обновить' : 'Добавить'; ?></button>
            <?php if ($edit_project): ?>
                <a href="projects.php" class="cancel">Отмена</a>
            <?php endif; ?>
        </form>
        
        <h3>Список проектов</h3>
        <table class="admin-table">
            <thead><tr><th>ID</th><th>Название</th><th>Заказчик</th><th>Дата</th><th>Действия</th></tr></thead>
            <tbody>
                <?php foreach ($projects as $proj): ?>
                <tr>
                    <td data-label="ID"><?php echo $proj['id']; ?></td>
                    <td data-label="Название"><?php echo htmlspecialchars($proj['title']); ?></td>
                    <td data-label="Заказчик"><?php echo htmlspecialchars($proj['customer_name']); ?></td>
                    <td data-label="Дата"><?php echo date('d.m.Y', strtotime($proj['created_at'])); ?></td>
                    <td data-label="Действия">
                        <a href="?edit=<?php echo $proj['id']; ?>">Редактировать</a>
                        <a href="?delete=<?php echo $proj['id']; ?>" onclick="return confirm('Удалить проект? Все отклики также будут удалены.');">Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <footer><div class="container"><p>&copy; 2025</p></div></footer>
</body>
</html>