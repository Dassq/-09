<?php
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'executor') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$message = trim($_POST['message']);

if (!$project_id || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Проект не найден']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO responses (project_id, executor_id, message) VALUES (?, ?, ?)");
if ($stmt->execute([$project_id, $_SESSION['user_id'], $message])) {
    $response_id = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'response' => [
            'id' => $response_id,
            'executor_name' => $_SESSION['user_name'],
            'created_at' => date('d.m.Y H:i'),
            'message' => nl2br(htmlspecialchars($message))
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка добавления отклика']);
}