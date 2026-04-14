<?php
require_once 'config/db.php';
session_start();

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$budget_min = isset($_GET['budget_min']) && $_GET['budget_min'] !== '' ? (float)$_GET['budget_min'] : null;
$budget_max = isset($_GET['budget_max']) && $_GET['budget_max'] !== '' ? (float)$_GET['budget_max'] : null;

// Основной запрос
$sql = "SELECT p.*, u.name as customer_name 
        FROM projects p 
        JOIN users u ON p.customer_id = u.id 
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND p.title LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($budget_min !== null) {
    $sql .= " AND p.budget >= ?";
    $params[] = $budget_min;
}
if ($budget_max !== null) {
    $sql .= " AND p.budget <= ?";
    $params[] = $budget_max;
}

// LIMIT и OFFSET вставляем напрямую (безопасно, т.к. приведены к int)
$sql .= " ORDER BY p.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Подсчёт общего количества (для пагинации)
$countSql = "SELECT COUNT(*) FROM projects p WHERE 1=1";
$countParams = [];
if ($search !== '') {
    $countSql .= " AND p.title LIKE ?";
    $countParams[] = '%' . $search . '%';
}
if ($budget_min !== null) {
    $countSql .= " AND p.budget >= ?";
    $countParams[] = $budget_min;
}
if ($budget_max !== null) {
    $countSql .= " AND p.budget <= ?";
    $countParams[] = $budget_max;
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalProjects = $countStmt->fetchColumn();
$totalPages = ceil($totalProjects / $limit);

// Лайки пользователя
$userLikes = [];
if (isset($_SESSION['user_id'])) {
    $likesStmt = $pdo->prepare("SELECT project_id FROM likes WHERE user_id = ?");
    $likesStmt->execute([$_SESSION['user_id']]);
    $userLikes = $likesStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<?php include 'header.php'; ?>

<h2>Все проекты</h2>

<!-- Форма поиска и фильтра -->
<div class="search-filter">
    <form method="GET" action="">
        <div class="filter-row">
            <input type="text" name="search" placeholder="Поиск по названию" value="<?php echo htmlspecialchars($search); ?>">
            <input type="number" name="budget_min" placeholder="Бюджет от" step="100" value="<?php echo $budget_min !== null ? htmlspecialchars($budget_min) : ''; ?>">
            <input type="number" name="budget_max" placeholder="Бюджет до" step="100" value="<?php echo $budget_max !== null ? htmlspecialchars($budget_max) : ''; ?>">
            <button type="submit">🔍 Найти</button>
            <a href="index.php" class="reset">Сбросить</a>
        </div>
    </form>
</div>

<?php if (empty($projects)): ?>
    <p>Проектов не найдено.</p>
<?php else: ?>
    <?php foreach ($projects as $project): ?>
        <div class="project-card">
            <h3><a href="project.php?id=<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['title']); ?></a></h3>
            <p class="description"><?php echo htmlspecialchars(mb_substr($project['description'], 0, 200)) . '...'; ?></p>
            <div class="meta">
                <span>Заказчик: <?php echo htmlspecialchars($project['customer_name']); ?></span>
                <span>Дата: <?php echo date('d.m.Y H:i', strtotime($project['created_at'])); ?></span>
                <?php if ($project['budget']): ?>
                    <span>💰 Бюджет: <?php echo number_format($project['budget'], 0, '.', ' '); ?> ₽</span>
                <?php endif; ?>
                <?php if (!empty($project['file_path'])): ?>
                    <span>📎 Есть ТЗ</span>
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
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">Предыдущая</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">Следующая</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>