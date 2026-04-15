<?php
require __DIR__ . '/../../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die('Доступ запрещен');
}

// Удаление отклика
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM responses WHERE id = ?")->execute([$id]);
    // Перенаправляем с сохранением фильтра по проекту
    $redirect = 'responses.php';
    if (isset($_GET['project_id']) && $_GET['project_id'] > 0) {
        $redirect .= '?project_id=' . (int)$_GET['project_id'];
    }
    header('Location: ' . $redirect);
    exit;
}

// Фильтр по проекту
$project_filter = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Запрос откликов с возможным фильтром
$sql = "
    SELECT r.*, p.title as project_title, u.name as executor_name 
    FROM responses r 
    JOIN projects p ON r.project_id = p.id 
    JOIN users u ON r.executor_id = u.id 
";
$params = [];
if ($project_filter) {
    $sql .= " WHERE r.project_id = ?";
    $params[] = $project_filter;
}
$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$responses = $stmt->fetchAll();

// Список проектов для фильтра
$projects = $pdo->query("SELECT id, title FROM projects ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Управление откликами</title>
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
    <h2>Управление откликами</h2>
    
    <form method="GET" class="filter-form">
        <label>Фильтр по проекту:</label>
        <select name="project_id" onchange="this.form.submit()">
            <option value="0">Все проекты</option>
            <?php foreach ($projects as $proj): ?>
                <option value="<?php echo $proj['id']; ?>" <?php echo $project_filter == $proj['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($proj['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <table class="admin-table">
        <thead>
            <tr><th>ID</th><th>Проект</th><th>Исполнитель</th><th>Текст отклика</th><th>Дата</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($responses as $resp): ?>
            <tr>
                <td data-label="ID"><?php echo $resp['id']; ?></td>
                <td data-label="Проект"><a href="../project.php?id=<?php echo $resp['project_id']; ?>"><?php echo htmlspecialchars($resp['project_title']); ?></a></td>
                <td data-label="Исполнитель"><?php echo htmlspecialchars($resp['executor_name']); ?></td>
                <td data-label="Текст"><?php echo htmlspecialchars(mb_substr($resp['message'], 0, 100)) . '...'; ?></td>
                <td data-label="Дата"><?php echo date('d.m.Y H:i', strtotime($resp['created_at'])); ?></td>
                <td data-label="Действия">
                    <a href="?delete=<?php echo $resp['id']; ?>&project_id=<?php echo $project_filter; ?>" onclick="return confirm('Удалить отклик?');">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
<footer><div class="container"><p>&copy; 2025 Биржа фриланс-услуг</p></div></footer>
</body>
</html>