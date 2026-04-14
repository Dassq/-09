<?php
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$project_id || !in_array($action, ['like', 'unlike'])) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Проект не найден']);
    exit;
}

if ($action == 'like') {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND project_id = ?");
    $stmt->execute([$_SESSION['user_id'], $project_id]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO likes (user_id, project_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $project_id]);
        $pdo->prepare("UPDATE projects SET likes_count = likes_count + 1 WHERE id = ?")->execute([$project_id]);
    }
    $stmt = $pdo->prepare("SELECT likes_count FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $likes_count = $stmt->fetchColumn();
    echo json_encode(['success' => true, 'likes_count' => $likes_count, 'action' => 'like']);
} else { // unlike
    $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND project_id = ?");
    $stmt->execute([$_SESSION['user_id'], $project_id]);
    if ($stmt->rowCount() > 0) {
        $pdo->prepare("UPDATE projects SET likes_count = likes_count - 1 WHERE id = ?")->execute([$project_id]);
    }
    $stmt = $pdo->prepare("SELECT likes_count FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $likes_count = $stmt->fetchColumn();
    echo json_encode(['success' => true, 'likes_count' => $likes_count, 'action' => 'unlike']);
}