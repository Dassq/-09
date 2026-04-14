<?php
require_once 'config/db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif (!in_array($role, ['customer', 'executor'])) {
        $error = 'Неверная роль';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed, $role])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = $role;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Ошибка регистрации';
            }
        }
    }
}
?>
<?php include 'header.php'; ?>
<div class="form-container">
    <h2>Регистрация</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Имя:</label>
        <input type="text" name="name" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Пароль (мин. 6 символов):</label>
        <input type="password" name="password" required>
        <label>Подтверждение пароля:</label>
        <input type="password" name="confirm_password" required>
        <label>Роль:</label>
        <select name="role">
            <option value="customer">Заказчик</option>
            <option value="executor">Исполнитель</option>
        </select>
        <button type="submit">Зарегистрироваться</button>
    </form>
    <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
</div>
<?php include 'footer.php'; ?>