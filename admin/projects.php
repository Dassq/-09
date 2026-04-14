<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die('Доступ запрещен');
}

// Удаление проекта (каскадно удалит отклики, файл ТЗ удаляем вручную)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Сначала получим путь к файлу, чтобы удалить его с диска
    $stmt = $pdo->prepare("SELECT file_path FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    if ($project && $project['file_path'] && file_exists('../' . $project['file_path'])) {
        unlink('../' . $project['file_path']);
    }
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

// Обработка добавления / редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    $contact_info = trim($_POST['contact_info']);
    $customer_id = (int)$_POST['customer_id'];
    $delete_file = isset($_POST['delete_file']) && $_POST['delete_file'] == '1';
    
    $error = '';
    $file_path = $edit_project ? $edit_project['file_path'] : null;
    
    // Обработка нового файла
    if (isset($_FILES['tz_file']) && $_FILES['tz_file']['error'] == UPLOAD_ERR_OK) {
        $allowed = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['tz_file']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed)) {
            $error = 'Можно загружать только PDF или DOC/DOCX файлы';
        } else {
            $ext = pathinfo($_FILES['tz_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $destination = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['tz_file']['tmp_name'], $destination)) {
                // Удаляем старый файл, если есть
                if ($edit_project && $edit_project['file_path'] && file_exists('../' . $edit_project['file_path'])) {
                    unlink('../' . $edit_project['file_path']);
                }
                $file_path = 'uploads/' . $filename;
            } else {
                $error = 'Ошибка сохранения файла';
            }
        }
    } elseif ($delete_file && $edit_project && $edit_project['file_path']) {
        // Удаляем существующий файл, если отмечен чекбокс
        if (file_exists('../' . $edit_project['file_path'])) {
            unlink('../' . $edit_project['file_path']);
        }
        $file_path = null;
    }
    
    if (empty($error)) {
        if (isset($_POST['project_id']) && !empty($_POST['project_id'])) {
            $id = (int)$_POST['project_id'];
            $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, budget=?, contact_info=?, customer_id=?, file_path=? WHERE id=?");
            $stmt->execute([$title, $description, $budget, $contact_info, $customer_id, $file_path, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, budget, contact_info, customer_id, file_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $budget, $contact_info, $customer_id, $file_path]);
        }
        header('Location: projects.php');
        exit;
    } else {
        $error_message = $error;
    }
}

// Получение списка проектов для таблицы
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
        
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <h3><?php echo $edit_project ? 'Редактировать проект' : 'Добавить новый проект'; ?></h3>
        <form method="POST" enctype="multipart/form-data" class="admin-form">
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
            
            <label>Техническое задание (PDF, DOC, DOCX):</label>
            <input type="file" name="tz_file" accept=".pdf,.doc,.docx">
            <?php if ($edit_project && $edit_project['file_path']): ?>
                <p>Текущий файл: <a href="../<?php echo htmlspecialchars($edit_project['file_path']); ?>" target="_blank">Скачать</a></p>
                <label><input type="checkbox" name="delete_file" value="1"> Удалить текущий файл</label>
            <?php endif; ?>
            
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
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Заказчик</th>
                    <th>Дата</th>
                    <th>ТЗ</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $proj): ?>
                <tr>
                    <td data-label="ID"><?php echo $proj['id']; ?></td>
                    <td data-label="Название"><?php echo htmlspecialchars($proj['title']); ?></td>
                    <td data-label="Заказчик"><?php echo htmlspecialchars($proj['customer_name']); ?></td>
                    <td data-label="Дата"><?php echo date('d.m.Y', strtotime($proj['created_at'])); ?></td>
                    <td data-label="ТЗ">
                        <?php if ($proj['file_path'] && file_exists('../' . $proj['file_path'])): ?>
                            <a href="../<?php echo htmlspecialchars($proj['file_path']); ?>" target="_blank">📎 Файл</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td data-label="Действия">
                        <a href="?edit=<?php echo $proj['id']; ?>">Редактировать</a>
                        <a href="?delete=<?php echo $proj['id']; ?>" onclick="return confirm('Удалить проект? Все отклики и файл ТЗ также будут удалены.');">Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2025 Биржа фриланс-услуг</p>
        </div>
    </footer>
</body>
</html>