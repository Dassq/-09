<?php
require_once 'config/db.php';
session_start();

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->query("SELECT COUNT(*) FROM projects");
$totalProjects = $countStmt->fetchColumn();
$totalPages = ceil($totalProjects / $limit);

$stmt = $pdo->prepare("
    SELECT p.*, u.name as customer_name 
    FROM projects p 
    JOIN users u ON p.customer_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

$userLikes = [];
if (isset($_SESSION['user_id'])) {
    $likesStmt = $pdo->prepare("SELECT project_id FROM likes WHERE user_id = ?");
    $likesStmt->execute([$_SESSION['user_id']]);
    $userLikes = $likesStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<?php include 'header.php'; ?>

<h2>Все проекты</h2>

<?php if (empty($projects)): ?>
    <p>Проектов пока нет.</p>
<?php else: ?>
    <?php foreach ($projects as $project): ?>
        <div class="project-card" data-project-id="<?php echo $project['id']; ?>">
            <h3><a href="project.php?id=<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['title']); ?></a></h3>
            <p class="description"><?php echo htmlspecialchars(mb_substr($project['description'], 0, 200)) . '...'; ?></p>
            <div class="meta">
                <span>Заказчик: <?php echo htmlspecialchars($project['customer_name']); ?></span>
                <span>Дата: <?php echo date('d.m.Y H:i', strtotime($project['created_at'])); ?></span>
                <?php if ($project['budget']): ?>
                    <span>Бюджет: <?php echo number_format($project['budget'], 0, '.', ' '); ?> ₽</span>
                <?php endif; ?>
                <span class="likes">
                    <button class="like-btn <?php echo in_array($project['id'], $userLikes) ? 'liked' : ''; ?>" data-project-id="<?php echo $project['id']; ?>">❤️</button>
                    <span class="likes-count"><?php echo (int)$project['likes_count']; ?></span>
                </span>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>">Предыдущая</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>">Следующая</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>