<?php
require_once '../config/db.php';
session_start();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$project_id) die('Неверный ID проекта');

$stmt = $pdo->prepare("
    SELECT p.*, u.name as customer_name 
    FROM projects p 
    JOIN users u ON p.customer_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$project = $stmt->fetch();
if (!$project) die('Проект не найден');

$respStmt = $pdo->prepare("
    SELECT r.*, u.name as executor_name 
    FROM responses r 
    JOIN users u ON r.executor_id = u.id 
    WHERE r.project_id = ? 
    ORDER BY r.created_at DESC
");
$respStmt->execute([$project_id]);
$responses = $respStmt->fetchAll();

$user_liked = false;
if (isset($_SESSION['user_id'])) {
    $likeStmt = $pdo->prepare("SELECT 1 FROM likes WHERE user_id = ? AND project_id = ?");
    $likeStmt->execute([$_SESSION['user_id'], $project_id]);
    $user_liked = (bool)$likeStmt->fetch();
}
?>
<?php include 'header.php'; ?>

<div class="project-detail">
    <h2><?php echo htmlspecialchars($project['title']); ?></h2>
    <div class="meta">
        <span>Заказчик: <?php echo htmlspecialchars($project['customer_name']); ?></span>
        <span>Дата: <?php echo date('d.m.Y H:i', strtotime($project['created_at'])); ?></span>
        <?php if ($project['budget']): ?>
            <span>Бюджет: <?php echo number_format($project['budget'], 0, '.', ' '); ?> ₽</span>
        <?php endif; ?>
        <?php if ($project['file_path'] && file_exists($project['file_path'])): ?>
    <div class="tz-file">
        <strong>Техническое задание:</strong>
        <a href="<?php echo htmlspecialchars($project['file_path']); ?>" target="_blank">Скачать файл (PDF/DOCX)</a>
    </div>
<?php endif; ?>
        <span class="likes">
            <button class="like-btn-detail <?php echo $user_liked ? 'liked' : ''; ?>" data-project-id="<?php echo $project['id']; ?>">❤️</button>
            <span class="likes-count"><?php echo (int)$project['likes_count']; ?></span>
        </span>
    </div>
    <?php if ($project['contact_info']): ?>
        <div class="contact-info">
            <strong>Контакты:</strong> <?php echo nl2br(htmlspecialchars($project['contact_info'])); ?>
        </div>
    <?php endif; ?>
    <div class="description">
        <h3>Описание проекта</h3>
        <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
    </div>

    <div class="responses-section">
        <h3>Отклики исполнителей (<?php echo count($responses); ?>)</h3>
        <div id="responses-list">
            <?php foreach ($responses as $response): ?>
                <div class="response-item" data-response-id="<?php echo $response['id']; ?>">
                    <div class="response-header">
                        <strong><?php echo htmlspecialchars($response['executor_name']); ?></strong>
                        <span><?php echo date('d.m.Y H:i', strtotime($response['created_at'])); ?></span>
                    </div>
                    <div class="response-message"><?php echo nl2br(htmlspecialchars($response['message'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'executor'): ?>
            <div class="add-response">
                <h4>Оставить отклик</h4>
                <textarea id="response-text" rows="3" placeholder="Ваше предложение..."></textarea>
                <button id="submit-response" data-project-id="<?php echo $project_id; ?>">Отправить отклик</button>
                <div id="response-error" class="error" style="display:none;"></div>
            </div>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <p><a href="login.php">Войдите</a>, чтобы откликнуться на проект.</p>
        <?php elseif ($_SESSION['role'] != 'executor'): ?>
            <p>Только исполнители могут откликаться на проекты.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>