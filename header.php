<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Биржа фриланс-услуг</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header>
    <div class="container">
        <h1><a href="index.php">Фриланс Биржа</a></h1>
        <button class="menu-toggle" aria-label="Меню">☰</button>
        <nav>
            <ul>
                <li><a href="index.php">Главная</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'customer' || $_SESSION['role'] == 'admin'): ?>
                        <li><a href="create_project.php">Создать проект</a></li>
                    <?php endif; ?>
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin/projects.php">Админ-панель</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Выйти (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Вход</a></li>
                    <li><a href="register.php">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main class="container">