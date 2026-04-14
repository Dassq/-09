<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['customer', 'admin'])) {
    header('HTTP/1.1 403 Forbidden');
    die('Доступ запрещен');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    $contact_info = trim($_POST['contact_info']);
    $customer_id = $_SESSION['user_id'];
    if ($_SESSION['role'] == 'admin' && isset($_POST['customer_select']) && $_POST['customer_select'] > 0) {
        $customer_id = (int)$_POST['customer_select'];
    }

    if (empty($title) || empty($description)) {
        $error = 'Название и описание обязательны';
    } else {
        // Обработка загруженного файла
        $file_path = null;
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
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['tz_file']['tmp_name'], $destination)) {
                    $file_path = $destination;
                } else {
                    $error = 'Ошибка сохранения файла';
                }
            }
        }
        
        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, budget, contact_info, customer_id, file_path) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $budget, $contact_info, $customer_id, $file_path])) {
                $success = 'Проект успешно создан';
            } else {
                $error = 'Ошибка создания проекта';
            }
        }
    }
}

$customers = [];
if ($_SESSION['role'] == 'admin') {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'customer'");
    $stmt->execute();
    $customers = $stmt->fetchAll();
}
?>
<?php include 'header.php'; ?>
<div class="form-container">
    <h2>Создать проект</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <label>Название проекта *</label>
        <input type="text" name="title" required>
        <label>Описание *</label>
        <textarea name="description" rows="5" required></textarea>
        <label>Бюджет (необязательно)</label>
        <input type="number" name="budget" step="0.01">
        <label>Контактная информация (необязательно)</label>
        <textarea name="contact_info" rows="2"></textarea>
        <label>Техническое задание (PDF, DOC, DOCX)</label>
        <input type="file" name="tz_file" accept=".pdf,.doc,.docx">
        <?php if ($_SESSION['role'] == 'admin' && !empty($customers)): ?>
            <label>Заказчик (выберите)</label>
            <select name="customer_select">
                <option value="">-- Выберите заказчика --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <button type="submit">Создать</button>
    </form>
</div>
<?php include 'footer.php'; ?>